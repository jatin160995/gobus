<?php

namespace App\Http\Resources\TaxiGo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tz = config('taxigo.timezone');
        $driver = $this->driver;
        $vehicle = $this->vehicle;

        return [
            'ref'          => $this->ref,
            'status'       => $this->status,
            'source'       => $this->source,
            'trip_type'    => $this->trip_type,
            'hub'          => ['id' => $this->hub_id, 'code' => $this->hub?->code, 'name' => $this->hub?->name],
            'zone'         => $this->zone ? ['id' => $this->zone_id, 'code' => $this->zone->code, 'name' => $this->zone->name] : null,
            'neighbourhood'=> $this->neighbourhood ? ['id' => $this->neighbourhood_id, 'name' => $this->neighbourhood->name] : null,
            'destination'  => $this->interurbanDestination ? ['id' => $this->interurban_destination_id, 'name' => $this->interurbanDestination->name] : null,
            'vip_hours'    => $this->vip_hours,
            'pickup'       => $this->point('pickup'),
            'dropoff'      => $this->point('dropoff'),
            'is_scheduled' => (bool) $this->is_scheduled,
            'pickup_at'    => $this->pickup_at?->copy()->setTimezone($tz)->toIso8601String(),
            'flight_number'=> $this->flight_number,
            'passenger'    => ['name' => $this->passenger_name, 'phone' => $this->passenger_phone],
            'fare'         => [
                'base'            => (int) $this->base_fare,
                'night_surcharge' => (int) $this->night_surcharge,
                'total'           => (int) $this->total_fare,
                'currency'        => $this->currency,
            ],
            'payment' => [
                'status'          => $this->payment_status,
                'method'          => $this->payment_method,
                'order_reference' => $this->paymentOrder?->order_reference,
                'paid_at'         => $this->paid_at?->copy()->setTimezone($tz)->toIso8601String(),
            ],
            'driver' => $driver ? [
                'name'     => $driver->user?->name,
                'phone'    => $driver->user?->phone,
                'photo'    => $driver->photo ? asset('storage/' . $driver->photo) : null,
                'location' => $driver->last_lat ? [
                    'lat' => (float) $driver->last_lat, 'lng' => (float) $driver->last_lng,
                    'updated_at' => $driver->last_location_at?->toIso8601String(),
                ] : null,
            ] : null,
            'vehicle' => $vehicle ? [
                'model' => $vehicle->model, 'plate' => $vehicle->plate, 'color' => $vehicle->color,
                'photo' => $vehicle->photo ? asset('storage/' . $vehicle->photo) : null,
            ] : null,
            'can_pay'    => $this->status === 'pending_payment' && $this->payment_status !== 'paid',
            'can_cancel' => $this->status === 'pending_payment',
            'created_at' => $this->created_at?->copy()->setTimezone($tz)->toIso8601String(),
        ];
    }

    private function point(string $which): ?array
    {
        $address = $this->{"{$which}_address"};
        $lat = $this->{"{$which}_lat"};

        if (!$address && !$lat) {
            return null;
        }

        return ['address' => $address, 'lat' => $lat ? (float) $lat : null, 'lng' => $lat ? (float) $this->{"{$which}_lng"} : null];
    }
}
