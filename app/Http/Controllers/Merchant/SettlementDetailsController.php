<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettlementDetailsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        return view('merchant.settlements.details');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('settlement_details')
                ->leftJoin('transactions', 'settlement_details.transaction_id', '=', 'transactions.id')
                ->where('settlement_details.merchant_id', $merchant->id)
                ->where('settlement_details.test_mode', $merchant->test_mode)
                ->select('settlement_details.*', 'transactions.order_id as transaction_order_id', 'transactions.txn_id as transaction_txn_id');

            // Filters
            if ($request->has('filter_order_id') && $request->get('filter_order_id')) {
                $query->where('settlement_details.order_id', 'like', "%{$request->get('filter_order_id')}%");
            }
            if ($request->has('filter_transaction_id') && $request->get('filter_transaction_id')) {
                $query->where('transactions.txn_id', 'like', "%{$request->get('filter_transaction_id')}%");
            }
            if ($request->has('filter_settlement_status') && $request->get('filter_settlement_status') !== 'all') {
                $query->where('settlement_details.settlement_status', $request->get('filter_settlement_status'));
            }

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('settlement_details.transaction_date', [trim($dates[0]), trim($dates[1])]);
                }
            }

            $details = $query->latest('settlement_details.transaction_date')->paginate($perPage);

            $data = collect($details->items())->map(function($detail) use ($merchant) {
                return [
                    'id' => $detail->id,
                    'merchant_id' => $detail->merchant_id ?? '-',
                    'merchant_name' => $merchant->name ?? '-',
                    'order_id' => $detail->order_id ?? '-',
                    'transaction_id' => $detail->transaction_txn_id ?? '-',
                    'tran_seq_id' => $detail->tran_seq_id ?? '-',
                    'transaction_date' => $detail->transaction_date ? date('Y-m-d', strtotime($detail->transaction_date)) : '-',
                    'transaction_qualifier' => $detail->transaction_qualifier ?? '-',
                    'settlement_qualifier' => $detail->settlement_qualifier ?? '-',
                    'setl_id' => $detail->setl_id ?? '-',
                    'amount_paid_by_customer' => number_format($detail->amount_paid_by_customer ?? 0, 2),
                    'settlement_amount' => number_format($detail->settlement_amount ?? 0, 2),
                    'bank_settlement_date' => $detail->bank_settlement_date ? date('Y-m-d', strtotime($detail->bank_settlement_date)) : '-',
                    'bank_settlement_amount' => $detail->bank_settlement_amount ? number_format($detail->bank_settlement_amount, 2) : '-',
                    'bank_reference' => $detail->bank_reference ?? '-',
                    'settlement_account_name' => $detail->settlement_account_name ?? '-',
                    'settlement_account_number' => $detail->settlement_account_number ?? '-',
                    'settlement_ifsc_code' => $detail->settlement_ifsc_code ?? '-',
                    'settlement_bank_name' => $detail->settlement_bank_name ?? '-',
                    'settlement_bank_branch' => $detail->settlement_bank_branch ?? '-',
                    'payment_mode' => $detail->payment_mode ?? '-',
                    'payment_channel' => $detail->payment_channel ?? '-',
                    'tdr_percentage' => $detail->tdr_percentage ? number_format($detail->tdr_percentage, 2) . '%' : '-',
                    'tdr_fixed_fee' => $detail->tdr_fixed_fee ? number_format($detail->tdr_fixed_fee, 2) : '-',
                    'tdr_amount' => $detail->tdr_amount ? number_format($detail->tdr_amount, 2) : '-',
                    'earliest_priority_settlement_date' => $detail->earliest_priority_settlement_date ? date('Y-m-d', strtotime($detail->earliest_priority_settlement_date)) : '-',
                    'latest_priority_settlement_date' => $detail->latest_priority_settlement_date ? date('Y-m-d', strtotime($detail->latest_priority_settlement_date)) : '-',
                    'tax_amount' => $detail->tax_amount ? number_format($detail->tax_amount, 2) : '-',
                    'setd_id' => $detail->setd_id ?? '-',
                    'provider' => $detail->provider ?? '-',
                    'account_id' => $detail->account_id ?? '-',
                    'acq_payment_id' => $detail->acq_payment_id ?? '-',
                    'udf1' => $detail->udf1 ?? '-',
                    'udf2' => $detail->udf2 ?? '-',
                    'udf3' => $detail->udf3 ?? '-',
                    'udf4' => $detail->udf4 ?? '-',
                    'udf5' => $detail->udf5 ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $details->currentPage(),
                    'per_page' => $details->perPage(),
                    'total' => $details->total(),
                    'last_page' => $details->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settlement details',
            ], 500);
        }
    }
}

