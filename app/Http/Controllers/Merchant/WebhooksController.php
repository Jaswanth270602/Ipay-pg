<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class WebhooksController extends Controller
{
    public function index()
    {
        $merchant = auth()->user()->merchant;
        $webhooks = $merchant->webhookEvents()->latest()->paginate(50);
        
        $stats = [
            'total' => $merchant->webhookEvents()->count(),
            'successful' => $merchant->webhookEvents()->where('delivered', true)->count(),
            'failed' => $merchant->webhookEvents()->where('delivered', false)->where('attempt_count', '>=', 5)->count(),
            'pending' => $merchant->webhookEvents()->where('delivered', false)->whereColumn('attempt_count', '<', 'max_attempts')->count(),
        ];

        return view('merchant.webhooks.index', compact('merchant', 'webhooks', 'stats'));
    }

    public function getData(Request $request)
    {
        $merchant = auth()->user()->merchant;
        $webhooks = $merchant->webhookEvents()->latest()->paginate(50);
        
        $stats = [
            'total' => $merchant->webhookEvents()->count(),
            'successful' => $merchant->webhookEvents()->where('delivered', true)->count(),
            'failed' => $merchant->webhookEvents()->where('delivered', false)->where('attempt_count', '>=', 5)->count(),
            'pending' => $merchant->webhookEvents()->where('delivered', false)->whereColumn('attempt_count', '<', 'max_attempts')->count(),
        ];

        return response()->json([
            'data' => [
                'webhooks' => $webhooks->items(),
                'pagination' => [
                    'current_page' => $webhooks->currentPage(),
                    'last_page' => $webhooks->lastPage(),
                    'per_page' => $webhooks->perPage(),
                    'total' => $webhooks->total(),
                    'from' => $webhooks->firstItem(),
                    'to' => $webhooks->lastItem(),
                ],
                'stats' => $stats,
                'merchant' => [
                    'webhook_url' => $merchant->webhook_url,
                    'webhook_secret' => $merchant->webhook_secret,
                ]
            ]
        ]);
    }

    public function updateWebhookUrl(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url|max:500',
        ]);

        $merchant = auth()->user()->merchant;
        $merchant->webhook_url = $request->webhook_url;
        $merchant->webhook_secret = \Illuminate\Support\Str::random(32);
        $merchant->save();

        return response()->json([
            'success' => true,
            'message' => 'Webhook URL updated successfully',
            'webhook_secret' => $merchant->webhook_secret
        ]);
    }

    public function testWebhook(Request $request)
    {
        $merchant = auth()->user()->merchant;

        if (!$merchant->webhook_url) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook URL not configured'
            ], 400);
        }

        // Create test webhook event
        $webhookEvent = WebhookEvent::create([
            'merchant_id' => $merchant->id,
            'event_type' => 'payment.test',
            'payload' => [
                'event' => 'payment.test',
                'transaction_id' => 'test_txn_' . time(),
                'amount' => 1000,
                'currency' => 'INR',
                'status' => 'success',
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => 'pending',
        ]);

        // Dispatch webhook job
        \App\Jobs\DeliverWebhookJob::dispatch($webhookEvent);

        return response()->json([
            'success' => true,
            'message' => 'Test webhook dispatched',
            'webhook_event_id' => $webhookEvent->id
        ]);
    }

    public function retryWebhook($id)
    {
        $merchant = auth()->user()->merchant;
        $webhookEvent = WebhookEvent::where('merchant_id', $merchant->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($webhookEvent->status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Webhook already delivered successfully'
            ], 400);
        }

        $webhookEvent->delivered = false;
        $webhookEvent->attempt_count = ($webhookEvent->attempt_count ?? 0);
        $webhookEvent->save();

        \App\Jobs\DeliverWebhookJob::dispatch($webhookEvent);

        return response()->json([
            'success' => true,
            'message' => 'Webhook retry dispatched'
        ]);
    }
}

