<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class FxSnapshotService
{
    public function __construct(
        private readonly DashboardDisplayCurrencyService $displayCurrency
    ) {}

    public function captureTransactionSnapshot(Transaction $transaction, ?CarbonInterface $capturedAt = null): bool
    {
        if ($transaction->amount_base !== null && $transaction->fx_captured_at !== null) {
            return false;
        }

        if ($transaction->status !== 'success') {
            return false;
        }

        $snapshot = $this->buildSnapshotForAmount(
            (float) $transaction->amount,
            (string) ($transaction->currency ?? config('ipay.default_currency', 'USD')),
            $capturedAt ?? now()
        );

        if ($snapshot === null) {
            return false;
        }

        $transaction->update($snapshot);

        return true;
    }

    /**
     * @return array{transactions: int, refunds: int, skipped_transactions: int, skipped_refunds: int}
     */
    public function backfillForMerchant(int $merchantId, bool $testMode, int $chunkSize = 200): array
    {
        $chunkSize = max(50, $chunkSize);
        $stats = [
            'transactions' => 0,
            'refunds' => 0,
            'skipped_transactions' => 0,
            'skipped_refunds' => 0,
        ];

        Transaction::query()
            ->where('merchant_id', $merchantId)
            ->where('test_mode', $testMode)
            ->where('status', 'success')
            ->whereNull('amount_base')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($transactions) use (&$stats) {
                foreach ($transactions as $transaction) {
                    if ($this->captureTransactionSnapshot($transaction, $transaction->created_at)) {
                        $stats['transactions']++;
                    } else {
                        $stats['skipped_transactions']++;
                    }
                }
            });

        Refund::query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'completed')
            ->whereNull('amount_base')
            ->whereHas('transaction', fn ($q) => $q->where('test_mode', $testMode))
            ->with('transaction')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($refunds) use (&$stats) {
                foreach ($refunds as $refund) {
                    if ($this->captureRefundSnapshot($refund, $refund->created_at)) {
                        $stats['refunds']++;
                    } else {
                        $stats['skipped_refunds']++;
                    }
                }
            });

        return $stats;
    }

    public function captureRefundSnapshot(Refund $refund, ?CarbonInterface $capturedAt = null): bool
    {
        if ($refund->amount_base !== null && $refund->fx_captured_at !== null) {
            return false;
        }

        if ($refund->status !== 'completed') {
            return false;
        }

        $transaction = $refund->transaction;
        if ($transaction && $transaction->fx_rate_from_to_base > 0) {
            $rate = (float) $transaction->fx_rate_from_to_base;
            $refund->update([
                'amount_base' => round((float) $refund->amount / $rate, 6),
                'fx_rate_from_to_base' => $rate,
                'fx_rates_snapshot' => $transaction->fx_rates_snapshot,
                'fx_captured_at' => $transaction->fx_captured_at ?? ($capturedAt ?? now()),
            ]);

            return true;
        }

        $currency = strtoupper(trim((string) ($refund->currency ?: $transaction?->currency ?: config('ipay.default_currency', 'USD'))));
        $snapshot = $this->buildSnapshotForAmount((float) $refund->amount, $currency, $capturedAt ?? now());
        if ($snapshot === null) {
            return false;
        }

        $refund->update([
            'amount_base' => $snapshot['amount_base'],
            'fx_rate_from_to_base' => $snapshot['fx_rate_from_to_base'],
            'fx_rates_snapshot' => $snapshot['fx_rates_snapshot'],
            'fx_captured_at' => $snapshot['fx_captured_at'],
        ]);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSnapshotForAmount(float $amount, string $currency, CarbonInterface $capturedAt): ?array
    {
        $rates = $this->displayCurrency->getUsdBasedRates();
        $from = strtoupper(trim($currency));
        if ($from === '') {
            $from = strtoupper((string) config('ipay.default_currency', 'USD'));
        }

        $fromRate = $this->resolveRateForCurrency($from, $rates);
        if ($fromRate <= 0) {
            Log::warning('FX snapshot skipped: missing rate for currency', ['currency' => $from]);

            return null;
        }

        $baseCurrency = strtoupper((string) config('ipay.fx.base_currency', 'USD'));

        return [
            'amount_base' => round($amount / $fromRate, 6),
            'fx_base_currency' => $baseCurrency,
            'fx_rate_from_to_base' => $fromRate,
            'fx_rates_snapshot' => $rates,
            'fx_captured_at' => $capturedAt,
        ];
    }

    /**
     * @param  array<string, float>  $rates
     */
    private function resolveRateForCurrency(string $currency, array $rates): float
    {
        if ($currency === 'USD') {
            return 1.0;
        }

        $rate = $rates[$currency] ?? 0.0;
        if ($rate > 0) {
            return (float) $rate;
        }

        $manual = config('ipay.dashboard_display.manual_rates_to_usd', []);

        return is_array($manual) ? (float) ($manual[$currency] ?? 0) : 0.0;
    }
}
