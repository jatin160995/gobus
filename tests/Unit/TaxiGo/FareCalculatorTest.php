<?php

namespace Tests\Unit\TaxiGo;

use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\InterurbanDestination;
use App\Models\TaxiGo\Zone;
use App\Services\TaxiGo\FareCalculator;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class FareCalculatorTest extends TestCase
{
    private FareCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new FareCalculator();
    }

    private function douala(): Hub
    {
        $hub = new Hub(['code' => 'DLA', 'night_start' => '22:00:00', 'night_end' => '05:00:00',
            'night_surcharge' => 2000, 'vip_hourly_rate' => 15000, 'vip_min_hours' => 1]);
        $hub->id = 1;
        return $hub;
    }

    private function nsimalen(): Hub
    {
        $hub = new Hub(['code' => 'NSI', 'night_start' => '22:00:00', 'night_end' => '05:30:00',
            'night_surcharge' => 2500, 'vip_hourly_rate' => 15000, 'vip_min_hours' => 2]);
        $hub->id = 2;
        return $hub;
    }

    private function zone(int $hubId, int $fare): Zone
    {
        return new Zone(['hub_id' => $hubId, 'code' => 'A', 'fare' => $fare]);
    }

    /** Cameroon local time (UTC+1) */
    private function at(string $local): Carbon
    {
        return Carbon::parse($local, 'Africa/Douala')->utc();
    }

    public function test_day_zone_fare_has_no_surcharge(): void
    {
        $q = $this->calc->quote($this->douala(), 'airport_to_city', $this->at('2026-11-10 14:00'), $this->zone(1, 10000));

        $this->assertSame(10000, $q['base_fare']);
        $this->assertSame(0, $q['night_surcharge']);
        $this->assertSame(10000, $q['total_fare']);
        $this->assertFalse($q['is_night']);
    }

    public function test_night_surcharge_before_midnight(): void
    {
        $q = $this->calc->quote($this->douala(), 'city_to_airport', $this->at('2026-11-10 22:00'), $this->zone(1, 15000));

        $this->assertTrue($q['is_night']);
        $this->assertSame(17000, $q['total_fare']);
    }

    public function test_night_surcharge_after_midnight(): void
    {
        $q = $this->calc->quote($this->douala(), 'airport_to_city', $this->at('2026-11-11 04:59'), $this->zone(1, 20000));

        $this->assertSame(22000, $q['total_fare']);
    }

    public function test_night_ends_exactly_at_end_time(): void
    {
        $this->assertFalse($this->calc->isNight($this->douala(), $this->at('2026-11-11 05:00')));
        $this->assertTrue($this->calc->isNight($this->nsimalen(), $this->at('2026-11-11 05:00')));
        $this->assertFalse($this->calc->isNight($this->nsimalen(), $this->at('2026-11-11 05:30')));
    }

    public function test_night_check_uses_cameroon_time_not_utc(): void
    {
        // 21:30 UTC is 22:30 in Douala: night
        $this->assertTrue($this->calc->isNight($this->douala(), Carbon::parse('2026-11-10 21:30', 'UTC')));
        // 04:30 UTC is 05:30 in Douala: day
        $this->assertFalse($this->calc->isNight($this->douala(), Carbon::parse('2026-11-11 04:30', 'UTC')));
    }

    public function test_interurban_flat_fare_with_night_surcharge(): void
    {
        $kribi = new InterurbanDestination(['hub_id' => 2, 'name' => 'Kribi', 'fare' => 150000]);
        $q = $this->calc->quote($this->nsimalen(), 'interurban', $this->at('2026-11-10 23:15'), destination: $kribi);

        $this->assertSame(150000, $q['base_fare']);
        $this->assertSame(152500, $q['total_fare']);
    }

    public function test_vip_hourly(): void
    {
        $q = $this->calc->quote($this->douala(), 'vip_hourly', $this->at('2026-11-10 10:00'), vipHours: 3);

        $this->assertSame(45000, $q['total_fare']);
    }

    public function test_vip_below_minimum_hours_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calc->quote($this->nsimalen(), 'vip_hourly', $this->at('2026-11-10 10:00'), vipHours: 1);
    }

    public function test_zone_from_another_hub_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calc->quote($this->douala(), 'airport_to_city', $this->at('2026-11-10 10:00'), $this->zone(2, 15000));
    }
}
