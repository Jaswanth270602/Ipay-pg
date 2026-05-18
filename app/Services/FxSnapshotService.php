<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FxSnapshotService
{
    public function __construct(
        private readonly DashboardDisplayCurrencyService $displayCurrency
    ) {}

    public function captureTransactionSnapshot(Transaction $transaction): void
    {
        if ($transaction->amount_base !== null && $transaction->fx_captured_at !== null) {
            return;
        }

        if ($transaction->status !== 'success') {
            return;
        }

        $snapshot = $this->buildSnapshotForAmount(
            (float) $transaction->amount,
            (string) ($transaction->currency ?? config('ipay.default_currency', 'USD'))
        );

        if ($snapshot === null) {
            return;
        }

        $transaction->update($snapshot);
    }

    public function captureRefundSnapshot(Refund $refund): void
    {
        if ($refund->amount_base !== null && $refund->fx_captured_at !== null) {
            return;
        }

        if ($refund->status !== 'completed') {
            return;
        }

        $transaction = $refund->transaction;
        if ($transaction && $transaction->fx_rate_from_to_base > 0) {
            $rate = (float) $transaction->fx_rate_from_to_base;
            $refund->update([
                'amount_base' => round((float) $refund->amount / $rate, 6),
                'fx_rate_from_to_base' => $rate,
                'fx_rates_snapshot' => $transaction->fx_rates_snapshot,
                'fx_captured_at' => $transaction->fx_captured_at ?? now(),
            ]);

            return;
        }

        $currency = strtoupper(trim((string) ($refund->currency ?: $transaction?->currency ?: config('ipay.default_currency', 'USD'))));
        $snapshot = $this->buildSnapshotForAmount((float) $refund->amount, $currency);
        if ($snapshot === null) {
            return;
        }

        $refund->update([
            'amount_base' => $snapshot['amount_base'],
            'fx_rate_from_to_base' => $snapshot['fx_rate_from_to_base'],
            'fx_rates_snapshot' => $snapshot['fx_rates_snapshot'],
            'fx_captured_at' => $snapshot['fx_captured_at'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSnapshotForAmount(float $amount, string $currency): ?array
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
            'fx_captured_at' => now(),
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
