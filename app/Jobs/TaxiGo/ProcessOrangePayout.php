<?php

namespace App\Jobs\TaxiGo;

use App\Models\PaymentTransaction;
use App\Models\PayoutAttempt;
use App\Services\OrangeMoneyService;
use App\Services\TaxiGo\RideSplitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends one TaxiGo payout row to an Orange Money number (cashin).
 */
class ProcessOrangePayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private int $transactionId)
    {
        $this->onQueue('payments');
    }

    public function handle(OrangeMoneyService $orange): void
    {
        $transaction = PaymentTransaction::find($this->transactionId);

        if (!$transaction || !in_array($transaction->transaction_status, ['pending', 'failed'])) {
            return;
        }

        $msisdn = RideSplitService::recipientMsisdn($transaction);
        if (!$msisdn) {
            $transaction->update(['transaction_status' => 'failed', 'failure_reason' => 'No Orange Money number configured for this recipient.']);
            return;
        }

        $attempt = PayoutAttempt::create([
            'attempt_reference'      => 'ATT-' . now()->format('YmdHis') . '-' . $transaction->id . '-' . random_int(100, 999),
            'payment_transaction_id' => $transaction->id,
            'payout_type'            => $transaction->transaction_type,
            'recipient_id'           => $transaction->recipient_id,
            'amount'                 => $transaction->amount,
            'currency'               => 'XAF',
            'gateway_name'           => 'orange_cashin',
            'attempt_number'         => PayoutAttempt::where('payment_transaction_id', $transaction->id)->count() + 1,
            'status'                 => 'processing',
            'attempted_at'           => now(),
        ]);

        $transaction->update(['transaction_status' => 'processing']);

        try {
            $payToken = $orange->initCashin();
            $orange->executeCashin(
                payToken:         $payToken,
                subscriberMsisdn: $msisdn,
                amount:           (int) $transaction->amount,
                orderId:          substr($transaction->transaction_reference, 0, 20),
                description:      'TaxiGo payout',
            );
            $status = $orange->checkCashinStatus($payToken);
            $ok = in_array(strtolower($status['status'] ?? ''), ['success', 'successfull', 'successful']);

            $transaction->update($ok
                ? ['transaction_status' => 'success', 'gateway_reference' => $status['txnid'] ?? $payToken, 'processed_at' => now()]
                : ['transaction_status' => 'failed', 'failure_reason' => 'Orange cashin status: ' . ($status['status'] ?? 'unknown')]);
            $attempt->update($ok
                ? ['status' => 'success', 'gateway_reference' => $status['txnid'] ?? $payToken]
                : ['status' => 'failed', 'failure_reason' => 'Orange cashin status: ' . ($status['status'] ?? 'unknown')]);
        } catch (\Throwable $e) {
            Log::error('TaxiGo Orange payout failed', ['transaction' => $transaction->id, 'error' => $e->getMessage()]);
            $transaction->update(['transaction_status' => 'failed', 'failure_reason' => $e->getMessage()]);
            $attempt->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
        }
    }
}
