<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\ChargebackCreationService;
use App\Traits\LogsConditionally;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
                ->select('chargebacks.*', 'transactions.txn_id as transaction_txn_id');

            if (Schema::hasColumn('chargebacks', 'test_mode')) {
                $query->where('chargebacks.test_mode', $merchant->test_mode);
            }

            // Filters
            if ($request->has('filter_chargeback_request_id') && $request->get('filter_chargeback_request_id')) {
                $query->where('chargebacks.chargeback_request_id', 'like', "%{$request->get('filter_chargeback_request_id')}%");
            }
            if ($request->has('filter_chargeback_status') && $request->get('filter_chargeback_status') !== 'all') {
                $statusFilter = $request->get('filter_chargeback_status');
                if ($statusFilter === 'disputed') {
                    $statusFilter = 'contested';
                }
                $query->where('chargebacks.chargeback_status', $statusFilter);
            }

            $chargebacks = $query->latest('chargebacks.created_at')->paginate($perPage);

            $data = collect($chargebacks->items())->map(function ($chargeback) use ($merchant) {
                $testMode = null;
                if (isset($chargeback->test_mode)) {
                    $testMode = ($chargeback->test_mode === true || $chargeback->test_mode === 1 || $chargeback->test_mode === '1') ? 'Yes' : 'No';
                }

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
                    'created_at' => $chargeback->created_at ? date('Y-m-d H:i:s', strtotime((string) $chargeback->created_at)) : '-',
                    'updated_at' => $chargeback->updated_at ? date('Y-m-d H:i:s', strtotime((string) $chargeback->updated_at)) : '-',
                    'test_mode' => $testMode,
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
            $this->logError('Failed to fetch chargebacks', [
                'merchant_id' => $request->user()->merchant_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chargebacks',
            ], 500);
        }
    }

    public function lookupTransaction(Request $request, ChargebackCreationService $chargebacks): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:120',
        ]);

        $merchant = $request->user()->merchant;
        $result = $chargebacks->lookupTransactionForMerchant($merchant, $request->input('q'));

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'No eligible payment found for this reference in your current Test/Live mode.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function store(Request $request, ChargebackCreationService $chargebacks): JsonResponse
    {
        $request->validate([
            'chargeback_request_id' => 'nullable|string|max:120',
            'transaction_id' => 'required|string|max:120',
            'chargeback_amount' => 'required|numeric|min:0.01',
            'chargeback_status' => 'nullable|string|max:32',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $merchant = $request->user()->merchant;
            $created = $chargebacks->createForMerchant($merchant, $request->only([
                'chargeback_request_id',
                'transaction_id',
                'chargeback_amount',
                'chargeback_status',
                'target_date',
                'notes',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Chargeback registered. Respond before the target date to contest with the bank.',
                'data' => $created,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logError('Failed to create chargeback', [
                'merchant_id' => $request->user()->merchant_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create chargeback: '.$e->getMessage(),
            ], 500);
        }
    }

    public function contest(Request $request, int $id, ChargebackCreationService $chargebacks): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $merchant = $request->user()->merchant;
            $updated = $chargebacks->contestChargeback($merchant, $id, $request->input('notes'));

            return response()->json([
                'success' => true,
                'message' => 'Chargeback marked as contested. Awaiting acquirer/bank decision.',
                'data' => $updated,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to contest chargeback: '.$e->getMessage(),
            ], 500);
        }
    }
}
