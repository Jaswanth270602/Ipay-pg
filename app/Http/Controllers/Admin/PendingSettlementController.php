<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PendingSettlementController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin pending settlement page accessed', ['user_id' => auth()->id()]);
        return view('admin.manage-settlements.pending');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('settlements')
                ->leftJoin('merchants', 'settlements.merchant_id', '=', 'merchants.id')
                ->select('settlements.*', 'merchants.name as merchant_name', 'merchants.id as merchant_id_val')
                ->where('settlements.status', 'pending');

            // Filters
            if ($request->has('filter_settlement_id') && $request->get('filter_settlement_id')) {
                $query->where('settlements.settlement_id', 'like', "%{$request->get('filter_settlement_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('settlements.merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->where('merchants.name', 'like', "%{$request->get('filter_merchant_name')}%");
            }
            if ($request->has('filter_settlement_status') && $request->get('filter_settlement_status') !== 'all') {
                $query->where('settlements.settlement_status', $request->get('filter_settlement_status'));
            }

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('settlements.settlement_date', [trim($dates[0]), trim($dates[1])]);
                }
            }

            $settlements = $query->latest('settlements.settlement_date')->paginate($perPage);

            $data = collect($settlements->items())->map(function($settlement) {
                return [
                    'id' => $settlement->id,
                    'settlement_id' => $settlement->settlement_id ?? '-',
                    'merchant_id' => $settlement->merchant_id ?? '-',
                    'merchant_name' => $settlement->merchant_name ?? '-',
                    'payout_amount' => number_format($settlement->payout_amount ?? 0, 2),
                    'settlement_status' => $settlement->settlement_status ?? 'pending',
                    'settlement_date' => $settlement->settlement_date ? date('Y-m-d', strtotime($settlement->settlement_date)) : '-',
                    'bank_reference' => $settlement->bank_reference ?? '-',
                    'account_name' => $settlement->account_name ?? '-',
                    'account_number' => $settlement->account_number ?? '-',
                    'ifsc_code' => $settlement->ifsc_code ?? '-',
                    'bank_name' => $settlement->bank_name ?? '-',
                    'bank_branch' => $settlement->bank_branch ?? '-',
                    'settlement_description' => $settlement->settlement_description ?? '-',
                    'payment_start_date' => $settlement->payment_start_date ? date('Y-m-d', strtotime($settlement->payment_start_date)) : '-',
                    'payment_end_date' => $settlement->payment_end_date ? date('Y-m-d', strtotime($settlement->payment_end_date)) : '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $settlements->currentPage(),
                    'per_page' => $settlements->perPage(),
                    'total' => $settlements->total(),
                    'last_page' => $settlements->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending settlements',
            ], 500);
        }
    }
}
