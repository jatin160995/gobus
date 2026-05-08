<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnCollectionService
{
    // Hardcoded callback URL — MTN requires the header but never calls it (confirmed dead)
    private const CALLBACK_URL = 'http://40.66.32.153/go-admin/api/mtn/webhook/collection';

    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Send a Request To Pay (USSD prompt) to the user's MTN MoMo number.
     * Returns the X-Reference-Id UUID on success (202), null on failure.
     */
    public function requestToPay(
        float  $amount,
        string $msisdn,
        string $externalId,
        string $payerMessage = 'GoBus Booking Payment',
        string $payeeNote    = 'GoBus'
    ): ?string {
        $token           = $this->tokenService->getCollectionToken();
        $subscriptionKey = $this->tokenService->getCollectionSubscriptionKey();
        $referenceId     = (string) Str::uuid();

        if (!$token) {
            Log::error('MTN Collection: Failed to get token');
            return null;
        }

        $msisdn  = $this->formatMsisdn($msisdn);
        $payload = [
            'amount'       => (string) intval($amount), // XAF has no decimals
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

        // ---- ADD THIS DEBUG BLOCK ----
        Log::info('MTN Request Debug', [
            'url'          => $this->tokenService->getBaseUrl() . '/collection/v1_0/requesttopay',
            'environment'  => $this->tokenService->getTargetEnv(),
            'sub_key_hint' => substr($subscriptionKey, 0, 8) . '...',
            'token_hint'   => substr($token, 0, 10) . '...',
            //'callback_url' => self::CALLBACK_URL,
            'outbound_ip'  => @file_get_contents('https://api.ipify.org'),
            'amount_sent' => (string) intval($amount),
            'payload'        => json_encode($payload),   // ← add this
            'token_length'   => strlen($token),  
            'laravel_version' => app()->version(),
            'guzzle_headers'  => 'check Accept header',
        ]);
        // ---- END DEBUG BLOCK ----

        // $response = Http::withHeaders([
        //     'Authorization'             => "Bearer {$token}",
        //     'X-Reference-Id'            => $referenceId,
        //     'X-Target-Environment'      => $this->tokenService->getTargetEnv(),
        //     'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        //     'Content-Type'              => 'application/json',
        //     // Header required by MTN API — callback is confirmed non-functional
        //     'X-Callback-Url'            => self::CALLBACK_URL,
        //     'Accept'                    => '*/*', 
        // ])->post($this->tokenService->getBaseUrl() . '/collection/v1_0/requesttopay', $payload);
        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Reference-Id'            => $referenceId,
            'X-Target-Environment'      => $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type'              => 'application/json',
        ])->withOptions([
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'X-Reference-Id: ' . $referenceId,
                    'X-Target-Environment: ' . $this->tokenService->getTargetEnv(),
                    'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
                    'Content-Type: application/json',
                    // Explicitly NO Accept header
                ],
            ],
        ])->send('POST', $this->tokenService->getBaseUrl() . '/collection/v1_0/requesttopay', [
            'body' => json_encode($payload),
        ]);

        if ($response->status() === 202) {
            Log::info('MTN Collection: requestToPay accepted (202)', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        if ($response->status() === 401) {
            $this->tokenService->clearCollectionToken();
            Log::warning('MTN Collection: Token expired, cleared cache');
        }

        Log::error('MTN Collection: requestToPay FAILED', [
            'httpStatus'  => $response->status(),
            'body'        => $response->body(),
            'referenceId' => $referenceId,
        ]);

        return null;
    }

    /**
     * Poll status of a requesttopay transaction.
     * Returns: 'PENDING' | 'SUCCESSFUL' | 'FAILED'
     */
    public function getTransactionStatus(string $referenceId): string
    {
        $token           = $this->tokenService->getCollectionToken();
        $subscriptionKey = $this->tokenService->getCollectionSubscriptionKey();

        if (!$token) {
            Log::error('MTN Collection: No token for status check', ['referenceId' => $referenceId]);
            return 'FAILED';
        }

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Target-Environment'      => $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->get($this->tokenService->getBaseUrl() . "/collection/v1_0/requesttopay/{$referenceId}");

        if ($response->successful()) {
            $status = strtoupper($response->json('status', 'PENDING'));
            Log::info('MTN Collection: Status polled', [
                'referenceId' => $referenceId,
                'status'      => $status,
            ]);
            return $status; // PENDING | SUCCESSFUL | FAILED
        }

        if ($response->status() === 401) {
            $this->tokenService->clearCollectionToken();
        }

        Log::error('MTN Collection: Status poll FAILED', [
            'referenceId' => $referenceId,
            'httpStatus'  => $response->status(),
            'body'        => $response->body(),
        ]);

        return 'FAILED';
    }

    /**
     * Normalize MSISDN to MTN Cameroon format: 237XXXXXXXXX
     */
    private function formatMsisdn(string $msisdn): string
    {
        $msisdn = preg_replace('/\D/', '', $msisdn);

        if (str_starts_with($msisdn, '0')) {
            $msisdn = '237' . substr($msisdn, 1);
        }

        if (strlen($msisdn) === 9) {
            $msisdn = '237' . $msisdn;
        }

        return $msisdn;
    }
}