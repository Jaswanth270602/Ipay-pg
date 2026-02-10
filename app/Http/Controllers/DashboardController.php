<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index(Request $request): View
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

            // Get statistics for the current mode only
            $stats = [
                'total_transactions' => $transactionsQuery->count(),
                'successful_transactions' => (clone $transactionsQuery)->where('status', 'success')->count(),
                'total_volume' => (clone $transactionsQuery)->where('status', 'success')->sum('amount'),
                'pending_refunds' => $refundsQuery->where('status', 'pending')->count(),
            ];

            return view('merchant.dashboard', [
                'user' => $user,
                'merchant' => $merchant,
                'stats' => $stats,
            ]);
        }

        return view('dashboard', ['user' => $user]);
    }
}

