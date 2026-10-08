<?php

namespace App\Services\TaxiGo;

use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\InterurbanDestination;
use App\Models\TaxiGo\Zone;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Fixed TaxiGo fares from the official tariff. All amounts are whole XAF.
 *
 *   Airport ↔ city : the zone fare
 *   Interurban     : the destination's flat fare
 *   VIP hourly     : hub hourly rate × hours
 *   + night surcharge when the pickup falls inside the hub's night hours
 */
class FareCalculator
{
    public const TRIP_TYPES = ['airport_to_city', 'city_to_airport', 'interurban', 'vip_hourly'];

    public function quote(
        Hub $hub,
        string $tripType,
        CarbonInterface $pickupAt,
        ?Zone $zone = null,
        ?InterurbanDestination $destination = null,
        ?int $vipHours = null,
    ): array {
        $baseFare = match ($tripType) {
            'airport_to_city', 'city_to_airport' => $this->zoneFare($hub, $zone),
            'interurban'                         => $this->interurbanFare($hub, $destination),
            'vip_hourly'                         => $this->vipFare($hub, $vipHours),
            default => throw new InvalidArgumentException("Unknown trip type: {$tripType}"),
        };

        $isNight   = $this->isNight($hub, $pickupAt);
        $surcharge = $isNight ? (int) round((float) $hub->night_surcharge) : 0;

        return [
            'base_fare'       => $baseFare,
            'night_surcharge' => $surcharge,
            'is_night'        => $isNight,
            'total_fare'      => $baseFare + $surcharge,
            'currency'        => 'XAF',
        ];
    }

    /**
     * Night hours can cross midnight (22:00–05:30). Checked in Cameroon time.
     */
    public function isNight(Hub $hub, CarbonInterface $pickupAt): bool
    {
        $local = $pickupAt->copy()->setTimezone(config('taxigo.timezone', 'Africa/Douala'));
        $time  = $local->format('H:i:s');
        $start = $this->normaliseTime($hub->night_start);
        $end   = $this->normaliseTime($hub->night_end);

        if ($start === $end) {
            return false;
        }

        return $start < $end
            ? ($time >= $start && $time < $end)
            : ($time >= $start || $time < $end);
    }

    private function zoneFare(Hub $hub, ?Zone $zone): int
    {
        if (!$zone || (int) $zone->hub_id !== (int) $hub->id) {
            throw new InvalidArgumentException('A zone of this hub is required.');
        }

        return (int) round((float) $zone->fare);
    }

    private function interurbanFare(Hub $hub, ?InterurbanDestination $destination): int
    {
        if (!$destination || (int) $destination->hub_id !== (int) $hub->id) {
            throw new InvalidArgumentException('An interurban destination of this hub is required.');
        }

        return (int) round((float) $destination->fare);
    }

    private function vipFare(Hub $hub, ?int $hours): int
    {
        $minimum = max(1, (int) $hub->vip_min_hours);
        if ($hours === null || $hours < $minimum) {
            throw new InvalidArgumentException("VIP hire needs at least {$minimum} hour(s).");
        }

        return (int) round((float) $hub->vip_hourly_rate) * $hours;
    }

    private function normaliseTime(?string $time): string
    {
        return strlen((string) $time) === 5 ? "{$time}:00" : (string) $time;
    }
}
