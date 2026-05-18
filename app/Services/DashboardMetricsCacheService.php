<?php

namespace App\Services;

use App\Support\DashboardFxContext;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsCacheService
{
    private const VERSION_KEY = 'dashboard_metrics_cache_version';

    public function version(): int
    {
        return max(1, (int) Cache::get(self::VERSION_KEY, 1));
    }

    public function invalidateAll(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::VERSION_KEY);
    }

    public function ttl(): int
    {
        return max(60, (int) config('ipay.dashboard_display.metrics_cache_ttl', 300));
    }

    public function remember(string $scope, array $parts, callable $callback): mixed
    {
        $key = $this->key($scope, $parts);

        return Cache::remember($key, $this->ttl(), $callback);
    }

    public function key(string $scope, array $parts): string
    {
        ksort($parts);

        return sprintf(
            'dashboard_metrics:v%d:%s:%s',
            $this->version(),
            $scope,
            md5((string) json_encode($parts))
        );
    }

    public function adminKey(bool $testMode, string $startDate, string $endDate, DashboardFxContext $ctx): string
    {
        return $this->key('admin', [
            'test' => $testMode ? 1 : 0,
            'start' => $startDate,
            'end' => $endDate,
            'currency' => $ctx->displayCurrency,
            'fx_mode' => $ctx->mode,
        ]);
    }

    public function merchantKey(int $merchantId, ?bool $testMode, DashboardFxContext $ctx): string
    {
        $testMode ??= \App\Support\PaymentViewMode::isTestMode();

        return $this->key('merchant', [
            'merchant_id' => $merchantId,
            'test' => $testMode ? 1 : 0,
            'currency' => $ctx->displayCurrency,
            'fx_mode' => $ctx->mode,
        ]);
    }

    public function resellerSummaryKey(int $resellerId, ?int $merchantId, ?string $from, ?string $to, DashboardFxContext $ctx, ?bool $testMode = null): string
    {
        return $this->key('reseller_summary', [
            'reseller_id' => $resellerId,
            'test' => ($testMode ?? \App\Support\PaymentViewMode::isTestMode()) ? 1 : 0,
            'merchant_id' => $merchantId ?? 0,
            'from' => $from ?? '',
            'to' => $to ?? '',
            'currency' => $ctx->displayCurrency,
            'fx_mode' => $ctx->mode,
        ]);
    }

    public function resellerMerchantSummaryKey(
        int $resellerId,
        ?int $merchantId,
        ?string $from,
        ?string $to,
        DashboardFxContext $ctx,
        int $page,
        int $perPage,
        string $search,
        ?bool $testMode = null
    ): string {
        return $this->key('reseller_merchant_summary', [
            'reseller_id' => $resellerId,
            'test' => ($testMode ?? \App\Support\PaymentViewMode::isTestMode()) ? 1 : 0,
            'merchant_id' => $merchantId ?? 0,
            'from' => $from ?? '',
            'to' => $to ?? '',
            'currency' => $ctx->displayCurrency,
            'fx_mode' => $ctx->mode,
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
        ]);
    }
}
