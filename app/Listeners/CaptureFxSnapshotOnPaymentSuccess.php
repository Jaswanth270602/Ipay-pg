<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\FxSnapshotService;

class CaptureFxSnapshotOnPaymentSuccess
{
    public function __construct(
        private readonly FxSnapshotService $fxSnapshotService
    ) {}

    public function handle(PaymentSuccess $event): void
    {
        $transaction = $event->transaction->fresh();
        if (! $transaction) {
            return;
        }

        $this->fxSnapshotService->captureTransactionSnapshot($transaction);
    }
}
