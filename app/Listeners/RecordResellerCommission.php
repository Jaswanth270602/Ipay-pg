<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\ResellerCommissionService;
use Illuminate\Support\Facades\Log;

class RecordResellerCommission
{
    public function __construct(
        protected ResellerCommissionService $resellerCommissionService
    ) {}

    public function handle(PaymentSuccess $event): void
    {
        try {
            $this->resellerCommissionService->recordForSuccessfulTransaction($event->transaction);
        } catch (\Throwable $e) {
            // Never fail the payment pipeline (e.g. missing resellers tables, schema drift).
            Log::error('Failed to record reseller commission on payment success', [
                'transaction_id' => $event->transaction->id,
                'txn_id' => $event->transaction->txn_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
