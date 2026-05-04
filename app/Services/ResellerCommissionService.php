<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\Merchant;
use App\Models\MerchantResellerSplit;
use App\Models\Refund;
use App\Models\Reseller;
use App\Models\ResellerCommission;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerCommissionService
{
    /**
     * Create commission row when a payment succeeds (idempotent per transaction).
     * Reseller share % applies to the transaction merchant fee (`fee_amount`, pre-VAT / TDR), not gross amount.
     */
    public function recordForSuccessfulTransaction(Transaction $transaction): ?ResellerCommission
    {
        if ($transaction->status !== 'success') {
            return null;
        }

        $merchant = $transaction->merchant ?? Merchant::find($transaction->merchant_id);
        if (! $merchant) {
            return null;
        }

        $resellerId = $merchant->reseller_id;
        if (! $resellerId) {
            $resellerId = $merchant->resellers()->wherePivot('status', 'active')->first()?->id
                ?? $merchant->resellers()->first()?->id;
        }

        if (! $resellerId) {
            return null;
        }

        if (! $merchant->reseller_id) {
            $merchant->forceFill(['reseller_id' => $resellerId])->saveQuietly();
        }

        $reseller = Reseller::query()->find($resellerId);
        if (! $reseller || $reseller->status !== 'active') {
            return null;
        }

        // Commission base = merchant fee before GST (`fee_amount` / TDR). Not gross txn, not GST.
        $feeBase = (float) $transaction->fee_amount;
        if ($feeBase <= 0) {
            return null;
        }

        $split = $this->resolveMerchantSplit($merchant, (string) ($transaction->payment_method ?? 'card'));
        if (! $split || (int) $split->reseller_id !== (int) $reseller->id) {
            return null;
        }

        $commission = $this->calculateCommissionAmount((float) $split->reseller_share_pct, $feeBase);
        if ($commission <= 0) {
            return null;
        }

        try {
            return ResellerCommission::firstOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'reseller_id' => $reseller->id,
                    'merchant_id' => $merchant->id,
                    'commission_amount' => round($commission, 2),
                    'reversed_amount' => 0,
                    'status' => 'pending',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Reseller commission record failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  float  $merchantFeeAmount  Merchant TDR / pre-GST fee amount for the transaction
     */
    public function calculateCommissionAmount(float $resellerSharePct, float $merchantFeeAmount): float
    {
        if ($merchantFeeAmount <= 0 || $resellerSharePct <= 0) {
            return 0.0;
        }

        return round($merchantFeeAmount * ($resellerSharePct / 100), 2);
    }

    protected function resolveMerchantSplit(Merchant $merchant, string $paymentMethod): ?MerchantResellerSplit
    {
        $baseRate = BaseRate::active()
            ->ofType(BaseRate::RATE_TYPE_MERCHANT)
            ->where('entity_id', $merchant->id)
            ->where('entity_type', 'merchant')
            ->forPaymentMethod($paymentMethod)
            ->forServiceType(BaseRate::SERVICE_TYPE_PAYMENT)
            ->forTransactionType(BaseRate::TRANSACTION_TYPE_DOMESTIC)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $baseRate) {
            return null;
        }

        return MerchantResellerSplit::query()
            ->where('base_rate_id', $baseRate->id)
            ->where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Apply proportional reversal when a refund completes.
     */
    public function applyRefund(Refund $refund): void
    {
        if ($refund->status !== 'completed') {
            return;
        }

        $row = ResellerCommission::query()->where('transaction_id', $refund->transaction_id)->first();
        if (! $row) {
            return;
        }

        $transaction = $refund->transaction ?? Transaction::find($refund->transaction_id);
        if (! $transaction) {
            return;
        }

        $txnAmount = (float) $transaction->amount;
        if ($txnAmount <= 0) {
            return;
        }

        $ratio = min(1, (float) $refund->amount / $txnAmount);
        $reverseIncrement = round((float) $row->commission_amount * $ratio, 2);

        DB::transaction(function () use ($row, $reverseIncrement) {
            $row->refresh();
            $maxReverse = (float) $row->commission_amount - (float) $row->reversed_amount;
            $applied = min($reverseIncrement, max(0, $maxReverse));
            $row->reversed_amount = round((float) $row->reversed_amount + $applied, 2);

            $remaining = (float) $row->commission_amount - (float) $row->reversed_amount;
            if ($remaining <= 0.009) {
                $row->status = 'reversed';
            } elseif ($row->reversed_amount > 0) {
                $row->status = 'partially_reversed';
            }

            $row->save();
        });
    }
}
