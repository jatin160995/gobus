<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnCollectionService
{
    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Send a Request To Pay (USSD prompt) to the user's MTN MoMo number.
     * Returns the X-Reference-Id UUID on success (202), null on failure.
     *
     * Uses raw PHP cURL instead of Guzzle/Laravel Http to have 100% control
     * over headers and avoid WAF rejection from auto-added headers.
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
        $payload = json_encode([
            'amount'       => (string) intval($amount),
            'currency'     => 'XAF',
            'externalId'   => $externalId,
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $msisdn,
            ],
            'payerMessage' => $payerMessage,
            'payeeNote'    => $payeeNote,
        ]);

        $url = $this->tokenService->getBaseUrl() . '/collection/v1_0/requesttopay';

        Log::info('MTN Collection: requestToPay initiating', [
            'referenceId' => $referenceId,
            'externalId'  => $externalId,
            'msisdn'      => $msisdn,
            'amount'      => $amount,
        ]);

        // ── Raw PHP cURL — exact same pattern that worked in debug test3 ──
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $token,
            'X-Reference-Id: ' . $referenceId,
            'X-Target-Environment: ' . $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('MTN Collection: cURL error', [
                'error'       => $curlError,
                'referenceId' => $referenceId,
            ]);
            return null;
        }

        if ($httpCode === 202) {
            Log::info('MTN Collection: requestToPay accepted (202)', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        if ($httpCode === 401) {
            $this->tokenService->clearCollectionToken();
            Log::warning('MTN Collection: Token expired, cleared cache');
        }

        Log::error('MTN Collection: requestToPay FAILED', [
            'httpStatus'  => $httpCode,
            'body'        => substr($responseBody, 0, 500),
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

        $url = $this->tokenService->getBaseUrl() . "/collection/v1_0/requesttopay/{$referenceId}";

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $token,
            'X-Target-Environment: ' . $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
        ];

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('MTN Collection: Status check cURL error', [
                'error'       => $curlError,
                'referenceId' => $referenceId,
            ]);
            return 'FAILED';
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $data   = json_decode($responseBody, true);
            $status = strtoupper($data['status'] ?? 'PENDING');
            Log::info('MTN Collection: Status polled', [
                'referenceId' => $referenceId,
                'status'      => $status,
            ]);
            return $status;
        }

        if ($httpCode === 401) {
            $this->tokenService->clearCollectionToken();
        }

        Log::error('MTN Collection: Status poll FAILED', [
            'referenceId' => $referenceId,
            'httpStatus'  => $httpCode,
            'body'        => substr($responseBody, 0, 500),
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
