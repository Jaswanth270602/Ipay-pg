<?php

namespace App\Listeners;

use App\Events\RefundCreated;
use App\Services\BillingFeeEngineService;
use Illuminate\Support\Facades\Log;

class ApplyBillingOnRefundCreated
{
    public function __construct(
        protected BillingFeeEngineService $billingFeeEngine
    ) {
    }

    public function handle(RefundCreated $event): void
    {
        $refund = $event->refund->fresh(['transaction.merchant']);
        if (! $refund || ! in_array($refund->status, ['completed', 'pending_processing', 'processing'], true)) {
            return;
        }

        try {
            $this->billingFeeEngine->applyRefundFees($refund);
        } catch (\Throwable $e) {
            Log::warning('Failed to apply billing fees on refund', [
                'refund_id' => $refund->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

