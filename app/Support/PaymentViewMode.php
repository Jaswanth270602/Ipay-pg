<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves TEST vs LIVE viewing scope for dashboards, grids, and aggregates.
 *
 * - Merchant: persisted on merchants.test_mode (toggle updates DB).
 * - Admin: session admin_view_mode (default test).
 * - Reseller: session reseller_view_mode (default test).
 */
class PaymentViewMode
{
    public const SESSION_ADMIN = 'admin_view_mode';

    public const SESSION_RESELLER = 'reseller_view_mode';

    public static function isTestMode(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return true;
        }

        if ($user->isMerchant() && $user->merchant) {
            return (bool) $user->merchant->test_mode;
        }

        if ($user->isAdmin()) {
            return strtolower((string) session(self::SESSION_ADMIN, 'test')) === 'test';
        }

        if ($user->isReseller()) {
            return strtolower((string) session(self::SESSION_RESELLER, 'test')) === 'test';
        }

        return true;
    }

    public static function modeLabel(?User $user = null): string
    {
        return self::isTestMode($user) ? 'TEST' : 'LIVE';
    }

    public static function setSessionMode(string $role, string $mode): void
    {
        $mode = strtolower($mode);
        if (! in_array($mode, ['test', 'live'], true)) {
            throw new \InvalidArgumentException('Mode must be test or live');
        }

        match ($role) {
            'admin' => session([self::SESSION_ADMIN => $mode]),
            'reseller' => session([self::SESSION_RESELLER => $mode]),
            default => throw new \InvalidArgumentException('Unknown role for view mode'),
        };
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function scopeTransactions(Builder $query, ?bool $testMode = null, string $column = 'test_mode'): Builder
    {
        return $query->where($column, $testMode ?? self::isTestMode());
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function scopeRefundsViaTransaction(Builder $query, ?bool $testMode = null): Builder
    {
        $testMode ??= self::isTestMode();

        return $query->whereHas('transaction', function ($q) use ($testMode) {
            $q->where('test_mode', $testMode);
        });
    }

    /**
     * When refunds query already joins transactions.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function scopeJoinedTransactionTestMode(Builder $query, ?bool $testMode = null): Builder
    {
        return $query->where('transactions.test_mode', $testMode ?? self::isTestMode());
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function scopeQueryBuilderTransactions($query, ?bool $testMode = null, string $table = 'transactions'): mixed
    {
        return $query->where("{$table}.test_mode", $testMode ?? self::isTestMode());
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function scopeResellerCommissions(Builder $query, ?bool $testMode = null): Builder
    {
        $testMode ??= self::isTestMode();

        return $query->whereHas('transaction', function ($q) use ($testMode) {
            $q->where('test_mode', $testMode);
        });
    }

    public static function cacheTestFlag(?User $user = null): int
    {
        return self::isTestMode($user) ? 1 : 0;
    }
}
