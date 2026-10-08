<?php

namespace App\Services\TaxiGo;

use App\Jobs\CheckMtnPaymentStatus;
use App\Models\PaymentOrder;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\RideStatusLog;
use App\Services\OrangeMoneyService;
use App\Services\Payment\MtnCollectionService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Collects a TaxiGo fare with MTN MoMo or Orange Money (push to the payer's
 * phone) and reacts when the gateway confirms it. The existing MTN and Orange
 * success handlers call markPaid() / markFailed() for orders of type 'taxigo'.
 */
class RidePaymentService
{
    public function __construct(
        private MtnCollectionService $mtn,
        private OrangeMoneyService $orange,
        private RideSplitService $splits,
    ) {}

    /**
     * Send a payment request to the payer's phone.
     *
     * @throws RuntimeException with a message safe to show the user
     */
    public function initiate(Ride $ride, string $method, string $phone): PaymentOrder
    {
        if ($ride->payment_status === 'paid') {
            throw new RuntimeException('This ride is already paid.');
        }
        if ($ride->status !== 'pending_payment') {
            throw new RuntimeException('This ride can no longer be paid.');
        }

        $previous = $ride->paymentOrder;
        $wait = (int) config('taxigo.payment_retry_after_seconds', 150);
        if ($previous && $previous->payment_status === 'pending' && $previous->created_at->gt(now()->subSeconds($wait))) {
            throw new RuntimeException('A payment request is already waiting on your phone. Approve it, or try again in a few minutes.');
        }

        $order = PaymentOrder::create([
            'order_reference' => 'PAY-TG-' . now()->format('ymdHis') . random_int(100, 999),
            'booking_id'      => $ride->id,
            'booking_type'    => 'taxigo',
            'user_id'         => $ride->customer_user_id ?? $ride->created_by_user_id,
            'provider_id'     => null,
            'total_amount'    => (int) round((float) $ride->total_fare),
            'currency'        => 'XAF',
            'payment_method'  => $method,
            'payment_status'  => 'pending',
        ]);

        try {
            $gatewayId = $method === 'mtn_momo'
                ? $this->requestMtn($ride, $order, $phone)
                : $this->requestOrange($ride, $order, $phone);
        } catch (\Throwable $e) {
            $order->update(['payment_status' => 'failed']);
            Log::error('TaxiGo payment request failed', ['ride' => $ride->ref, 'method' => $method, 'error' => $e->getMessage()]);
            throw new RuntimeException('We could not send the payment request. Check the number and try again.');
        }

        $order->update(['gateway_transaction_id' => $gatewayId]);
        $ride->update([
            'payment_order_id' => $order->id,
            'payment_method'   => $method,
            'payer_phone'      => $phone,
            'payment_status'   => 'pending',
        ]);

        if ($method === 'mtn_momo') {
            // Server-side polling, in case the app closes before approval
            CheckMtnPaymentStatus::dispatch($gatewayId, $order->id)->delay(now()->addSeconds(5));
        }

        return $order;
    }

    private function requestMtn(Ride $ride, PaymentOrder $order, string $phone): string
    {
        $referenceId = $this->mtn->requestToPay(
            amount:       (float) $order->total_amount,
            msisdn:       $phone,
            externalId:   'TAXIGO-' . $ride->id . '-' . time(),
            payerMessage: 'TaxiGo ' . $ride->ref,
            payeeNote:    'TaxiGo ride ' . $ride->ref,
        );

        if (!$referenceId) {
            throw new RuntimeException('MTN requestToPay returned no reference');
        }

        return $referenceId;
    }

    private function requestOrange(Ride $ride, PaymentOrder $order, string $phone): string
    {
        $payToken = $this->orange->initMerchantPayment();
        $this->orange->executeMerchantPayment(
            payToken:         $payToken,
            subscriberMsisdn: $this->localMsisdn($phone),
            amount:           (int) $order->total_amount,
            orderId:          substr($order->order_reference, 0, 20),
            description:      'TaxiGo ' . $ride->ref,
        );
        $this->orange->pushPaymentPrompt($payToken);

        return $payToken;
    }

    /**
     * Called by the MTN and Orange success handlers, inside their DB transaction.
     */
    public function markPaid(PaymentOrder $order): void
    {
        $ride = Ride::lockForUpdate()->find($order->booking_id);

        if (!$ride) {
            Log::critical('TaxiGo: paid order has no ride', ['order' => $order->order_reference]);
            return;
        }

        if ($ride->payment_status === 'paid' && (int) $ride->payment_order_id !== (int) $order->id) {
            Log::critical('TaxiGo: ride paid twice, refund the second payment', [
                'ride' => $ride->ref, 'kept_order' => $ride->payment_order_id, 'duplicate_order' => $order->order_reference,
            ]);
            return;
        }
        if ($ride->payment_status === 'paid') {
            return;
        }

        $from = $ride->status;
        $ride->update([
            'payment_order_id' => $order->id,
            'payment_method'   => $order->payment_method,
            'payment_status'   => 'paid',
            'paid_at'          => now(),
            // A payment approved just after the ride expired still books it
            'status'           => in_array($from, ['pending_payment', 'expired']) ? 'searching' : $from,
        ]);

        RideStatusLog::create([
            'ride_id' => $ride->id, 'from_status' => $from, 'to_status' => $ride->status,
            'actor_type' => 'system', 'note' => 'Payment confirmed: ' . $order->order_reference,
        ]);

        try {
            $this->splits->createForPaidRide($ride, $order);
            DB::afterCommit(fn () => $this->splits->dispatchPending($order));
        } catch (\Throwable $e) {
            // The customer has paid: the ride goes ahead, and the split is fixed from the Dashboard
            Log::critical('TaxiGo: revenue split failed', ['ride' => $ride->ref, 'error' => $e->getMessage()]);
        }

        // Step 4 sends the ride to the hub's online drivers from here.
    }

    public function markFailed(PaymentOrder $order): void
    {
        Ride::where('id', $order->booking_id)
            ->where('payment_order_id', $order->id)
            ->where('payment_status', '!=', 'paid')
            ->update(['payment_status' => 'failed']);
    }

    /**
     * Orange has no server-side polling job: when the app checks a ride, ask
     * Orange directly (at most every 10 s) in case its webhook never arrived.
     */
    public function syncOrangeStatus(Ride $ride): void
    {
        $order = $ride->paymentOrder;
        if (!$order || $order->payment_method !== 'orange_money' || $order->payment_status !== 'pending' || !$order->gateway_transaction_id) {
            return;
        }
        if (!Cache::add("taxigo:orange-sync:{$order->id}", 1, 10)) {
            return;
        }

        try {
            $status = strtolower($this->orange->checkPaymentStatus($order->gateway_transaction_id)['status'] ?? '');
            if (in_array($status, ['success', 'successfull', 'successful', 'failed', 'expired', 'cancelled'])) {
                // Same path as the webhook: verifies again and records the result
                app(PaymentService::class)->handleWebhookCallback(['payToken' => $order->gateway_transaction_id]);
            }
        } catch (\Throwable $e) {
            Log::warning('TaxiGo: Orange status check failed', ['order' => $order->order_reference, 'error' => $e->getMessage()]);
        }
    }

    /** Orange expects the 9-digit local number. */
    private function localMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) === 12 && str_starts_with($digits, '237') ? substr($digits, 3) : $digits;
    }
}
