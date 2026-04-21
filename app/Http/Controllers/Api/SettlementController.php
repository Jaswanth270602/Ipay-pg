<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle.api');
    }

    /**
     * Get all settlements for the merchant, filtered by API key mode.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->get('api_merchant');
        $apiKeyMode = $request->get('api_key_mode');
        $modeIsTest = $apiKeyMode === 'test';

        $perPage = min(
            (int) $request->get('per_page', config('ipay.pagination.default_per_page')),
            config('ipay.pagination.max_per_page')
        );

        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = $merchant->settlements()
            ->whereHas('transactions', function ($q) use ($modeIsTest) {
                $q->where('test_mode', $modeIsTest);
            })
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_id', 'like', '%' . $search . '%')
                    ->orWhere('reference_number', 'like', '%' . $search . '%');
            });
        }

        if ($fromDate) {
            $query->whereDate('settlement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('settlement_date', '<=', $toDate);
        }

        $settlements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'mode' => $apiKeyMode,
            'data' => $settlements->items(),
            'pagination' => [
                'current_page' => $settlements->currentPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
                'last_page' => $settlements->lastPage(),
                'from' => $settlements->firstItem(),
                'to' => $settlements->lastItem(),
            ],
        ]);
    }

    /**
     * Get a specific settlement by settlement_id, filtered by API key mode.
     *
     * @param Request $request
     * @param string $settlementId
     * @return JsonResponse
     */
    public function show(Request $request, string $settlementId): JsonResponse
    {
        $merchant = $request->get('api_merchant');
        $apiKeyMode = $request->get('api_key_mode');
        $modeIsTest = $apiKeyMode === 'test';

        $settlement = $merchant->settlements()
            ->where('settlement_id', $settlementId)
            ->whereHas('transactions', function ($q) use ($modeIsTest) {
                $q->where('test_mode', $modeIsTest);
            })
            ->with(['transactions', 'payouts'])
            ->first();

        if (! $settlement) {
            return response()->json([
                'error' => 'Settlement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'mode' => $apiKeyMode,
            'data' => [
                'settlement_id' => $settlement->settlement_id,
                'amount' => $settlement->amount,
                'fee_amount' => $settlement->fee_amount,
                'fee_breakdown' => $settlement->fee_breakdown ?? [],
                'refund_amount' => $settlement->refund_amount,
                'net_amount' => $settlement->net_amount,
                'payout_amount' => $settlement->payout_amount,
                'reserve_held_amount' => $settlement->reserve_held_amount ?? 0,
                'reserve_released_amount' => $settlement->reserve_released_amount ?? 0,
                'currency' => $settlement->currency,
                'transaction_count' => $settlement->transaction_count,
                'refund_count' => $settlement->refund_count,
                'status' => $settlement->status ?? $settlement->settlement_status,
                'settlement_date' => optional($settlement->settlement_date)->toDateString(),
                'period_start' => optional($settlement->period_start)->toIso8601String(),
                'period_end' => optional($settlement->period_end)->toIso8601String(),
                'processed_at' => optional($settlement->processed_at)->toIso8601String(),
                'utr_number' => $settlement->utr_number,
                'bank_details' => $settlement->bank_details,
                'bank_reference' => $settlement->bank_reference,
                'account_name' => $settlement->account_name,
                'account_number' => $settlement->account_number,
                'ifsc_code' => $settlement->ifsc_code,
                'bank_name' => $settlement->bank_name,
                'bank_branch' => $settlement->bank_branch,
                'notes' => $settlement->notes,
                'transactions' => $settlement->transactions
                    ->where('test_mode', $modeIsTest)
                    ->map(function ($txn) {
                    return [
                        'transaction_id' => $txn->txn_id,
                        'amount' => $txn->amount,
                        'currency' => $txn->currency,
                        'status' => $txn->status,
                        'created_at' => $txn->created_at->toIso8601String(),
                    ];
                }),
                'payouts' => $settlement->payouts->map(function ($payout) {
                    return [
                        'payout_id' => $payout->id,
                        'amount' => $payout->amount,
                        'currency' => $payout->currency,
                        'status' => $payout->status,
                        'created_at' => $payout->created_at->toIso8601String(),
                    ];
                }),
                'created_at' => $settlement->created_at->toIso8601String(),
                'updated_at' => $settlement->updated_at->toIso8601String(),
            ],
        ]);
    }
}


