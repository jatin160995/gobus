<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
/**
 * TEMPORARY — DELETE AFTER FIXING
 *
 * Completely self-contained MTN payment test.
 * Does NOT use any service class — everything hardcoded with raw cURL.
 * This bypasses OPcache on existing files.
 */
class MtnHardcodedDebugController extends Controller
{
    // ── Hardcoded credentials from your Postman collection ──
    private const BASE_URL         = 'https://proxy.momoapi.mtn.com';
    private const API_USER         = '984a6322-0c18-49e3-9807-f4dd13d5a5ae';
    private const API_KEY          = '0395389f261a49ecbee4f337e6b2189a';
    private const SUBSCRIPTION_KEY = '0e061949a8ce40048a564c3302cde50a';
    private const TARGET_ENV       = 'mtncameroon';
    private const CALLBACK_URL     = 'http://40.66.32.153/go-admin/api/mtn/webhook/collection';

    /**
     * GET /api/debug/mtn-hardcoded
     *
     * Step 1: Fetch a fresh token using raw cURL
     * Step 2: Call requestToPay using raw cURL (multiple header variations)
     * Returns all results so we can compare
     */
    public function test()
    {
        $results = [];

        // ── STEP 1: Get Token ──
        $tokenResult = $this->fetchToken();
        $results['step1_token'] = $tokenResult;

        if (!$tokenResult['success']) {
            return response()->json($results, 500, [], JSON_PRETTY_PRINT);
        }

        $token = $tokenResult['token'];

        // ── STEP 2: Request To Pay — Test A (exact Postman headers) ──
        $refA = $this->generateUuid();
        $results['step2a_postman_exact'] = $this->requestToPay(
            $token,
            $refA,
            [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $refA,
                'X-Target-Environment: ' . self::TARGET_ENV,
                'Ocp-Apim-Subscription-Key: ' . self::SUBSCRIPTION_KEY,
                'Content-Type: application/json',
                'X-Callback-Url: ' . self::CALLBACK_URL,  // Postman has this
            ]
        );

        // ── STEP 2: Request To Pay — Test B (no callback URL) ──
        $refB = $this->generateUuid();
        $results['step2b_no_callback'] = $this->requestToPay(
            $token,
            $refB,
            [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $refB,
                'X-Target-Environment: ' . self::TARGET_ENV,
                'Ocp-Apim-Subscription-Key: ' . self::SUBSCRIPTION_KEY,
                'Content-Type: application/json',
                // NO X-Callback-Url
            ]
        );

        // ── STEP 2: Request To Pay — Test C (no User-Agent at all) ──
        $refC = $this->generateUuid();
        $results['step2c_no_useragent'] = $this->requestToPay(
            $token,
            $refC,
            [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $refC,
                'X-Target-Environment: ' . self::TARGET_ENV,
                'Ocp-Apim-Subscription-Key: ' . self::SUBSCRIPTION_KEY,
                'Content-Type: application/json',
            ],
            true  // suppress User-Agent
        );

        // ── STEP 2: Request To Pay — Test D (via shell exec curl) ──
        $refD = $this->generateUuid();
        $results['step2d_shell_curl'] = $this->requestToPayViaShell(
            $token,
            $refD
        );

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Fetch collection token using raw cURL
     */
    private function fetchToken(): array
    {
        $url = self::BASE_URL . '/collection/token/';
        $credentials = base64_encode(self::API_USER . ':' . self::API_KEY);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $credentials,
                'Ocp-Apim-Subscription-Key: ' . self::SUBSCRIPTION_KEY,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLINFO_HEADER_OUT    => true,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => $curlError, 'http_code' => $httpCode];
        }

        $data = json_decode($responseBody, true);
        $token = $data['access_token'] ?? null;

        return [
            'success'         => (bool) $token,
            'token'           => $token,  // full token needed for step 2
            'token_hint'      => $token ? substr($token, 0, 15) . '...' : null,
            'http_code'       => $httpCode,
            'request_headers' => $requestHeaders,
            'is_waf_block'    => str_contains($responseBody, 'Request Rejected'),
        ];
    }

    /**
     * Call requestToPay using raw PHP cURL
     */
    private function requestToPay(string $token, string $refId, array $headers, bool $suppressUserAgent = false): array
    {
        $url = self::BASE_URL . '/collection/v1_0/requesttopay';

        $payload = json_encode([
            'amount'       => '1',
            'currency'     => 'XAF',
            'externalId'   => 'GOBUS-DEBUG-' . time(),
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => '237675096801',
            ],
            'payerMessage' => 'GoBus Debug Test',
            'payeeNote'    => 'Debug',
        ]);

        $ch = curl_init($url);

        $options = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLINFO_HEADER_OUT    => true,
        ];

        // Suppress User-Agent completely
        if ($suppressUserAgent) {
            $options[CURLOPT_USERAGENT] = '';
        }

        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $curlError    = curl_error($ch);
        curl_close($ch);

        return [
            'reference_id'    => $refId,
            'http_code'       => $httpCode,
            'body_preview'    => substr($responseBody, 0, 500),
            'is_waf_block'    => str_contains($responseBody, 'Request Rejected'),
            'is_success'      => $httpCode === 202,
            'curl_error'      => $curlError ?: null,
            'request_headers' => $requestHeaders,
        ];
    }

    /**
     * Call requestToPay by shelling out to the system curl binary.
     * This is the EXACT same as running curl from terminal.
     */
    private function requestToPayViaShell(string $token, string $refId): array
    {
        $url = self::BASE_URL . '/collection/v1_0/requesttopay';

        $payload = json_encode([
            'amount'       => '1',
            'currency'     => 'XAF',
            'externalId'   => 'GOBUS-SHELL-' . time(),
            'payer'        => [
                'partyIdType' => 'MSISDN',
                'partyId'     => '237675096801',
            ],
            'payerMessage' => 'GoBus Shell Test',
            'payeeNote'    => 'Shell',
        ]);

        // Build the exact curl command — same as Postman/curl from terminal
        $payloadEscaped = escapeshellarg($payload);
        $command = implode(' ', [
            'curl -s -w "\n%{http_code}" -X POST',
            '-H "Authorization: Bearer ' . $token . '"',
            '-H "X-Reference-Id: ' . $refId . '"',
            '-H "X-Target-Environment: ' . self::TARGET_ENV . '"',
            '-H "Ocp-Apim-Subscription-Key: ' . self::SUBSCRIPTION_KEY . '"',
            '-H "Content-Type: application/json"',
            '-d ' . $payloadEscaped,
            '"' . $url . '"',
        ]);

        $output = shell_exec($command . ' 2>&1');

        // Split body and status code (curl -w outputs status code last)
        $lines = explode("\n", trim($output));
        $httpCode = (int) array_pop($lines);
        $body = implode("\n", $lines);

        return [
            'reference_id' => $refId,
            'http_code'    => $httpCode,
            'body_preview' => substr($body, 0, 500),
            'is_waf_block' => str_contains($body, 'Request Rejected'),
            'is_success'   => $httpCode === 202,
            'method'       => 'shell_exec curl binary',
        ];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
