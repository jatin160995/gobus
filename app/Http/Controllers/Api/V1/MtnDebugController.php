<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payment\MtnTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * TEMPORARY DEBUG CONTROLLER — DELETE AFTER FIXING MTN PAYMENT
 *
 * This controller compares what Guzzle sends vs what raw cURL sends
 * to identify which automatic header is triggering MTN's WAF rejection.
 */
class MtnDebugController extends Controller
{
    public function __construct(
        private MtnTokenService $tokenService
    ) {}

    /**
     * GET /api/debug/mtn-headers
     *
     * Tests the requestToPay call using THREE methods and returns
     * the exact headers each one would send, plus the MTN response.
     */
    public function compareHeaders(Request $request)
    {
        $token           = $this->tokenService->getCollectionToken();
        $subscriptionKey = $this->tokenService->getCollectionSubscriptionKey();

        if (!$token) {
            return response()->json(['error' => 'Cannot get MTN token']);
        }

        $referenceId = (string) \Illuminate\Support\Str::uuid();
        $baseUrl     = $this->tokenService->getBaseUrl();
        $targetEnv   = $this->tokenService->getTargetEnv();
        $url         = $baseUrl . '/collection/v1_0/requesttopay';

        $payload = [
            'amount'       => '1',
            'currency'     => 'XAF',
            'externalId'   => 'GOBUS-TEST-' . time(),
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => '237675096801',
            ],
            'payerMessage' => 'GoBus Test',
            'payeeNote'    => 'Test',
        ];

        $results = [];

        // ── TEST 1: Laravel's Http::withHeaders()->send() (current approach) ──
        $results['test1_guzzle_send'] = $this->testGuzzleSend($token, $subscriptionKey, $referenceId, $url, $targetEnv, $payload);

        // ── TEST 2: Laravel's Http::withHeaders()->post() (original approach) ──
        $referenceId2 = (string) \Illuminate\Support\Str::uuid();
        $results['test2_guzzle_post'] = $this->testGuzzlePost($token, $subscriptionKey, $referenceId2, $url, $targetEnv, $payload);

        // ── TEST 3: Raw PHP cURL (bypasses Guzzle entirely) ──
        $referenceId3 = (string) \Illuminate\Support\Str::uuid();
        $results['test3_raw_curl'] = $this->testRawCurl($token, $subscriptionKey, $referenceId3, $url, $targetEnv, $payload);

        // ── TEST 4: Raw PHP cURL with ONLY the same headers as token request ──
        $referenceId4 = (string) \Illuminate\Support\Str::uuid();
        $results['test4_curl_minimal'] = $this->testRawCurlMinimal($token, $subscriptionKey, $referenceId4, $url, $targetEnv, $payload);

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * TEST 1: Current approach — Http::withHeaders()->send()
     */
    private function testGuzzleSend($token, $subKey, $refId, $url, $env, $payload): array
    {
        try {
            // Capture what Guzzle would send
            $debugHeaders = [];

            $response = Http::withHeaders([
                'Authorization'             => "Bearer {$token}",
                'X-Reference-Id'            => $refId,
                'X-Target-Environment'      => $env,
                'Ocp-Apim-Subscription-Key' => $subKey,
                'Content-Type'              => 'application/json',
            ])->withOptions([
                'debug' => true,
            ])->send('POST', $url, [
                'body' => json_encode($payload),
            ]);

            return [
                'method'       => 'Guzzle send()',
                'http_status'  => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
                'is_waf_block' => str_contains($response->body(), 'Request Rejected'),
            ];
        } catch (\Throwable $e) {
            return ['method' => 'Guzzle send()', 'error' => $e->getMessage()];
        }
    }

    /**
     * TEST 2: Original approach — Http::withHeaders()->post()
     */
    private function testGuzzlePost($token, $subKey, $refId, $url, $env, $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization'             => "Bearer {$token}",
                'X-Reference-Id'            => $refId,
                'X-Target-Environment'      => $env,
                'Ocp-Apim-Subscription-Key' => $subKey,
                'Content-Type'              => 'application/json',
            ])->post($url, $payload);

            return [
                'method'       => 'Guzzle post()',
                'http_status'  => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
                'is_waf_block' => str_contains($response->body(), 'Request Rejected'),
            ];
        } catch (\Throwable $e) {
            return ['method' => 'Guzzle post()', 'error' => $e->getMessage()];
        }
    }

    /**
     * TEST 3: Raw PHP cURL — complete control over headers
     */
    private function testRawCurl($token, $subKey, $refId, $url, $env, $payload): array
    {
        try {
            $ch = curl_init($url);
            $body = json_encode($payload);

            $headers = [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $refId,
                'X-Target-Environment: ' . $env,
                'Ocp-Apim-Subscription-Key: ' . $subKey,
                'Content-Type: application/json',
            ];

            // Capture request headers via CURLINFO_HEADER_OUT
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HEADER         => false,
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $curlError    = curl_error($ch);
            curl_close($ch);

            return [
                'method'          => 'Raw cURL',
                'http_status'     => $httpCode,
                'body_preview'    => substr($responseBody, 0, 500),
                'is_waf_block'    => str_contains($responseBody, 'Request Rejected'),
                'request_headers' => $requestHeaders,
                'curl_error'      => $curlError ?: null,
            ];
        } catch (\Throwable $e) {
            return ['method' => 'Raw cURL', 'error' => $e->getMessage()];
        }
    }

    /**
     * TEST 4: Raw PHP cURL with MINIMAL headers (matching token request pattern)
     * Only sends headers that the working token request also sends,
     * plus the required X-Reference-Id and X-Target-Environment.
     * No Accept header. No extra Guzzle defaults.
     */
    private function testRawCurlMinimal($token, $subKey, $refId, $url, $env, $payload): array
    {
        try {
            $ch = curl_init($url);
            $body = json_encode($payload);

            // Absolute minimum headers — nothing that Guzzle would auto-add
            $headers = [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $refId,
                'X-Target-Environment: ' . $env,
                'Ocp-Apim-Subscription-Key: ' . $subKey,
                'Content-Type: application/json',
                // NOTE: Intentionally NO Accept header
                // NOTE: Intentionally NO User-Agent header
            ];

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HEADER         => false,
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_SSL_VERIFYPEER => true,
                // Suppress automatic User-Agent
                CURLOPT_USERAGENT      => '',
            ]);

            $responseBody = curl_exec($ch);
            $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $curlError    = curl_error($ch);
            curl_close($ch);

            return [
                'method'          => 'Raw cURL (minimal, no Accept/UA)',
                'http_status'     => $httpCode,
                'body_preview'    => substr($responseBody, 0, 500),
                'is_waf_block'    => str_contains($responseBody, 'Request Rejected'),
                'request_headers' => $requestHeaders,
                'curl_error'      => $curlError ?: null,
            ];
        } catch (\Throwable $e) {
            return ['method' => 'Raw cURL (minimal)', 'error' => $e->getMessage()];
        }
    }
}
