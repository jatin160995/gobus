<?php

namespace App\Http\Controllers\Api\V1\TaxiGo;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxiGo\QuoteRequest;
use App\Services\TaxiGo\RideBookingService;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    /**
     * Fare before booking.
     * POST /api/taxigo/quote
     */
    public function store(QuoteRequest $request, RideBookingService $booking): JsonResponse
    {
        $q = $booking->quote($request->validated());

        return response()->json([
            'status' => true,
            'data'   => $q['quote'] + [
                'hub_id'      => $q['hub']->id,
                'trip_type'   => $request->trip_type,
                'zone'        => $q['zone'] ? ['id' => $q['zone']->id, 'code' => $q['zone']->code] : null,
                'destination' => $q['destination'] ? ['id' => $q['destination']->id, 'name' => $q['destination']->name] : null,
                'pickup_at'   => $q['pickup_at']->copy()->setTimezone(config('taxigo.timezone'))->toIso8601String(),
            ],
        ]);
    }
}
