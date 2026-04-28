<?php

namespace App\Services;

use App\Models\City;
use App\Models\TripSchedule;

class TripSearchService
{
    public function search(array $data)
    {
        $children = (int) ($data['children'] ?? 0);
        $adults   = (int) $data['passengers'];

        $fromCity = $this->resolveCity($data['from']);
        $toCity   = $this->resolveCity($data['to']);

        if (!$fromCity || !$toCity) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $isRoundTrip = ($data['trip_type'] ?? 'one_way') === 'round_trip';

        $outboundSchedules = $this->fetchSchedules(
            fromCity: $fromCity,
            toCity: $toCity,
            date: $data['date'],
            passengers: $adults,
            children: $children,
        );

        if (!$isRoundTrip) {
            return response()->json([
                'status'    => true,
                'trip_type' => 'one_way',
                'data'      => $this->formatOneWay($outboundSchedules, $adults, $children)
            ]);
        }

        $returnSchedules = $this->fetchSchedules(
            fromCity: $toCity,
            toCity: $fromCity,
            date: $data['return_date'],
            passengers: $adults,
            children: $children,
            roundTripOnly: true
        );

        $returnByProvider = $returnSchedules->keyBy(fn($s) => $s->trip->provider_id);

        $matched = $outboundSchedules->filter(function ($schedule) use ($returnByProvider) {
            $providerId        = $schedule->trip->provider_id;
            $hasRoundTripPrice = !is_null($schedule->trip->round_trip_price);
            $hasReturnTrip     = isset($returnByProvider[$providerId]);

            return $hasRoundTripPrice && $hasReturnTrip;
        });

        return response()->json([
            'status'    => true,
            'trip_type' => 'round_trip',
            'data'      => $this->formatRoundTrip($matched, $returnByProvider, $adults, $children)
        ]);
    }

    private function fetchSchedules(
        $fromCity,
        $toCity,
        string $date,
        int $passengers,
        int $children = 0,
        bool $roundTripOnly = false
    ) {
        return TripSchedule::query()
            ->with([
                'trip.route',
                'trip.vehicle',
                'trip.provider',
            ])
            ->withCount([
                'trip as total_stops' => function ($q) {
                    $q->join('trip_stops', 'trip_stops.trip_id', '=', 'trips.id');
                }
            ])
            ->where('date', $date)
            ->where('status', 'active')
            ->where('seats_available', '>=', $passengers + $children)
            ->whereHas('trip', function ($q) use ($fromCity, $toCity, $roundTripOnly) {
                $q->where('status', 'active')
                  ->where('transport_type', 'bus')
                  ->when($roundTripOnly, fn($q) => $q->whereNotNull('round_trip_price'))
                  ->whereHas('route', function ($r) use ($fromCity, $toCity) {
                      $r->where('departure_city_id', $fromCity->id)
                        ->where('arrival_city_id', $toCity->id);
                  });
            })
            ->orderBy('departure_datetime')
            ->get();
    }

    private function formatOneWay($schedules, int $adults, int $children): array
    {
        return $schedules->map(function ($schedule) use ($adults, $children) {
            return $this->buildOutboundItem($schedule, $adults, $children);
        })->values()->toArray();
    }

    private function formatRoundTrip($outboundSchedules, $returnByProvider, int $adults, int $children): array
    {
        return $outboundSchedules->map(function ($schedule) use ($returnByProvider, $adults, $children) {
            $trip           = $schedule->trip;
            $providerId     = $trip->provider_id;
            $returnSchedule = $returnByProvider[$providerId];

            $roundTripPrice      = (float) $trip->round_trip_price;
            $childRoundTripPrice = $trip->child_price
                                    ? (float) $trip->child_price
                                    : $roundTripPrice;

            // Adults pay round_trip_price × 2 legs (already flat, covers both legs)
            // Children pay child_price × 2 legs (or round_trip_price if no child price set)
            // The round_trip_price is already a flat price covering both legs,
            // so we apply it once per adult and once per child (with child rate)
            $adultTotal = $roundTripPrice * $adults;
            $childTotal = ($childRoundTripPrice * 2) * $children;
            $grandTotal = $adultTotal + $childTotal;

            return [
                'outbound' => $this->buildOutboundItem($schedule, $adults, $children),
                'return'   => $this->buildReturnItem($returnSchedule),

                'round_trip_price_per_passenger'       => $roundTripPrice,
                'round_trip_child_price_per_passenger' => $childRoundTripPrice * 2,
                'adults'                               => $adults,
                'children'                             => $children,
                'round_trip_total_price'               => $grandTotal,
            ];
        })->values()->toArray();
    }

