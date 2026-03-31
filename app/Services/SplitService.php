<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Transaction;
use App\Services\Rates\MerchantVendorRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SplitService
{
    public function __construct(
        protected MerchantVendorRateService $vendorRateService
    ) {
    }

    public function createSplitsForSuccessfulTransaction(Transaction $transaction): void
    {
        if ($transaction->status !== 'success') {
            return;
        }

        // Avoid duplicate work (idempotent)
        $exists = DB::table('payment_splits')
            ->where('transaction_id', $transaction->id)
            ->exists();

        if ($exists) {
            return;
        }

        $order = $transaction->order;
        if (! $order instanceof Order) {
            return;
        }

        $merchant = $transaction->merchant;
        if (! $merchant) {
            return;
        }

        $paymentLink = $order->paymentLink;

        // Resolve vendor splits from order metadata or payment link
        [$legs, $totalNet] = $this->buildSplitLegs($transaction, $order, $paymentLink);

        if ($legs === []) {
            return;
        }

        DB::transaction(function () use ($transaction, $order, $merchant, $legs) {
            $firstVendorLeg = null;
            foreach ($legs as $idx => $leg) {
                $idempotencyKey = sprintf(
                    'split:%d:%s:%d',
                    $transaction->id,
                    $leg['beneficiary_type'],
                    $leg['beneficiary_id'] ?? 0
                );

                $already = DB::table('payment_splits')
                    ->where('idempotency_key', $idempotencyKey)
                    ->exists();

                if ($already) {
                    continue;
                }

                DB::table('payment_splits')->insert([
                    'transaction_id' => $transaction->id,
                    'order_id' => $order->id,
                    'merchant_id' => $merchant->id,
                    'admin_rate_snapshot_id' => $transaction->admin_rate_snapshot_id,
                    'merchant_vendor_rate_snapshot_id' => $leg['vendor_rate_snapshot_id'] ?? null,
                    'merchant_vendor_split_percentage_snapshot' => $leg['vendor_split_percentage_snapshot'] ?? null,
                    'beneficiary_type' => $leg['beneficiary_type'],
                    'beneficiary_id' => $leg['beneficiary_id'],
                    'split_rule_type' => $leg['split_rule_type'],
                    'split_rule_value' => $leg['split_rule_value'],
                    'gross_amount' => $transaction->amount,
                    'fee_amount' => $transaction->fee_amount ?? 0,
                    'tax_amount' => $transaction->gst_amount ?? 0,
                    'net_amount' => $leg['net_amount'],
                    'currency' => $transaction->currency,
                    'status' => 'posted',
                    'idempotency_key' => $idempotencyKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($leg['beneficiary_type'] === 'vendor' && $firstVendorLeg === null) {
                    $firstVendorLeg = $leg;
                }
            }

            if ($firstVendorLeg) {
                $transaction->update([
                    'vendor_id' => $firstVendorLeg['beneficiary_id'] ?? $transaction->vendor_id,
                    'merchant_vendor_rate_snapshot_id' => $firstVendorLeg['vendor_rate_snapshot_id'] ?? null,
                    'merchant_vendor_split_percentage_snapshot' => $firstVendorLeg['vendor_split_percentage_snapshot'] ?? null,
                ]);
            }
        });
    }

    /**
     * Determine split legs based on metadata or payment link vendor.
     *
     * Returns [array $legs, float $totalNet].
     */
    protected function buildSplitLegs(Transaction $transaction, Order $order, ?PaymentLink $paymentLink): array
    {
        $net = (float) ($transaction->net_amount ?? $transaction->amount);
        if ($net <= 0) {
            return [[], 0.0];
        }

        $metadata = is_array($order->metadata) ? $order->metadata : [];

        $vendorSplits = [];
        if (! empty($metadata['vendor_splits']) && is_array($metadata['vendor_splits'])) {
            // Expected format: [['vendor_id' => int, 'percentage' => float], ...]
            foreach ($metadata['vendor_splits'] as $row) {
                if (! isset($row['vendor_id'], $row['percentage'])) {
                    continue;
                }
                $p = (float) $row['percentage'];
                if ($p <= 0) {
                    continue;
                }
                $vendorSplits[] = [
                    'vendor_id' => (int) $row['vendor_id'],
                    'percentage' => $p,
                ];
            }
        } elseif ($paymentLink && $paymentLink->vendor_id) {
            // Simple case: single vendor from payment link; use merchant->vendor configured share.
            $vendorPct = $this->vendorRateService->resolveVendorSharePercentage(
                (int) $transaction->merchant_id,
                (int) $paymentLink->vendor_id,
                (string) ($transaction->payment_method ?? 'card')
            );
            $vendorSnapshot = $this->vendorRateService->snapshotVendorRate(
                (int) $transaction->merchant_id,
                (int) $paymentLink->vendor_id,
                (string) ($transaction->payment_method ?? 'card'),
                $vendorPct
            );
            $vendorSplits[] = [
                'vendor_id' => (int) $paymentLink->vendor_id,
                'percentage' => $vendorPct,
                'vendor_rate_snapshot_id' => $vendorSnapshot->id,
            ];
        }

        if ($vendorSplits === []) {
            // No vendor split configured – only merchant leg
            return [[
                'beneficiary_type' => 'merchant',
                'beneficiary_id' => $transaction->merchant_id,
                'split_rule_type' => 'percentage',
                'split_rule_value' => 100.0,
                'net_amount' => round($net, 2),
                'vendor_rate_snapshot_id' => null,
                'vendor_split_percentage_snapshot' => null,
            ], $net];
        }

        $legs = [];
        $allocated = 0.0;
        foreach ($vendorSplits as $row) {
            $snapshotId = $row['vendor_rate_snapshot_id'] ?? null;
            if (! $snapshotId) {
                $snap = $this->vendorRateService->snapshotVendorRate(
                    (int) $transaction->merchant_id,
                    (int) $row['vendor_id'],
                    (string) ($transaction->payment_method ?? 'card'),
                    (float) $row['percentage']
                );
                $snapshotId = $snap->id;
            }
            $portion = round(($net * $row['percentage']) / 100, 2);
            $allocated += $portion;
            $legs[] = [
                'beneficiary_type' => 'vendor',
                'beneficiary_id' => $row['vendor_id'],
                'split_rule_type' => 'percentage',
                'split_rule_value' => $row['percentage'],
                'net_amount' => $portion,
                'vendor_rate_snapshot_id' => $snapshotId,
                'vendor_split_percentage_snapshot' => (float) $row['percentage'],
            ];
        }

        // Platform / merchant share is remainder (can be zero)
        $merchantShare = round($net - $allocated, 2);
        if ($merchantShare > 0.0) {
            $legs[] = [
                'beneficiary_type' => 'merchant',
                'beneficiary_id' => $transaction->merchant_id,
                'split_rule_type' => 'percentage',
                'split_rule_value' => max(0.0, 100.0 - array_sum(array_column($vendorSplits, 'percentage'))),
                'net_amount' => $merchantShare,
                'vendor_rate_snapshot_id' => null,
                'vendor_split_percentage_snapshot' => null,
            ];
            $allocated += $merchantShare;
        }

        return [$legs, $allocated];
    }
}

