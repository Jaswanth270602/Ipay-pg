<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorLedgerService
{
    /**
     * Post a single immutable ledger entry and update vendor_balances atomically.
     */
    public function postEntry(array $data): void
    {
        DB::transaction(function () use ($data) {
            $idempotencyKey = $data['idempotency_key']
                ?? $this->generateIdempotencyKey($data);

            // If entry already exists, skip (idempotent)
            $exists = DB::table('vendor_ledger_entries')
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            if ($exists) {
                return;
            }

            $entry = [
                'vendor_id' => $data['vendor_id'],
                'merchant_id' => $data['merchant_id'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'refund_id' => $data['refund_id'] ?? null,
                'vendor_settlement_id' => $data['vendor_settlement_id'] ?? null,
                'entry_type' => $data['entry_type'],
                'dr_cr' => $data['dr_cr'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'pending_delta' => $data['pending_delta'] ?? 0,
                'available_delta' => $data['available_delta'] ?? 0,
                'settled_delta' => $data['settled_delta'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('vendor_ledger_entries')->insert($entry);

            // Upsert vendor balance snapshot
            $balance = DB::table('vendor_balances')
                ->where('vendor_id', $entry['vendor_id'])
                ->lockForUpdate()
                ->first();

            $pendingDelta = $entry['pending_delta'];
            $availableDelta = $entry['available_delta'];
            $settledDelta = $entry['settled_delta'];

            if (! $balance) {
                DB::table('vendor_balances')->insert([
                    'vendor_id' => $entry['vendor_id'],
                    'merchant_id' => $entry['merchant_id'],
                    'pending_amount' => $pendingDelta,
                    'available_amount' => $availableDelta,
                    'settled_amount' => $settledDelta,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('vendor_balances')
                    ->where('vendor_id', $entry['vendor_id'])
                    ->update([
                        'pending_amount' => $balance->pending_amount + $pendingDelta,
                        'available_amount' => $balance->available_amount + $availableDelta,
                        'settled_amount' => $balance->settled_amount + $settledDelta,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    protected function generateIdempotencyKey(array $data): string
    {
        $parts = [
            $data['entry_type'] ?? 'unknown',
            $data['vendor_id'] ?? 'vendor',
            $data['transaction_id'] ?? 'txn',
            $data['refund_id'] ?? 'rfd',
            $data['vendor_settlement_id'] ?? 'vs',
        ];

        return implode(':', $parts);
    }

    /**
     * Convenience helper for posting a credit for a captured payment.
     */
    public function postPaymentCaptured(Transaction $transaction, int $vendorId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->postEntry([
            'vendor_id' => $vendorId,
            'merchant_id' => $transaction->merchant_id,
            'transaction_id' => $transaction->id,
            'entry_type' => 'PAYMENT_CAPTURED',
            'dr_cr' => 'CR',
            'amount' => $amount,
            'currency' => $transaction->currency,
            'pending_delta' => $amount,
            'available_delta' => 0,
            'settled_delta' => 0,
            'idempotency_key' => 'ledger:payment:' . $transaction->id . ':' . $vendorId,
        ]);
    }

    /**
     * Convenience helper for posting a debit for a completed refund.
     */
    public function postRefundDebit(Refund $refund, int $vendorId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->postEntry([
            'vendor_id' => $vendorId,
            'merchant_id' => $refund->merchant_id,
            'refund_id' => $refund->id,
            'entry_type' => 'REFUND_DEBIT',
            'dr_cr' => 'DR',
            'amount' => $amount,
            'currency' => $refund->currency,
            'pending_delta' => -$amount,
            'available_delta' => 0,
            'settled_delta' => 0,
            'idempotency_key' => 'ledger:refund:' . $refund->id . ':' . $vendorId,
        ]);
    }
}

