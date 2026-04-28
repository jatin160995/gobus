<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Models\Provider;
use App\Models\Setting;
use App\Services\Payment\MtnDisbursementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMtnDisbursement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        private int $transactionId
    ) {}

    public function handle(MtnDisbursementService $disbursementService): void
    {
        $transaction = PaymentTransaction::findOrFail($this->transactionId);

        if ($transaction->transaction_status === 'success') {
            return; // Already processed, skip
        }

        $transaction->update(['transaction_status' => 'processing']);

        // Resolve the MSISDN to pay
        $msisdn = $this->resolveMsisdn($transaction);

        if (!$msisdn) {
            Log::error('MTN Disbursement: No MSISDN found', [
                'transaction_id' => $this->transactionId,
                'type'           => $transaction->transaction_type,
                'recipient_id'   => $transaction->recipient_id,
            ]);
            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'No MTN MSISDN configured for recipient',
            ]);
            return;
        }

        $referenceId = $disbursementService->transfer(
            amount:       (float) $transaction->amount,
            msisdn:       $msisdn,
            externalId:   $transaction->transaction_reference,
            payerMessage: 'GoBus Payout - ' . $transaction->transaction_reference,
            payeeNote:    'GoBus ' . ucfirst(str_replace('_', ' ', $transaction->transaction_type)),
        );

        if ($referenceId) {
            $transaction->update([
                'transaction_status' => 'processing',
                'gateway_reference'  => $referenceId,
                'processed_at'       => now(),
            ]);
            Log::info('MTN Disbursement: Transfer initiated', [
                'transaction_id' => $this->transactionId,
                'referenceId'    => $referenceId,
            ]);
        } else {
            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'MTN disbursement API call failed',
            ]);
            Log::error('MTN Disbursement: Transfer failed', ['transaction_id' => $this->transactionId]);

            // Will retry automatically based on $tries and $backoff
            $this->fail('MTN disbursement transfer failed');
        }
    }

    private function resolveMsisdn(PaymentTransaction $transaction): ?string
    {
        if ($transaction->transaction_type === 'provider_payout') {
            $provider = Provider::find($transaction->recipient_id);
            return $provider?->mtn_msisdn;
        }

        if ($transaction->transaction_type === 'insurance_payout') {
            // Store insurance MTN MSISDN in settings
            return Setting::getValue('insurance_mtn_msisdn');
        }

        return null;
    }
}