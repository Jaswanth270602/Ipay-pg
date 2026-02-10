<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use App\Services\WebhookService;

class SendPaymentWebhook
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentCreated|PaymentSuccess|PaymentFailed $event): void
    {
        if ($event instanceof PaymentCreated) {
            $merchant = $event->order->merchant;
            $payload = [
                'entity' => 'event',
                'account_id' => (string) $merchant->id,
                'event' => 'payment.created',
                'contains' => ['order'],
                'payload' => [
                    'order' => [
                        'entity' => [
                            'id' => $event->order->order_id,
                            'entity' => 'order',
                            'amount' => (int) ($event->order->amount * 100),
                            'currency' => $event->order->currency,
                            'status' => $event->order->status,
                            'created_at' => $event->order->created_at->timestamp,
                        ],
                    ],
                ],
                'created_at' => now()->timestamp,
            ];
            
            $this->webhookService->sendWebhook($merchant, 'payment.created', $payload);
            
        } elseif ($event instanceof PaymentSuccess) {
            $payload = $this->webhookService->buildPaymentPayload($event->transaction, [
                'event' => 'payment.success',
            ]);
            
            $this->webhookService->sendWebhook(
                $event->transaction->merchant,
                'payment.success',
                $payload
            );
            
            // Also send payment.charged event
            event(new \App\Events\PaymentCharged($event->transaction));
            
        } else { // PaymentFailed
            $merchant = $event->transaction->merchant;
            $payload = $this->webhookService->buildPaymentPayload($event->transaction, [
                'event' => 'payment.failed',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'error' => [
                                'code' => $event->transaction->gateway_response['code'] ?? 'PAYMENT_FAILED',
                                'description' => $event->transaction->gateway_response['message'] ?? 'Payment failed',
                            ],
                        ],
                    ],
                ],
            ]);
            
            $this->webhookService->sendWebhook($merchant, 'payment.failed', $payload);
        }
    }
}

