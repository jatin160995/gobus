<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // -------------------------------------------------------
    // 1. Initiate Payment
    // POST /api/payments/initiate
    // -------------------------------------------------------
    public function initiate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id'   => 'required|integer',
            'booking_type' => 'required|in:bus,car',
            'msisdn'       => 'required|string|min:9|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->initiatePayment(
                bookingId:        $request->booking_id,
                bookingType:      $request->booking_type,
                subscriberMsisdn: $request->msisdn,
                paymentMethod:    'orange_money'
            );

            return response()->json([
                'status'  => true,
                'message' => 'Payment initiated successfully.',
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment initiation failed', [
                'user_id'      => Auth::id(),
                'booking_id'   => $request->booking_id,
                'booking_type' => $request->booking_type,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // -------------------------------------------------------
    // 2. Check Payment Status (frontend polling)
    // GET /api/payments/status/{orderReference}
    // -------------------------------------------------------
    public function status(string $orderReference): JsonResponse
    {
        try {
            $result = $this->paymentService->checkPaymentStatus($orderReference);

            return response()->json([
                'status'  => true,
                'message' => 'Payment status fetched.',
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'order_reference' => $orderReference,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}