<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentSuccess;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

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
    public function handle(PaymentCreated|PaymentSuccess|PaymentFailed $event): void
    {
        $status = 'pending';
        $merchantId = null;
        $orderPublicId = null;
        $txnPublicId = null;

        if ($event instanceof PaymentCreated) {
            $merchantId = $event->order->merchant_id;
            $orderPublicId = $event->order->order_id ?? (string) $event->order->id;
            $status = 'pending';
        } else {
            $merchantId = $event->transaction->merchant_id;
            $orderPublicId = $event->transaction->order?->order_id ?? (string) $event->transaction->order_id;
            $txnPublicId = $event->transaction->txn_id ?? null;
            $status = $event instanceof PaymentSuccess ? 'success' : 'failed';
        }

        if (!$merchantId) {
            return;
        }

        $message = match ($status) {
            'success' => "Payment successful for Order #{$orderPublicId}",
            'failed' => "Payment failed for Order #{$orderPublicId}",
            default => "Payment initiated for Order #{$orderPublicId}",
        };

        // Default redirect: Transactions page
        $merchantTransactionsUrl = route('merchant.transactions.index');
        $merchantOrdersUrl = route('merchant.orders.index');
        $adminTransactionsUrl = route('admin.payments.transactions');
        $adminOrdersUrl = route('admin.orders.index');

        $meta = [
            'status' => $status,
            'order_id' => $orderPublicId,
            'txn_id' => $txnPublicId,
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
                'url' => $merchantTransactionsUrl,
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
