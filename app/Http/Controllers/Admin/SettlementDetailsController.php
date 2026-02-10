<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
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
        $this->logInfo('Admin settlement details page accessed', ['user_id' => auth()->id()]);
        return view('admin.settlements.details');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('settlement_details')
                ->leftJoin('merchants', 'settlement_details.merchant_id', '=', 'merchants.id')
                ->leftJoin('transactions', 'settlement_details.transaction_id', '=', 'transactions.id')
                ->select('settlement_details.*', 'merchants.name as merchant_name', 'merchants.id as merchant_id_val', 'transactions.order_id as transaction_order_id', 'transactions.txn_id as transaction_txn_id');

            // Filters
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('settlement_details.merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->where('merchants.name', 'like', "%{$request->get('filter_merchant_name')}%");
            }
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

            $data = collect($details->items())->map(function($detail) {
                return [
                    'id' => $detail->id,
                    'merchant_id' => $detail->merchant_id ?? '-',
                    'merchant_name' => $detail->merchant_name ?? '-',
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

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'order_id' => 'nullable|string|max:255',
            'tran_seq_id' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'amount_paid_by_customer' => 'required|numeric|min:0',
            'settlement_amount' => 'required|numeric|min:0',
            'settlement_account_name' => 'required|string|max:255',
            'settlement_account_number' => 'required|string|max:255',
            'settlement_ifsc_code' => 'required|string|max:255',
            'settlement_bank_name' => 'required|string|max:255',
            'settlement_bank_branch' => 'nullable|string|max:255',
            'payment_mode' => 'nullable|string|max:255',
            'payment_channel' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settlementDetail = DB::table('settlement_details')->insertGetId([
                'merchant_id' => $request->merchant_id,
                'transaction_id' => $request->transaction_id,
                'settlement_id' => $request->settlement_id,
                'order_id' => $request->order_id,
                'tran_seq_id' => $request->tran_seq_id,
                'transaction_date' => $request->transaction_date,
                'transaction_qualifier' => $request->transaction_qualifier,
                'settlement_qualifier' => $request->settlement_qualifier,
                'setl_id' => $request->setl_id,
                'amount_paid_by_customer' => $request->amount_paid_by_customer,
                'settlement_amount' => $request->settlement_amount,
                'bank_settlement_date' => $request->bank_settlement_date,
                'bank_settlement_amount' => $request->bank_settlement_amount,
                'bank_reference' => $request->bank_reference,
                'settlement_account_name' => $request->settlement_account_name,
                'settlement_account_number' => $request->settlement_account_number,
                'settlement_ifsc_code' => $request->settlement_ifsc_code,
                'settlement_bank_name' => $request->settlement_bank_name,
                'settlement_bank_branch' => $request->settlement_bank_branch,
                'payment_mode' => $request->payment_mode,
                'payment_channel' => $request->payment_channel,
                'tdr_percentage' => $request->tdr_percentage,
                'tdr_fixed_fee' => $request->tdr_fixed_fee,
                'tdr_amount' => $request->tdr_amount,
                'earliest_priority_settlement_date' => $request->earliest_priority_settlement_date,
                'latest_priority_settlement_date' => $request->latest_priority_settlement_date,
                'tax_amount' => $request->tax_amount,
                'setd_id' => $request->setd_id,
                'provider' => $request->provider,
                'account_id' => $request->account_id,
                'acq_payment_id' => $request->acq_payment_id,
                'settlement_status' => 'not_settled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settlement detail created successfully',
                'id' => $settlementDetail,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create settlement detail: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of merchants for dropdown
     */
    public function getMerchants(): JsonResponse
    {
        try {
            $merchants = Merchant::where('status', 'active')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $merchants,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchants',
            ], 500);
        }
    }
}
