<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerCommission;
use App\Support\PaymentViewMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $reseller = $request->user()->reseller;
        $merchantOptions = $reseller
            ? $reseller->merchants()->orderBy('name')->get(['id', 'name'])
            : collect();

        $totals = ResellerCommission::netTotalsForReseller($reseller?->id, PaymentViewMode::isTestMode());

        return view('reseller.earnings', [
            'user' => $request->user(),
            'reseller' => $reseller,
            'merchantOptions' => $merchantOptions,
            'totals' => $totals,
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller not found',
            ], 403);
        }

        $merchantIds = $reseller->merchants()->pluck('id');
        $perPage = min((int) $request->get('per_page', 10), 50);

        $query = PaymentViewMode::scopeResellerCommissions(
            ResellerCommission::query()
                ->with(['merchant', 'transaction'])
                ->where('reseller_id', $reseller->id)
        )->latest();

        if ($merchantIds->isNotEmpty() && $request->filled('merchant_id')) {
            $mid = (int) $request->get('merchant_id');
            if ($merchantIds->contains($mid)) {
                $query->where('merchant_id', $mid);
            }
        }

        if ($request->has('date_range') && $request->get('date_range') !== '') {
            $dates = explode(' - ', $request->get('date_range'));
            if (count($dates) === 2 && trim($dates[0]) !== '' && trim($dates[1]) !== '') {
                $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
            }
        }

        $page = max(1, (int) $request->get('page', 1));
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $rows = $query->forPage($page, $perPage)->get();

        $data = $rows->map(function (ResellerCommission $row) {
            $txn = $row->transaction;
            $net = max(0, (float) $row->commission_amount - (float) $row->reversed_amount);

            return [
                'id' => $row->id,
                'transaction_table_id' => $row->transaction_id,
                'transaction_id' => $txn?->txn_id ?? '-',
                'merchant_name' => $row->merchant->name ?? '-',
                'transaction_amount' => $txn ? number_format((float) $txn->amount, 2) : '-',
                'commission' => number_format($net, 2),
                'commission_gross' => number_format((float) $row->commission_amount, 2),
                'reversed_amount' => number_format((float) $row->reversed_amount, 2),
                'status' => $row->status,
                'created_at' => $row->created_at?->format('d-m-Y H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->all(),
            'totals' => ResellerCommission::netTotalsForReseller($reseller->id, PaymentViewMode::isTestMode()),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
                'to' => $total > 0 ? min($page * $perPage, $total) : null,
            ],
        ]);
    }
}
