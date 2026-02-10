<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SettlementSummaryController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin settlement summary page accessed', ['user_id' => auth()->id()]);
        return view('admin.settlements.summary');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = Settlement::with('merchant')->latest();

            // Date range filter (only apply if provided and not empty)
            if ($request->has('date_range') && !empty($request->get('date_range'))) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2 && !empty(trim($dates[0])) && !empty(trim($dates[1]))) {
                    $query->whereBetween('settlement_date', [trim($dates[0]), trim($dates[1])]);
                }
            }

            // Column filters
            if ($request->has('filter_settlement_id') && $request->get('filter_settlement_id')) {
                $query->where('settlement_id', 'like', "%{$request->get('filter_settlement_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->whereHas('merchant', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->get('filter_merchant_name')}%");
                });
            }
            if ($request->has('filter_partner_id') && $request->get('filter_partner_id')) {
                $query->where('partner_id', 'like', "%{$request->get('filter_partner_id')}%");
            }
            if ($request->has('filter_partner_name') && $request->get('filter_partner_name')) {
                $query->where('partner_name', 'like', "%{$request->get('filter_partner_name')}%");
            }
            if ($request->has('filter_settlement_status') && $request->get('filter_settlement_status') !== 'all') {
                $query->where('settlement_status', $request->get('filter_settlement_status'));
            }
            if ($request->has('filter_settlement_date') && $request->get('filter_settlement_date')) {
                $query->whereDate('settlement_date', $request->get('filter_settlement_date'));
            }
            if ($request->has('filter_bank_reference') && $request->get('filter_bank_reference')) {
                $query->where('bank_reference', 'like', "%{$request->get('filter_bank_reference')}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'settlement_id', 'settlement_date', 'payout_amount', 'settlement_status'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $settlements = $query->paginate($perPage);

            // Format data with merchant and partner info
            $data = $settlements->map(function($settlement) {
                return [
                    'id' => $settlement->id,
                    'settlement_id' => $settlement->settlement_id,
                    'merchant_id' => $settlement->merchant_id,
                    'merchant_name' => $settlement->merchant->name ?? '-',
                    'partner_id' => $settlement->partner_id ?? '-',
                    'partner_name' => $settlement->partner_name ?? '-',
                    'payout_amount' => number_format($settlement->payout_amount ?? $settlement->net_amount, 2),
                    'settlement_status' => $settlement->settlement_status ?? $settlement->status,
                    'settlement_date' => $settlement->settlement_date ? $settlement->settlement_date->format('Y-m-d') : ($settlement->created_at->format('Y-m-d')),
                    'bank_reference' => $settlement->bank_reference ?? '-',
                    'account_name' => $settlement->account_name ?? '-',
                    'account_number' => $settlement->account_number ?? '-',
                    'ifsc_code' => $settlement->ifsc_code ?? '-',
                    'bank_name' => $settlement->bank_name ?? '-',
                    'bank_branch' => $settlement->bank_branch ?? '-',
                    'settlement_description' => $settlement->settlement_description ?? '-',
                    'payment_start_date' => $settlement->payment_start_date ? $settlement->payment_start_date->format('Y-m-d') : '-',
                    'payment_end_date' => $settlement->payment_end_date ? $settlement->payment_end_date->format('Y-m-d') : '-',
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
            $this->logError('Error fetching settlement summary', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settlements',
            ], 500);
        }
    }

    public function markAsSettled(Request $request): JsonResponse
    {
        try {
            $ids = $request->get('ids', []);
            Settlement::whereIn('id', $ids)->update([
                'settlement_status' => 'settled',
                'status' => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settlements marked as settled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settlements',
            ], 500);
        }
    }
}



