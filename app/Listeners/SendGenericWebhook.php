<?php

namespace App\Listeners;

use App\Services\WebhookService;
use App\Events\PaymentCharged;
use App\Events\PaymentActivated;
use App\Events\PaymentAuthorized;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCharged;

class SendGenericWebhook
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle payment.charged event.
     */
    public function handlePaymentCharged(PaymentCharged $event): void
    {
        $payload = $this->webhookService->buildPaymentPayload($event->transaction, [
            'event' => 'payment.charged',
        ]);

        $this->webhookService->sendWebhook(
            $event->transaction->merchant,
            'payment.charged',
            $payload
        );
    }

    /**
     * Handle payment.activated event.
     */
    public function handlePaymentActivated(PaymentActivated $event): void
    {
        $payload = $this->webhookService->buildPaymentPayload($event->transaction, [
            'event' => 'payment.activated',
        ]);

        $this->webhookService->sendWebhook(
            $event->transaction->merchant,
            'payment.activated',
            $payload
        );
    }

    /**
     * Handle payment.authorized event.
     */
    public function handlePaymentAuthorized(PaymentAuthorized $event): void
    {
        $payload = $this->webhookService->buildPaymentPayload($event->transaction, [
            'event' => 'payment.authorized',
        ]);

        $this->webhookService->sendWebhook(
            $event->transaction->merchant,
            'payment.authorized',
            $payload
        );
    }

    /**
     * Handle subscription.created event.
     */
    public function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        $payload = $this->webhookService->buildSubscriptionPayload($event->subscription, [
            'event' => 'subscription.created',
        ]);

        $this->webhookService->sendWebhook(
            $event->subscription->merchant,
            'subscription.created',
            $payload
        );
    }

    /**
     * Handle subscription.activated event.
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        $payload = $this->webhookService->buildSubscriptionPayload($event->subscription, [
            'event' => 'subscription.activated',
        ]);

        $this->webhookService->sendWebhook(
            $event->subscription->merchant,
            'subscription.activated',
            $payload
        );
    }

    /**
     * Handle subscription.charged event.
     */
    public function handleSubscriptionCharged(SubscriptionCharged $event): void
    {
        $payload = $this->webhookService->buildSubscriptionPayload($event->subscription, [
            'event' => 'subscription.charged',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $event->transaction->txn_id,
                        'amount' => (int) ($event->transaction->amount * 100),
                        'currency' => $event->transaction->currency,
                        'status' => $event->transaction->status,
                    ],
                ],
            ],
        ]);

        $this->webhookService->sendWebhook(
            $event->subscription->merchant,
            'subscription.charged',
            $payload
        );
    }
}

