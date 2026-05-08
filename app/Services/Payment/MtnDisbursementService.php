<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnDisbursementService
{
    // Hardcoded callback URL — MTN requires the header but never calls it (confirmed dead)
    private const CALLBACK_URL = 'http://40.66.32.153/go-admin/api/mtn/webhook/disbursement';

    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Transfer money to a recipient's MTN MoMo number.
     * Body uses "payee" (not "payer") — disbursement-specific.
     * Returns the X-Reference-Id on success (202), null on failure.
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
        $payload = [
            'amount'       => (string) intval($amount), // XAF no decimals
            'currency'     => 'XAF',
            'externalId'   => $externalId,
            'payee'        => [  // NOTE: disbursement uses "payee", collection uses "payer"
                'partyIdType' => 'MSISDN',
                'partyId'     => $msisdn,
            ],
            'payerMessage' => $payerMessage,
            'payeeNote'    => $payeeNote,
        ];

        Log::info('MTN Disbursement: Transfer initiating', [
            'referenceId' => $referenceId,
            'externalId'  => $externalId,
            'msisdn'      => $msisdn,
            'amount'      => $amount,
        ]);

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Reference-Id'            => $referenceId,
            'X-Target-Environment'      => $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type'              => 'application/json',
            // Header required by MTN API — callback confirmed non-functional
            'X-Callback-Url'            => self::CALLBACK_URL,
        ])->post($this->tokenService->getBaseUrl() . '/disbursement/v1_0/transfer', $payload);

        if ($response->status() === 202) {
            Log::info('MTN Disbursement: Transfer accepted (202)', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        if ($response->status() === 401) {
            $this->tokenService->clearDisbursementToken();
            Log::warning('MTN Disbursement: Token expired, cleared cache');
        }

        Log::error('MTN Disbursement: Transfer FAILED', [
            'httpStatus'  => $response->status(),
            'body'        => $response->body(),
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

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Target-Environment'      => $this->tokenService->getTargetEnv(),
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->get($this->tokenService->getBaseUrl() . "/disbursement/v1_0/transfer/{$referenceId}");

        if ($response->successful()) {
            $status = strtoupper($response->json('status', 'PENDING'));
            Log::info('MTN Disbursement: Status polled', [
                'referenceId' => $referenceId,
                'status'      => $status,
            ]);
            return $status;
        }

        if ($response->status() === 401) {
            $this->tokenService->clearDisbursementToken();
        }

        Log::error('MTN Disbursement: Status poll FAILED', [
            'referenceId' => $referenceId,
            'httpStatus'  => $response->status(),
            'body'        => $response->body(),
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