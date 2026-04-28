<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Payment\MtnPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MtnCallbackController extends Controller
{
    public function __construct(
        private MtnPaymentService $paymentService
    ) {}

    /**
     * MTN calls this when the user approves/rejects payment.
     */
    public function collectionX(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();

        Log::info('MTN Collection Webhook received', $payload);

        $referenceId = $payload['externalId']    // sometimes in body
            ?? $request->header('X-Reference-Id') // sometimes in header
            ?? null;

        // MTN sends the X-Reference-Id we generated in the header
        // But the body also contains status and financialTransactionId
        $mtnReferenceId = $request->header('X-Reference-Id');
        $status         = strtoupper($payload['status'] ?? '');

        if (!$mtnReferenceId) {
            Log::warning('MTN Collection Webhook: No X-Reference-Id in headers', $payload);
            return response()->json(['message' => 'Missing reference'], 400);
        }

        if ($status === 'SUCCESSFUL') {
            $this->paymentService->handlePaymentSuccess($mtnReferenceId);
        } elseif ($status === 'FAILED') {
            $this->paymentService->handlePaymentFailed($mtnReferenceId);
        } else {
            Log::info('MTN Collection Webhook: Status still pending', [
                'referenceId' => $mtnReferenceId,
                'status'      => $status,
            ]);
        }

        // Always return 200 to MTN so they don't retry
        return response()->json(['message' => 'OK']);
    }

    /**
     * MTN calls this when a disbursement transfer completes.
     */
    public function disbursement(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload        = $request->all();
        $mtnReferenceId = $request->header('X-Reference-Id');
        $status         = strtoupper($payload['status'] ?? '');

        Log::info('MTN Disbursement Webhook received', [
            'referenceId' => $mtnReferenceId,
            'status'      => $status,
            'payload'     => $payload,
        ]);

        if (!$mtnReferenceId) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        // Find the transaction by gateway_reference
        $transaction = PaymentTransaction::where('gateway_reference', $mtnReferenceId)->first();

        if ($transaction) {
            if ($status === 'SUCCESSFUL') {
                $transaction->update([
                    'transaction_status' => 'success',
                    'processed_at'       => now(),
                ]);
                Log::info('MTN Disbursement: Confirmed successful', ['referenceId' => $mtnReferenceId]);
            } elseif ($status === 'FAILED') {
                $transaction->update([
                    'transaction_status' => 'failed',
                    'failure_reason'     => $payload['reason'] ?? 'MTN disbursement failed',
                ]);
                Log::error('MTN Disbursement: Failed', [
                    'referenceId' => $mtnReferenceId,
                    'reason'      => $payload['reason'] ?? 'unknown',
                ]);
            }
        } else {
            Log::warning('MTN Disbursement Webhook: Transaction not found', ['referenceId' => $mtnReferenceId]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * TEMPORARY DEBUG — logs full MTN webhook payload
     * Remove this method and route after MTN confirms payload structure
     */
    public function collection(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('=== MTN WEBHOOK DEBUG START ===');
        Log::info('MTN Debug - Headers', $request->headers->all());
        Log::info('MTN Debug - Body', $request->all());
        Log::info('MTN Debug - Raw Body', ['raw' => $request->getContent()]);
        Log::info('=== MTN WEBHOOK DEBUG END ===');

        return response()->json(['message' => 'OK', 'received' => true]);
    }
}