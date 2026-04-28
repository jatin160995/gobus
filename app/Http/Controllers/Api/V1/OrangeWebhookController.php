<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OrangeWebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // -------------------------------------------------------
    // Orange Webhook Handler
    // POST /api/orange/webhook
    // -------------------------------------------------------
    public function handle(Request $request): Response
    {
        // Step 1 — Log raw incoming payload immediately
        Log::info('Orange webhook hit', [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Step 2 — Get payload
        $payload = $request->all();

        // Step 3 — Basic payload validation
        if (empty($payload) || !isset($payload['payToken'])) {
            Log::warning('Orange webhook received invalid payload', [
                'payload' => $payload,
            ]);

            // Always return 200 to Orange even on invalid payload
            // Otherwise Orange will keep retrying
            return response('OK', 200);
        }

        // Step 4 — Process the webhook
        try {
            $this->paymentService->handleWebhookCallback($payload);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Still return 200 to prevent Orange retry storm
            return response('OK', 200);
        }

        // Step 5 — Always return 200 to Orange
        return response('OK', 200);
    }
}