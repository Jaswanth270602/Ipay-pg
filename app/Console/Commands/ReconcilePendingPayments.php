<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Acquirers\AcquirerResolver;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePendingPayments extends Command
{
    protected $signature = 'payments:reconcile-pending {--limit=100 : Max transactions per run}';

    protected $description = 'Poll gateway status for pending live payments when callback was not received';

    public function handle(AcquirerResolver $resolver): int
    {
        $limit = (int) $this->option('limit');

        $transactions = Transaction::query()
            ->where('status', 'pending')
            ->where('test_mode', false)
            ->whereNotNull('gateway_txn_id')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;

        foreach ($transactions as $transaction) {
            $merchant = $transaction->merchant;
            if (!$merchant) {
                continue;
            }

            $account = $merchant->getActiveAcquirerAccount();
            if (!$account) {
                continue;
            }

            try {
                $adapter = $resolver->resolve($account);
            } catch (\Throwable $e) {
                Log::warning('Reconcile: could not resolve acquirer', [
                    'transaction_id' => $transaction->txn_id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $gatewayId = $transaction->gateway_txn_id;
            if (str_starts_with($gatewayId, 'test_')) {
                continue;
            }

            $result = $adapter->getPaymentStatus($gatewayId);
            if (empty($result['success'])) {
                continue;
            }

            $normalized = $result['status'] ?? 'pending';
            $payload = array_merge($transaction->gateway_response ?? [], [
                'reconciled_at' => now()->toIso8601String(),
                'poll_status' => $normalized,
                'poll_raw' => $result['raw_response'] ?? $result,
            ]);

            if ($normalized === 'success' && $transaction->status !== 'success') {
                $transaction->update([
                    'status' => 'success',
                    'gateway_response' => $payload,
                    'captured_at' => now(),
                ]);
                $transaction->order?->update(['status' => 'completed']);
                event(new PaymentSuccess($transaction));
                $updated++;
                Log::info('payment_orchestration_reconcile', [
                    'transaction_id' => $transaction->txn_id,
                    'merchant_id' => $transaction->merchant_id,
                    'new_status' => 'success',
                ]);
            } elseif (in_array($normalized, ['failed', 'cancelled'], true)) {
                $transaction->update([
                    'status' => 'failed',
                    'gateway_response' => $payload,
                    'failure_reason' => 'Reconciled as failed from gateway',
                ]);
                $transaction->order?->update(['status' => 'failed']);
                event(new PaymentFailed($transaction));
                $updated++;
                Log::info('payment_orchestration_reconcile', [
                    'transaction_id' => $transaction->txn_id,
                    'merchant_id' => $transaction->merchant_id,
                    'new_status' => 'failed',
                ]);
            } else {
                $transaction->update([
                    'gateway_response' => $payload,
                ]);
            }
        }

        $this->info("Reconciled {$updated} transaction(s).");

        return self::SUCCESS;
    }
}
