<?php

namespace App\Services;

use App\Models\TripSchedule;
use App\Models\TripSeat;
use App\Models\Setting;
use App\Services\PriceCalculatorService;

class TripDetailService
{
    public function getDetails(int $scheduleId, string $tripType = 'one_way', ?int $returnScheduleId = null, ?int $adult = 0,?int $children = 0)
    {
        $schedule = $this->fetchSchedule($scheduleId);

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'error'  => 'Trip schedule not found'
            ], 404);
        }

        $data = [
            'trip_type'  => $tripType,
            'trip'       => $this->tripInfo($schedule, $tripType),
            'stops'      => $this->stops($schedule->trip_id),
            'vehicle'    => $this->vehicleInfo($schedule),
            'seat_map'   => $this->seatMap($scheduleId),
            'legal_values' => $this->legalInfo(),
        ];

        // If round trip, append return leg details
        if ($tripType === 'round_trip' && $returnScheduleId) {
            $returnSchedule = $this->fetchSchedule($returnScheduleId);

            if (!$returnSchedule) {
                return response()->json([
                    'status' => false,
                    'error'  => 'Return trip schedule not found'
                ], 404);
            }

            // Validate return trip belongs to same provider
            if ($returnSchedule->trip->provider_id !== $schedule->trip->provider_id) {
                return response()->json([
                    'status' => false,
                    'error'  => 'Return trip must belong to the same provider'
                ], 422);
            }

            $data['return_trip']     = $this->returnTripInfo($returnSchedule);
            $data['return_stops']    = $this->stops($returnSchedule->trip_id);
            $data['return_seat_map'] = $this->seatMap($returnScheduleId);

            
            
        }
        //total price Calculation
            $adultPrice = (float) $schedule->trip->price;
            $childPrice = $schedule->trip->child_price
                            ? (float) $schedule->trip->child_price
                            : $adultPrice;
            $adultPrice = $adultPrice * $adult;
            $childPrice = ($childPrice) * $children;

            //dd($tripType);
            //total price  round trip Calculation
            if ($tripType === 'round_trip') {
            $adultPrice      = (float) $schedule->trip->round_trip_price;
            $adultPrice = $adultPrice * $adult;
            $childPrice = $schedule->trip->child_price
                                    ? (float) $schedule->trip->child_price
                                    : $adultPrice;
            $childPrice = ($childPrice * 2) * $children;
            }                        
            //dd($childPrice);
            $calculator = new PriceCalculatorService();

            $priceData = $calculator->calculateBusPrice(
                $adultPrice + $childPrice,
                $schedule->trip->provider_id
            );

            // Pricing summary
            $data['pricing'] = [
                'one_way_price_per_passenger'          => (float) $schedule->trip->price,
                'child_price_per_passenger'            => $schedule->trip->child_price
                                                        ? (float) $schedule->trip->child_price
                                                        : (float) $schedule->trip->price,
                'round_trip_price_per_passenger'       => (float) $schedule->trip->round_trip_price,
                'round_trip_child_price_per_passenger' => $schedule->trip->child_price
                                                        ? (float) $schedule->trip->child_price
                                                        : (float) $schedule->trip->round_trip_price,

                'base_price' => round($priceData['base_price'],2),
                'commission' => round($priceData['commission'],2),
                'insurance' => round($priceData['insurance'],2),
                'platform_commission' => round($priceData['platform_commission'],2),
                'vat_percent' => $priceData['vat_percent'],
                'vat_amount' => round($priceData['vat_amount'],2),
                'grand_total' => round($priceData['grand_total'],2),
                'currency' => 'XAF'
            ];
        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    private function fetchSchedule(int $scheduleId): ?TripSchedule
    {
        return TripSchedule::with([
            'trip.route.departureCity',
            'trip.route.arrivalCity',
            'trip.vehicle.images',
            'trip.provider',
        ])->where('status', 'active')
          ->find($scheduleId);
    }

    private function tripInfo($schedule, string $tripType = 'one_way'): array
    {
        $trip  = $schedule->trip;
        $route = $trip->route;

        return [
            'schedule_id'                    => $schedule->id,
            'trip_id'                        => $trip->id,
            'provider_id'                    => $trip->provider_id,
            'departure_datetime'             => $schedule->departure_datetime,
            'price_per_passenger'            => (float) $trip->price,
            'child_price_per_passenger'      => $trip->child_price
                                                ? (float) $trip->child_price
                                                : (float) $trip->price,
            'round_trip_price_per_passenger' => $trip->round_trip_price
                                                ? (float) $trip->round_trip_price
                                                : null,
            'round_trip_child_price_per_passenger' => $trip->round_trip_price
                                                ? ($trip->child_price
                                                    ? (float) $trip->child_price
                                                    : (float) $trip->round_trip_price)
                                                : null,
            'seats_available'                => $schedule->seats_available,
            'comfort_type'                   => $trip->comfort_type,
            'transport_type'                 => $trip->transport_type,

            'from' => [
                'city_id'   => $route->departureCity->id,
                'name'      => $route->departureCity->name,
                'city_code' => $route->departureCity->city_code,
            ],
            'to' => [
                'city_id'   => $route->arrivalCity->id,
                'name'      => $route->arrivalCity->name,
                'city_code' => $route->arrivalCity->city_code,
            ],
            
        ];
    }


    private function returnTripInfo($schedule): array
    {
        $trip  = $schedule->trip;
        $route = $trip->route;

        return [
            'schedule_id'        => $schedule->id,
            'trip_id'            => $trip->id,
            'departure_datetime' => $schedule->departure_datetime,
            'seats_available'    => $schedule->seats_available,
            'comfort_type'       => $trip->comfort_type,
            'transport_type'     => $trip->transport_type,

            'from' => [
                'city_id'   => $route->departureCity->id,
                'name'      => $route->departureCity->name,
                'city_code' => $route->departureCity->city_code,
            ],
            'to' => [
                'city_id'   => $route->arrivalCity->id,
                'name'      => $route->arrivalCity->name,
                'city_code' => $route->arrivalCity->city_code,
            ],
        ];
    }

    private function stops(int $tripId): array
    {
        return \App\Models\TripStop::where('trip_id', $tripId)
            ->orderBy('sequence')
            ->with('city:id,name,city_code')
            ->get()
            ->map(function ($stop) {
                return [
                    'city_id'        => $stop->city_id,
                    'city_name'      => $stop->city->name,
                    'city_code'      => $stop->city->city_code,
                    'arrival_time'   => $stop->arrival_time,
                    'departure_time' => $stop->departure_time,
                    'sequence'       => $stop->sequence,
                ];
            })
            ->toArray();
    }

    private function vehicleInfo($schedule): ?array
    {
        $vehicle = $schedule->trip->vehicle;

        if (!$vehicle) return null;

        return [
            'id'           => $vehicle->id,
            'model'        => $vehicle->model,
            'plate_number' => $vehicle->plate_number,
            'capacity'     => $vehicle->capacity,
            'comfort_type' => $vehicle->comfort_type,
            'layout'       => asset('storage/' . ltrim($vehicle->layout, '/')),
            'images'       => collect(
                                $vehicle->photo
                                    ? [asset('storage/' . ltrim($vehicle->photo, '/'))]
                                    : []
                              )
                              ->merge(
                                  $vehicle->images->map(fn($img) =>
                                      asset('storage/' . ltrim($img->image_path, '/'))
                                  )
                              )
                              ->values()
                              ->toArray(),
        ];
    }

    private function seatMap(int $scheduleId): array
    {
        return TripSeat::where('schedule_id', $scheduleId)
            ->get()
            ->map(fn($seat) => [
                'seat_number' => $seat->seat_number,
                'status'      => $seat->status,
            ])
            ->toArray();
    }

    private function legalInfo(): array
    {
        return Setting::where('group', 'legal')->get()->toArray();
    }
}