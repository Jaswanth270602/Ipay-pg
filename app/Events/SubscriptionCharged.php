<?php

namespace App\Events;

use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCharged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Subscription $subscription;
    public Transaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Subscription $subscription, Transaction $transaction)
    {
        $this->subscription = $subscription;
        $this->transaction = $transaction;
    }
}

