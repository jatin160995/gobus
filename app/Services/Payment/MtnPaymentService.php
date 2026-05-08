<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\ChauffeurBooking;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnPaymentService
{
    public function __construct(
        private MtnCollectionService $collectionService
    ) {}

    /**
     * Initiate MTN MoMo payment for a bus booking.
     */
    public function initiateForBusBooking(int $bookingId, string $msisdn): array
    {
        $booking = Booking::with('trip.provider')->findOrFail($bookingId);
        return $this->initiate($booking, 'bus', $msisdn);
    }

    /**
     * Initiate MTN MoMo payment for a car booking.
     */
    public function initiateForCarBooking(int $bookingId, string $msisdn): array
    {
        $booking = ChauffeurBooking::with('provider')->findOrFail($bookingId);
        return $this->initiate($booking, 'car', $msisdn);
    }

    private function initiate($booking, string $bookingType, string $msisdn): array
    {
        try {
            DB::beginTransaction();

            $totalAmount = (int) round($booking->total_price ?? $booking->total_amount ?? 0);
            $providerId = ($bookingType === 'bus')
                ? $booking->trip->provider_id      // bus: booking → trip → provider_id
                : $booking->provider_id; 
            $externalId  = 'GOBUS-' . strtoupper($bookingType) . '-' . $booking->id . '-' . time();

            // Create PaymentOrder
            $order = PaymentOrder::create([
                'order_reference'      => 'PAY-MTN-' . now()->format('YmdHis') . rand(100, 999),
                'booking_id'           => $booking->id,
                'booking_type'         => $bookingType,
                'user_id'              => $booking->user_id,
                'provider_id'          => $providerId,
                'total_amount'         => $totalAmount,
                'currency'             => 'XAF',
                'payment_method'       => 'mtn_momo',
                'payment_status'       => 'pending',
            ]);

            // Create the 3 pending transaction records
            $splits = $this->calculateSplits($totalAmount, $bookingType, $booking);
            $this->createPendingTransactions($order, $booking, $bookingType, $splits, $providerId);

            // Call MTN API
            $referenceId = $this->collectionService->requestToPay(
                amount:       $totalAmount,
                msisdn:       $msisdn,
                externalId:   $externalId,
                payerMessage: 'GoBus Payment - Ref: ' . $order->order_reference,
                payeeNote:    'GoBus Booking #' . $booking->id,
            );

            if (!$referenceId) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Failed to initiate MTN MoMo payment. Please try again.'];
            }

            // Store MTN reference on the order
            $order->update(['gateway_transaction_id' => $referenceId]);

            DB::commit();

            return [
                'success'      => true,
                'message'      => 'Payment request sent. Please approve on your MTN MoMo.',
                'reference_id' => $referenceId,
                'order_id'     => $order->id,
                'amount'       => $totalAmount,
                'currency'     => 'XAF',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MTN Payment initiation failed', [
                'booking_id'   => $booking->id,
                'booking_type' => $bookingType,
                'error'        => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Payment initiation error: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate the 3 split amounts.
     */
    public function calculateSplits(float $totalAmount, string $bookingType, $booking): array
    {
        // Fetch fee settings (same keys used by Orange)
       if ($bookingType === 'bus') {
            $commissionRate = (float) ($booking->trip->provider->commission_rate ?? 10);
        } else {
            $commissionRate = (float) ($booking->provider->commission_rate ?? 10);
        }
        $insuranceBusFee    = (float) Setting::getValue('insurance_bus_fee', 100);
        $insuranceCarPct    = (float) Setting::getValue('insurance_car_percentage', 2.2);
        $vatPct             = (float) Setting::getValue('vat_tax_percentage', 19.25);

        if ($bookingType === 'car') {
            $insuranceAmount = round(($totalAmount * $insuranceCarPct) / 100, 2);
        } else {
            // For bus: insurance is a flat fee per booking
            $passengerCount = $booking->passenger_count ?: 1;
            $insuranceAmount = $insuranceBusFee * $passengerCount;
        }

        $vatAmount         = round(($totalAmount * $vatPct) / 119.25, 2); // VAT extracted from total
        $platformAmount    = round(($totalAmount * $commissionRate) / 100, 2);
        $providerAmount    = round($totalAmount - $insuranceAmount - $platformAmount, 2);

        return [
            'provider_amount'  => $providerAmount,
            'insurance_amount' => $insuranceAmount,
            'platform_amount'  => $platformAmount,
            'vat_amount'       => $vatAmount,
        ];
    }

    /**
     * Create the 3 pending PaymentTransaction rows.
     */
    private function createPendingTransactions(
        PaymentOrder $order,
        $booking,
        string $bookingType,
        array $splits,
         int $providerId 
    ): void {
        
        // 1. Provider payout
        PaymentTransaction::create([
            'transaction_reference' => 'TXN-MTN-' . uniqid(),
            'payment_order_id'      => $order->id,
            'booking_id'            => $booking->id,
            'booking_type'          => $bookingType,
            'transaction_type'      => 'provider_payout',
            'recipient_type'        => 'provider',
            'recipient_id'          => $providerId,
            'amount'                => $splits['provider_amount'],
            'currency'              => 'XAF',
            'payment_method'        => 'mtn_momo',
            'transaction_status'    => 'pending',
        ]);

        // 2. Insurance payout
        PaymentTransaction::create([
            'transaction_reference' => 'TXN-MTN-' . uniqid(),
            'payment_order_id'      => $order->id,
            'booking_id'            => $booking->id,
            'booking_type'          => $bookingType,
            'transaction_type'      => 'insurance_payout',
            'recipient_type'        => 'insurance',
            'recipient_id'          => 1, // Insurance provider ID
            'amount'                => $splits['insurance_amount'],
            'currency'              => 'XAF',
            'payment_method'        => 'mtn_momo',
            'transaction_status'    => 'pending',
        ]);

        // 3. Platform commission (no outgoing transfer, just recorded)
        PaymentTransaction::create([
            'transaction_reference' => 'TXN-MTN-' . uniqid(),
            'payment_order_id'      => $order->id,
            'booking_id'            => $booking->id,
            'booking_type'          => $bookingType,
            'transaction_type'      => 'platform_commission',
            'recipient_type'        => 'platform',
            'recipient_id'          => null,
            'amount'                => $splits['platform_amount'],
            'currency'              => 'XAF',
            'payment_method'        => 'mtn_momo',
            'transaction_status'    => 'pending',
        ]);
    }

    /**
     * Handle confirmed payment — mark order paid, trigger disbursements.
     * Called from both webhook and polling.
     */
    public function handlePaymentSuccess(string $referenceId): bool
    {
        $order = PaymentOrder::where('gateway_transaction_id', $referenceId)
            ->where('payment_status', 'pending')
            ->first();

        if (!$order) {
            Log::warning('MTN: handlePaymentSuccess — order not found', ['referenceId' => $referenceId]);
            return false;
        }

        try {
            DB::beginTransaction();

            $order->update([
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            // Update booking status
            $this->confirmBooking($order);

            // Dispatch disbursement jobs
            $transactions = PaymentTransaction::where('payment_order_id', $order->id)
                ->whereIn('transaction_type', ['provider_payout', 'insurance_payout'])
                ->get();

            foreach ($transactions as $transaction) {
                \App\Jobs\ProcessMtnDisbursement::dispatch($transaction->id)
                    ->delay(now()->addSeconds(5)); // small delay so collection settles
            }

            // Mark platform commission as success immediately (no outgoing transfer)
            PaymentTransaction::where('payment_order_id', $order->id)
                ->where('transaction_type', 'platform_commission')
                ->update(['transaction_status' => 'success', 'processed_at' => now()]);

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MTN: handlePaymentSuccess failed', [
                'referenceId' => $referenceId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle failed payment.
     */
    public function handlePaymentFailed(string $referenceId): void
    {
        $order = PaymentOrder::where('gateway_transaction_id', $referenceId)->first();
        if (!$order) return;

        $order->update(['payment_status' => 'failed']);

        PaymentTransaction::where('payment_order_id', $order->id)
            ->update(['transaction_status' => 'failed']);

        Log::info('MTN: Payment marked as failed', ['referenceId' => $referenceId]);
    }

    private function confirmBooking(PaymentOrder $order): void
    {
        if ($order->booking_type === 'bus') {
            Booking::where('id', $order->booking_id)
                ->update(['booking_status' => 'confirmed', 'payment_status' => 'paid']);
        } else {
            ChauffeurBooking::where('id', $order->booking_id)
                ->update(['status' => 'provider_confirmed', 'payment_status' => 'paid']);
        }
    }
}