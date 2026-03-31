<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RefundSplitService
{
    public function applyForCompletedRefund(Refund $refund): void
    {
        if ($refund->status !== 'completed') {
            return;
        }

        $transaction = $refund->transaction;
        if (! $transaction instanceof Transaction) {
            return;
        }

        DB::transaction(function () use ($refund, $transaction) {
            // Idempotent: if allocations exist, do nothing.
            $exists = DB::table('refund_split_allocations')
                ->where('refund_id', $refund->id)
                ->exists();

            if ($exists) {
                return;
            }

            $splits = DB::table('payment_splits')
                ->where('transaction_id', $transaction->id)
                ->get();

            if ($splits->isEmpty()) {
                return;
            }

            $strategy = $refund->refund_strategy ?? 'proportional';
            $allocations = [];

            switch ($strategy) {
                case 'vendor_specific':
                    $allocations = $this->allocateVendorSpecific($refund, $splits);
                    break;
                case 'platform_bear':
                    $allocations = $this->allocatePlatformBear($refund, $splits, $transaction);
                    break;
                case 'proportional':
                default:
                    $allocations = $this->allocateProportional($refund, $splits);
                    break;
            }

            foreach ($allocations as $row) {
                DB::table('refund_split_allocations')->insert($row);

                // Post vendor ledger debits when applicable
                if ($row['beneficiary_type'] === 'vendor' && $row['beneficiary_id'] && $row['allocated_amount'] > 0) {
                    app(\App\Services\VendorLedgerService::class)->postRefundDebit(
                        $refund,
                        (int) $row['beneficiary_id'],
                        (float) $row['allocated_amount']
                    );
                }
            }
        });
    }

    protected function allocateProportional(Refund $refund, $splits): array
    {
        $totalNet = (float) $splits->sum('net_amount');
        if ($totalNet <= 0) {
            return [];
        }

        $rows = [];
        foreach ($splits as $split) {
            $ratio = $split->net_amount / $totalNet;
            $amount = round($refund->amount * $ratio, 2);
            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'refund_id' => $refund->id,
                'transaction_id' => $split->transaction_id,
                'payment_split_id' => $split->id,
                'strategy' => 'proportional',
                'allocated_amount' => $amount,
                'beneficiary_type' => $split->beneficiary_type,
                'beneficiary_id' => $split->beneficiary_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    protected function allocateVendorSpecific(Refund $refund, $splits): array
    {
        $targetVendorId = $refund->vendor_id;
        if (! $targetVendorId) {
            // Fallback to proportional
            return $this->allocateProportional($refund, $splits);
        }

        $rows = [];

        foreach ($splits as $split) {
            if ($split->beneficiary_type === 'vendor' && (int) $split->beneficiary_id === (int) $targetVendorId) {
                $rows[] = [
                    'refund_id' => $refund->id,
                    'transaction_id' => $split->transaction_id,
                    'payment_split_id' => $split->id,
                    'strategy' => 'vendor_specific',
                    'allocated_amount' => (float) $refund->amount,
                    'beneficiary_type' => 'vendor',
                    'beneficiary_id' => $split->beneficiary_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;
            }
        }

        return $rows;
    }

    protected function allocatePlatformBear(Refund $refund, $splits, Transaction $transaction): array
    {
        $rows = [];

        foreach ($splits as $split) {
            if ($split->beneficiary_type === 'merchant' && (int) $split->beneficiary_id === (int) $transaction->merchant_id) {
                $rows[] = [
                    'refund_id' => $refund->id,
                    'transaction_id' => $split->transaction_id,
                    'payment_split_id' => $split->id,
                    'strategy' => 'platform_bear',
                    'allocated_amount' => (float) $refund->amount,
                    'beneficiary_type' => 'merchant',
                    'beneficiary_id' => $split->beneficiary_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;
            }
        }

        return $rows;
    }
}

