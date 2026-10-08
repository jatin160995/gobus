<?php

namespace Tests\Unit\TaxiGo;

use App\Services\TaxiGo\SplitCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SplitCalculatorTest extends TestCase
{
    private const AGREEMENT = [
        'bank_financing' => 40, 'driver' => 25, 'alo_fuel' => 15,
        'alo_commission' => 10, 'adc' => 5, 'dealership' => 5,
    ];

    public function test_agreement_split_of_a_round_fare(): void
    {
        $this->assertSame([
            'bank_financing' => 4000, 'driver' => 2500, 'alo_fuel' => 1500,
            'alo_commission' => 1000, 'adc' => 500, 'dealership' => 500,
        ], (new SplitCalculator())->split(10000, self::AGREEMENT));
    }

    public function test_shares_always_add_up_to_the_fare(): void
    {
        $calc = new SplitCalculator();
        foreach ([1, 7, 999, 12345, 17000, 22500, 152500, 999999] as $fare) {
            $this->assertSame($fare, array_sum($calc->split($fare, self::AGREEMENT)), "fare {$fare}");
        }
    }

    public function test_leftover_francs_go_to_the_largest_share(): void
    {
        $split = (new SplitCalculator())->split(12345, self::AGREEMENT);

        // 12345 × 40% = 4938, plus the 2 francs left over after rounding down the others
        $this->assertSame(4940, $split['bank_financing']);
        $this->assertSame(3086, $split['driver']);
    }

    public function test_decimal_percentages(): void
    {
        $split = (new SplitCalculator())->split(10000, ['a' => 33.33, 'b' => 33.33, 'c' => 33.34]);

        $this->assertSame(10000, array_sum($split));
        $this->assertSame(3333, $split['a']);
    }

    public function test_percentages_must_total_100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SplitCalculator())->split(10000, ['a' => 60, 'b' => 30]);
    }
}
