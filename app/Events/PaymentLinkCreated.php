<?php

namespace App\Events;

use App\Models\PaymentLink;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentLinkCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PaymentLink $paymentLink;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentLink $paymentLink)
    {
        $this->paymentLink = $paymentLink;
    }
}
