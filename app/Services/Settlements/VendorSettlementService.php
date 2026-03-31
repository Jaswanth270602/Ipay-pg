<?php

namespace App\Services\Settlements;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VendorSettlementService
{
    /**
     * Create vendor settlement batches from vendor_ledger_entries.
     *
     * This is intentionally conservative: it only settles positive pending balances.
     */
    public function processDailyVendorSettlements(
        Carbon $date,
        ?int $merchantId = null,
        ?string $mode = null
    ): void {
        // Mode is reserved for future use (e.g. test/live vendor payouts).
        $settlementDate = $date->copy()->toDateString();

        DB::transaction(function () use ($settlementDate, $merchantId) {
            // Aggregate pending credits per vendor/merchant.
            $query = DB::table('vendor_balances')
                ->join('merchant_vendors', 'vendor_balances.vendor_id', '=', 'merchant_vendors.id')
                ->select(
                    'vendor_balances.vendor_id',
                    'vendor_balances.merchant_id',
                    'vendor_balances.pending_amount'
                )
                ->where('vendor_balances.pending_amount', '>', 0);

            if ($merchantId) {
                $query->where('vendor_balances.merchant_id', $merchantId);
            }

            $rows = $query->get();
            if ($rows->isEmpty()) {
                return;
            }

            foreach ($rows as $row) {
                $amount = (float) $row->pending_amount;
                if ($amount <= 0) {
                    continue;
                }

                $batchId = $this->generateBatchId($row->vendor_id, $settlementDate);

                // Idempotent: if batch exists, skip.
                $existing = DB::table('vendor_settlements')
                    ->where('batch_id', $batchId)
                    ->first();

                if ($existing) {
                    continue;
                }

                $settlementId = DB::table('vendor_settlements')->insertGetId([
                    'batch_id' => $batchId,
                    'vendor_id' => $row->vendor_id,
                    'merchant_id' => $row->merchant_id,
                    'gross_amount' => $amount,
                    'refund_deduction' => 0,
                    'net_payout' => $amount,
                    'currency' => 'INR',
                    'status' => 'pending',
                    'settlement_date' => $settlementDate,
                    'processed_at' => null,
                    'bank_reference' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // For now we do not create per-split items; we simply mark ledger.
                DB::table('vendor_ledger_entries')
                    ->where('vendor_id', $row->vendor_id)
                    ->where('pending_delta', '>', 0)
                    ->update([
                        'vendor_settlement_id' => $settlementId,
                        'updated_at' => now(),
                    ]);

                // Move pending -> settled in balances.
                DB::table('vendor_balances')
                    ->where('vendor_id', $row->vendor_id)
                    ->update([
                        'pending_amount' => 0,
                        'settled_amount' => DB::raw('settled_amount + ' . $amount),
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    protected function generateBatchId(int $vendorId, string $date): string
    {
        return 'VSET_' . str_replace('-', '', $date) . '_V' . $vendorId . '_' . strtoupper(Str::random(6));
    }
}

