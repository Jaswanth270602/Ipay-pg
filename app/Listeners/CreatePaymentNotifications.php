<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentLinkCreated;
use App\Events\PaymentSuccess;
use App\Models\Notification;
use App\Models\User;

class CreatePaymentNotifications
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentCreated|PaymentSuccess|PaymentFailed|PaymentLinkCreated $event): void
    {
        $status = 'pending';
        $merchantId = null;
        $orderPublicId = null;
        $txnPublicId = null;
        $message = null;
        $merchantName = null;

        if ($event instanceof PaymentLinkCreated) {
            $paymentLink = $event->paymentLink;
            $merchantId = $paymentLink->merchant_id;
            $merchantName = $paymentLink->merchant?->name;
            $status = 'payment_link_created';
            $message = "Payment link created by merchant {$merchantName} (Link #{$paymentLink->link_token})";
        } elseif ($event instanceof PaymentCreated) {
            $merchantId = $event->order->merchant_id;
            $orderPublicId = $event->order->order_id ?? (string) $event->order->id;
            $merchantName = $event->order->merchant?->name;
            $status = 'pending';
        } else {
            $merchantId = $event->transaction->merchant_id;
            $orderPublicId = $event->transaction->order?->order_id ?? (string) $event->transaction->order_id;
            $txnPublicId = $event->transaction->txn_id ?? null;
            $merchantName = $event->transaction->merchant?->name;
            $status = $event instanceof PaymentSuccess ? 'success' : 'failed';
        }

        if (!$merchantId) {
            return;
        }

        if ($message === null) {
            $prefix = $merchantName ? "Merchant {$merchantName}: " : '';
            $message = match ($status) {
                'success' => "{$prefix}Payment successful for Order #{$orderPublicId}",
                'failed' => "{$prefix}Payment failed for Order #{$orderPublicId}",
                default => "{$prefix}Payment initiated for Order #{$orderPublicId}",
            };
        }

        // Default redirect: Transactions page
        $merchantTransactionsUrl = route('merchant.transactions.index');
        $merchantPaymentLinksUrl = route('merchant.payment_links.index');
        $merchantOrdersUrl = route('merchant.orders.index');
        $adminTransactionsUrl = route('admin.payments.transactions');
        $adminOrdersUrl = route('admin.orders.index');

        $meta = [
            'status' => $status,
            'order_id' => $orderPublicId,
            'txn_id' => $txnPublicId,
            'merchant_name' => $merchantName,
        ];

        // Merchant notifications: all active merchant users under this merchant
        $merchantUsers = User::query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'active')
            ->whereHas('role', function ($q) {
                $q->where('name', 'merchant');
            })
            ->get();

        foreach ($merchantUsers as $u) {
            Notification::create([
                'user_id' => $u->id,
                'role' => 'merchant',
                'message' => $message,
                'is_read' => false,
                'url' => $status === 'payment_link_created' ? $merchantPaymentLinksUrl : $merchantTransactionsUrl,
                'order_url' => $merchantOrdersUrl,
                'meta' => $meta,
            ]);
        }

        // Admin notifications: all active admin users
        $adminUsers = User::query()
            ->where('status', 'active')
            ->whereHas('role', function ($q) {
                $q->where('name', 'admin');
            })
            ->get();

        foreach ($adminUsers as $u) {
            Notification::create([
                'user_id' => $u->id,
                'role' => 'admin',
                'message' => $message,
                'is_read' => false,
                'url' => $adminTransactionsUrl,
                'order_url' => $adminOrdersUrl,
                'meta' => $meta,
            ]);
        }

    }
}
