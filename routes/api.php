<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TripSearchController;
use App\Http\Controllers\Api\V1\TripDetailController;
use App\Http\Controllers\Api\V1\CitySearchController;
use App\Http\Controllers\Api\V1\CarSearchController;
use App\Http\Controllers\Api\V1\CarRouteDetailController;
use App\Http\Controllers\Api\V1\CarBookingController;
use App\Http\Controllers\Api\V1\BusBookingController;       // ← NEW
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\OrangeWebhookController;
use App\Http\Controllers\Api\V1\PassengerController;
use App\Http\Controllers\Api\V1\MtnCallbackController;
use App\Http\Controllers\Api\V1\MtnPaymentController;
use App\Http\Controllers\Api\V1\MtnDebugController;
use App\Http\Controllers\Api\V1\MtnHardcodedDebugController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\TaxiGo\HubController as TaxiGoHubController;
use App\Http\Controllers\Api\V1\TaxiGo\QuoteController as TaxiGoQuoteController;
use App\Http\Controllers\Api\V1\TaxiGo\RideController as TaxiGoRideController;

Route::get('/', function () {
    return response()->json(['message' => 'GO API is running']);
});

Route::get('/app-config', [AppConfigController::class, 'show']);

/*
|--------------------------------------------------------------------------
| TaxiGo — customer app
|--------------------------------------------------------------------------
*/
Route::prefix('taxigo')->group(function () {
    Route::get('hubs',   [TaxiGoHubController::class, 'index']);
    Route::post('quote', [TaxiGoQuoteController::class, 'store'])->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('rides',               [TaxiGoRideController::class, 'index']);
        Route::post('rides',              [TaxiGoRideController::class, 'store'])->middleware('throttle:20,1');
        Route::get('rides/{ref}',         [TaxiGoRideController::class, 'show']);
        Route::post('rides/{ref}/pay',    [TaxiGoRideController::class, 'pay'])->middleware('throttle:10,1');
        Route::post('rides/{ref}/cancel', [TaxiGoRideController::class, 'cancel']);
    });
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register',         [AuthController::class, 'register']);
    Route::post('verify-otp',       [AuthController::class, 'verifyOtp']);
    Route::post('login',            [AuthController::class, 'login']);
    Route::post('forgot-password',  [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',   [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile',   [AuthController::class, 'profile']);
        Route::post('logout',   [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Public Bus Routes (no auth needed)
|--------------------------------------------------------------------------
*/
Route::post('/trips/search',                [TripSearchController::class, 'search']);
Route::get('/trips/{schedule_id}/details',  [TripDetailController::class, 'show']);
Route::get('/cities/search',                [CitySearchController::class, 'search']);

/*
|--------------------------------------------------------------------------
| Public Car Routes
|--------------------------------------------------------------------------
*/
Route::post('/car/search',          [CarSearchController::class, 'search']);
Route::post('car/route-details',    [CarRouteDetailController::class, 'details']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Passengers
    Route::apiResource('passengers', PassengerController::class);

    // Car Booking
    Route::post('/car/bookings', [CarBookingController::class, 'store']);

    // Bus Bookings  ← NEW
    Route::post('/bus/bookings',                        [BusBookingController::class, 'store']);
    Route::get('/bus/bookings',                         [BusBookingController::class, 'index']);
    Route::get('/bus/bookings/{booking_ref}',            [BusBookingController::class, 'show']);
    Route::post('/bus/bookings/{booking_ref}/cancel',    [BusBookingController::class, 'cancel']);

    // Payments
    Route::post('/payments/initiate',                       [PaymentController::class, 'initiate']);
    Route::get('/payments/status/{orderReference}',         [PaymentController::class, 'status']);
    Route::post('payments/mtn/initiate',                    [MtnPaymentController::class, 'initiate']);
    Route::get('payments/mtn/status/{referenceId}',         [MtnPaymentController::class, 'checkStatus']);
});

/*
|--------------------------------------------------------------------------
| Webhooks (no auth — called by payment gateways)
|--------------------------------------------------------------------------
*/
Route::post('/orange/webhook',          [OrangeWebhookController::class, 'handle']);
Route::post('mtn/webhook/collection',   [MtnCallbackController::class, 'collection']);
Route::post('mtn/webhook/disbursement', [MtnCallbackController::class, 'disbursement']);

Route::get('/test-api', function () {
    return response()->json(['status' => true]);
});

// MTN debug tools: only registered on a local machine, never on the live server
if (app()->environment('local')) {
    Route::get('/debug/mtn-headers', [MtnDebugController::class, 'compareHeaders']);
    Route::get('/debug/mtn-hardcoded', [MtnHardcodedDebugController::class, 'test']);
    Route::get('/debug/mtn-token', function () {
        $tokenService = app(\App\Services\Payment\MtnTokenService::class);
        return response()->json([
            'collection_token'    => $tokenService->getCollectionToken() ? 'OK ✅' : 'FAILED ❌',
            'disbursement_token'  => $tokenService->getDisbursementToken() ? 'OK ✅' : 'FAILED ❌',
            'collection_sub_key'  => $tokenService->getCollectionSubscriptionKey() ? 'SET ✅' : 'MISSING ❌',
            'disbursement_sub_key'=> $tokenService->getDisbursementSubscriptionKey() ? 'SET ✅' : 'MISSING ❌',
        ]);
    });
}