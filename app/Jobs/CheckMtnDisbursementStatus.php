<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Services\Payment\MtnDisbursementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMtnDisbursementStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const POLL_INTERVAL     = 5;   // seconds between polls
    private const MAX_POLL_ATTEMPTS = 24;  // 24 × 5s = 120s max

    public $tries = 1;

    public function __construct(
        private string $referenceId,
        private int    $transactionId,
        private int    $attemptNumber = 1
    ) {}

    public function handle(MtnDisbursementService $disbursementService): void
    {
        $transaction = PaymentTransaction::find($this->transactionId);

        if (!$transaction) {
            Log::error('MTN Disbursement Poll: Transaction not found', ['id' => $this->transactionId]);
            return;
        }

        if ($transaction->transaction_status === 'success') {
            return; // Already resolved
        }

        // Timeout guard
        if ($this->attemptNumber > self::MAX_POLL_ATTEMPTS) {
            Log::warning('MTN Disbursement Poll: Timeout — marking failed', [
                'referenceId'   => $this->referenceId,
                'transactionId' => $this->transactionId,
            ]);
            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'Disbursement polling timeout after 120s',
            ]);
            return;
        }

        $status = $disbursementService->getTransferStatus($this->referenceId);

        Log::info('MTN Disbursement Poll: Status check', [
            'referenceId'   => $this->referenceId,
            'transactionId' => $this->transactionId,
            'attempt'       => $this->attemptNumber,
            'status'        => $status,
        ]);

        if ($status === 'SUCCESSFUL') {
            $transaction->update([
                'transaction_status' => 'success',
                'processed_at'       => now(),
            ]);
            Log::info('MTN Disbursement Poll: SUCCESSFUL', [
                'referenceId'   => $this->referenceId,
                'transactionId' => $this->transactionId,
                'type'          => $transaction->transaction_type,
            ]);
            return;
        }

        if ($status === 'FAILED') {
            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason'     => 'MTN disbursement returned FAILED status',
            ]);
            Log::error('MTN Disbursement Poll: FAILED', [
                'referenceId'   => $this->referenceId,
                'transactionId' => $this->transactionId,
            ]);
            return;
        }

        // Still PENDING — re-dispatch
        self::dispatch(
            $this->referenceId,
            $this->transactionId,
            $this->attemptNumber + 1
        )->delay(now()->addSeconds(self::POLL_INTERVAL));
    }
}