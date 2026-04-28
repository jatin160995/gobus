<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Services\Payment\MtnCollectionService;
use App\Services\Payment\MtnPaymentService;
use Illuminate\Http\Request;

class MtnPaymentController extends Controller
{
    public function __construct(
        private MtnPaymentService    $paymentService,
        private MtnCollectionService $collectionService,
    ) {}

    /**
     * Initiate MTN MoMo payment.
     * POST /api/payments/mtn/initiate
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'booking_id'   => 'required|integer',
            'booking_type' => 'required|in:bus,car',
            'msisdn'       => 'required|string|min:9',
        ]);

        if ($request->booking_type === 'bus') {
            $result = $this->paymentService->initiateForBusBooking(
                $request->booking_id,
                $request->msisdn
            );
        } else {
            $result = $this->paymentService->initiateForCarBooking(
                $request->booking_id,
                $request->msisdn
            );
        }

        if ($result['success']) {
            return response()->json(['status' => true, 'data' => $result]);
        }

        return response()->json(['status' => false, 'error' => $result['message']], 422);
    }

    /**
     * Poll payment status — Flutter calls this every few seconds.
     * GET /api/payments/mtn/status/{referenceId}
     */
    public function checkStatus(string $referenceId)
    {
        // First check our DB (fastest — updated by webhook)
        $order = PaymentOrder::where('gateway_transaction_id', $referenceId)->first();

        if (!$order) {
            return response()->json(['status' => false, 'error' => 'Order not found'], 404);
        }

        // If already resolved in DB, return immediately
        if (in_array($order->payment_status, ['paid', 'failed'])) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'payment_status' => $order->payment_status,
                    'order_id'       => $order->id,
                    'booking_id'     => $order->booking_id,
                    'booking_type'   => $order->booking_type,
                ],
            ]);
        }

        // Still pending — check MTN directly
        $mtnStatus = $this->collectionService->getTransactionStatus($referenceId);

        if ($mtnStatus === 'SUCCESSFUL') {
            $this->paymentService->handlePaymentSuccess($referenceId);
            $order->refresh();
        } elseif ($mtnStatus === 'FAILED') {
            $this->paymentService->handlePaymentFailed($referenceId);
            $order->refresh();
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'payment_status' => $order->payment_status, // pending | paid | failed
                'mtn_status'     => $mtnStatus,
                'order_id'       => $order->id,
                'booking_id'     => $order->booking_id,
                'booking_type'   => $order->booking_type,
            ],
        ]);
    }
}