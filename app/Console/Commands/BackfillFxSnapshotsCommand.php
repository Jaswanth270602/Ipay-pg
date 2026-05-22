<?php

namespace App\Console\Commands;

use App\Models\Refund;
use App\Models\Transaction;
use App\Services\FxSnapshotService;
use Illuminate\Console\Command;

class BackfillFxSnapshotsCommand extends Command
{
    protected $signature = 'ipay:backfill-fx-snapshots {--chunk=200}';

    protected $description = 'Backfill FX snapshot fields on successful transactions and completed refunds';

    public function handle(FxSnapshotService $fxSnapshotService): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $txnCount = 0;
        $refundCount = 0;

        Transaction::query()
            ->where('status', 'success')
            ->whereNull('amount_base')
            ->orderBy('id')
            ->chunkById($chunk, function ($transactions) use ($fxSnapshotService, &$txnCount) {
                foreach ($transactions as $transaction) {
                    if ($fxSnapshotService->captureTransactionSnapshot($transaction, $transaction->created_at)) {
                        $txnCount++;
                    }
                }
            });

        Refund::query()
            ->where('status', 'completed')
            ->whereNull('amount_base')
            ->with('transaction')
            ->orderBy('id')
            ->chunkById($chunk, function ($refunds) use ($fxSnapshotService, &$refundCount) {
                foreach ($refunds as $refund) {
                    if ($fxSnapshotService->captureRefundSnapshot($refund, $refund->created_at)) {
                        $refundCount++;
                    }
                }
            });

        $this->info("Backfilled {$txnCount} transaction(s) and {$refundCount} refund(s).");

        return self::SUCCESS;
    }
}
