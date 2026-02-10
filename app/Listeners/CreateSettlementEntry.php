<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use Illuminate\Support\Facades\Log;

class CreateSettlementEntry
{
    /**
     * Handle the event.
     * 
     * Note: In production, settlements would be batched and processed periodically.
     * This is a placeholder for settlement logic.
     */
    public function handle(PaymentSuccess $event): void
    {
        Log::info('Settlement entry created', [
            'transaction_id' => $event->transaction->txn_id,
            'net_amount' => $event->transaction->net_amount,
        ]);

        // TODO: Implement actual settlement creation logic
        // This would typically be handled by a scheduled job that processes
        // successful transactions and creates settlement batches
    }
}

