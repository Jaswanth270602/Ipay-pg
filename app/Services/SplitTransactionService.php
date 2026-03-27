<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\SplitTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SplitTransactionService
{
    /**
     * Create split transaction record for a successful payment.
     *
     * Business rules:
     * - No secondary merchant => 100% to primary merchant
     * - Secondary merchant present => split by fixed amount OR percentage
     * - Split must total 100%
     */
    public function createForSuccessfulTransaction(Transaction $transaction): SplitTransaction
    {
        if ($transaction->status !== 'success') {
            throw ValidationException::withMessages([
                'transaction' => ['Split can only be created for successful transactions.'],
            ]);
        }

        $existing = SplitTransaction::where('transaction_id', $transaction->id)->first();
        if ($existing) {
            return $existing;
        }

        $merchant = $transaction->merchant;
        if (!$merchant) {
            throw ValidationException::withMessages([
                'merchant' => ['Primary merchant not found for transaction.'],
            ]);
        }

        $config = $this->resolveSplitConfig($merchant, $transaction);
        $amounts = $this->calculateSplitAmounts((float) $transaction->amount, $config);

        DB::beginTransaction();
        try {
            $split = SplitTransaction::create([
                'transaction_id' => $transaction->id,
                'merchant_id' => $merchant->id,
                'split_id' => $this->generateSplitId($transaction),
                'order_id' => (string) ($transaction->order->order_id ?? $transaction->order_id ?? $transaction->txn_id),
                'total_amount' => $transaction->amount,
                'primary_amount' => $amounts['primary_amount'],
                'secondary_amount' => $amounts['secondary_amount'],
                'primary_merchant_id' => $merchant->id,
                'secondary_merchant_id' => $config['secondary_merchant_id'],
                'primary_percentage' => $amounts['primary_percentage'],
                'secondary_percentage' => $amounts['secondary_percentage'],
                'status' => 'completed',
                'notes' => $config['notes'],
            ]);

            DB::commit();

            Log::info('Split transaction auto-created', [
                'split_id' => $split->split_id,
                'transaction_id' => $transaction->id,
                'txn_id' => $transaction->txn_id,
                'primary_merchant_id' => $split->primary_merchant_id,
                'secondary_merchant_id' => $split->secondary_merchant_id,
                'primary_amount' => $split->primary_amount,
                'secondary_amount' => $split->secondary_amount,
            ]);

            return $split;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function resolveSplitConfig(Merchant $merchant, Transaction $transaction): array
    {
        $settings = $merchant->settings ?? [];
        $metadata = is_array($transaction->order?->metadata) ? $transaction->order->metadata : [];

        $secondaryMerchantId = $metadata['secondary_merchant_id']
            ?? $settings['split_secondary_merchant_id']
            ?? null;

        if (!$secondaryMerchantId) {
            return [
                'secondary_merchant_id' => null,
                'mode' => 'none',
                'value' => null,
                'notes' => 'Auto-split: 100% to primary merchant',
            ];
        }

        $secondaryMerchant = Merchant::find($secondaryMerchantId);
        if (!$secondaryMerchant || !$secondaryMerchant->isActive()) {
            throw ValidationException::withMessages([
                'secondary_merchant_id' => ['Secondary merchant is missing or inactive.'],
            ]);
        }

        $fixedAmount = $metadata['split_secondary_amount'] ?? $settings['split_secondary_amount'] ?? null;
        if ($fixedAmount !== null) {
            return [
                'secondary_merchant_id' => (int) $secondaryMerchantId,
                'mode' => 'fixed',
                'value' => (float) $fixedAmount,
                'notes' => 'Auto-split: fixed secondary amount',
            ];
        }

        $percentage = $metadata['split_secondary_percentage'] ?? $settings['split_secondary_percentage'] ?? 0;
        return [
            'secondary_merchant_id' => (int) $secondaryMerchantId,
            'mode' => 'percentage',
            'value' => (float) $percentage,
            'notes' => 'Auto-split: percentage based',
        ];
    }

    protected function calculateSplitAmounts(float $totalAmount, array $config): array
    {
        if ($config['secondary_merchant_id'] === null) {
            return [
                'primary_amount' => round($totalAmount, 2),
                'secondary_amount' => 0.00,
                'primary_percentage' => 100.00,
                'secondary_percentage' => 0.00,
            ];
        }

        if ($config['mode'] === 'fixed') {
            $secondaryAmount = round((float) $config['value'], 2);
            if ($secondaryAmount < 0 || $secondaryAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'split_secondary_amount' => ['Fixed secondary amount must be between 0 and total amount.'],
                ]);
            }
            $primaryAmount = round($totalAmount - $secondaryAmount, 2);
            $secondaryPercentage = $totalAmount > 0 ? round(($secondaryAmount / $totalAmount) * 100, 2) : 0.00;
            $primaryPercentage = round(100 - $secondaryPercentage, 2);

            return [
                'primary_amount' => $primaryAmount,
                'secondary_amount' => $secondaryAmount,
                'primary_percentage' => $primaryPercentage,
                'secondary_percentage' => $secondaryPercentage,
            ];
        }

        $secondaryPercentage = round((float) $config['value'], 2);
        if ($secondaryPercentage < 0 || $secondaryPercentage > 100) {
            throw ValidationException::withMessages([
                'split_secondary_percentage' => ['Secondary percentage must be between 0 and 100.'],
            ]);
        }

        $secondaryAmount = round(($totalAmount * $secondaryPercentage) / 100, 2);
        $primaryAmount = round($totalAmount - $secondaryAmount, 2);
        $primaryPercentage = round(100 - $secondaryPercentage, 2);

        if (round($primaryPercentage + $secondaryPercentage, 2) !== 100.00) {
            throw ValidationException::withMessages([
                'split' => ['Invalid split percentages. Total must be 100%.'],
            ]);
        }

        return [
            'primary_amount' => $primaryAmount,
            'secondary_amount' => $secondaryAmount,
            'primary_percentage' => $primaryPercentage,
            'secondary_percentage' => $secondaryPercentage,
        ];
    }

    protected function generateSplitId(Transaction $transaction): string
    {
        return 'SPL_' . strtoupper(now()->format('YmdHis')) . '_' . $transaction->id . '_' . strtoupper(substr(md5((string) $transaction->txn_id), 0, 6));
    }
}

