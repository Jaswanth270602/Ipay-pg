<?php

namespace App\Http\Controllers;

use App\Services\DashboardDisplayCurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDisplayCurrencyService $dashboardDisplayCurrency
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

            $usdRates = $this->dashboardDisplayCurrency->getUsdBasedRates();

            $totalVolume = $this->dashboardDisplayCurrency->sumTransactionAmountsToDisplayCurrency(
                (clone $transactionsQuery)->where('status', 'success'),
                $usdRates
            );

            $refundSumQuery = (clone $refundsQuery)
                ->where('refunds.status', 'completed')
                ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id');

            $totalRefundedVolume = $this->dashboardDisplayCurrency->sumRefundAmountsToDisplayCurrency(
                $refundSumQuery,
                $usdRates
            );

            // Gross of captures already paid out via settlement (merchant should not count this as "still on platform")
            $totalSettledVolume = $this->dashboardDisplayCurrency->sumTransactionAmountsToDisplayCurrency(
                (clone $transactionsQuery)
                    ->where('status', 'success')
                    ->where('settlement_status', 'settled'),
                $usdRates
            );

            // Net volume ≈ success gross − refunds − amounts already settled to bank (same mode as merchant toggle)
            $stats = [
                'total_transactions' => $transactionsQuery->count(),
                'successful_transactions' => (clone $transactionsQuery)->where('status', 'success')->count(),
                'total_volume' => $totalVolume,
                'total_refunded_volume' => $totalRefundedVolume,
                'total_settled_volume' => $totalSettledVolume,
                'net_volume' => max(0, (float) $totalVolume - (float) $totalRefundedVolume - (float) $totalSettledVolume),
                'pending_refunds' => $refundsQuery->whereIn('status', [
                    'pending',
                    'pending_approval',
                    'pending_processing',
                    'processing',
                ])->count(),
            ];

            return view('merchant.dashboard', [
                'user' => $user,
                'merchant' => $merchant,
                'stats' => $stats,
                'dashboard_display_currency' => $this->dashboardDisplayCurrency->displayCurrencyCode(),
            ]);
        }

        if ($user->isReseller()) {
            return redirect()->route('reseller.dashboard');
        }

        return redirect()->route('landing');
    }
}

