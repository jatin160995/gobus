<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtnTokenService
{
    private const BASE_URL   = 'https://proxy.momoapi.mtn.com';
    private const TARGET_ENV = 'mtncameroon';
    private const TOKEN_TTL  = 3300; // Cache token for 55 min (expires at 60 min)

    // ─── Credential helpers (read from settings table, cached 10 min) ────────────

    private function setting(string $key): ?string
    {
        return Cache::remember("setting_{$key}", 600, function () use ($key) {
            return DB::table('settings')
                ->where('key', $key)
                ->where('is_active', 1)
                ->value('value');
        });
    }

    // ─── Public credential accessors ─────────────────────────────────────────────

    public function getCollectionSubscriptionKey(): string
    {
        return $this->setting('mtn_collection_subscription_key') ?? '';
    }

    public function getDisbursementSubscriptionKey(): string
    {
        return $this->setting('mtn_disbursement_subscription_key') ?? '';
    }

    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }

    public function getTargetEnv(): string
    {
        return self::TARGET_ENV;
    }

    // ─── Token getters (cached) ───────────────────────────────────────────────────

    public function getCollectionToken(): ?string
    {
        return Cache::remember('mtn_collection_token', self::TOKEN_TTL, function () {
            return $this->fetchToken('collection');
        });
    }

    public function getDisbursementToken(): ?string
    {
        return Cache::remember('mtn_disbursement_token', self::TOKEN_TTL, function () {
            return $this->fetchToken('disbursement');
        });
    }

    public function clearCollectionToken(): void
    {
        Cache::forget('mtn_collection_token');
    }

    public function clearDisbursementToken(): void
    {
        Cache::forget('mtn_disbursement_token');
    }

    // ─── Internal token fetch ─────────────────────────────────────────────────────

    private function fetchToken(string $product): ?string
    {
        [$userKey, $apiKeyKey, $subKeyKey] = match ($product) {
            'collection'   => [
                'mtn_collection_api_user',
                'mtn_collection_api_key',
                'mtn_collection_subscription_key',
            ],
            'disbursement' => [
                'mtn_disbursement_api_user',
                'mtn_disbursement_api_key',
                'mtn_disbursement_subscription_key',
            ],
            default => [null, null, null],
        };

        if (!$userKey) {
            Log::error("MTN Token: Unknown product [{$product}]");
            return null;
        }

        $apiUser         = $this->setting($userKey);
        $apiKey          = $this->setting($apiKeyKey);
        $subscriptionKey = $this->setting($subKeyKey);

        if (!$apiUser || !$apiKey || !$subscriptionKey) {
            Log::error("MTN Token: Missing credentials in settings table for [{$product}]", [
                'api_user_key'  => $userKey,
                'api_key_key'   => $apiKeyKey,
                'sub_key_key'   => $subKeyKey,
                'api_user'      => $apiUser ? 'SET' : 'MISSING',
                'api_key'       => $apiKey  ? 'SET' : 'MISSING',
                'sub_key'       => $subscriptionKey ? 'SET' : 'MISSING',
            ]);
            return null;
        }

        $credentials = base64_encode("{$apiUser}:{$apiKey}");

        $response = Http::withHeaders([
            'Authorization'             => "Basic {$credentials}",
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->post(self::BASE_URL . "/{$product}/token/");

        if ($response->successful()) {
            $token = $response->json('access_token');
            Log::info("MTN Token: Fetched successfully for [{$product}]");
            return $token;
        }

        Log::error("MTN Token: Fetch FAILED for [{$product}]", [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }
}