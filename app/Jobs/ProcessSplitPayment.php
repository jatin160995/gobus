<?php

namespace App\Jobs;

use App\Models\InsuranceCompany;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\Provider;
use App\Models\PayoutAttempt;
use App\Services\OrangeMoneyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSplitPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    protected int $paymentOrderId;

    public function __construct(int $paymentOrderId)
    {
        $this->paymentOrderId = $paymentOrderId;
        $this->onQueue('payments');
    }

    // -------------------------------------------------------
    // Main Handle
    // -------------------------------------------------------
    public function handle(OrangeMoneyService $orangeService): void
    {
        Log::info('ProcessSplitPayment started', [
            'payment_order_id' => $this->paymentOrderId,
        ]);

        // Step 1 — Load payment order
        $paymentOrder = PaymentOrder::find($this->paymentOrderId);

        if (!$paymentOrder) {
            Log::error('Payment order not found in split job', [
                'payment_order_id' => $this->paymentOrderId,
            ]);
            return;
        }

        // Step 2 — Safety check: only process paid orders
        if ($paymentOrder->payment_status !== 'paid') {
            Log::warning('Split payment skipped — order not paid', [
                'payment_order_id' => $this->paymentOrderId,
                'status'           => $paymentOrder->payment_status,
            ]);
            return;
        }

        // Step 3 — Load all pending payout transactions for this order
        $pendingTransactions = PaymentTransaction::where('payment_order_id', $this->paymentOrderId)
            ->whereIn('transaction_type', ['provider_payout', 'insurance_payout'])
            ->where('transaction_status', 'pending')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            Log::info('No pending payout transactions found', [
                'payment_order_id' => $this->paymentOrderId,
            ]);
            return;
        }

        // Step 4 — Process each payout transaction
        foreach ($pendingTransactions as $transaction) {
            $this->processSinglePayout($transaction, $orangeService);
        }

        Log::info('ProcessSplitPayment completed', [
            'payment_order_id' => $this->paymentOrderId,
        ]);
    }

    // -------------------------------------------------------
    // Process a Single Payout Transaction
    // -------------------------------------------------------
    protected function processSinglePayout(
        PaymentTransaction $transaction,
        OrangeMoneyService $orangeService
    ): void {
        Log::info('Processing payout', [
            'transaction_reference' => $transaction->transaction_reference,
            'transaction_type'      => $transaction->transaction_type,
            'amount'                => $transaction->amount,
            'recipient_type'        => $transaction->recipient_type,
        ]);

        // Step 1 — Get recipient MSISDN
        $recipientMsisdn = $this->getRecipientMsisdn(
            $transaction->recipient_type,
            $transaction->recipient_id
        );

        if (!$recipientMsisdn) {
            Log::error('Recipient MSISDN not found — skipping payout', [
                'transaction_reference' => $transaction->transaction_reference,
                'recipient_type'        => $transaction->recipient_type,
                'recipient_id'          => $transaction->recipient_id,
            ]);

            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'Recipient Orange MSISDN not configured.',
            ]);

            return;
        }

        // Step 2 — Create payout attempt record
        $attempt = PayoutAttempt::create([
            'attempt_reference'      => 'ATT-' . now()->format('YmdHis') . '-' . $transaction->id,
            'payment_transaction_id' => $transaction->id,
            'payout_type'            => $transaction->transaction_type,
            'recipient_id'           => $transaction->recipient_id,
            'amount'                 => $transaction->amount,
            'currency'               => $transaction->currency ?? 'XAF',
            'gateway_name'           => 'orange_cashin',
            'attempt_number'         => $this->getAttemptNumber($transaction->id),
            'status'                 => 'processing',
            'attempted_at'           => now(),
        ]);

        // Step 3 — Mark transaction as processing
        $transaction->update(['transaction_status' => 'processing']);

        try {
            // Step 4 — Init Cashin
            $payToken = $orangeService->initCashin();

            // Step 5 — Execute Cashin payout
            $cashinResponse = $orangeService->executeCashin(
                payToken:          $payToken,
                subscriberMsisdn:  $recipientMsisdn,
                amount:            (int) $transaction->amount,
                orderId:           substr($transaction->transaction_reference, 0, 20),
                description:       $this->buildPayoutDescription($transaction)
            );

            // Step 6 — Verify Cashin status
            $statusResponse = $orangeService->checkCashinStatus($payToken);
            $confirmedStatus = strtolower($statusResponse['status'] ?? '');

            if (in_array($confirmedStatus, ['successfull', 'success', 'successfull'])) {
                $transaction->update([
                    'transaction_status' => 'success',
                    'gateway_reference'  => $statusResponse['txnid'] ?? $payToken,
                    'processed_at'       => now(),
                ]);

                $attempt->update([
                    'status'            => 'success',
                    'gateway_reference' => $statusResponse['txnid'] ?? $payToken,
                ]);

                Log::info('Payout successful', [
                    'transaction_reference' => $transaction->transaction_reference,
                    'amount'                => $transaction->amount,
                    'recipient_msisdn'      => $recipientMsisdn,
                ]);

            } else {
                $this->markPayoutFailed(
                    $transaction,
                    $attempt,
                    'Cashin status not confirmed: ' . ($statusResponse['status'] ?? 'unknown')
                );
            }

        } catch (\Exception $e) {
            Log::error('Payout failed with exception', [
                'transaction_reference' => $transaction->transaction_reference,
                'error'                 => $e->getMessage(),
            ]);

            $this->markPayoutFailed($transaction, $attempt, $e->getMessage());

            throw $e;
        }
    }

    // -------------------------------------------------------
    // Get Recipient Orange MSISDN
    // -------------------------------------------------------
    protected function getRecipientMsisdn(string $recipientType, ?int $recipientId): ?string
    {
        if ($recipientType === 'provider') {
            $provider = Provider::find($recipientId);
            return $provider?->orange_msisdn;
        }

        if ($recipientType === 'insurance') {
            $insurance = InsuranceCompany::find($recipientId);
            return $insurance?->orange_msisdn;
        }

        return null;
    }

    // -------------------------------------------------------
    // Build Payout Description
    // -------------------------------------------------------
    protected function buildPayoutDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->transaction_type === 'provider_payout') {
            return 'GoBus provider payout - booking #' . $transaction->booking_id;
        }

        return 'GoBus insurance payout - booking #' . $transaction->booking_id;
    }

    // -------------------------------------------------------
    // Get Current Attempt Number
    // -------------------------------------------------------
    protected function getAttemptNumber(int $transactionId): int
    {
        return PayoutAttempt::where('payment_transaction_id', $transactionId)->count() + 1;
    }

    // -------------------------------------------------------
    // Mark Payout as Failed
    // -------------------------------------------------------
    protected function markPayoutFailed(
        PaymentTransaction $transaction,
        PayoutAttempt      $attempt,
        string             $reason
    ): void {
        $transaction->update([
            'transaction_status' => 'failed',
            'failure_reason'     => $reason,
        ]);

        $attempt->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
        ]);

        Log::warning('Payout marked as failed', [
            'transaction_reference' => $transaction->transaction_reference,
            'reason'                => $reason,
        ]);
    }

    // -------------------------------------------------------
    // Handle Job Failure (after all retries exhausted)
    // -------------------------------------------------------
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessSplitPayment job permanently failed', [
            'payment_order_id' => $this->paymentOrderId,
            'error'            => $exception->getMessage(),
        ]);

        PaymentTransaction::where('payment_order_id', $this->paymentOrderId)
            ->whereIn('transaction_status', ['pending', 'processing'])
            ->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'Job failed after max retries: ' . $exception->getMessage(),
            ]);
    }
}