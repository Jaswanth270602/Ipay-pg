<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\ResellerCommissionService;

class RecordResellerCommission
{
    public function __construct(
        protected ResellerCommissionService $resellerCommissionService
    ) {}

    public function handle(PaymentSuccess $event): void
    {
        $this->resellerCommissionService->recordForSuccessfulTransaction($event->transaction);
    }
}
