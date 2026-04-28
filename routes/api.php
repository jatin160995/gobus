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
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\OrangeWebhookController;
use App\Http\Controllers\Api\V1\PassengerController;
use App\Http\Controllers\Api\V1\MtnCallbackController;
use App\Http\Controllers\Api\V1\MtnPaymentController;

Route::get('/', function () {
    return response()->json(['message' => 'GO API is running']);
});
Route::prefix('auth')->group(function() {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
Route::post('/trips/search', [TripSearchController::class, 'search']);
Route::get('/trips/{schedule_id}/details', [TripDetailController::class, 'show']);
Route::get(
    '/cities/search',
    [CitySearchController::class, 'search']
);


//----Chauffeur-Car-API-----------------------------
Route::post('/car/search', [CarSearchController::class, 'search']);
Route::post('car/route-details', [CarRouteDetailController::class, 'details']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/car/bookings', [CarBookingController::class, 'store']);
});


// -------------------------------------------------------
// Payment Routes (authenticated)
// -------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    // Passenger
    Route::apiResource('passengers', PassengerController::class);
    //Payments
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::get('/payments/status/{orderReference}', [PaymentController::class, 'status']);
});

// -------------------------------------------------------
// Orange Webhook (NO auth middleware — Orange calls this)
// -------------------------------------------------------
Route::post('/orange/webhook', [OrangeWebhookController::class, 'handle']);


// MTN MoMo Webhooks (no auth)
Route::post('mtn/webhook/collection',   [MtnCallbackController::class, 'collection']);
Route::post('mtn/webhook/disbursement', [MtnCallbackController::class, 'disbursement']);
// TEMP DEBUG — remove after MTN tech team confirms payload
Route::post('mtn/webhook/debug', [MtnCallbackController::class, 'debug']);

// MTN MoMo Payment (auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/mtn/initiate',          [MtnPaymentController::class, 'initiate']);
    Route::get('payments/mtn/status/{referenceId}', [MtnPaymentController::class, 'checkStatus']);
});

Route::get('/test-api', function () {
    return response()->json(['status' => true]);
});
