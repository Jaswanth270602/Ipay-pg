<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

            $mode = $request->get('mode');
            if ($mode === 'test') {
                $query->where('settlement_details.test_mode', true);
            } elseif ($mode === 'live') {
                $query->where('settlement_details.test_mode', false);
            }

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
        $payload = $request->all();
        foreach ([
            'order_id',
            'tran_seq_id',
            'transaction_qualifier',
            'settlement_qualifier',
            'setl_id',
            'bank_reference',
            'settlement_account_name',
            'settlement_account_number',
            'settlement_ifsc_code',
            'settlement_bank_name',
            'settlement_bank_branch',
            'payment_mode',
            'payment_channel',
            'setd_id',
            'provider',
            'account_id',
            'acq_payment_id',
        ] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }
        if (!empty($payload['settlement_ifsc_code']) && is_string($payload['settlement_ifsc_code'])) {
            $payload['settlement_ifsc_code'] = strtoupper($payload['settlement_ifsc_code']);
        }

        $validator = Validator::make($payload, [
            'merchant_id' => 'required|exists:merchants,id',
            'settlement_id' => Schema::hasTable('settlements')
                ? 'nullable|integer|exists:settlements,id'
                : 'nullable|integer',
            'transaction_id' => Schema::hasTable('transactions')
                ? 'nullable|integer|exists:transactions,id'
                : 'nullable|integer',
            'order_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'tran_seq_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'transaction_date' => 'required|date',
            'transaction_qualifier' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _\\-\\/]*$/'],
            'settlement_qualifier' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _\\-\\/]*$/'],
            'setl_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'amount_paid_by_customer' => 'required|numeric|min:0',
            'settlement_amount' => 'required|numeric|min:0',
            'bank_settlement_date' => 'nullable|date',
            'bank_settlement_amount' => 'nullable|numeric|min:0',
            'bank_reference' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 _\\-\\/]*$/'],
            'settlement_account_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]+$/'],
            'settlement_account_number' => ['required', 'string', 'min:6', 'max:34', 'regex:/^[A-Za-z0-9]+$/'],
            'settlement_ifsc_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'settlement_bank_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'settlement_bank_branch' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]*$/'],
            'payment_mode' => 'nullable|string|in:card,netbanking,upi,wallet,emi,cash,bank_transfer,bbps,bharat_qr',
            'payment_channel' => 'nullable|string|in:web,mobile,pos,api',
            'tdr_percentage' => 'nullable|numeric|between:0,100',
            'tdr_fixed_fee' => 'nullable|numeric|min:0',
            'tdr_amount' => 'nullable|numeric|min:0',
            'earliest_priority_settlement_date' => 'nullable|date',
            'latest_priority_settlement_date' => 'nullable|date|after_or_equal:earliest_priority_settlement_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'setd_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'provider' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 _\\-\\.]*$/'],
            'account_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
            'acq_payment_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\-\\/]*$/'],
        ], [
            'merchant_id.required' => 'Merchant is required.',
            'merchant_id.exists' => 'Selected merchant is invalid.',
            'transaction_date.required' => 'Transaction date is required.',
            'amount_paid_by_customer.required' => 'Amount paid by customer is required.',
            'amount_paid_by_customer.min' => 'Amount paid by customer cannot be negative.',
            'settlement_amount.required' => 'Settlement amount is required.',
            'settlement_amount.min' => 'Settlement amount cannot be negative.',
            'bank_settlement_amount.min' => 'Bank settlement amount cannot be negative.',
            'settlement_account_name.required' => 'Settlement account name is required.',
            'settlement_account_name.regex' => 'Settlement account name may contain only letters, numbers, and spaces.',
            'settlement_account_number.required' => 'Settlement account number is required.',
            'settlement_account_number.regex' => 'Settlement account number may contain only letters and numbers.',
            'settlement_ifsc_code.required' => 'Settlement IFSC code is required.',
            'settlement_ifsc_code.regex' => 'Settlement IFSC code may contain only letters and numbers.',
            'settlement_bank_name.required' => 'Settlement bank name is required.',
            'settlement_bank_name.regex' => 'Settlement bank name may contain only letters and spaces.',
            'settlement_bank_branch.regex' => 'Settlement bank branch may contain only letters, numbers, and spaces.',
            'payment_mode.in' => 'Payment source is invalid.',
            'payment_channel.in' => 'Payment channel is invalid.',
            'tdr_percentage.between' => 'TDR percentage must be between 0 and 100.',
            'tdr_fixed_fee.min' => 'TDR fixed fee cannot be negative.',
            'tdr_amount.min' => 'TDR amount cannot be negative.',
            'tax_amount.min' => 'Tax amount cannot be negative.',
            'latest_priority_settlement_date.after_or_equal' => 'Latest priority settlement date must be on or after earliest priority settlement date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $merchant = Merchant::findOrFail($data['merchant_id']);

            $settlementDetail = DB::table('settlement_details')->insertGetId([
                'merchant_id' => $data['merchant_id'],
                'test_mode' => (bool) $merchant->test_mode,
                'transaction_id' => $data['transaction_id'] ?? null,
                'settlement_id' => $data['settlement_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'tran_seq_id' => $data['tran_seq_id'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'transaction_qualifier' => $data['transaction_qualifier'] ?? null,
                'settlement_qualifier' => $data['settlement_qualifier'] ?? null,
                'setl_id' => $data['setl_id'] ?? null,
                'amount_paid_by_customer' => $data['amount_paid_by_customer'],
                'settlement_amount' => $data['settlement_amount'],
                'bank_settlement_date' => $data['bank_settlement_date'] ?? null,
                'bank_settlement_amount' => $data['bank_settlement_amount'] ?? 0,
                'bank_reference' => $data['bank_reference'] ?? null,
                'settlement_account_name' => $data['settlement_account_name'],
                'settlement_account_number' => $data['settlement_account_number'],
                'settlement_ifsc_code' => $data['settlement_ifsc_code'],
                'settlement_bank_name' => $data['settlement_bank_name'],
                'settlement_bank_branch' => $data['settlement_bank_branch'] ?? null,
                'payment_mode' => $data['payment_mode'] ?? null,
                'payment_channel' => $data['payment_channel'] ?? null,
                'tdr_percentage' => $data['tdr_percentage'] ?? 0,
                'tdr_fixed_fee' => $data['tdr_fixed_fee'] ?? 0,
                'tdr_amount' => $data['tdr_amount'] ?? 0,
                'earliest_priority_settlement_date' => $data['earliest_priority_settlement_date'] ?? null,
                'latest_priority_settlement_date' => $data['latest_priority_settlement_date'] ?? null,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'setd_id' => $data['setd_id'] ?? null,
                'provider' => $data['provider'] ?? null,
                'account_id' => $data['account_id'] ?? null,
                'acq_payment_id' => $data['acq_payment_id'] ?? null,
                'settlement_status' => 'pending',
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
