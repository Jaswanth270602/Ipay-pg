<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\PaymentCreated::class => [
            \App\Listeners\SendPaymentWebhook::class,
            \App\Listeners\LogPaymentCreated::class,
        ],
        \App\Events\PaymentSuccess::class => [
            \App\Listeners\SendPaymentWebhook::class,
            \App\Listeners\CreateSettlementEntry::class,
        ],
        \App\Events\PaymentFailed::class => [
            \App\Listeners\SendPaymentWebhook::class,
        ],
        \App\Events\PaymentCharged::class => [
            \App\Listeners\SendGenericWebhook::class . '@handlePaymentCharged',
        ],
        \App\Events\PaymentActivated::class => [
            \App\Listeners\SendGenericWebhook::class . '@handlePaymentActivated',
        ],
        \App\Events\PaymentAuthorized::class => [
            \App\Listeners\SendGenericWebhook::class . '@handlePaymentAuthorized',
        ],
        \App\Events\RefundCreated::class => [
            \App\Listeners\SendRefundWebhook::class,
            \App\Listeners\AdjustSettlement::class,
        ],
        \App\Events\SubscriptionCreated::class => [
            \App\Listeners\SendGenericWebhook::class . '@handleSubscriptionCreated',
        ],
        \App\Events\SubscriptionActivated::class => [
            \App\Listeners\SendGenericWebhook::class . '@handleSubscriptionActivated',
        ],
        \App\Events\SubscriptionCharged::class => [
            \App\Listeners\SendGenericWebhook::class . '@handleSubscriptionCharged',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

