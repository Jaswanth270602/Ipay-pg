<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Receive webhook from bank/payment gateway.
     * This is an internal webhook receiver for bank callbacks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receive(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook
            Log::info('Bank webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);

            // TODO: Implement actual webhook processing based on bank provider
            // This would typically:
            // 1. Verify webhook signature
            // 2. Parse webhook payload
            // 3. Update transaction status
            // 4. Trigger merchant webhooks

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing',
            ]);

        } catch (\Exception $e) {
            Log::error('Bank webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test webhook endpoint for merchants to test their webhook URLs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        $merchant = $request->get('api_merchant');

        if (!$merchant->webhook_url) {
            return response()->json([
                'error' => 'No webhook URL configured',
            ], 400);
        }

        try {
            $testPayload = [
                'event_type' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'message' => 'This is a test webhook from ' . config('app.name'),
                ],
            ];

            $signature = hash_hmac('sha256', json_encode($testPayload), $merchant->webhook_secret ?? config('app.key'));

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-' . str_replace(' ', '-', config('app.name')) . '-Signature' => $signature,
                    'X-' . str_replace(' ', '-', config('app.name')) . '-Event' => 'webhook.test',
                ])
                ->post($merchant->webhook_url, $testPayload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test webhook delivered successfully',
                    'response_status' => $response->status(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook delivery failed',
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Webhook test failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get webhook logs for a transaction.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getLogs(string $transactionId): JsonResponse
    {
        try {
            $logs = WebhookEvent::where('transaction_id', $transactionId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'event' => $log->event_type,
                        'status' => $log->status,
                        'payload' => $log->payload,
                        'created_at' => $log->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch webhook logs',
                'logs' => [],
            ]);
        }
    }
}

