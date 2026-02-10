<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ChargebacksController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        return view('merchant.payments.chargebacks');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('chargebacks')
                ->leftJoin('transactions', 'chargebacks.transaction_id', '=', 'transactions.id')
                ->where('chargebacks.merchant_id', $merchant->id)
                ->where('chargebacks.test_mode', $merchant->test_mode)
                ->select('chargebacks.*', 'transactions.txn_id as transaction_txn_id');

            // Filters
            if ($request->has('filter_chargeback_request_id') && $request->get('filter_chargeback_request_id')) {
                $query->where('chargebacks.chargeback_request_id', 'like', "%{$request->get('filter_chargeback_request_id')}%");
            }
            if ($request->has('filter_chargeback_status') && $request->get('filter_chargeback_status') !== 'all') {
                $query->where('chargebacks.chargeback_status', $request->get('filter_chargeback_status'));
            }

            $chargebacks = $query->latest('chargebacks.created_at')->paginate($perPage);

            $data = collect($chargebacks->items())->map(function($chargeback) use ($merchant) {
                return [
                    'id' => $chargeback->id,
                    'chargeback_request_id' => $chargeback->chargeback_request_id ?? '-',
                    'merchant_id' => $chargeback->merchant_id ?? '-',
                    'merchant_name' => $merchant->name ?? '-',
                    'transaction_id' => $chargeback->transaction_txn_id ?? '-',
                    'refunded_or_not' => $chargeback->refunded_or_not ?? '-',
                    'debit_settlement_id' => $chargeback->debit_settlement_id ?? '-',
                    'decision_in_favour_of' => $chargeback->decision_in_favour_of ?? '-',
                    'chargeback_status' => $chargeback->chargeback_status ?? 'pending',
                    'contested' => $chargeback->contested ?? 'No',
                    'account_id' => $chargeback->account_id ?? '-',
                    'account_id_descript' => $chargeback->account_id_descript ?? '-',
                    'merchant_debit_date' => $chargeback->merchant_debit_date ? date('Y-m-d', strtotime($chargeback->merchant_debit_date)) : '-',
                    'merchant_credit_date' => $chargeback->merchant_credit_date ? date('Y-m-d', strtotime($chargeback->merchant_credit_date)) : '-',
                    'bank_debit_date' => $chargeback->bank_debit_date ? date('Y-m-d', strtotime($chargeback->bank_debit_date)) : '-',
                    'bank_credit_date' => $chargeback->bank_credit_date ? date('Y-m-d', strtotime($chargeback->bank_credit_date)) : '-',
                    'target_date' => $chargeback->target_date ? date('Y-m-d', strtotime($chargeback->target_date)) : '-',
                    'debit_merchant' => $chargeback->debit_merchant ?? 'No',
                    'is_dispute' => $chargeback->is_dispute ?? 'No',
                    'second_chargeback' => $chargeback->second_chargeback ?? 'No',
                    'chargeback_amount' => number_format($chargeback->chargeback_amount ?? 0, 2),
                    'notes' => $chargeback->notes ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $chargebacks->currentPage(),
                    'per_page' => $chargebacks->perPage(),
                    'total' => $chargebacks->total(),
                    'last_page' => $chargebacks->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chargebacks',
            ], 500);
        }
    }
}

