<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\MerchantVendor;
use App\Models\Transaction;
use App\Models\SplitTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SplitTransactionsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        return view('merchant.payments.split-transactions');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = $merchant->transactions()
                ->where('test_mode', $merchant->test_mode)
                ->whereNotNull('order_id')
                ->latest();

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
            if ($request->has('filter_order_id') && $request->get('filter_order_id')) {
                $query->where('order_id', 'like', "%{$request->get('filter_order_id')}%");
            }

            $transactions = $query->paginate($perPage);

            // Format data for split transactions
            $data = $transactions->map(function($transaction) use ($merchant) {
                $paymentDetails = $transaction->payment_details ?? [];
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
                    'amount_numeric' => round((float) $transaction->amount, 2),
                    'status' => $transaction->status,
                    'can_manual_split' => $transaction->status === 'success',
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
            $merchant = $request->user()->merchant;
            $transaction = Transaction::where('id', $transactionId)
                ->where('merchant_id', $merchant->id)
                ->with(['merchant', 'order'])
                ->firstOrFail();
            
            // Check if split_transactions table exists and has data
            $splitTransactions = [];
            if (DB::getSchemaBuilder()->hasTable('split_transactions')) {
                $splits = SplitTransaction::where('transaction_id', $transactionId)
                    ->where('merchant_id', $merchant->id)
                    ->with(['primaryMerchant', 'secondaryMerchant', 'merchantVendor'])
                    ->get();

                foreach ($splits as $split) {
                    $primaryType = ($split->merchant_vendor_id || $split->secondary_merchant_id) ? 'Primary' : ($split->split_type === 'primary_only' ? 'Primary' : 'Primary');

                    // Primary split
                    $splitTransactions[] = [
                        'order_id' => $split->order_id,
                        'amount_paid_by_customer' => '₹' . number_format($split->total_amount, 2),
                        'account_holder_name' => $split->primaryMerchant->name ?? '-',
                        'account_number' => '-',
                        'split_type' => $primaryType,
                        'split_amount' => '₹' . number_format($split->primary_amount, 2),
                        'split_percentage' => number_format($split->primary_percentage, 2) . '%',
                    ];

                    // Secondary: another merchant
                    if ($split->secondary_merchant_id && $split->secondary_amount > 0) {
                        $splitTransactions[] = [
                            'order_id' => $split->order_id,
                            'amount_paid_by_customer' => '₹' . number_format($split->total_amount, 2),
                            'account_holder_name' => $split->secondaryMerchant->name ?? '-',
                            'account_number' => $split->account_number ?? '-',
                            'split_type' => 'Secondary',
                            'split_amount' => '₹' . number_format($split->secondary_amount, 2),
                            'split_percentage' => number_format($split->secondary_percentage, 2) . '%',
                        ];
                    }

                    // Secondary: merchant vendor (bank profile)
                    if ($split->merchant_vendor_id && $split->secondary_amount > 0) {
                        $v = $split->merchantVendor;
                        $label = $v
                            ? ($v->vendor_name . ' (' . ($v->bank_account_holder_name ?? 'Bank') . ')')
                            : ($split->account_holder_name ?? 'Vendor');
                        $splitTransactions[] = [
                            'order_id' => $split->order_id,
                            'amount_paid_by_customer' => '₹' . number_format($split->total_amount, 2),
                            'account_holder_name' => $label,
                            'account_number' => $split->account_number ?? '-',
                            'split_type' => 'Vendor',
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
                    'account_holder_name' => $paymentDetails['account_holder_name'] ?? ($merchant->name ?? '-'),
                    'account_number' => $paymentDetails['account_number'] ?? '-',
                    'split_type' => 'Primary',
                    'split_amount' => '₹' . number_format($transaction->amount, 2),
                    'split_percentage' => '100.00%',
                ];
            }

            $totalAmt = round((float) $transaction->amount, 2);
            $manualDefaults = [
                'primary_amount' => $totalAmt,
                'secondary_amount' => 0.0,
                'merchant_vendor_id' => null,
            ];
            $existingSplit = SplitTransaction::where('transaction_id', $transaction->id)
                ->where('merchant_id', $merchant->id)
                ->first();
            if ($existingSplit) {
                $manualDefaults['primary_amount'] = round((float) $existingSplit->primary_amount, 2);
                $manualDefaults['secondary_amount'] = round((float) $existingSplit->secondary_amount, 2);
                $manualDefaults['merchant_vendor_id'] = $existingSplit->merchant_vendor_id;
            }

            return response()->json([
                'success' => true,
                'data' => $splitTransactions,
                'transaction' => [
                    'id' => $transaction->id,
                    'txn_id' => $transaction->txn_id,
                    'order_id' => $transaction->order_id,
                    'amount' => '₹' . number_format($transaction->amount, 2),
                    'amount_numeric' => $totalAmt,
                    'merchant_name' => $merchant->name ?? '-',
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'status' => $transaction->status,
                    'can_manual_split' => $transaction->status === 'success',
                ],
                'manual_split_defaults' => $manualDefaults,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch split details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approved vendors for manual split dropdown.
     */
    public function getApprovedVendors(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $vendors = MerchantVendor::where('merchant_id', $merchant->id)
            ->where('status', 'approved')
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name', 'vendor_code']);

        return response()->json([
            'success' => true,
            'data' => $vendors,
        ]);
    }

    /**
     * Replace split with manually entered merchant vs vendor amounts (successful txns only).
     */
    public function updateManualSplit(Request $request, int $transactionId): JsonResponse
    {
        $merchant = $request->user()->merchant;

        $request->validate([
            'primary_amount' => 'required|numeric|min:0',
            'secondary_amount' => 'required|numeric|min:0',
            'merchant_vendor_id' => 'nullable|integer|exists:merchant_vendors,id',
        ]);

        $transaction = Transaction::where('id', $transactionId)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();

        if ($transaction->status !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Only successful transactions can be split manually.',
            ], 422);
        }

        $total = round((float) $transaction->amount, 2);
        $primary = round((float) $request->input('primary_amount'), 2);
        $secondary = round((float) $request->input('secondary_amount'), 2);

        if (abs(($primary + $secondary) - $total) > 0.02) {
            return response()->json([
                'success' => false,
                'message' => 'Primary amount plus vendor amount must equal the transaction amount (₹' . number_format($total, 2) . ').',
            ], 422);
        }

        if ($secondary > 0 && ! $request->filled('merchant_vendor_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Select a vendor when the vendor share is greater than zero.',
            ], 422);
        }

        $vendor = null;
        if ($secondary > 0) {
            $vendor = MerchantVendor::where('id', (int) $request->input('merchant_vendor_id'))
                ->where('merchant_id', $merchant->id)
                ->where('status', 'approved')
                ->first();

            if (! $vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or unapproved vendor for this account.',
                ], 422);
            }
        }

        $pctPrimary = $total > 0 ? round(($primary / $total) * 100, 2) : 100.0;
        $pctSecondary = $total > 0 ? round(($secondary / $total) * 100, 2) : 0.0;

        try {
            DB::transaction(function () use ($transaction, $merchant, $primary, $secondary, $total, $vendor, $pctPrimary, $pctSecondary) {
                SplitTransaction::where('transaction_id', $transaction->id)->delete();

                SplitTransaction::create([
                    'transaction_id' => $transaction->id,
                    'merchant_id' => $merchant->id,
                    'split_id' => 'SPL_MAN_' . strtoupper(now()->format('YmdHis')) . '_' . $transaction->id . '_' . strtoupper(Str::random(6)),
                    'order_id' => (string) ($transaction->order_id ?? $transaction->txn_id),
                    'total_amount' => $total,
                    'primary_amount' => $primary,
                    'secondary_amount' => $secondary,
                    'primary_merchant_id' => $merchant->id,
                    'secondary_merchant_id' => null,
                    'merchant_vendor_id' => $vendor?->id,
                    'primary_percentage' => $pctPrimary,
                    'secondary_percentage' => $pctSecondary,
                    'status' => 'completed',
                    'notes' => 'Manual split (merchant dashboard)',
                    'split_type' => $vendor ? 'merchant_vendor' : 'primary_only',
                    'account_holder_name' => $vendor?->bank_account_holder_name,
                    'account_number' => $vendor?->bank_account_number,
                    'ifsc_code' => $vendor?->bank_account_ifsc,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not save split: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Split amounts saved.',
        ]);
    }
}
