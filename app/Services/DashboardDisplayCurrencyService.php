<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardDisplayCurrencyService
{
    private const OPEN_ER_LATEST_TEMPLATE = 'https://open.er-api.com/v6/latest/USD';

    private const STALE_BACKUP_KEY = 'dashboard_fx_rates_usd_backup';

    /**
     * Sum transaction `amount` values grouped by currency, converted to the display currency (default KES).
     */
    public function sumTransactionAmountsToDisplayCurrency(Builder|Relation $transactionQuery, ?array $usdRates = null): float
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $transactionQuery)->selectRaw(
            '`transactions`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates);
    }

    /**
     * Sum refund amounts (joining transactions for currency fallback), converted to display currency.
     *
     * @param  Builder|Relation  $refundQuery  Eloquent builder or relation (e.g. merchant refunds); must use `refunds` / `transactions` tables when joining
     */
    public function sumRefundAmountsToDisplayCurrency(Builder|Relation $refundQuery, ?array $usdRates = null): float
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $refundQuery)->selectRaw(
            '`refunds`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`refunds`.`currency`), ""), NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates);
    }

    /**
     * Sum dispute amounts with currency fallback from the linked transaction.
     *
     * @param  Builder|Relation  $disputeQuery
     */
    public function sumDisputeAmountsToDisplayCurrency(Builder|Relation $disputeQuery, ?array $usdRates = null): float
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $disputeQuery)->selectRaw(
            '`disputes`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`disputes`.`currency`), ""), NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates);
    }

    /**
     * Per-day, per-normalized-currency success volume (for admin GTV chart). MySQL ONLY_FULL_GROUP_BY safe.
     *
     * @return Collection<int, object>
     */
    public function successfulTxnVolumeRowsByDayAndCurrency(Builder|Relation $transactionQuery): Collection
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $transactionQuery)->selectRaw(
            'DATE(`transactions`.`created_at`) as agg_date, `transactions`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );

        return $sub->getConnection()->query()
            ->fromSub($sub, 'day_currency_sub')
            ->selectRaw('agg_date, agg_currency, SUM(sub_amount) as agg_total')
            ->groupByRaw('agg_date, agg_currency')
            ->get();
    }

    /**
     * Per payment method, per-normalized-currency aggregates (for admin doughnut / API). MySQL ONLY_FULL_GROUP_BY safe.
     *
     * @return Collection<int, object>
     */
    public function successfulTxnVolumeRowsByPaymentMethodAndCurrency(Builder|Relation $transactionQuery): Collection
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $transactionQuery)->selectRaw(
            '`transactions`.`payment_method` as payment_method, `transactions`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );

        return $sub->getConnection()->query()
            ->fromSub($sub, 'pm_currency_sub')
            ->selectRaw('payment_method, agg_currency, SUM(sub_amount) as agg_total, COUNT(*) as cnt')
            ->groupByRaw('payment_method, agg_currency')
            ->get();
    }

    /**
     * Outer aggregate: normalized currency buckets (avoids ONLY_FULL_GROUP_BY on TRIM(currency) expressions).
     *
     * @param  Builder|Relation  $currencySub  Inner query rows must expose sub_amount and agg_currency
     * @return Collection<int, object>
     */
    private function aggregateCurrencySub(Builder|Relation $currencySub): Collection
    {
        return $currencySub->getConnection()->query()
            ->fromSub($currencySub, 'currency_sub')
            ->selectRaw('agg_currency, SUM(sub_amount) as agg_total')
            ->groupBy('agg_currency')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    public function sumConvertedCurrencyGroups(Collection $rows, string $totalKey = 'agg_total', string $currencyKey = 'agg_currency', ?array $usdRates = null): float
    {
        $rates = $usdRates ?? $this->getUsdBasedRates();
        $target = strtoupper((string) config('ipay.dashboard_display.currency', 'KES'));
        $sum = 0.0;

        foreach ($rows as $row) {
            $codeRaw = $row->{$currencyKey} ?? null;
            $code = strtoupper(trim((string) $codeRaw));
            if ($code === '') {
                $code = strtoupper((string) config('ipay.default_currency', 'USD'));
            }
            $total = (float) ($row->{$totalKey} ?? 0);
            $sum += $this->convertAmountUsingUsdBaseRates($total, $code, $target, $rates);
        }

        return round($sum, 2);
    }

    /**
     * Rates keyed by ISO code: units of each currency per 1 USD (open.er-api.com /v6/latest/USD).
     *
     * @return array<string, float>
     */
    public function getUsdBasedRates(): array
    {
        $ttl = (int) config('ipay.dashboard_display.fx_cache_ttl', 3600);
        $cacheKey = 'dashboard_fx_rates_usd_v1';

        $rates = Cache::get($cacheKey);
        if (is_array($rates) && $rates !== []) {
            return $rates;
        }

        try {
            $rates = $this->fetchUsdRatesFromApi();
            Cache::put($cacheKey, $rates, $ttl);
            Cache::forever(self::STALE_BACKUP_KEY, $rates);

            return $rates;
        } catch (\Throwable $e) {
            Log::warning('Dashboard FX fetch failed, using stale or empty rates', [
                'message' => $e->getMessage(),
            ]);
            $stale = Cache::get(self::STALE_BACKUP_KEY, []);

            return is_array($stale) ? $stale : [];
        }
    }

    /**
     * @return array<string, float>
     */
    private function fetchUsdRatesFromApi(): array
    {
        $timeout = (int) config('ipay.dashboard_display.fx_http_timeout', 10);
        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get(self::OPEN_ER_LATEST_TEMPLATE);

        if (! $response->successful()) {
            throw new \RuntimeException('FX HTTP '.$response->status());
        }

        $data = $response->json();
        if (($data['result'] ?? '') !== 'success') {
            throw new \RuntimeException('FX API result not success');
        }

        $rates = $data['rates'] ?? $data['conversion_rates'] ?? null;
        if (! is_array($rates) || $rates === []) {
            throw new \RuntimeException('FX API missing rates');
        }

        $normalized = [];
        foreach ($rates as $code => $rate) {
            $normalized[strtoupper((string) $code)] = (float) $rate;
        }

        return $normalized;
    }

    /**
     * @param  array<string, float>  $rates
     */
    private function convertAmountUsingUsdBaseRates(float $amount, string $fromCurrency, string $toCurrency, array $rates): float
    {
        if ($amount == 0.0) {
            return 0.0;
        }

        $from = strtoupper($fromCurrency);
        $to = strtoupper($toCurrency);

        if ($from === $to) {
            return $amount;
        }

        $manual = config('ipay.dashboard_display.manual_rates_to_usd', []);
        $fromRate = $rates[$from] ?? (is_array($manual) ? (float) ($manual[$from] ?? 0) : 0);
        $toRate = $rates[$to] ?? (is_array($manual) ? (float) ($manual[$to] ?? 0) : 0);

        if ($fromRate <= 0 || $toRate <= 0) {
            Log::warning('Dashboard FX missing rate; skipping amount in aggregate', [
                'from' => $from,
                'to' => $to,
                'amount' => $amount,
            ]);

            return 0.0;
        }

        // USD is base: amount_usd = amount_in_from / rates[from]; amount_in_to = amount_usd * rates[to]
        return ($amount / $fromRate) * $toRate;
    }

    public function displayCurrencyCode(): string
    {
        return strtoupper((string) config('ipay.dashboard_display.currency', 'KES'));
    }
}
