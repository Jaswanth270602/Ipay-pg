<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\Rates\MerchantRateSnapshotService;

class SnapshotTransactionRates
{
    public function __construct(
        protected MerchantRateSnapshotService $snapshotService
    ) {
    }

    public function handle(PaymentSuccess $event): void
    {
        $transaction = $event->transaction->fresh(['merchant']);
        if (! $transaction || ! $transaction->merchant) {
            return;
        }

        if ($transaction->admin_rate_snapshot_id) {
            return;
        }

        $snapshot = $this->snapshotService->createPaymentSnapshot(
            merchant: $transaction->merchant,
            paymentMethod: (string) ($transaction->payment_method ?? 'card'),
            amount: (float) ($transaction->amount ?? 0),
            feeAmount: (float) ($transaction->fee_amount ?? 0),
            baseRateId: null
        );

        $transaction->update([
            'admin_rate_snapshot_id' => $snapshot->id,
            'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
        ]);
    }
}

