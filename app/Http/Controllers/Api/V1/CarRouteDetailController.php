<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\PriceCalculatorService;
use DB;

class CarRouteDetailController extends Controller
{
    public function details(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:chauffeur_routes,id',
            'vehicle_category' => 'required|in:standard,executive,premium,suv',
            'trip_type' => 'required|in:one_way,round_trip',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'return_date' => 'nullable|date'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Fetch Route + Provider
        |--------------------------------------------------------------------------
        */

        $route = DB::table('chauffeur_routes')
            ->join('providers', 'providers.id', '=', 'chauffeur_routes.provider_id')
            ->join('cities as from_city', 'from_city.id', '=', 'chauffeur_routes.from_city_id')
            ->join('cities as to_city', 'to_city.id', '=', 'chauffeur_routes.to_city_id')
            ->where('chauffeur_routes.id', $request->route_id)
            ->select(
                'chauffeur_routes.id',
                'chauffeur_routes.distance_km',
                'chauffeur_routes.estimated_duration_minutes',
                'providers.id as provider_id',
                'providers.name as provider_name',
                'providers.logo as provider_logo',
                'providers.phone',
                'from_city.name as from_city',
                'to_city.name as to_city'
            )
            ->first();

        if (!$route) {
            return response()->json([
                'status' => false,
                'message' => 'Route not found'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Pricing
        |--------------------------------------------------------------------------
        */

        $priceRow = DB::table('chauffeur_route_prices')
            ->where('chauffeur_route_id', $route->id)
            ->where('vehicle_category', $request->vehicle_category)
            ->where('is_active', 1)
            ->first();

        if (!$priceRow) {
            return response()->json([
                'status' => false,
                'message' => 'Price not found for selected category'
            ]);
        }

        // $pickupDate = Carbon::parse($request->pickup_date);
        // $returnDate = $request->return_date ? Carbon::parse($request->return_date) : null;

        // $days = 1;
        // $price = 0;

        // if ($request->trip_type == 'round_trip' && $returnDate) {

        //     $days = $pickupDate->diffInDays($returnDate) + 1;

        //     if ($days > 1) {
        //         $price = $priceRow->per_day_price * $days;
        //     } else {
        //         $price = $priceRow->round_trip_price;
        //     }

        // } else {
        //     $price = $priceRow->one_way_price;
        // }

        $pickupDate = Carbon::parse($request->pickup_date);
        $returnDate = $request->return_date ? Carbon::parse($request->return_date) : null;

        $days = 1;
        $price = 0;

        if ($request->trip_type == 'round_trip' && $returnDate) {

            $days = $pickupDate->diffInDays($returnDate) + 1;

            if ($days > 1) {
                $price = $priceRow->per_day_price * $days;
            } else {
                $price = $priceRow->round_trip_price;
            }

        } else {
            $price = $priceRow->one_way_price;
        }

        // /*
        // |--------------------------------------------------------------------------
        // | VAT TAX
        // |--------------------------------------------------------------------------
        // */

        // $vatPercent = DB::table('settings')
        //     ->where('key', 'vat_tax_percentage')
        //     ->value('value');

        // $vatPercent = $vatPercent ? floatval($vatPercent) : 0;

        // $vatAmount = ($price * $vatPercent) / 100;

        // $grandTotal = $price + $vatAmount;

        // /*
        // |--------------------------------------------------------------------------
        // | Provider Commission (Internal)
        // |--------------------------------------------------------------------------
        // */

        // $providerCommission = DB::table('providers')
        //     ->where('id', $route->provider_id)
        //     ->value('commission_rate');

        // $providerCommission = $providerCommission ?? 0;

        // $platformCommission = ($price * $providerCommission) / 100;

        // $providerEarning = $price - $platformCommission;
//-----------------New Calculations--------------------------------------------------------------------
        /*
        |--------------------------------------------------------------------------
        | Provider Commission Rate
        |--------------------------------------------------------------------------
        */

        // $commissionPercent = DB::table('providers')
        //     ->where('id', $route->provider_id)
        //     ->value('commission_rate');

        // $commissionPercent = $commissionPercent ?? 0;

        // $commissionAmount = ($price * $commissionPercent) / 100;


        // /*
        // |--------------------------------------------------------------------------
        // | Insurance (Car = 2.2%)
        // |--------------------------------------------------------------------------
        // */

        // $insurancePercent = DB::table('settings')
        //     ->where('key', 'insurance_car_percentage')
        //     ->value('value');

        // $insurancePercent = $insurancePercent ? floatval($insurancePercent) : 2.2;

        // $insuranceAmount = ($price * $insurancePercent) / 100;


        // /*
        // |--------------------------------------------------------------------------
        // | Platform Commission After Insurance
        // |--------------------------------------------------------------------------
        // */

        // $platformCommission = $commissionAmount - $insuranceAmount;

        // if ($platformCommission < 0) {
        //     $platformCommission = 0;
        // }


        // /*
        // |--------------------------------------------------------------------------
        // | VAT on Commission
        // |--------------------------------------------------------------------------
        // */

        // $vatPercent = DB::table('settings')
        //     ->where('key', 'vat_tax_percentage')
        //     ->value('value');

        // $vatPercent = $vatPercent ? floatval($vatPercent) : 0;

        // $vatAmount = ($commissionAmount * $vatPercent) / 100;


        // /*
        // |--------------------------------------------------------------------------
        // | Grand Total
        // |--------------------------------------------------------------------------
        // */

        // $grandTotal = $price + $commissionAmount + $vatAmount;

        /*
        |--------------------------------------------------------------------------
        | Fetch Vehicles of Same Provider & Category
        |--------------------------------------------------------------------------
        */

        $vehicles = DB::table('chauffeur_vehicles')
            ->where('provider_id', $route->provider_id)
            ->where('category', $request->vehicle_category)
            ->where('is_active', 1)
            ->get();

        $vehicleData = [];

        foreach ($vehicles as $vehicle) {

            $images = DB::table('chauffeur_vehicle_images')
                ->where('chauffeur_vehicle_id', $vehicle->id)
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($img) {
                    return [
                        'type' => $img->image_type,
                        'image' => url('storage/' . $img->image_path)
                    ];
                });

            $vehicleData[] = [
                'id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'seats' => $vehicle->seats,
                'plate_number' => $vehicle->plate_number,
                'color' => $vehicle->color,
                'fuel_type' => $vehicle->fuel_type,
                'transmission' => $vehicle->transmission,
                'images' => $images
            ];
        }



        /*
        |--------------------------------------------------------------------------
        | Calculations
        |--------------------------------------------------------------------------
        */
        $calculator = new PriceCalculatorService();

        $priceData = $calculator->calculateCarPrice(
            $price,
            $route->provider_id
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,
            'message' => 'Route details fetched successfully',
            'data' => [

                'route' => [
                    'id' => $route->id,
                    'from_city' => $route->from_city,
                    'to_city' => $route->to_city,
                    'distance_km' => $route->distance_km,
                    'duration_minutes' => $route->estimated_duration_minutes
                ],

                'provider' => [
                    'id' => $route->provider_id,
                    'name' => $route->provider_name,
                    'logo' => $route->provider_logo
                        ? url('storage/' . $route->provider_logo)
                        : null,
                    'phone' => $route->phone
                ],

                'vehicle_category' => $request->vehicle_category,

                'pricing' => [
                        'trip_type' => $request->trip_type,
                        'days' => $days,

                        'base_price' => round($priceData['base_price'],2),
                        'commission' => round($priceData['commission'],2),
                        'insurance' => round($priceData['insurance'],2),
                        'platform_commission' => round($priceData['platform_commission'],2),
                        'vat_percent' => $priceData['vat_percent'],
                        'vat_amount' => round($priceData['vat_amount'],2),
                        'grand_total' => round($priceData['grand_total'],2),
                        'currency' => 'XAF'
                    ],

                'vehicles' => $vehicleData
            ]
        ]);
    }
}