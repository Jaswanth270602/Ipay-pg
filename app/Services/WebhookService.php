<?php

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Models\WebhookEventType;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Send webhook for an event if it's enabled.
     */
    public function sendWebhook(Merchant $merchant, string $eventKey, array $payload): void
    {
        // Check if merchant has webhook URL configured
        if (!$merchant->webhook_url) {
            Log::debug('Merchant webhook URL not configured', [
                'merchant_id' => $merchant->id,
                'event' => $eventKey,
            ]);
            return;
        }

        // Check if event type is enabled
        if (!WebhookEventType::isEnabled($eventKey)) {
            Log::debug('Webhook event type is disabled', [
                'merchant_id' => $merchant->id,
                'event' => $eventKey,
            ]);
            return;
        }

        // Create webhook event record
        $webhookEvent = WebhookEvent::create([
            'merchant_id' => $merchant->id,
            'event_type' => $eventKey,
            'payload' => $payload,
            'webhook_url' => $merchant->webhook_url,
            'delivered' => false,
            'attempt_count' => 0,
            'max_attempts' => config('badlicash.webhook.max_retry_attempts', 5),
            'next_retry_at' => now(),
        ]);

        // Dispatch webhook delivery job
        DeliverWebhookJob::dispatch($webhookEvent);

        Log::info('Webhook event created and queued', [
            'merchant_id' => $merchant->id,
            'event' => $eventKey,
            'webhook_event_id' => $webhookEvent->id,
        ]);
    }

    /**
     * Build payload for payment events.
     */
    public function buildPaymentPayload($transaction, array $additionalData = []): array
    {
        $basePayload = [
            'entity' => 'event',
            'account_id' => (string) $transaction->merchant_id,
            'event' => 'payment',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $transaction->txn_id,
                        'entity' => 'payment',
                        'amount' => (int) ($transaction->amount * 100), // Convert to paise/cents
                        'currency' => $transaction->currency,
                        'status' => $transaction->status,
                        'method' => $transaction->payment_method,
                        'order_id' => $transaction->order->order_id ?? null,
                        'description' => $transaction->order->description ?? null,
                        'fee' => (int) ($transaction->fee_amount * 100),
                        'tax' => 0,
                        'created_at' => $transaction->created_at->timestamp,
                        'captured_at' => $transaction->captured_at?->timestamp,
                    ],
                ],
            ],
            'created_at' => now()->timestamp,
        ];

        return array_merge_recursive($basePayload, $additionalData);
    }

    /**
     * Build payload for subscription events.
     */
    public function buildSubscriptionPayload($subscription, array $additionalData = []): array
    {
        $basePayload = [
            'entity' => 'event',
            'account_id' => (string) $subscription->merchant_id,
            'event' => 'subscription',
            'contains' => ['subscription'],
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => (string) $subscription->id,
                        'entity' => 'subscription',
                        'plan_id' => (string) $subscription->plan_id,
                        'status' => $subscription->status,
                        'current_start' => $subscription->current_period_start?->timestamp,
                        'current_end' => $subscription->current_period_end?->timestamp,
                        'created_at' => $subscription->created_at->timestamp,
                    ],
                ],
            ],
            'created_at' => now()->timestamp,
        ];

        return array_merge_recursive($basePayload, $additionalData);
    }
}