    private function buildOutboundItem($schedule, int $adults, int $children): array
    {
        $trip       = $schedule->trip;
        $vehicle    = $trip->vehicle;
        $route      = $trip->route;
        $provider   = $trip->provider;
        $totalStops = (int) $schedule->total_stops;

        $adultPrice = (float) $trip->price;
        $childPrice = $trip->child_price
                        ? (float) $trip->child_price
                        : $adultPrice;

        // One way: adult price × adults + child price × children (one leg only)
        $totalPrice = ($adultPrice * $adults) + ($childPrice * $children);

        return [
            'schedule_id'                    => $schedule->id,
            'trip_id'                        => $trip->id,
            'provider'                       => $provider ? [
                'id'   => $provider->id,
                'name' => $provider->name,
                'logo' => $provider->logo
                            ? asset('storage/' . $provider->logo)
                            : null,
            ] : null,
            'date'                           => $schedule->date,
            'departure_datetime'             => $schedule->departure_datetime,
            'price_per_passenger'            => $adultPrice,
            'child_price_per_passenger'      => $childPrice,
            'total_price'                    => $totalPrice,
            'adults'                         => $adults,
            'children'                       => $children,
            'round_trip_price_per_passenger' => $trip->round_trip_price
                                                ? (float) $trip->round_trip_price
                                                : null,
            'seats_available'                => $schedule->seats_available,
            'comfort_type'                   => $trip->comfort_type,
            'stops'                          => [
                'total' => $totalStops,
                'label' => $this->stopLabel($totalStops),
            ],
            'vehicle'                        => $vehicle ? [
                'id'           => $vehicle->id,
                'model'        => $vehicle->model,
                'capacity'     => $vehicle->capacity,
                'comfort_type' => $vehicle->comfort_type,
            ] : null,
            'route'                          => $route ? [
                'distance_km'      => $route->distance_km,
                'duration_minutes' => $route->duration_minutes,
            ] : null,
        ];
    }

    private function buildReturnItem($schedule): array
    {
        $trip       = $schedule->trip;
        $vehicle    = $trip->vehicle;
        $route      = $trip->route;
        $totalStops = (int) $schedule->total_stops;

        return [
            'schedule_id'        => $schedule->id,
            'trip_id'            => $trip->id,
            'date'               => $schedule->date,
            'departure_datetime' => $schedule->departure_datetime,
            'seats_available'    => $schedule->seats_available,
            'comfort_type'       => $trip->comfort_type,
            'stops'              => [
                'total' => $totalStops,
                'label' => $this->stopLabel($totalStops),
            ],
            'vehicle'            => $vehicle ? [
                'id'           => $vehicle->id,
                'model'        => $vehicle->model,
                'capacity'     => $vehicle->capacity,
                'comfort_type' => $vehicle->comfort_type,
            ] : null,
            'route'              => $route ? [
                'distance_km'      => $route->distance_km,
                'duration_minutes' => $route->duration_minutes,
            ] : null,
        ];
    }

    private function resolveCity(string $value): ?City
    {
        return City::query()
            ->where('city_code', $value)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($value)])
            ->first();
    }

    private function stopLabel(int $totalStops): string
    {
        if ($totalStops === 0) return 'Non-stop';
        if ($totalStops === 1) return '1 Stop';
        return $totalStops . ' Stops';
    }
}