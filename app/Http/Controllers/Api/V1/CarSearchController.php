<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Validator;

class CarSearchController extends Controller
{
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_city_id' => 'required|exists:cities,id',
            'to_city_id' => 'required|exists:cities,id',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'trip_type' => 'required|in:one_way,round_trip',
            'return_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 422);
        }

        $tripType = $request->trip_type;

        $routes = DB::table('chauffeur_routes')
            ->join('providers', 'providers.id', '=', 'chauffeur_routes.provider_id')
            ->join('chauffeur_route_prices', 'chauffeur_route_prices.chauffeur_route_id', '=', 'chauffeur_routes.id')
            ->where('chauffeur_routes.from_city_id', $request->from_city_id)
            ->where('chauffeur_routes.to_city_id', $request->to_city_id)
            ->where('chauffeur_routes.is_active', 1)
            ->where('chauffeur_route_prices.is_active', 1)
            ->where('providers.status', 'active')
            ->where('providers.type', 'car')
            ->select(
                'chauffeur_routes.id as route_id',
                'chauffeur_routes.distance_km',
                'chauffeur_routes.estimated_duration_minutes',
                'chauffeur_route_prices.vehicle_category',
                'chauffeur_route_prices.one_way_price',
                'chauffeur_route_prices.round_trip_price',
                'chauffeur_route_prices.per_day_price',
                'providers.id as provider_id',
                'providers.name as provider_name',
                'providers.logo as provider_logo'
            )
            ->get();

        $pickupDate = Carbon::parse($request->pickup_date);
        $returnDate = $request->return_date ? Carbon::parse($request->return_date) : null;

        $results = [];

        foreach ($routes as $route) {

            $price = 0;
            $days = 1;

            if ($tripType == 'round_trip' && $returnDate) {

                $days = $pickupDate->startOfDay()->diffInDays($returnDate->startOfDay()) + 1;

                if ($days < 1) {
                    $days = 1;
                }

                if ($days > 1) {
                    $price = $route->per_day_price * $days;
                } else {
                    $price = $route->round_trip_price;
                }

            } else {
                $price = $route->one_way_price;
            }

            $results[] = [
                'route_id' => $route->route_id,
                'provider' => [
                    'id' => $route->provider_id,
                    'name' => $route->provider_name,
                    'logo' => $route->provider_logo
                        ? url('storage/' . $route->provider_logo)
                        : null
                ],
                'vehicle_category' => $route->vehicle_category,
                'distance_km' => $route->distance_km,
                'estimated_duration_minutes' => $route->estimated_duration_minutes,
                'trip_type' => $tripType,
                'days' => $days,
                'price' => $price,
                'currency' => 'XAF'
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Car routes fetched successfully',
            'data' => $results
        ]);
    }
}