<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Models\Provider;
use App\Services\Payment\MtnDisbursementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessMtnDisbursement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_RETRIES      = 3;
    private const RETRY_DELAY_SECS = 30;
    // Poll disbursement status for up to 2 minutes
    private const MAX_POLL_ATTEMPTS = 24;  // 24 × 5s = 120s
    private const POLL_INTERVAL     = 5;

    public $tries = 1;

    public function __construct(
        private int $transactionId,
        private int $retryCount = 0
    ) {}

    public function handle(MtnDisbursementService $disbursementService): void
    {
        $transaction = PaymentTransaction::find($this->transactionId);

        if (!$transaction) {
            Log::error('MTN Disbursement Job: Transaction not found', ['id' => $this->transactionId]);
            return;
        }

        // Already processed
        if ($transaction->transaction_status === 'success') {
            Log::info('MTN Disbursement Job: Already succeeded — skipping', ['id' => $this->transactionId]);
            return;
        }

        // Resolve the recipient's MTN MoMo number
        $msisdn = $this->resolveRecipientMsisdn($transaction);

        if (!$msisdn) {
            Log::error('MTN Disbursement Job: No MoMo number for recipient', [
                'transactionId'  => $this->transactionId,
                'recipientType'  => $transaction->recipient_type,
                'recipientId'    => $transaction->recipient_id,
            ]);
            $this->markFailed($transaction, 'No MoMo number configured for recipient');
            return;
        }

        $externalId = 'GOBUS-DISB-' . $transaction->id . '-' . time();

        // Build human-readable messages based on recipient type
        [$payerMessage, $payeeNote] = match ($transaction->transaction_type) {
            'insurance_payout' => [
                'GoBus Insurance Payout',
                'Insurance for Booking #' . $transaction->booking_id,
            ],
            'provider_payout' => [
                'GoBus Agency Payout',
                'Agency payout for Booking #' . $transaction->booking_id,
            ],
            default => ['GoBus Payout', 'Booking #' . $transaction->booking_id],
        };

        // Initiate the MTN transfer
        $referenceId = $disbursementService->transfer(
            amount:       (float) $transaction->amount,
            msisdn:       $msisdn,
            externalId:   $externalId,
            payerMessage: $payerMessage,
            payeeNote:    $payeeNote,
        );

        if (!$referenceId) {
            $this->handleTransferInitFailure($transaction);
            return;
        }

        // Save the MTN reference ID on the transaction
        $transaction->update([
            'gateway_reference'  => $referenceId,
            'transaction_status' => 'processing',
        ]);

        Log::info('MTN Disbursement Job: Transfer initiated', [
            'transactionId' => $this->transactionId,
            'referenceId'   => $referenceId,
            'amount'        => $transaction->amount,
            'msisdn'        => $msisdn,
        ]);

        // Start polling for disbursement status
        CheckMtnDisbursementStatus::dispatch($referenceId, $this->transactionId)
            ->delay(now()->addSeconds(self::POLL_INTERVAL));
    }

    /**
     * Resolve the recipient's MTN MoMo number from the DB.
     */
    private function resolveRecipientMsisdn(PaymentTransaction $transaction): ?string
    {
        return match ($transaction->recipient_type) {
            'provider'  => $this->getProviderMsisdn($transaction->recipient_id),
            'insurance' => $this->getInsuranceMsisdn(),   // no ID needed — single global setting
            default     => null,
        };
    }

    private function getProviderMsisdn(int $providerId): ?string
    {
        return DB::table('providers')
            ->where('id', $providerId)
            ->where('status', 'active')
            ->value('mtn_msisdn');
    }
    private function getInsuranceMsisdn(): ?string
    {
        return DB::table('settings')
            ->where('key', 'insurance_mtn_msisdn')
            ->where('is_active', 1)
            ->value('value');
    }

    private function handleTransferInitFailure(PaymentTransaction $transaction): void
    {
        if ($this->retryCount < self::MAX_RETRIES) {
            Log::warning('MTN Disbursement Job: Transfer init failed — scheduling retry', [
                'transactionId' => $this->transactionId,
                'retryCount'    => $this->retryCount + 1,
            ]);

            // Log to payout_attempts if model exists
            $this->logPayoutAttempt($transaction, 'failed', 'MTN transfer initiation failed');

            self::dispatch($this->transactionId, $this->retryCount + 1)
                ->delay(now()->addSeconds(self::RETRY_DELAY_SECS));
        } else {
            Log::error('MTN Disbursement Job: Max retries reached — marking failed', [
                'transactionId' => $this->transactionId,
            ]);
            $this->markFailed($transaction, 'Max retries reached after MTN transfer init failures');
        }
    }

    private function markFailed(PaymentTransaction $transaction, string $reason): void
    {
        $transaction->update([
            'transaction_status' => 'failed',
            'failure_reason'     => $reason,
        ]);
        $this->logPayoutAttempt($transaction, 'failed', $reason);
    }

    private function logPayoutAttempt(PaymentTransaction $transaction, string $status, string $reason): void
    {
        try {
            \App\Models\PayoutAttempt::create([
                'payment_transaction_id' => $transaction->id,
                'payment_order_id'       => $transaction->payment_order_id,
                'attempt_number'         => $this->retryCount + 1,
                'status'                 => $status,
                'failure_reason'         => $reason,
                'attempted_at'           => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('MTN Disbursement: Could not log payout_attempt', ['error' => $e->getMessage()]);
        }
    }
}