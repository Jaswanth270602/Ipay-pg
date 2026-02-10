<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Transaction;
use App\Models\SplitTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class SplitTransactionsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin split transactions page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.split-transactions');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = Transaction::with('merchant')->whereNotNull('order_id')->latest();

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            // Column filters
            if ($request->has('filter_transaction_id') && $request->get('filter_transaction_id')) {
                $query->where('txn_id', 'like', "%{$request->get('filter_transaction_id')}%");
            }
            if ($request->has('filter_tran_id') && $request->get('filter_tran_id')) {
                $query->where('txn_id', 'like', "%{$request->get('filter_tran_id')}%");
            }
            if ($request->has('filter_order_id') && $request->get('filter_order_id')) {
                $query->where('order_id', 'like', "%{$request->get('filter_order_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->whereHas('merchant', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->get('filter_merchant_name')}%");
                });
            }
            if ($request->has('filter_msac_code') && $request->get('filter_msac_code')) {
                $query->whereHas('merchant', function($q) use ($request) {
                    $q->where('msac_code', 'like', "%{$request->get('filter_msac_code')}%");
                });
            }
            if ($request->has('filter_transaction_date') && $request->get('filter_transaction_date')) {
                $query->whereDate('created_at', 'like', "%{$request->get('filter_transaction_date')}%");
            }
            if ($request->has('filter_amount') && $request->get('filter_amount')) {
                $amount = floatval(str_replace(['₹', ',', ' '], '', $request->get('filter_amount')));
                if ($amount > 0) {
                    $query->where('amount', '>=', $amount * 0.9)
                          ->where('amount', '<=', $amount * 1.1);
                }
            }

            $transactions = $query->paginate($perPage);

            // Format data for split transactions
            $data = $transactions->map(function($transaction) {
                $paymentDetails = $transaction->payment_details ?? [];
                $merchant = $transaction->merchant;
                
                // Get MSAC code from merchant or payment details
                $msacCode = $merchant->msac_code ?? $paymentDetails['msac_code'] ?? '-';
                
                return [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'merchant_id' => $transaction->merchant_id,
                    'merchant_name' => $merchant->name ?? '-',
                    'msac_code' => $msacCode,
                    'tran_id' => $transaction->txn_id,
                    'transaction_id' => $transaction->txn_id,
                    'order_id' => $transaction->order_id ?? '-',
                    'amount_paid_by_customer' => '₹' . number_format($transaction->amount, 2),
                    'account' => $paymentDetails['account_number'] ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch split transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get split details for a specific transaction
     */
    public function getSplitDetails(Request $request, $transactionId): JsonResponse
    {
        try {
            $transaction = Transaction::with(['merchant', 'order'])->findOrFail($transactionId);
            
            // Check if split_transactions table exists and has data
            $splitTransactions = [];
            if (DB::getSchemaBuilder()->hasTable('split_transactions')) {
                $splits = SplitTransaction::where('transaction_id', $transactionId)
                    ->with(['primaryMerchant', 'secondaryMerchant'])
                    ->get();
                
                foreach ($splits as $split) {
                    // Primary split
                    $splitTransactions[] = [
                        'order_id' => $split->order_id,
                        'amount_paid_by_customer' => '₹' . number_format($split->total_amount, 2),
                        'account_holder_name' => $split->account_holder_name ?? ($split->primaryMerchant->name ?? '-'),
                        'account_number' => $split->account_number ?? '-',
                        'split_type' => $split->split_type ?? ($split->secondary_merchant_id ? 'Split' : 'Primary'),
                        'split_amount' => '₹' . number_format($split->primary_amount, 2),
                        'split_percentage' => number_format($split->primary_percentage, 2) . '%',
                    ];
                    
                    // Secondary split if exists
                    if ($split->secondary_merchant_id && $split->secondary_amount > 0) {
                        $splitTransactions[] = [
                            'order_id' => $split->order_id,
                            'amount_paid_by_customer' => '₹' . number_format($split->total_amount, 2),
                            'account_holder_name' => $split->account_holder_name ?? ($split->secondaryMerchant->name ?? '-'),
                            'account_number' => $split->account_number ?? '-',
                            'split_type' => 'Secondary',
                            'split_amount' => '₹' . number_format($split->secondary_amount, 2),
                            'split_percentage' => number_format($split->secondary_percentage, 2) . '%',
                        ];
                    }
                }
            }
            
            // If no splits found, return transaction details as a single entry
            if (empty($splitTransactions)) {
                $paymentDetails = $transaction->payment_details ?? [];
                $splitTransactions[] = [
                    'order_id' => $transaction->order_id ?? '-',
                    'amount_paid_by_customer' => '₹' . number_format($transaction->amount, 2),
                    'account_holder_name' => $paymentDetails['account_holder_name'] ?? ($transaction->merchant->name ?? '-'),
                    'account_number' => $paymentDetails['account_number'] ?? '-',
                    'split_type' => 'Primary',
                    'split_amount' => '₹' . number_format($transaction->amount, 2),
                    'split_percentage' => '100.00%',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $splitTransactions,
                'transaction' => [
                    'id' => $transaction->id,
                    'txn_id' => $transaction->txn_id,
                    'order_id' => $transaction->order_id,
                    'amount' => '₹' . number_format($transaction->amount, 2),
                    'merchant_name' => $transaction->merchant->name ?? '-',
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch split details: ' . $e->getMessage(),
            ], 500);
        }
    }
}

