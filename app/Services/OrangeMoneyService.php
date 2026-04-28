<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrangeMoneyService
{
    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $merchantMsisdn;
    protected string $channelUserMsisdn;
    protected string $webhookUrl;
    protected string $xAuthToken;
    protected string $pin;

    public function __construct()
    {
        $this->baseUrl           = rtrim(config('services.orange.base_url'), '/');
        $this->tokenUrl          = config('services.orange.token_url');
        $this->consumerKey       = config('services.orange.consumer_key');
        $this->consumerSecret    = config('services.orange.consumer_secret');
        $this->merchantMsisdn    = config('services.orange.merchant_msisdn');
        $this->channelUserMsisdn = config('services.orange.channel_user_msisdn');
        $this->webhookUrl        = config('services.orange.webhook_url');
        $this->xAuthToken        = config('services.orange.x_auth_token');
        $this->pin               = config('services.orange.pin', '2222');
    }

    // -------------------------------------------------------
    // Common headers for all API calls
    // -------------------------------------------------------
    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'X-AUTH-TOKEN'  => $this->xAuthToken,
            'Content-Type'  => 'application/json',
        ];
    }

    // -------------------------------------------------------
    // 1. Generate Access Token (cached for 55 minutes)
    // -------------------------------------------------------
    public function getAccessToken(): string
    {
        return Cache::remember('orange_access_token', 3300, function () {
            $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ])->asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                Log::error('Orange token generation failed', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to generate Orange access token.');
            }

            return $response->json('access_token');
        });
    }

    // -------------------------------------------------------
    // 2. Initiate Merchant Payment (MP Init)
    // -------------------------------------------------------
    public function initMerchantPayment(): string
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/mp/init");

        Log::info('Orange MP Init response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (!$response->successful()) {
            Log::error('Orange MP Init failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to initiate Orange merchant payment.');
        }

        $payToken = $response->json('data.payToken');

        if (!$payToken) {
            throw new \Exception('No payToken returned from Orange MP Init.');
        }

        return $payToken;
    }

    // -------------------------------------------------------
    // 3. Execute Merchant Payment (MP Pay)
    // -------------------------------------------------------
    public function executeMerchantPayment(
        string $payToken,
        string $subscriberMsisdn,
        int    $amount,
        string $orderId,
        string $description
    ): array {
        $payload = [
            'payToken'          => $payToken,
            'subscriberMsisdn'  => $subscriberMsisdn,
            'channelUserMsisdn' => $this->channelUserMsisdn,
            'amount'            => $amount,
            'orderId'           => $orderId,
            'description'       => $description,
            'notifUrl'          => $this->webhookUrl,
            'pin'               => $this->pin,
        ];

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/mp/pay", $payload);

        Log::info('Orange MP Pay response', [
            'order_id' => $orderId,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Orange MP Pay request failed: ' . $response->body());
        }

        return $response->json('data') ?? [];
    }

    // -------------------------------------------------------
    // 4. Push Payment Prompt to User
    // -------------------------------------------------------
    public function pushPaymentPrompt(string $payToken): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/mp/push/{$payToken}");

        Log::info('Orange MP Push response', [
            'payToken' => $payToken,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        return $response->json('data') ?? [];
    }

    // -------------------------------------------------------
    // 5. Check Merchant Payment Status
    // -------------------------------------------------------
    public function checkPaymentStatus(string $payToken): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/mp/paymentstatus/{$payToken}");

        Log::info('Orange MP Status response', [
            'payToken' => $payToken,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        if (!$response->successful()) {
            Log::error('Orange payment status check failed', [
                'payToken' => $payToken,
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to check Orange payment status.');
        }

        return $response->json('data') ?? [];
    }

    // -------------------------------------------------------
    // 6. Cashin Init (for provider/insurance payouts)
    // -------------------------------------------------------
    public function initCashin(): string
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/cashin/init");

        Log::info('Orange Cashin Init response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (!$response->successful()) {
            Log::error('Orange Cashin Init failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to initiate Orange Cashin.');
        }

        $payToken = $response->json('data.payToken');

        if (!$payToken) {
            throw new \Exception('No payToken returned from Orange Cashin Init.');
        }

        return $payToken;
    }

    // -------------------------------------------------------
    // 7. Execute Cashin (Payout to Provider/Insurance)
    // -------------------------------------------------------
    public function executeCashin(
        string $payToken,
        string $subscriberMsisdn,
        int    $amount,
        string $orderId,
        string $description
    ): array {
        $payload = [
            'payToken'          => $payToken,
            'channelUserMsisdn' => $this->channelUserMsisdn,
            'subscriberMsisdn'  => $subscriberMsisdn,
            'amount'            => $amount,
            'orderId'           => $orderId,
            'description'       => $description,
            'notifUrl'          => $this->webhookUrl,
            'pin'               => $this->pin,
        ];

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/cashin/pay", $payload);

        Log::info('Orange Cashin Pay response', [
            'order_id'          => $orderId,
            'to_msisdn'         => $subscriberMsisdn,
            'amount'            => $amount,
            'status'            => $response->status(),
            'body'              => $response->json(),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Orange Cashin Pay request failed: ' . $response->body());
        }

        return $response->json('data') ?? [];
    }

    // -------------------------------------------------------
    // 8. Check Cashin Status
    // -------------------------------------------------------
    public function checkCashinStatus(string $payToken): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/cashin/paymentstatus/{$payToken}");

        Log::info('Orange Cashin Status response', [
            'payToken' => $payToken,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        return $response->json('data') ?? [];
    }
}