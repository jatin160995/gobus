<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Setting;

class MtnDisbursementService
{
    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * Transfer money to a recipient MSISDN.
     * Returns the X-Reference-Id on success, null on failure.
     */
    public function transfer(
        float  $amount,
        string $msisdn,
        string $externalId,
        string $payerMessage = 'GoBus Payout',
        string $payeeNote    = 'GoBus'
    ): ?string {
        $token           = $this->tokenService->getDisbursementToken();
        $subscriptionKey = Setting::getValue('mtn_disbursement_subscription_key');
        $referenceId     = (string) Str::uuid();

        if (!$token || !$subscriptionKey) {
            Log::error('MTN Disbursement: Missing token or subscription key');
            return null;
        }

        $msisdn = $this->formatMsisdn($msisdn);

        $payload = [
            'amount'       => (string) intval($amount),
            'currency'     => 'XAF',
            'externalId'   => $externalId,
            'payee'        => [                      // NOTE: disbursement uses "payee" not "payer"
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
            'X-Target-Environment'      => 'mtncameroon',
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type'              => 'application/json',
            'X-Callback-Url'            => config('app.url') . '/api/mtn/webhook/disbursement',
        ])->post('https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer', $payload);

        if ($response->status() === 202) {
            Log::info('MTN Disbursement: Transfer accepted', ['referenceId' => $referenceId]);
            return $referenceId;
        }

        if ($response->status() === 401) {
            $this->tokenService->clearDisbursementToken();
        }

        Log::error('MTN Disbursement: Transfer failed', [
            'status'      => $response->status(),
            'body'        => $response->body(),
            'referenceId' => $referenceId,
        ]);

        return null;
    }

    /**
     * Check status of a disbursement transfer.
     */
    public function getTransferStatus(string $referenceId): string
    {
        $token           = $this->tokenService->getDisbursementToken();
        $subscriptionKey = Setting::getValue('mtn_disbursement_subscription_key');

        if (!$token || !$subscriptionKey) return 'FAILED';

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Target-Environment'      => 'mtncameroon',
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->get("https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer/{$referenceId}");

        if ($response->successful()) {
            return strtoupper($response->json()['status'] ?? 'PENDING');
        }

        if ($response->status() === 401) {
            $this->tokenService->clearDisbursementToken();
        }

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