<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CheckMtnPaymentStatus;
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

        if (!$result['success']) {
            return response()->json(['status' => false, 'error' => $result['message']], 422);
        }

        // Kick off server-side polling job (handles app-close / timeout scenarios)
        CheckMtnPaymentStatus::dispatch(
            $result['reference_id'],
            $result['order_id']
        )->delay(now()->addSeconds(5));

        return response()->json([
            'status'  => true,
            'message' => 'Payment request sent. Please approve on your MTN MoMo phone.',
            'data'    => [
                'reference_id'    => $result['reference_id'],
                'order_id'        => $result['order_id'],
                'amount'          => $result['amount'],
                'currency'        => 'XAF',
                'poll_interval_s' => 5,    // Tell Flutter how often to poll
                'timeout_s'       => 120,  // Tell Flutter when to give up
            ],
        ]);
    }

    /**
     * Flutter calls this every 5 seconds to check payment status.
     * GET /api/payments/mtn/status/{referenceId}
     *
     * Both Flutter (active polling) AND CheckMtnPaymentStatus job (server-side)
     * call the same handlePaymentSuccess/Failed — idempotent, safe to call twice.
     */
    public function checkStatus(string $referenceId)
    {
        $order = PaymentOrder::where('gateway_transaction_id', $referenceId)->first();

        if (!$order) {
            return response()->json(['status' => false, 'error' => 'Order not found'], 404);
        }

        // Auto-fail if over 2 minutes and still pending (Flutter-side timeout guard)
        if (
            $order->payment_status === 'pending'
            && $order->created_at->diffInSeconds(now()) > 120
        ) {
            $this->paymentService->handlePaymentFailed($referenceId);
            $order->refresh();
        }

        // If already resolved in DB, return immediately (no MTN call needed)
        if (in_array($order->payment_status, ['paid', 'failed'])) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'payment_status' => $order->payment_status,
                    'order_id'       => $order->id,
                    'booking_id'     => $order->booking_id,
                    'booking_type'   => $order->booking_type,
                    'mtn_status'     => strtoupper($order->payment_status === 'paid' ? 'SUCCESSFUL' : 'FAILED'),
                ],
            ]);
        }

        // Still pending — poll MTN directly
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
                'mtn_status'     => $mtnStatus,             // PENDING | SUCCESSFUL | FAILED
                'order_id'       => $order->id,
                'booking_id'     => $order->booking_id,
                'booking_type'   => $order->booking_type,
            ],
        ]);
    }
}