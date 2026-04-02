<?php

namespace App\Listeners;

use App\Events\RefundCreated;
use App\Services\ResellerCommissionService;

class AdjustResellerCommissionOnRefund
{
    public function __construct(
        protected ResellerCommissionService $resellerCommissionService
    ) {}

    public function handle(RefundCreated $event): void
    {
        if ($event->refund->status !== 'completed') {
            return;
        }

        $this->resellerCommissionService->applyRefund($event->refund);
    }
}
