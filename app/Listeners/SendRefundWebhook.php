<?php

namespace App\Listeners;

use App\Events\RefundCreated;
use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEvent;

class SendRefundWebhook
{
    /**
     * Handle the event.
     */
    public function handle(RefundCreated $event): void
    {
        $merchant = $event->refund->merchant;

        if ($merchant->webhook_url) {
            $webhookEvent = WebhookEvent::create([
                'merchant_id' => $merchant->id,
                'event_type' => 'refund.created',
                'payload' => [
                    'refund_id' => $event->refund->refund_id,
                    'transaction_id' => $event->refund->transaction->txn_id,
                    'amount' => $event->refund->amount,
                    'currency' => $event->refund->currency,
                    'status' => $event->refund->status,
                    'is_partial' => $event->refund->is_partial,
                    'processed_at' => $event->refund->processed_at?->toIso8601String(),
                ],
                'webhook_url' => $merchant->webhook_url,
                'delivered' => false,
                'attempt_count' => 0,
                'max_attempts' => config('badlicash.webhook.max_retry_attempts', 5),
                'next_retry_at' => now(),
            ]);

            DeliverWebhookJob::dispatch($webhookEvent);
        }
    }
}

