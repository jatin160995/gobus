<?php

namespace App\Jobs;

use App\Models\PaymentOrder;
use App\Services\Payment\MtnPaymentService;
use App\Services\Payment\MtnCollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMtnPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // How long to keep polling before auto-failing (seconds)
    private const MAX_POLL_SECONDS = 120;
    // Interval between each poll attempt (seconds)
    private const POLL_INTERVAL    = 5;

    public $tries = 1; // Job itself runs once; re-dispatching handles retries

    public function __construct(
        private string $referenceId,
        private int    $paymentOrderId,
        private int    $attemptNumber = 1
    ) {}

    public function handle(
        MtnCollectionService $collectionService,
        MtnPaymentService    $paymentService
    ): void {
        $order = PaymentOrder::find($this->paymentOrderId);

        // Order gone or already resolved — stop polling
        if (!$order || in_array($order->payment_status, ['paid', 'failed'])) {
            Log::info('MTN Poll: Order already resolved or not found — stopping', [
                'referenceId'    => $this->referenceId,
                'paymentOrderId' => $this->paymentOrderId,
            ]);
            return;
        }

        // Check if we've exceeded the 2-minute timeout
        $elapsedSeconds = $this->attemptNumber * self::POLL_INTERVAL;

        if ($elapsedSeconds > self::MAX_POLL_SECONDS) {
            Log::warning('MTN Poll: Timeout reached — auto-failing payment', [
                'referenceId'    => $this->referenceId,
                'paymentOrderId' => $this->paymentOrderId,
                'elapsedSeconds' => $elapsedSeconds,
            ]);
            $paymentService->handlePaymentFailed($this->referenceId);
            return;
        }

        // Poll MTN for current status
        $mtnStatus = $collectionService->getTransactionStatus($this->referenceId);

        Log::info('MTN Poll: Status check', [
            'referenceId' => $this->referenceId,
            'attempt'     => $this->attemptNumber,
            'elapsed'     => "{$elapsedSeconds}s",
            'mtnStatus'   => $mtnStatus,
        ]);

        if ($mtnStatus === 'SUCCESSFUL') {
            $paymentService->handlePaymentSuccess($this->referenceId);
            Log::info('MTN Poll: Payment SUCCESSFUL — disbursement jobs dispatched', [
                'referenceId' => $this->referenceId,
            ]);
            return;
        }

        if ($mtnStatus === 'FAILED') {
            $paymentService->handlePaymentFailed($this->referenceId);
            Log::info('MTN Poll: Payment FAILED — order marked failed', [
                'referenceId' => $this->referenceId,
            ]);
            return;
        }

        // Still PENDING — dispatch next poll after interval
        self::dispatch(
            $this->referenceId,
            $this->paymentOrderId,
            $this->attemptNumber + 1
        )->delay(now()->addSeconds(self::POLL_INTERVAL));
    }
}