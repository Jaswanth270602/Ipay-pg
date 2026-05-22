<?php

namespace App\Http\Controllers;

use App\Services\DashboardDisplayCurrencyService;
use App\Services\DashboardMetricsCacheService;
use App\Services\FxSnapshotService;
use App\Support\DashboardFxContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDisplayCurrencyService $dashboardDisplayCurrency,
        private readonly DashboardMetricsCacheService $metricsCache,
        private readonly FxSnapshotService $fxSnapshotService,
    ) {}

    /**
     * Show the dashboard.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('admin.dashboard', [
                'user' => $user,
            ]);
        }

        if ($user->isMerchant()) {
            $merchant = $user->merchant;

            // Scope all stats to the merchant's current mode (TEST vs LIVE)
            // so that the dashboard numbers differ between Test and Live.
            $transactionsQuery = $merchant->transactions()
                ->where('test_mode', $merchant->test_mode);

            $refundsQuery = $merchant->refunds()
                ->whereHas('transaction', function ($q) use ($merchant) {
                    $q->where('test_mode', $merchant->test_mode);
                });

            $fxCtx = DashboardFxContext::fromRequest($request);

            $stats = $this->metricsCache->remember('merchant', [
                'merchant_id' => $merchant->id,
                'test' => $merchant->test_mode ? 1 : 0,
                'currency' => $fxCtx->displayCurrency,
                'fx_mode' => $fxCtx->mode,
            ], function () use ($merchant, $transactionsQuery, $refundsQuery, $fxCtx) {
                $usdRates = $this->dashboardDisplayCurrency->getUsdBasedRates();

                $totalVolume = $this->dashboardDisplayCurrency->sumTransactionAmountsToDisplayCurrency(
                    (clone $transactionsQuery)->where('status', 'success'),
                    $usdRates,
                    $fxCtx
                );

                $refundSumQuery = (clone $refundsQuery)
                    ->where('refunds.status', 'completed')
                    ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id');

                $totalRefundedVolume = $this->dashboardDisplayCurrency->sumRefundAmountsToDisplayCurrency(
                    $refundSumQuery,
                    $usdRates,
                    $fxCtx
                );

                $totalSettledVolume = $this->dashboardDisplayCurrency->sumTransactionAmountsToDisplayCurrency(
                    (clone $transactionsQuery)
                        ->where('status', 'success')
                        ->where('settlement_status', 'settled'),
                    $usdRates,
                    $fxCtx
                );

                $netVolume = max(0, round((float) $totalVolume - (float) $totalRefundedVolume, 2));
                $unsettledVolume = max(0, round((float) $totalVolume - (float) $totalRefundedVolume - (float) $totalSettledVolume, 2));

                return [
                    'total_transactions' => $transactionsQuery->count(),
                    'successful_transactions' => (clone $transactionsQuery)->where('status', 'success')->count(),
                    'total_volume' => $totalVolume,
                    'total_refunded_volume' => $totalRefundedVolume,
                    'total_settled_volume' => $totalSettledVolume,
                    'net_volume' => $netVolume,
                    'unsettled_volume' => $unsettledVolume,
                    'pending_refunds' => $refundsQuery->whereIn('status', [
                        'pending',
                        'pending_approval',
                        'pending_processing',
                        'processing',
                    ])->count(),
                ];
            });

            $legacyFxCount = 0;
            if (Schema::hasColumn('transactions', 'amount_base')) {
                $legacyFxCount = (clone $transactionsQuery)
                    ->where('status', 'success')
                    ->whereNull('amount_base')
                    ->count();
            }

            return view('merchant.dashboard', [
                'user' => $user,
                'merchant' => $merchant,
                'stats' => $stats,
                'dashboard_display_currency' => $fxCtx->displayCurrency,
                'dashboard_fx_mode' => $fxCtx->mode,
                'fx_options' => $this->dashboardDisplayCurrency->fxOptionsPayload(),
                'legacy_fx_transaction_count' => $legacyFxCount,
            ]);
        }

        if ($user->isReseller()) {
            return redirect()->route('reseller.dashboard');
        }

        return redirect()->route('landing');
    }

    public function backfillFxSnapshots(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isMerchant() || ! $user->merchant) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (! Schema::hasColumn('transactions', 'amount_base')) {
            return response()->json([
                'message' => 'FX snapshot storage is not ready. Please run database migrations first.',
            ], 422);
        }

        $merchant = $user->merchant;
        $result = $this->fxSnapshotService->backfillForMerchant(
            (int) $merchant->id,
            (bool) $merchant->test_mode
        );

        $this->metricsCache->invalidateAll();

        $updated = $result['transactions'] + $result['refunds'];
        $skipped = $result['skipped_transactions'] + $result['skipped_refunds'];

        if ($updated === 0 && $skipped > 0) {
            return response()->json([
                'message' => 'No payments could be updated — exchange rates may be missing for some currencies. Check logs or contact support.',
                'legacy_remaining' => $this->legacyFxCountForMerchant($merchant),
                'result' => $result,
            ], 422);
        }

        $message = $updated > 0
            ? sprintf(
                'Updated FX snapshots for %d payment%s%s.',
                $result['transactions'],
                $result['transactions'] === 1 ? '' : 's',
                $result['refunds'] > 0
                    ? sprintf(' and %d refund%s', $result['refunds'], $result['refunds'] === 1 ? '' : 's')
                    : ''
            )
            : 'All payments already have FX snapshots.';

        if ($skipped > 0) {
            $message .= sprintf(' %d item(s) could not be updated (missing currency rate).', $skipped);
        }

        return response()->json([
            'message' => $message,
            'legacy_remaining' => $this->legacyFxCountForMerchant($merchant),
            'result' => $result,
        ]);
    }

    private function legacyFxCountForMerchant($merchant): int
    {
        if (! Schema::hasColumn('transactions', 'amount_base')) {
            return 0;
        }

        return (int) $merchant->transactions()
            ->where('test_mode', $merchant->test_mode)
            ->where('status', 'success')
            ->whereNull('amount_base')
            ->count();
    }
}

