<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Models\AuditLog;

class LogPaymentCreated
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCreated $event): void
    {
        AuditLog::logAction(
            'payment.created',
            'Order',
            $event->order->id,
            [
                'order_id' => $event->order->order_id,
                'amount' => $event->order->amount,
                'merchant_id' => $event->order->merchant_id,
            ]
        );
    }
}

