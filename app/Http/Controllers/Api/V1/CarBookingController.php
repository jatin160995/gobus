<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\ChauffeurBooking;
use App\Models\ChauffeurRoute;
use App\Models\ChauffeurRoutePrice;
use App\Models\Setting;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Services\PriceCalculatorService;

class CarBookingController extends Controller
{

    private function calculateInsurance($type, $basePrice)
    {
        if ($type === 'car') {

            $percentage = $this->getSetting('insurance_car_percentage', 2.2);

            return ($basePrice * $percentage) / 100;
        }

        if ($type === 'bus') {

            return $this->getSetting('insurance_bus_fee', 100);
        }

        return 0;
    }

    private function getSetting($key, $default = null)
    {
        $setting = \DB::table('settings')->where('key', $key)->value('value');

        return $setting !== null ? $setting : $default;
    }

    public function store(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:chauffeur_routes,id',
            'vehicle_category' => 'required|in:standard,executive,premium,suv',
            'trip_type' => 'required|in:one_way,round_trip',
            'pickup_datetime' => 'required|date',
            'return_datetime' => 'nullable|date',
            'pickup_address' => 'required|string',
            'drop_address' => 'required|string',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Route
        |--------------------------------------------------------------------------
        */

        $route = ChauffeurRoute::find($request->route_id);

        if (!$route) {
            return response()->json([
                'status' => false,
                'message' => 'Route not found'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Pricing
        |--------------------------------------------------------------------------
        */

        $priceRow = ChauffeurRoutePrice::where('chauffeur_route_id', $route->id)
            ->where('vehicle_category', $request->vehicle_category)
            ->where('is_active', 1)
            ->first();

        if (!$priceRow) {
            return response()->json([
                'status' => false,
                'message' => 'Pricing not available'
            ]);
        }

        // /*
        // |--------------------------------------------------------------------------
        // | Calculate Price
        // |--------------------------------------------------------------------------
        // */

        // $pickupDate = Carbon::parse($request->pickup_datetime);
        // $returnDate = $request->return_datetime ? Carbon::parse($request->return_datetime) : null;

        // $days = 1;
        // $basePrice = 0;

        // if ($request->trip_type == 'round_trip' && $returnDate) {

        //     $days = $pickupDate->diffInDays($returnDate) + 1;

        //     if ($days > 1) {
        //         $basePrice = $priceRow->per_day_price * $days;
        //     } else {
        //         $basePrice = $priceRow->round_trip_price;
        //     }

        // } else {

        //     $basePrice = $priceRow->one_way_price;
        // }

        // /*
        // |--------------------------------------------------------------------------
        // | VAT
        // |--------------------------------------------------------------------------
        // */

        // $vatPercent = \DB::table('settings')
        //     ->where('key', 'vat_tax')
        //     ->value('value');

        // $vatPercent = $vatPercent ? floatval($vatPercent) : 0;

        // $vatAmount = ($basePrice * $vatPercent) / 100;

        // $totalPrice = $basePrice + $vatAmount;

        /*
        |--------------------------------------------------------------------------
        | Base Price
        |--------------------------------------------------------------------------
        */

        $basePrice = $priceRow->one_way_price;

        if ($request->trip_type == 'round_trip') {

            $pickupDate = Carbon::parse($request->pickup_datetime);
            $returnDate = Carbon::parse($request->return_datetime);

            $days = $pickupDate->diffInDays($returnDate) + 1;

            if ($days > 1) {

                $basePrice = $priceRow->per_day_price * $days;

            } else {

                $basePrice = $priceRow->round_trip_price;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Provider Commission Rate
        |--------------------------------------------------------------------------
        */

        //$commissionPercent = $route->provider->commission_rate ?? 10;

        //$commissionAmount = ($basePrice * $commissionPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Insurance Deduction
        |--------------------------------------------------------------------------
        */

       // $insuranceAmount = $this->calculateInsurance('car', $basePrice);


        /*
        |--------------------------------------------------------------------------
        | Platform Commission After Insurance
        |--------------------------------------------------------------------------
        */

        // $platformCommission = $commissionAmount - $insuranceAmount;

        // if ($platformCommission < 0) {
        //     $platformCommission = 0;
        // }


        /*
        |--------------------------------------------------------------------------
        | VAT on commission
        |--------------------------------------------------------------------------
        */

        //$vatPercent = $this->getSetting('vat_tax_percentage', 19.25);

        //$vatAmount = ($commissionAmount * $vatPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Total Price
        |--------------------------------------------------------------------------
        */

        //$totalPrice = $basePrice + $commissionAmount + $vatAmount;

       

        // $booking = ChauffeurBooking::create([
        //     'booking_reference' => $bookingReference,
        //     'user_id' => $user->id,
        //     'provider_id' => $route->provider_id,
        //     'chauffeur_route_id' => $route->id,
        //     'chauffeur_vehicle_id' => null,
        //     'chauffeur_driver_id' => null,
        //     'trip_type' => $request->trip_type,
        //     'pickup_datetime' => $request->pickup_datetime,
        //     'return_datetime' => $request->return_datetime,
        //     'pickup_address' => $request->pickup_address,
        //     'drop_address' => $request->drop_address,
        //     'base_price' => $basePrice,
        //     'total_price' => $totalPrice,
        //     'currency' => 'XAF',
        //     'status' => 'pending',
        //     'payment_status' => 'pending',
        // ]);





         /*
        |--------------------------------------------------------------------------
        | Calculations
        |--------------------------------------------------------------------------
        */
        $calculator = new PriceCalculatorService();

        $priceData = $calculator->calculateCarPrice(
            $basePrice,
            $route->provider_id
        );
        $commissionAmount = $priceData['commission'];
        $insuranceAmount = $priceData['insurance'];
        $platformCommission = $priceData['platform_commission'];
        $vatPercent = $priceData['vat_percent'];
        $vatAmount = $priceData['vat_amount'];
        $totalPrice = $priceData['grand_total'];

         /*
        |--------------------------------------------------------------------------
        | Booking Reference
        |--------------------------------------------------------------------------
        */

        $bookingReference = 'CAR-' . date('Ymd') . '-' . random_int(10000, 99999);

        /*
        |--------------------------------------------------------------------------
        | Create Booking
        |--------------------------------------------------------------------------
        */


        $booking = ChauffeurBooking::create([
            'booking_reference' => $bookingReference,
            'user_id' => $user->id,
            'provider_id' => $route->provider_id,
            'chauffeur_route_id' => $route->id,
            'trip_type' => $request->trip_type,
            'pickup_datetime' => $request->pickup_datetime,
            'return_datetime' => $request->return_datetime,
            'pickup_address' => $request->pickup_address,
            'drop_address' => $request->drop_address,
            'base_price' => $basePrice,
            'commission_amount' => $commissionAmount,
            'platform_commission' => $platformCommission,
            'insurance_amount' => $insuranceAmount,
            'vat_amount' => $vatAmount,
            'total_price' => $totalPrice,
            'currency' => 'XAF',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Creating order in Database -----------------------------------------
        $orderReference = 'PAY-' . now()->format('YmdHis') . rand(100,999);

        $paymentOrder = PaymentOrder::create([
            'order_reference' => $orderReference,
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'provider_id' => $route->provider_id,
            'total_amount' => $totalPrice,
            'currency' => 'XAF',
            'payment_status' => 'pending',
            'booking_type' => 'car'
        ]);

        // SPlit payments tracking in Database
        PaymentTransaction::create([
            'transaction_reference' => 'TXN-' . uniqid(),
            'payment_order_id' => $paymentOrder->id,
            'booking_id' => $booking->id,
            'transaction_type' => 'provider_payout',
            'recipient_type' => 'provider',
            'recipient_id' => $route->provider_id,
            'amount' => $basePrice,
            'currency' => 'XAF',
            'transaction_status' => 'pending',
            'booking_type' => 'car'
        ]);

        PaymentTransaction::create([
            'transaction_reference' => 'TXN-' . uniqid(),
            'payment_order_id' => $paymentOrder->id,
            'booking_id' => $booking->id,
            'transaction_type' => 'insurance_payout',
            'recipient_type' => 'insurance',
            'recipient_id' => 1,
            'amount' => $insuranceAmount,
            'currency' => 'XAF',
            'transaction_status' => 'pending',
            'booking_type' => 'car'
        ]);

       

        return response()->json([
            'status' => true,
            'message' => 'Booking created successfully',
            'data' => [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,

                'base_price' => round($priceData['base_price'],2),
                'commission' => round($priceData['commission'],2),
                'insurance' => round($priceData['insurance'],2),
                'platform_commission' => round($priceData['platform_commission'],2),
                'vat_percent' => $priceData['vat_percent'],
                'vat_amount' => round($priceData['vat_amount'],2),
                'grand_total' => round($priceData['grand_total'],2),
                'currency' => 'XAF'
            ]
        ]);
    }
}