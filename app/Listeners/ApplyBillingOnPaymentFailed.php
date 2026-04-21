<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Services\BillingFeeEngineService;
use Illuminate\Support\Facades\Log;

class ApplyBillingOnPaymentFailed
{
    public function __construct(
        protected BillingFeeEngineService $billingFeeEngine
    ) {
    }

    public function handle(PaymentFailed $event): void
    {
        try {
            $this->billingFeeEngine->applyTransactionFees($event->transaction->fresh());
        } catch (\Throwable $e) {
            Log::warning('Failed to apply billing fees on payment failed', [
                'transaction_id' => $event->transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

