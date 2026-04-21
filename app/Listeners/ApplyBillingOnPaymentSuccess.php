<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\BillingFeeEngineService;
use Illuminate\Support\Facades\Log;

class ApplyBillingOnPaymentSuccess
{
    public function __construct(
        protected BillingFeeEngineService $billingFeeEngine
    ) {
    }

    public function handle(PaymentSuccess $event): void
    {
        try {
            $this->billingFeeEngine->applyTransactionFees($event->transaction->fresh());
        } catch (\Throwable $e) {
            Log::warning('Failed to apply billing fees on payment success', [
                'transaction_id' => $event->transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

