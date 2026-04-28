<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class MtnTokenService
{
    // Fetch bearer token for Collection
    public function getCollectionToken(): ?string
    {
        return Cache::remember('mtn_collection_token', 3300, function () {
            return $this->fetchToken('collection');
        });
    }

    // Fetch bearer token for Disbursement
    public function getDisbursementToken(): ?string
    {
        return Cache::remember('mtn_disbursement_token', 3300, function () {
            return $this->fetchToken('disbursement');
        });
    }

    private function fetchToken(string $product): ?string
    {
        $apiUser        = Setting::getValue("mtn_{$product}_api_user");
        $apiKey         = Setting::getValue("mtn_{$product}_api_key");
        $subscriptionKey = Setting::getValue("mtn_{$product}_subscription_key");

        if (!$apiUser || !$apiKey || !$subscriptionKey) {
            Log::error("MTN MoMo: Missing credentials for {$product}");
            return null;
        }

        $credentials = base64_encode("{$apiUser}:{$apiKey}");

        $response = Http::withHeaders([
            'Authorization'          => "Basic {$credentials}",
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ])->post("https://proxy.momoapi.mtn.com/{$product}/token/");

        if ($response->successful()) {
            $data = $response->json();
            Log::info("MTN MoMo: Token fetched for {$product}");
            return $data['access_token'] ?? null;
        }

        Log::error("MTN MoMo: Token fetch failed for {$product}", [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    public function clearCollectionToken(): void
    {
        Cache::forget('mtn_collection_token');
    }

    public function clearDisbursementToken(): void
    {
        Cache::forget('mtn_disbursement_token');
    }
}