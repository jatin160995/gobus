<?php

namespace App\Services;

use App\Jobs\ProcessSplitPayment;
use App\Models\ChauffeurBooking;
use App\Models\Booking;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    protected OrangeMoneyService $orangeService;

    public function __construct(OrangeMoneyService $orangeService)
    {
        $this->orangeService = $orangeService;
    }

    // -------------------------------------------------------
    // 1. Initiate Payment
    // Called when user clicks "Pay Now"
    // -------------------------------------------------------
    public function initiatePayment(
        int    $bookingId,
        string $bookingType,   // 'bus' or 'car'
        string $subscriberMsisdn,
        string $paymentMethod = 'orange_money'
    ): array {
        // Step 1 — Load the booking
        $booking = $this->getBooking($bookingId, $bookingType);

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        // Step 2 — Check booking status
        $bookingStatus = $bookingType === 'car'
            ? $booking->status
            : $booking->booking_status;

        if (!in_array($bookingStatus, ['pending', 'confirmed'])) {
            throw new \Exception('Booking is not in a payable state.');
        }

        // Step 3 — Check if payment order already exists and is paid
        $existingOrder = PaymentOrder::where('booking_id', $bookingId)
            ->where('booking_type', $bookingType)
            ->where('payment_status', 'paid')
            ->first();

        if ($existingOrder) {
            throw new \Exception('This booking has already been paid.');
        }

        // Step 4 — Get or create payment order
        $paymentOrder = PaymentOrder::where('booking_id', $bookingId)
            ->where('booking_type', $bookingType)
            ->where('payment_status', 'pending')
            ->first();

        if (!$paymentOrder) {
            $paymentOrder = PaymentOrder::create([
                'order_reference' => 'PAY-' . now()->format('YmdHis') . rand(100, 999),
                'booking_id'      => $bookingId,
                'booking_type'    => $bookingType,
                'user_id'         => $booking->user_id,
                'provider_id'     => $booking->provider_id,
                'total_amount'    => $booking->total_price ?? $booking->total_amount,
                'currency'        => 'XAF',
                'payment_method'  => $paymentMethod,
                'payment_status'  => 'pending',
            ]);
        }

        // Step 5 — Call Orange MP Init to get payToken
        $payToken = $this->orangeService->initMerchantPayment();

        // Step 6 — Call Orange MP Pay
        $mpResponse = $this->orangeService->executeMerchantPayment(
            payToken:          $payToken,
            subscriberMsisdn:  $subscriberMsisdn,
            amount:            (int) $paymentOrder->total_amount,
            orderId:           substr($paymentOrder->order_reference, 0, 20),
            description:       'GoBus booking payment'
        );

        // Step 7 — Push payment prompt to user phone
        $this->orangeService->pushPaymentPrompt($payToken);

        // Step 8 — Store payToken in payment order for webhook matching
        $paymentOrder->update([
            'gateway_transaction_id' => $payToken,
        ]);

        Log::info('Payment initiated', [
            'order_reference' => $paymentOrder->order_reference,
            'pay_token'       => $payToken,
            'booking_id'      => $bookingId,
            'booking_type'    => $bookingType,
        ]);

        return [
            'order_reference' => $paymentOrder->order_reference,
            'pay_token'       => $payToken,
            'amount'          => $paymentOrder->total_amount,
            'currency'        => 'XAF',
            'status'          => 'pending',
            'message'         => 'Payment request sent to your phone. Please confirm with your PIN.',
        ];
    }

    // -------------------------------------------------------
    // 2. Handle Orange Webhook Callback
    // Called by Orange after user confirms payment
    // -------------------------------------------------------
    public function handleWebhookCallback(array $payload): bool
    {
        Log::info('Orange webhook received', $payload);

        $payToken = $payload['payToken'] ?? null;
        $txnId    = $payload['txnid'] ?? null;
        $status   = $payload['status'] ?? null;

        if (!$payToken) {
            Log::warning('Orange webhook missing payToken', $payload);
            return false;
        }

        // Step 1 — Find payment order by payToken
        $paymentOrder = PaymentOrder::where('gateway_transaction_id', $payToken)->first();

        if (!$paymentOrder) {
            Log::warning('Payment order not found for payToken', ['pay_token' => $payToken]);
            return false;
        }

        // Step 2 — Idempotency check — already processed
        if ($paymentOrder->payment_status === 'paid') {
            Log::info('Webhook already processed for order', [
                'order_reference' => $paymentOrder->order_reference,
            ]);
            return true;
        }

        // Step 3 — Verify payment status directly with Orange (never trust webhook alone)
        try {
            $verification = $this->orangeService->checkPaymentStatus($payToken);
            $confirmedStatus = $verification['status'] ?? null;
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'pay_token' => $payToken,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }

        // Step 4 — Only process if Orange confirms SUCCESS
        if (strtolower($confirmedStatus) !== 'success') {
            Log::warning('Payment not confirmed by Orange', [
                'pay_token' => $payToken,
                'status'    => $confirmedStatus,
            ]);

            // Mark as failed if Orange says failed
            if (in_array(strtolower($confirmedStatus), ['failed', 'expired', 'cancelled'])) {
                $paymentOrder->update(['payment_status' => 'failed']);
            }

            return false;
        }

        // Step 5 — Mark payment order as paid inside transaction
        DB::transaction(function () use ($paymentOrder, $txnId) {

            $paymentOrder->update([
                'payment_status'         => 'paid',
                'gateway_transaction_id' => $txnId ?? $paymentOrder->gateway_transaction_id,
                'paid_at'                => now(),
            ]);

            // Step 6 — Update booking status
            $this->confirmBooking(
                $paymentOrder->booking_id,
                $paymentOrder->booking_type
            );

            Log::info('Payment confirmed and booking updated', [
                'order_reference' => $paymentOrder->order_reference,
                'booking_id'      => $paymentOrder->booking_id,
                'booking_type'    => $paymentOrder->booking_type,
            ]);
        });

        // Step 7 — Dispatch split payment job (runs in background)
        ProcessSplitPayment::dispatch($paymentOrder->id)
            ->delay(now()->addSeconds(5));

        Log::info('Split payment job dispatched', [
            'payment_order_id' => $paymentOrder->id,
        ]);

        return true;
    }

    // -------------------------------------------------------
    // 3. Confirm Booking After Payment
    // -------------------------------------------------------
    protected function confirmBooking(int $bookingId, string $bookingType): void
    {
        if ($bookingType === 'car') {
            ChauffeurBooking::where('id', $bookingId)
                ->update([
                    'status'         => 'provider_confirmed',
                    'payment_status' => 'paid',
                ]);
        } else {
            Booking::where('id', $bookingId)
                ->update([
                    'booking_status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);
        }
    }

    // -------------------------------------------------------
    // 4. Check Payment Status (for frontend polling)
    // -------------------------------------------------------
    public function checkPaymentStatus(string $orderReference): array
    {
        $paymentOrder = PaymentOrder::where('order_reference', $orderReference)->first();

        if (!$paymentOrder) {
            throw new \Exception('Payment order not found.');
        }

        // If already paid, return immediately
        if ($paymentOrder->payment_status === 'paid') {
            return [
                'status'          => 'paid',
                'order_reference' => $orderReference,
                'message'         => 'Payment successful.',
            ];
        }

        // If pending, verify with Orange
        if ($paymentOrder->gateway_transaction_id) {
            try {
                $orangeStatus = $this->orangeService->checkPaymentStatus(
                    $paymentOrder->gateway_transaction_id
                );

                return [
                    'status'          => $paymentOrder->payment_status,
                    'orange_status'   => $orangeStatus['status'] ?? 'unknown',
                    'order_reference' => $orderReference,
                    'message'         => 'Payment is being processed.',
                ];
            } catch (\Exception $e) {
                Log::error('Status check failed', ['error' => $e->getMessage()]);
            }
        }

        return [
            'status'          => $paymentOrder->payment_status,
            'order_reference' => $orderReference,
            'message'         => 'Payment pending.',
        ];
    }

    // -------------------------------------------------------
    // 5. Helper — Load booking by type
    // -------------------------------------------------------
    protected function getBooking(int $bookingId, string $bookingType): mixed
    {
        if ($bookingType === 'car') {
            return ChauffeurBooking::find($bookingId);
        }

        return Booking::find($bookingId);
    }
}