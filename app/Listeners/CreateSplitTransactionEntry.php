<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\SplitTransactionService;
use Illuminate\Support\Facades\Log;

class CreateSplitTransactionEntry
{
    public function __construct(protected SplitTransactionService $splitService)
    {
    }

    public function handle(PaymentSuccess $event): void
    {
        try {
            $this->splitService->createForSuccessfulTransaction($event->transaction);
        } catch (\Throwable $e) {
            // Do not break payment success pipeline on split creation failure.
            Log::error('Failed to auto-create split transaction', [
                'transaction_id' => $event->transaction->id,
                'txn_id' => $event->transaction->txn_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

