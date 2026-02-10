<?php

namespace App\Events;

use App\Models\Refund;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Refund $refund;

    /**
     * Create a new event instance.
     */
    public function __construct(Refund $refund)
    {
        $this->refund = $refund;
    }
}

