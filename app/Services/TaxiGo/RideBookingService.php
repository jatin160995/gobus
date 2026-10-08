<?php

namespace App\Services\TaxiGo;

use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\InterurbanDestination;
use App\Models\TaxiGo\Neighbourhood;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\RideStatusLog;
use App\Models\TaxiGo\Zone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Shared by the customer app and (later) the airport desk: turns validated
 * booking input into a fare quote and a ride waiting for payment.
 */
class RideBookingService
{
    public function __construct(private FareCalculator $fares) {}

    /**
     * @return array{hub: Hub, zone: ?Zone, neighbourhood: ?Neighbourhood, destination: ?InterurbanDestination, pickup_at: CarbonInterface, quote: array}
     */
    public function quote(array $input): array
    {
        $hub = Hub::findOrFail($input['hub_id']);
        $tripType = $input['trip_type'];
        $isScheduled = filter_var($input['is_scheduled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $pickupAt = $isScheduled && !empty($input['pickup_at']) ? Carbon::parse($input['pickup_at'])->utc() : now();

        $neighbourhood = null;
        $zone = null;
        $destination = null;

        if (in_array($tripType, ['airport_to_city', 'city_to_airport'])) {
            $neighbourhood = !empty($input['neighbourhood_id']) ? Neighbourhood::with('zone')->find($input['neighbourhood_id']) : null;
            $zone = $neighbourhood?->zone ?? (!empty($input['zone_id']) ? Zone::find($input['zone_id']) : null);
        } elseif ($tripType === 'interurban') {
            $destination = InterurbanDestination::find($input['interurban_destination_id'] ?? null);
        }

        try {
            $quote = $this->fares->quote($hub, $tripType, $pickupAt, $zone, $destination, isset($input['vip_hours']) ? (int) $input['vip_hours'] : null);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['trip_type' => $e->getMessage()]);
        }

        return compact('hub', 'zone', 'neighbourhood', 'destination', 'pickupAt', 'quote') + [
            'pickup_at'    => $pickupAt,
            'is_scheduled' => $isScheduled,
        ];
    }

    /**
     * Create a ride in 'pending_payment' with the fare fixed at booking time.
     */
    public function create(array $input, string $source, ?int $customerUserId, ?int $createdByUserId, string $passengerName, string $passengerPhone): Ride
    {
        $q = $this->quote($input);
        $hub = $q['hub'];
        $tripType = $input['trip_type'];

        $airport = ['address' => $hub->name, 'lat' => $hub->lat, 'lng' => $hub->lng];
        $point = isset($input['location']) ? [
            'address' => $input['location']['address'] ?? null,
            'lat'     => $input['location']['lat'] ?? null,
            'lng'     => $input['location']['lng'] ?? null,
        ] : null;

        [$pickup, $dropoff] = match ($tripType) {
            'airport_to_city' => [$airport, $point],
            'city_to_airport' => [$point, $airport],
            'interurban'      => [$airport, $point ?? ['address' => $q['destination']?->name, 'lat' => null, 'lng' => null]],
            'vip_hourly'      => [$point, null],
        };

        $ride = Ride::create([
            'ref'                       => $this->newRef(),
            'source'                    => $source,
            'customer_user_id'          => $customerUserId,
            'created_by_user_id'        => $createdByUserId,
            'passenger_name'            => $passengerName,
            'passenger_phone'           => $passengerPhone,
            'hub_id'                    => $hub->id,
            'trip_type'                 => $tripType,
            'zone_id'                   => $q['zone']?->id,
            'neighbourhood_id'          => $q['neighbourhood']?->id,
            'interurban_destination_id' => $q['destination']?->id,
            'vip_hours'                 => $tripType === 'vip_hourly' ? (int) $input['vip_hours'] : null,
            'pickup_address'            => $pickup['address'] ?? null,
            'pickup_lat'                => $pickup['lat'] ?? null,
            'pickup_lng'                => $pickup['lng'] ?? null,
            'dropoff_address'           => $dropoff['address'] ?? null,
            'dropoff_lat'               => $dropoff['lat'] ?? null,
            'dropoff_lng'               => $dropoff['lng'] ?? null,
            'is_scheduled'              => $q['is_scheduled'],
            'pickup_at'                 => $q['pickup_at'],
            'flight_number'             => isset($input['flight_number']) ? strtoupper(trim($input['flight_number'])) : null,
            'notes'                     => $input['notes'] ?? null,
            'base_fare'                 => $q['quote']['base_fare'],
            'night_surcharge'           => $q['quote']['night_surcharge'],
            'total_fare'                => $q['quote']['total_fare'],
            'currency'                  => 'XAF',
            'payment_status'            => 'pending',
            'status'                    => 'pending_payment',
        ]);

        RideStatusLog::create([
            'ride_id' => $ride->id, 'from_status' => null, 'to_status' => 'pending_payment',
            'actor_type' => $source === 'desk' ? 'desk' : 'customer', 'actor_id' => $createdByUserId ?? $customerUserId,
        ]);

        return $ride;
    }

    private function newRef(): string
    {
        do {
            $ref = 'TG-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (Ride::where('ref', $ref)->exists());

        return $ref;
    }
}
