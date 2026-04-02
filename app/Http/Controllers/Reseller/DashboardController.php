<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerCommission;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $reseller = $user->reseller;

        $stats = [
            'total_merchants' => 0,
            'total_transactions' => 0,
            'total_volume' => 0.0,
            'total_earnings' => 0.0,
            'today_transactions' => 0,
            'today_volume' => 0.0,
            'pending_earnings' => 0.0,
            'paid_earnings' => 0.0,
        ];

        if ($reseller) {
            $merchantIds = $reseller->merchants()->pluck('id');
            $stats['total_merchants'] = $merchantIds->count();

            if ($merchantIds->isNotEmpty()) {
                $successBase = Transaction::query()
                    ->whereIn('merchant_id', $merchantIds)
                    ->where('status', 'success');

                $stats['total_transactions'] = (clone $successBase)->count();
                $stats['total_volume'] = (float) (clone $successBase)->sum('amount');

                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $todayBase = Transaction::query()
                    ->whereIn('merchant_id', $merchantIds)
                    ->where('status', 'success')
                    ->whereBetween('created_at', [$start, $end]);

                $stats['today_transactions'] = (clone $todayBase)->count();
                $stats['today_volume'] = (float) (clone $todayBase)->sum('amount');
            }

            $earn = ResellerCommission::netTotalsForReseller($reseller->id);
            $stats['total_earnings'] = $earn['total_earnings'];
            $stats['pending_earnings'] = $earn['pending_earnings'];
            $stats['paid_earnings'] = $earn['paid_earnings'];
        }

        return view('reseller.dashboard', [
            'user' => $user,
            'reseller' => $reseller,
            'stats' => $stats,
        ]);
    }
}
