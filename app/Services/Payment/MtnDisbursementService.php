<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnDisbursementService
{
    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Transfer money to a recipient's MTN MoMo number.
     * Body uses "payee" (not "payer") — disbursement-specific.
     * Returns the X-Reference-Id on success (202), null on failure.
     *
     * Uses raw PHP cURL instead of Guzzle/Laravel Http to have 100% control
     * over headers and avoid WAF rejection from auto-added headers.
     */
    public function transfer(
        float  $amount,
        string $msisdn,
        string $externalId,
        string $payerMessage = 'GoBus Payout',
        string $payeeNote    = 'GoBus'
    ): ?string {
        $token           = $this->tokenService->getDisbursementToken();
        $subscriptionKey = $this->tokenService->getDisbursementSubscriptionKey();
        $referenceId     = (string) Str::uuid();

        if (!$token) {
            Log::error('MTN Disbursement: Failed to get token');
            return null;
        }

        $msisdn  = $this->formatMsisdn($msisdn);
        $payload = json_encode([
            'amount'       => (string) intval($amount),
            'currency'     => 'XAF',
            'externalId'   => $externalId,
            'payee'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $msisdn,
            ],
            'payerMessage' => $payerMessage,
            'payeeNote'    => $payeeNote,
        ]);

        $url = $this->tokenService->getBaseUrl() . '/disbursement/v1_0/transfer';

        Log::info('MTN Disbursement: Transfer initiating', [
            'referenceId' => $referenceId,
            'externalId'  => $externalId,
            'msisdn'      => $msisdn,
            'amount'      => $amount,
        ]);

        // ── Raw PHP cURL — same proven pattern as MtnCollectionService ──
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
            Log::error('MTN Disbursement: cURL error', [
                'error'       => $curlError,
                'referenceId' => $referenceId,
            ]);
            return null;
        }

        if ($httpCode === 202) {
            Log::info('MTN Disbursement: Transfer accepted (202)', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        if ($httpCode === 401) {
            $this->tokenService->clearDisbursementToken();
            Log::warning('MTN Disbursement: Token expired, cleared cache');
        }

        Log::error('MTN Disbursement: Transfer FAILED', [
            'httpStatus'  => $httpCode,
            'body'        => substr($responseBody, 0, 500),
            'referenceId' => $referenceId,
        ]);

        return null;
    }

    /**
     * Poll status of a disbursement transfer.
     * Returns: 'PENDING' | 'SUCCESSFUL' | 'FAILED'
     */
    public function getTransferStatus(string $referenceId): string
    {
        $token           = $this->tokenService->getDisbursementToken();
        $subscriptionKey = $this->tokenService->getDisbursementSubscriptionKey();

        if (!$token) {
            return 'FAILED';
        }

        $url = $this->tokenService->getBaseUrl() . "/disbursement/v1_0/transfer/{$referenceId}";

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
            Log::error('MTN Disbursement: Status check cURL error', [
                'error'       => $curlError,
                'referenceId' => $referenceId,
            ]);
            return 'FAILED';
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $data   = json_decode($responseBody, true);
            $status = strtoupper($data['status'] ?? 'PENDING');
            Log::info('MTN Disbursement: Status polled', [
                'referenceId' => $referenceId,
                'status'      => $status,
            ]);
            return $status;
        }

        if ($httpCode === 401) {
            $this->tokenService->clearDisbursementToken();
        }

        Log::error('MTN Disbursement: Status poll FAILED', [
            'referenceId' => $referenceId,
            'httpStatus'  => $httpCode,
            'body'        => substr($responseBody, 0, 500),
        ]);

        return 'FAILED';
    }

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
