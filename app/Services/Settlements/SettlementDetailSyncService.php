<?php

namespace App\Services\Settlements;

use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds one settlement_details row per transaction for reconciliation and merchant drill-down.
 */
class SettlementDetailSyncService
{
    /**
     * Replace all detail rows for a settlement (idempotent for re-sync).
     */
    public function replaceDetailsForSettlement(Settlement $settlement, Collection $transactions, Merchant $merchant): void
    {
        DB::table('settlement_details')->where('settlement_id', $settlement->id)->delete();

        if ($transactions->isEmpty()) {
            return;
        }

        $transactionModels = Transaction::with('order')
            ->whereIn('id', $transactions->pluck('id'))
            ->get()
            ->keyBy('id');

        $rows = [];
        $now = now();
        foreach ($transactions as $tx) {
            $t = $transactionModels->get($tx->id);
            if (! $t) {
                continue;
            }
            $gw = is_array($t->gateway_response) ? $t->gateway_response : [];
            $fee = (float) ($t->fee_amount ?? 0);
            $gst = (float) ($t->gst_amount ?? 0);
            $other = (float) ($t->other_fees ?? 0);
            $amount = (float) $t->amount;
            $lineNet = max(0, round($amount - $fee - $gst - $other, 2));

            $orderPublicId = $t->order?->order_id ?? (string) ($t->order_id ?? '');

            $rows[] = [
                'merchant_id' => $merchant->id,
                'settlement_id' => $settlement->id,
                'order_id' => $orderPublicId ?: null,
                'transaction_id' => $t->id,
                'tran_seq_id' => (string) $t->id,
                'transaction_date' => $t->captured_at ?? $t->created_at,
                'transaction_qualifier' => $t->status,
                'settlement_qualifier' => $settlement->settlement_status ?? 'pending',
                'setl_id' => $settlement->settlement_id,
                'amount_paid_by_customer' => $amount,
                'settlement_amount' => $lineNet,
                'bank_settlement_date' => $settlement->settlement_date,
                'bank_settlement_amount' => $lineNet,
                'bank_reference' => $settlement->bank_reference,
                'settlement_account_name' => $merchant->bank_account_holder_name,
                'settlement_account_number' => $merchant->bank_account_number,
                'settlement_ifsc_code' => $merchant->bank_ifsc_code,
                'settlement_bank_name' => $merchant->bank_name,
                'settlement_bank_branch' => $merchant->bank_branch,
                'payment_mode' => $t->payment_method,
                'payment_channel' => $gw['channel'] ?? null,
                'tdr_percentage' => (float) ($merchant->fee_percentage ?? 0),
                'tdr_fixed_fee' => (float) ($merchant->fee_flat ?? 0),
                'tdr_amount' => $fee,
                'earliest_priority_settlement_date' => $settlement->settlement_date,
                'latest_priority_settlement_date' => $settlement->settlement_date,
                'tax_amount' => $gst,
                'setd_id' => 'SD_'.$settlement->id.'_'.$t->id.'_'.strtoupper(Str::random(4)),
                'provider' => $gw['provider'] ?? null,
                'account_id' => $gw['account_id'] ?? null,
                'acq_payment_id' => $gw['acq_payment_id'] ?? null,
                'test_mode' => (bool) $settlement->test_mode,
                'settlement_status' => $settlement->settlement_status ?? 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('settlement_details')->insert($chunk);
        }
    }
}
