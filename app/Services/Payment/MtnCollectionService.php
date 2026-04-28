<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Setting;

class MtnCollectionService
{
    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Send a Request To Pay to the user's MTN MoMo number.
     * Returns the X-Reference-Id UUID on success, null on failure.
     */
    public function requestToPay(
        float  $amount,
        string $msisdn,
        string $externalId,
        string $payerMessage = 'GoBus Booking Payment',
        string $payeeNote    = 'GoBus'
    ): ?string {
        $token           = $this->tokenService->getCollectionToken();
        $subscriptionKey = Setting::getValue('mtn_collection_subscription_key');
        $referenceId     = (string) Str::uuid();

        if (!$token || !$subscriptionKey) {
            Log::error('MTN Collection: Missing token or subscription key');
            return null;
        }

        // MTN requires MSISDN in format 2376XXXXXXXX (no + sign)
        $msisdn = $this->formatMsisdn($msisdn);

        $payload = [
            'amount'       => (string) intval($amount), // MTN expects string, no decimals for XAF
            'currency'     => 'XAF',
            'externalId'   => $externalId,
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $msisdn,
            ],
            'payerMessage' => $payerMessage,
            'payeeNote'    => $payeeNote,
        ];

        Log::info('MTN Collection: requestToPay initiating', [
            'referenceId' => $referenceId,
            'externalId'  => $externalId,
            'msisdn'      => $msisdn,
            'amount'      => $amount,
        ]);

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Reference-Id'            => $referenceId,
            'X-Target-Environment'      => 'mtncameroon',
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type'              => 'application/json',
            // Callback URL — MTN will POST result here
            'X-Callback-Url'            => config('app.url') . '/api/mtn/webhook/collection',
        ])->post('https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay', $payload);

        // MTN returns 202 Accepted on success (not 200)
        if ($response->status() === 202) {
            Log::info('MTN Collection: requestToPay accepted', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        // Token may be expired — clear cache and log
        if ($response->status() === 401) {
            $this->tokenService->clearCollectionToken();
        }

        Log::error('MTN Collection: requestToPay failed', [
            'status'      => $response->status(),
            'body'        => $response->body(),
            'referenceId' => $referenceId,
        ]);

        return null;
    }

    /**
     * Check the status of a requesttopay transaction.
     * Returns status string: PENDING | SUCCESSFUL | FAILED
     */
    public function getTransactionStatus(string $referenceId): string
    {
        $token           = $this->tokenService->getCollectionToken();
        $subscriptionKey = Setting::getValue('mtn_collection_subscription_key');

        if (!$token || !$subscriptionKey) {
            return 'FAILED';
        }

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Target-Environment'      => 'mtncameroon',
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->get("https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/{$referenceId}");

        if ($response->successful()) {
            $data   = $response->json();
            $status = strtoupper($data['status'] ?? 'PENDING');

            Log::info('MTN Collection: status check', [
                'referenceId' => $referenceId,
                'status'      => $status,
            ]);

            return $status; // PENDING | SUCCESSFUL | FAILED
        }

        if ($response->status() === 401) {
            $this->tokenService->clearCollectionToken();
        }

        Log::error('MTN Collection: status check failed', [
            'referenceId' => $referenceId,
            'status'      => $response->status(),
            'body'        => $response->body(),
        ]);

        return 'FAILED';
    }

    /**
     * Format MSISDN to MTN format: 2376XXXXXXXX
     */
    private function formatMsisdn(string $msisdn): string
    {
        // Remove all non-digits
        $msisdn = preg_replace('/\D/', '', $msisdn);

        // If starts with 0, replace with 237
        if (str_starts_with($msisdn, '0')) {
            $msisdn = '237' . substr($msisdn, 1);
        }

        // If 9 digits (local format like 676XXXXXX), prepend 237
        if (strlen($msisdn) === 9) {
            $msisdn = '237' . $msisdn;
        }

        return $msisdn;
    }
}