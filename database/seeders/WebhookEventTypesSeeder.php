<?php

namespace Database\Seeders;

use App\Models\WebhookEventType;
use Illuminate\Database\Seeder;

class WebhookEventTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            // Payment Events
            [
                'event_key' => 'payment.created',
                'name' => 'Payment Created',
                'description' => 'Triggered when a payment order is created',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'status' => 'string',
                    'created_at' => 'datetime',
                ],
                'sort_order' => 1,
            ],
            [
                'event_key' => 'payment.authorized',
                'name' => 'Payment Authorized',
                'description' => 'Triggered when a payment is authorized',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'payment_method' => 'string',
                    'authorized_at' => 'datetime',
                ],
                'sort_order' => 2,
            ],
            [
                'event_key' => 'payment.captured',
                'name' => 'Payment Captured',
                'description' => 'Triggered when a payment is captured',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'captured_at' => 'datetime',
                ],
                'sort_order' => 3,
            ],
            [
                'event_key' => 'payment.charged',
                'name' => 'Payment Charged',
                'description' => 'Triggered when a payment is successfully charged',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'payment_method' => 'string',
                    'charged_at' => 'datetime',
                ],
                'sort_order' => 4,
            ],
            [
                'event_key' => 'payment.success',
                'name' => 'Payment Success',
                'description' => 'Triggered when a payment is successful',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'payment_method' => 'string',
                    'status' => 'string',
                    'captured_at' => 'datetime',
                ],
                'sort_order' => 5,
            ],
            [
                'event_key' => 'payment.failed',
                'name' => 'Payment Failed',
                'description' => 'Triggered when a payment fails',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'error' => 'string',
                    'failed_at' => 'datetime',
                ],
                'sort_order' => 6,
            ],
            [
                'event_key' => 'payment.activated',
                'name' => 'Payment Activated',
                'description' => 'Triggered when a payment is activated',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'activated_at' => 'datetime',
                ],
                'sort_order' => 7,
            ],
            [
                'event_key' => 'payment.pending',
                'name' => 'Payment Pending',
                'description' => 'Triggered when a payment is pending',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'pending_at' => 'datetime',
                ],
                'sort_order' => 8,
            ],
            [
                'event_key' => 'payment.cancelled',
                'name' => 'Payment Cancelled',
                'description' => 'Triggered when a payment is cancelled',
                'category' => 'payment',
                'enabled' => true,
                'payload_structure' => [
                    'transaction_id' => 'string',
                    'order_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'cancelled_at' => 'datetime',
                ],
                'sort_order' => 9,
            ],

            // Refund Events
            [
                'event_key' => 'refund.created',
                'name' => 'Refund Created',
                'description' => 'Triggered when a refund is created',
                'category' => 'refund',
                'enabled' => true,
                'payload_structure' => [
                    'refund_id' => 'string',
                    'transaction_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'status' => 'string',
                    'created_at' => 'datetime',
                ],
                'sort_order' => 10,
            ],
            [
                'event_key' => 'refund.processed',
                'name' => 'Refund Processed',
                'description' => 'Triggered when a refund is processed',
                'category' => 'refund',
                'enabled' => true,
                'payload_structure' => [
                    'refund_id' => 'string',
                    'transaction_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'processed_at' => 'datetime',
                ],
                'sort_order' => 11,
            ],
            [
                'event_key' => 'refund.failed',
                'name' => 'Refund Failed',
                'description' => 'Triggered when a refund fails',
                'category' => 'refund',
                'enabled' => true,
                'payload_structure' => [
                    'refund_id' => 'string',
                    'transaction_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'error' => 'string',
                    'failed_at' => 'datetime',
                ],
                'sort_order' => 12,
            ],

            // Subscription Events
            [
                'event_key' => 'subscription.created',
                'name' => 'Subscription Created',
                'description' => 'Triggered when a subscription is created',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'current_period_start' => 'datetime',
                    'current_period_end' => 'datetime',
                    'created_at' => 'datetime',
                ],
                'sort_order' => 13,
            ],
            [
                'event_key' => 'subscription.activated',
                'name' => 'Subscription Activated',
                'description' => 'Triggered when a subscription is activated',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'activated_at' => 'datetime',
                ],
                'sort_order' => 14,
            ],
            [
                'event_key' => 'subscription.charged',
                'name' => 'Subscription Charged',
                'description' => 'Triggered when a subscription payment is charged',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'transaction_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'charged_at' => 'datetime',
                ],
                'sort_order' => 15,
            ],
            [
                'event_key' => 'subscription.pending',
                'name' => 'Subscription Pending',
                'description' => 'Triggered when a subscription payment is pending',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'pending_at' => 'datetime',
                ],
                'sort_order' => 16,
            ],
            [
                'event_key' => 'subscription.halted',
                'name' => 'Subscription Halted',
                'description' => 'Triggered when a subscription is halted',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'halted_at' => 'datetime',
                ],
                'sort_order' => 17,
            ],
            [
                'event_key' => 'subscription.cancelled',
                'name' => 'Subscription Cancelled',
                'description' => 'Triggered when a subscription is cancelled',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'cancelled_at' => 'datetime',
                ],
                'sort_order' => 18,
            ],
            [
                'event_key' => 'subscription.paused',
                'name' => 'Subscription Paused',
                'description' => 'Triggered when a subscription is paused',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'paused_at' => 'datetime',
                ],
                'sort_order' => 19,
            ],
            [
                'event_key' => 'subscription.resumed',
                'name' => 'Subscription Resumed',
                'description' => 'Triggered when a subscription is resumed',
                'category' => 'subscription',
                'enabled' => true,
                'payload_structure' => [
                    'subscription_id' => 'string',
                    'plan_id' => 'string',
                    'status' => 'string',
                    'resumed_at' => 'datetime',
                ],
                'sort_order' => 20,
            ],

            // Payment Link Events
            [
                'event_key' => 'payment_link.created',
                'name' => 'Payment Link Created',
                'description' => 'Triggered when a payment link is created',
                'category' => 'payment_link',
                'enabled' => true,
                'payload_structure' => [
                    'payment_link_id' => 'string',
                    'link_token' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'status' => 'string',
                    'created_at' => 'datetime',
                ],
                'sort_order' => 21,
            ],
            [
                'event_key' => 'payment_link.paid',
                'name' => 'Payment Link Paid',
                'description' => 'Triggered when a payment link is paid',
                'category' => 'payment_link',
                'enabled' => true,
                'payload_structure' => [
                    'payment_link_id' => 'string',
                    'link_token' => 'string',
                    'transaction_id' => 'string',
                    'amount' => 'decimal',
                    'currency' => 'string',
                    'paid_at' => 'datetime',
                ],
                'sort_order' => 22,
            ],
            [
                'event_key' => 'payment_link.expired',
                'name' => 'Payment Link Expired',
                'description' => 'Triggered when a payment link expires',
                'category' => 'payment_link',
                'enabled' => true,
                'payload_structure' => [
                    'payment_link_id' => 'string',
                    'link_token' => 'string',
                    'expired_at' => 'datetime',
                ],
                'sort_order' => 23,
            ],
        ];

        foreach ($events as $event) {
            WebhookEventType::updateOrCreate(
                ['event_key' => $event['event_key']],
                $event
            );
        }
    }
}

