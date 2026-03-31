<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Services\SplitService;
use App\Services\VendorLedgerService;
use Illuminate\Support\Facades\Log;

class CreatePaymentSplits
{
    public function __construct(
        protected SplitService $splitService,
        protected VendorLedgerService $vendorLedger
    ) {
    }

    public function handle(PaymentSuccess $event): void
    {
        $transaction = $event->transaction;

        try {
            // 1) Persist generic payment_splits rows
            $this->splitService->createSplitsForSuccessfulTransaction($transaction);

            // 2) Post vendor ledger credits for vendor legs
            $splits = $transaction->order
                ? \DB::table('payment_splits')
                    ->where('transaction_id', $transaction->id)
                    ->where('beneficiary_type', 'vendor')
                    ->get()
                : collect();

            foreach ($splits as $split) {
                if (! $split->beneficiary_id || $split->net_amount <= 0) {
                    continue;
                }

                $this->vendorLedger->postPaymentCaptured(
                    $transaction,
                    (int) $split->beneficiary_id,
                    (float) $split->net_amount
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create payment splits / vendor ledger', [
                'transaction_id' => $transaction->id,
                'txn_id' => $transaction->txn_id,
                'error' => $e->getMessage(),
            ]);
            // Do not break the payment success pipeline
        }
    }
}

