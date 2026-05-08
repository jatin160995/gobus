<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MTN Cameroon has confirmed their webhook/callback system does NOT work.
 * These routes are kept alive as passive loggers only — in case MTN sends
 * anything in future. All payment resolution is done via polling jobs.
 */
class MtnCallbackController extends Controller
{
    /**
     * POST /api/mtn/webhook/collection
     * MTN confirmed: this will never be called. Kept as passive logger.
     */
    public function collection(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('MTN Collection Webhook received (unexpected — webhook is dead)', [
            'headers' => $request->headers->all(),
            'body'    => $request->all(),
            'raw'     => $request->getContent(),
        ]);

        // Always return 200 so MTN doesn't retry (if they ever do call it)
        return response()->json(['message' => 'OK']);
    }

    /**
     * POST /api/mtn/webhook/disbursement
     * Passive logger — disbursement status is handled by CheckMtnDisbursementStatus job.
     */
    public function disbursement(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload        = $request->all();
        $mtnReferenceId = $request->header('X-Reference-Id');
        $status         = strtoupper($payload['status'] ?? '');

        Log::info('MTN Disbursement Webhook received (passive log only)', [
            'referenceId' => $mtnReferenceId,
            'status'      => $status,
            'payload'     => $payload,
        ]);

        // Opportunistically update if MTN does send something
        if ($mtnReferenceId && in_array($status, ['SUCCESSFUL', 'FAILED'])) {
            $transaction = PaymentTransaction::where('gateway_reference', $mtnReferenceId)->first();
            if ($transaction && $transaction->transaction_status !== 'success') {
                $transaction->update([
                    'transaction_status' => $status === 'SUCCESSFUL' ? 'success' : 'failed',
                    'processed_at'       => $status === 'SUCCESSFUL' ? now() : null,
                    'failure_reason'     => $status === 'FAILED'
                        ? ($payload['reason'] ?? 'MTN disbursement webhook: FAILED')
                        : null,
                ]);
                Log::info('MTN Disbursement Webhook: Opportunistically updated transaction', [
                    'referenceId' => $mtnReferenceId,
                    'status'      => $status,
                ]);
            }
        }

        return response()->json(['message' => 'OK']);
    }
}