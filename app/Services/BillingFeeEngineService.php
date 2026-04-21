<?php

namespace App\Services;

use App\Models\BillingFeeRule;
use App\Models\FeeLedgerEntry;
use App\Models\Refund;
use App\Models\RollingReserveHold;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingFeeEngineService
{
    public function applyTransactionFees(Transaction $transaction): void
    {
        $transaction->loadMissing('merchant');
        if (! $transaction->merchant) {
            return;
        }

        $status = $transaction->status === 'success' ? 'success' : ($transaction->status === 'failed' ? 'failed' : 'all');
        $rules = $this->getApplicableRules(
            merchantId: (int) $transaction->merchant_id,
            eventType: 'transaction',
            paymentMethod: $transaction->payment_method,
            currency: $transaction->currency,
            status: $status
        );

        if ($rules->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($transaction, $rules): void {
            $additionalDeduction = 0.0;

            foreach ($rules as $rule) {
                $amount = $this->calculateFeeAmount($rule, (float) $transaction->amount);
                if ($amount <= 0) {
                    continue;
                }

                // Rolling reserve is handled separately to store hold/release lifecycle.
                if (($rule->definition->category ?? '') === 'rolling_reserve') {
                    $reserve = $this->applyRollingReserveHold($transaction, $rule, $amount);
                    $amount = $reserve['hold_amount'];
                    if ($amount <= 0) {
                        continue;
                    }
                }

                $entry = $this->recordLedgerEntry(
                    rule: $rule,
                    sourceType: 'transaction',
                    sourceId: (int) $transaction->id,
                    eventType: 'transaction',
                    basisAmount: (float) $transaction->amount,
                    currency: (string) $transaction->currency,
                    amount: $amount,
                    metadata: [
                        'merchant_id' => $transaction->merchant_id,
                        'reseller_id' => $transaction->merchant->reseller_id,
                        'transaction_id' => $transaction->id,
                        'txn_id' => $transaction->txn_id,
                        'payment_status' => $transaction->status,
                    ]
                );

                if ($entry && $entry->entry_direction === 'debit' && $entry->bill_to === 'merchant') {
                    $additionalDeduction += (float) $entry->amount;
                }
            }

            if ($additionalDeduction > 0) {
                $otherFees = round(((float) $transaction->other_fees) + $additionalDeduction, 2);
                $transaction->update([
                    'other_fees' => $otherFees,
                    'net_amount' => round((float) $transaction->amount - (float) $transaction->fee_amount - (float) $transaction->gst_amount - $otherFees, 2),
                ]);
            }
        });
    }

    public function applyRefundFees(Refund $refund): void
    {
        $refund->loadMissing('transaction');
        if (! $refund->transaction) {
            return;
        }

        $rules = $this->getApplicableRules(
            merchantId: (int) $refund->merchant_id,
            eventType: 'refund',
            paymentMethod: $refund->transaction->payment_method,
            currency: $refund->currency,
            status: $refund->status === 'failed' ? 'failed' : 'success'
        );

        if ($rules->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($refund, $rules): void {
            $refundFee = 0.0;
            foreach ($rules as $rule) {
                $amount = $this->calculateFeeAmount($rule, (float) $refund->amount);
                if ($amount <= 0) {
                    continue;
                }

                $entry = $this->recordLedgerEntry(
                    rule: $rule,
                    sourceType: 'refund',
                    sourceId: (int) $refund->id,
                    eventType: 'refund',
                    basisAmount: (float) $refund->amount,
                    currency: (string) $refund->currency,
                    amount: $amount,
                    metadata: [
                        'merchant_id' => $refund->merchant_id,
                        'reseller_id' => $refund->transaction->merchant->reseller_id ?? null,
                        'refund_id' => $refund->refund_id,
                        'transaction_id' => $refund->transaction_id,
                        'status' => $refund->status,
                    ]
                );

                if ($entry && $entry->entry_direction === 'debit' && $entry->bill_to === 'merchant') {
                    $refundFee += (float) $entry->amount;
                }
            }

            if ($refundFee > 0) {
                $refund->update([
                    'fee_amount' => round($refundFee, 2),
                    'net_debit_amount' => round((float) $refund->amount + $refundFee, 2),
                ]);
            }
        });
    }

    public function buildSettlementFeeBreakdown(Collection $transactionIds): array
    {
        if ($transactionIds->isEmpty()) {
            return [];
        }

        return FeeLedgerEntry::query()
            ->where('bill_to', 'merchant')
            ->where('status', 'posted')
            ->where(function ($query) use ($transactionIds): void {
                $query->where(function ($transactionQuery) use ($transactionIds): void {
                    $transactionQuery->where('source_type', 'transaction')
                        ->whereIn('source_id', $transactionIds);
                })->orWhere(function ($refundQuery) use ($transactionIds): void {
                    $refundQuery->where('source_type', 'refund')
                        ->whereIn('source_id', function ($sub) use ($transactionIds): void {
                            $sub->select('id')
                                ->from('refunds')
                                ->whereIn('transaction_id', $transactionIds)
                                ->where('status', 'completed');
                        });
                });
            })
            ->selectRaw('fee_code, fee_name, event_type, SUM(CASE WHEN entry_direction = "debit" THEN amount ELSE -amount END) as total_amount')
            ->groupBy('fee_code', 'fee_name', 'event_type')
            ->orderBy('event_type')
            ->orderBy('fee_name')
            ->get()
            ->map(fn ($row) => [
                'fee_code' => $row->fee_code,
                'fee_name' => $row->fee_name,
                'event_type' => $row->event_type,
                'amount' => round((float) $row->total_amount, 2),
            ])
            ->all();
    }

    public function releaseDueReservesForMerchant(int $merchantId, ?\Carbon\Carbon $asOf = null): void
    {
        $asOf = $asOf ?? now();

        $holds = RollingReserveHold::query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'held')
            ->where('release_due_at', '<=', $asOf)
            ->get();

        foreach ($holds as $hold) {
            $exists = FeeLedgerEntry::query()
                ->where('fee_rule_id', $hold->fee_rule_id)
                ->where('source_type', 'transaction')
                ->where('source_id', $hold->transaction_id)
                ->where('entry_direction', 'credit')
                ->exists();

            if (! $exists) {
                $rule = $hold->rule;
                FeeLedgerEntry::create([
                    'merchant_id' => $hold->merchant_id,
                    'partner_id' => $rule?->partner_id,
                    'reseller_id' => null,
                    'fee_definition_id' => $rule?->fee_definition_id,
                    'fee_rule_id' => $hold->fee_rule_id,
                    'source_type' => 'transaction',
                    'source_id' => $hold->transaction_id,
                    'event_type' => 'reserve_release',
                    'fee_code' => 'rolling_reserve_release',
                    'fee_name' => 'Rolling Reserve Release',
                    'bill_to' => 'merchant',
                    'entry_direction' => 'credit',
                    'currency' => $hold->currency,
                    'basis_amount' => (float) $hold->hold_amount,
                    'percentage_rate' => null,
                    'fixed_amount' => null,
                    'amount' => (float) $hold->hold_amount,
                    'referral_commission_amount' => 0,
                    'status' => 'posted',
                    'metadata' => [
                        'rolling_reserve_hold_id' => $hold->id,
                        'released_at' => $asOf->toDateTimeString(),
                    ],
                ]);
            }

            $hold->update([
                'status' => 'released',
                'released_at' => $asOf,
            ]);
        }
    }

    protected function getApplicableRules(
        int $merchantId,
        string $eventType,
        ?string $paymentMethod,
        ?string $currency,
        string $status
    ): Collection {
        $now = now();

        return BillingFeeRule::query()
            ->with('definition')
            ->whereHas('definition', function ($query): void {
                $query->where('is_active', true);
            })
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->where(function ($query) use ($merchantId): void {
                $query->where('merchant_id', $merchantId)
                    ->orWhereNull('merchant_id');
            })
            ->where(function ($query) use ($status): void {
                $query->where('applies_to_status', 'all')
                    ->orWhere('applies_to_status', $status);
            })
            ->where(function ($query) use ($paymentMethod): void {
                $query->whereNull('payment_method')
                    ->orWhere('payment_method', $paymentMethod);
            })
            ->where(function ($query) use ($currency): void {
                $query->whereNull('currency')
                    ->orWhere('currency', $currency);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $now);
            })
            ->orderBy('priority')
            ->orderByDesc('merchant_id')
            ->get();
    }

    protected function calculateFeeAmount(BillingFeeRule $rule, float $basisAmount): float
    {
        $percentageRate = (float) ($rule->percentage_rate ?? 0);
        $fixedAmount = (float) ($rule->fixed_amount ?? 0);

        $amount = match ($rule->pricing_model) {
            'fixed' => $fixedAmount,
            'percentage_plus_fixed' => (($basisAmount * $percentageRate) / 100) + $fixedAmount,
            default => ($basisAmount * $percentageRate) / 100,
        };

        if ($rule->minimum_amount !== null) {
            $amount = max($amount, (float) $rule->minimum_amount);
        }
        if ($rule->maximum_amount !== null) {
            $amount = min($amount, (float) $rule->maximum_amount);
        }

        return round($amount, 4);
    }

    protected function recordLedgerEntry(
        BillingFeeRule $rule,
        string $sourceType,
        int $sourceId,
        string $eventType,
        float $basisAmount,
        string $currency,
        float $amount,
        array $metadata
    ): ?FeeLedgerEntry {
        $existing = FeeLedgerEntry::query()
            ->where('fee_rule_id', $rule->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('entry_direction', 'debit')
            ->first();

        if ($existing) {
            return $existing;
        }

        return FeeLedgerEntry::create([
            'merchant_id' => $rule->merchant_id ?? ($metadata['merchant_id'] ?? null),
            'partner_id' => $rule->partner_id,
            'reseller_id' => $metadata['reseller_id'] ?? null,
            'fee_definition_id' => $rule->fee_definition_id,
            'fee_rule_id' => $rule->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'event_type' => $eventType,
            'fee_code' => (string) ($rule->definition->code ?? 'custom_fee'),
            'fee_name' => (string) ($rule->definition->name ?? 'Custom Fee'),
            'bill_to' => $rule->bill_to ?? 'merchant',
            'entry_direction' => 'debit',
            'currency' => $currency ?: 'INR',
            'basis_amount' => $basisAmount,
            'percentage_rate' => $rule->percentage_rate,
            'fixed_amount' => $rule->fixed_amount,
            'amount' => $amount,
            'referral_commission_amount' => $this->calculateReferralCommission($rule, $amount),
            'status' => 'posted',
            'metadata' => $metadata,
        ]);
    }

    protected function calculateReferralCommission(BillingFeeRule $rule, float $feeAmount): float
    {
        $pct = (float) ($rule->referral_commission_percentage ?? 0);
        $fixed = (float) ($rule->referral_commission_fixed ?? 0);
        return round((($feeAmount * $pct) / 100) + $fixed, 4);
    }

    /**
     * @return array{hold_amount: float}
     */
    protected function applyRollingReserveHold(Transaction $transaction, BillingFeeRule $rule, float $calculatedAmount): array
    {
        $cap = (float) ($rule->rolling_reserve_cap ?? 0);
        $currentHeld = (float) RollingReserveHold::query()
            ->where('merchant_id', $transaction->merchant_id)
            ->where('currency', $transaction->currency)
            ->where('status', 'held')
            ->sum('hold_amount');

        $holdAmount = $calculatedAmount;
        if ($cap > 0) {
            $remaining = max(0, $cap - $currentHeld);
            $holdAmount = min($holdAmount, $remaining);
        }

        if ($holdAmount <= 0) {
            return ['hold_amount' => 0.0];
        }

        $heldAt = now();
        $releaseDueAt = $heldAt->copy()->addDays((int) ($rule->hold_days ?? 180));

        RollingReserveHold::create([
            'merchant_id' => $transaction->merchant_id,
            'transaction_id' => $transaction->id,
            'fee_rule_id' => $rule->id,
            'hold_amount' => $holdAmount,
            'currency' => $transaction->currency,
            'held_at' => $heldAt,
            'release_due_at' => $releaseDueAt,
            'status' => 'held',
            'metadata' => [
                'txn_id' => $transaction->txn_id,
                'payment_method' => $transaction->payment_method,
                'rule_cap' => $cap,
            ],
        ]);

        return ['hold_amount' => round($holdAmount, 4)];
    }
}

