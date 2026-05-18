<?php

namespace App\Services;

use App\Support\DashboardFxContext;
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
    public function sumTransactionAmountsToDisplayCurrency(
        Builder|Relation $transactionQuery,
        ?array $usdRates = null,
        ?DashboardFxContext $ctx = null
    ): float {
        $ctx ??= new DashboardFxContext($this->displayCurrencyCode());
        $usdRates ??= $this->getUsdBasedRates();

        if ($ctx->isLive()) {
            return $this->sumLiveTransactionAmounts($transactionQuery, $usdRates, $ctx);
        }

        return $this->sumHistoricalTransactionAmounts($transactionQuery, $usdRates, $ctx);
    }

    private function sumLiveTransactionAmounts(Builder|Relation $transactionQuery, array $usdRates, DashboardFxContext $ctx): float
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $transactionQuery)->selectRaw(
            '`transactions`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates, $ctx->displayCurrency);
    }

    private function sumHistoricalTransactionAmounts(Builder|Relation $transactionQuery, array $usdRates, DashboardFxContext $ctx): float
    {
        $sum = 0.0;

        foreach (
            (clone $transactionQuery)
                ->whereNotNull('transactions.amount_base')
                ->select(['transactions.id', 'transactions.amount_base', 'transactions.fx_rates_snapshot'])
                ->orderBy('transactions.id')
                ->lazyById(500, 'transactions.id', 'id') as $row
        ) {
            $sum += $this->amountBaseToDisplay(
                (float) $row->amount_base,
                $row->fx_rates_snapshot,
                $ctx->displayCurrency,
                $usdRates
            );
        }

        $legacyQuery = (clone $transactionQuery)->whereNull('transactions.amount_base');
        if ($legacyQuery->exists()) {
            $sum += $this->sumLiveTransactionAmounts($legacyQuery, $usdRates, $ctx);
        }

        return round($sum, 2);
    }

    /**
     * Sum refund amounts (joining transactions for currency fallback), converted to display currency.
     *
     * @param  Builder|Relation  $refundQuery  Eloquent builder or relation (e.g. merchant refunds); must use `refunds` / `transactions` tables when joining
     */
    public function sumRefundAmountsToDisplayCurrency(
        Builder|Relation $refundQuery,
        ?array $usdRates = null,
        ?DashboardFxContext $ctx = null
    ): float {
        $ctx ??= new DashboardFxContext($this->displayCurrencyCode());
        $usdRates ??= $this->getUsdBasedRates();

        if ($ctx->isLive()) {
            return $this->sumLiveRefundAmounts($refundQuery, $usdRates, $ctx);
        }

        return $this->sumHistoricalRefundAmounts($refundQuery, $usdRates, $ctx);
    }

    private function sumLiveRefundAmounts(Builder|Relation $refundQuery, array $usdRates, DashboardFxContext $ctx): float
    {
        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $refundQuery)->selectRaw(
            '`refunds`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`refunds`.`currency`), ""), NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates, $ctx->displayCurrency);
    }

    private function sumHistoricalRefundAmounts(Builder|Relation $refundQuery, array $usdRates, DashboardFxContext $ctx): float
    {
        $sum = 0.0;

        foreach (
            (clone $refundQuery)
                ->whereNotNull('refunds.amount_base')
                ->select(['refunds.id', 'refunds.amount_base', 'refunds.fx_rates_snapshot'])
                ->orderBy('refunds.id')
                ->lazyById(500, 'refunds.id', 'id') as $row
        ) {
            $sum += $this->amountBaseToDisplay(
                (float) $row->amount_base,
                $row->fx_rates_snapshot,
                $ctx->displayCurrency,
                $usdRates
            );
        }

        $legacyQuery = (clone $refundQuery)->whereNull('refunds.amount_base');
        if ($legacyQuery->exists()) {
            $sum += $this->sumLiveRefundAmounts($legacyQuery, $usdRates, $ctx);
        }

        return round($sum, 2);
    }

    /**
     * Sum dispute amounts with currency fallback from the linked transaction.
     *
     * @param  Builder|Relation  $disputeQuery
     */
    public function sumDisputeAmountsToDisplayCurrency(
        Builder|Relation $disputeQuery,
        ?array $usdRates = null,
        ?DashboardFxContext $ctx = null
    ): float {
        $ctx ??= new DashboardFxContext($this->displayCurrencyCode());
        $usdRates ??= $this->getUsdBasedRates();

        $default = (string) config('ipay.default_currency', 'USD');
        $sub = (clone $disputeQuery)->selectRaw(
            '`disputes`.`amount` as sub_amount, COALESCE(NULLIF(TRIM(`disputes`.`currency`), ""), NULLIF(TRIM(`transactions`.`currency`), ""), ?) as agg_currency',
            [$default]
        );
        $rows = $this->aggregateCurrencySub($sub);

        return $this->sumConvertedCurrencyGroups($rows, 'agg_total', 'agg_currency', $usdRates, $ctx->displayCurrency);
    }

    /**
     * Per-day, per-normalized-currency success volume (for admin GTV chart). MySQL ONLY_FULL_GROUP_BY safe.
     *
     * @return Collection<int, object>
     */
    public function successfulTxnVolumeRowsByDayAndCurrency(
        Builder|Relation $transactionQuery,
        ?DashboardFxContext $ctx = null
    ): Collection {
        $ctx ??= new DashboardFxContext($this->displayCurrencyCode());

        if ($ctx->isHistorical()) {
            return $this->historicalTxnVolumeRowsByDay($transactionQuery, $ctx);
        }

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
     * @return Collection<int, object>
     */
    private function historicalTxnVolumeRowsByDay(Builder|Relation $transactionQuery, DashboardFxContext $ctx): Collection
    {
        $usdRates = $this->getUsdBasedRates();
        $byDate = [];

        foreach (
            (clone $transactionQuery)
                ->whereNotNull('transactions.amount_base')
                ->select(['transactions.id', 'transactions.created_at', 'transactions.amount_base', 'transactions.fx_rates_snapshot'])
                ->orderBy('transactions.id')
                ->lazyById(500, 'transactions.id', 'id') as $row
        ) {
            $day = $row->created_at?->format('Y-m-d') ?? substr((string) $row->created_at, 0, 10);
            $converted = $this->amountBaseToDisplay(
                (float) $row->amount_base,
                $row->fx_rates_snapshot,
                $ctx->displayCurrency,
                $usdRates
            );
            $byDate[$day] = ($byDate[$day] ?? 0) + $converted;
        }

        $legacyQuery = (clone $transactionQuery)->whereNull('transactions.amount_base');
        $legacyRows = $this->successfulTxnVolumeRowsByDayAndCurrency($legacyQuery, new DashboardFxContext($ctx->displayCurrency, DashboardFxContext::MODE_LIVE));
        foreach ($legacyRows as $row) {
            $day = $row->agg_date instanceof \DateTimeInterface
                ? $row->agg_date->format('Y-m-d')
                : substr((string) $row->agg_date, 0, 10);
            $converted = $this->sumConvertedCurrencyGroups(
                collect([$row]),
                'agg_total',
                'agg_currency',
                $usdRates,
                $ctx->displayCurrency
            );
            $byDate[$day] = ($byDate[$day] ?? 0) + $converted;
        }

        return collect($byDate)->map(function ($total, $date) use ($ctx) {
            return (object) [
                'agg_date' => $date,
                'agg_currency' => $ctx->displayCurrency,
                'agg_total' => $total,
            ];
        })->values();
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
    public function sumConvertedCurrencyGroups(
        Collection $rows,
        string $totalKey = 'agg_total',
        string $currencyKey = 'agg_currency',
        ?array $usdRates = null,
        ?string $targetCurrency = null
    ): float {
        $rates = $usdRates ?? $this->getUsdBasedRates();
        $target = strtoupper($targetCurrency ?? (string) config('ipay.dashboard_display.currency', 'KES'));
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
     * Convert a normalized base amount (USD) to display currency using the snapshot rate at payment time.
     *
     * @param  array<string, float>|null  $snapshot
     * @param  array<string, float>  $liveRates
     */
    public function amountBaseToDisplay(float $amountBase, array|string|null $snapshot, string $displayCurrency, array $liveRates): float
    {
        if ($amountBase == 0.0) {
            return 0.0;
        }

        $snapshot = $this->normalizeRatesSnapshot($snapshot);

        $target = strtoupper($displayCurrency);
        $base = strtoupper((string) config('ipay.fx.base_currency', 'USD'));

        if ($target === $base) {
            return $amountBase;
        }

        $rate = null;
        if ($snapshot !== null && isset($snapshot[$target])) {
            $rate = (float) $snapshot[$target];
        }
        if (($rate === null || $rate <= 0) && isset($liveRates[$target])) {
            $rate = (float) $liveRates[$target];
        }

        if ($rate === null || $rate <= 0) {
            Log::warning('Dashboard FX missing historical/display rate', ['target' => $target]);

            return 0.0;
        }

        return $amountBase * $rate;
    }

    /**
     * @return array<string, float>|null
     */
    private function normalizeRatesSnapshot(array|string|null $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);

            return is_array($decoded) ? $this->normalizeRatesSnapshot($decoded) : null;
        }

        if (! is_array($snapshot)) {
            return null;
        }

        $normalized = [];
        foreach ($snapshot as $code => $rate) {
            $normalized[strtoupper((string) $code)] = (float) $rate;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function fxOptionsPayload(): array
    {
        return [
            'supported_currencies' => $this->supportedDisplayCurrencies(),
            'default_display_currency' => $this->displayCurrencyCode(),
            'default_fx_mode' => (string) config('ipay.dashboard_display.default_fx_mode', DashboardFxContext::MODE_HISTORICAL),
            'fx_modes' => [
                ['id' => DashboardFxContext::MODE_HISTORICAL, 'label' => 'Historical rate (at payment time)'],
                ['id' => DashboardFxContext::MODE_LIVE, 'label' => 'Live current rate'],
            ],
            'fx_base_currency' => strtoupper((string) config('ipay.fx.base_currency', 'USD')),
        ];
    }

    /**
     * @return list<string>
     */
    public function supportedDisplayCurrencies(): array
    {
        return array_values(config('ipay.dashboard_display.supported_currencies', ['USD', 'INR', 'KES']));
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
