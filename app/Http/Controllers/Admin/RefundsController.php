<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin refunds page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.refunds');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            // Get admin's viewing mode from session
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $this->logInfo('Admin refunds data requested', [
                'user_id' => auth()->id(),
                'admin_view_mode' => $adminViewMode,
            ]);

            $perPage = min($request->get('per_page', 5), 50);
            
            // Filter by admin's viewing mode through transaction relationship
            $query = Refund::with(['merchant', 'transaction'])
                ->whereHas('transaction', function($q) use ($isTestMode) {
                    $q->where('test_mode', $isTestMode);
                })
                ->latest();

            // Date range filter (only apply if provided)
            if ($request->has('date_range') && !empty($request->get('date_range'))) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2 && !empty(trim($dates[0])) && !empty(trim($dates[1]))) {
                    $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            // Column filters
            if ($request->has('filter_refund_id') && $request->get('filter_refund_id')) {
                $query->where('refund_id', 'like', "%{$request->get('filter_refund_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->whereHas('merchant', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->get('filter_merchant_name')}%");
                });
            }
            if ($request->has('filter_payment_id') && $request->get('filter_payment_id')) {
                $query->whereHas('transaction', function($q) use ($request) {
                    $q->where('txn_id', 'like', "%{$request->get('filter_payment_id')}%");
                });
            }
            if ($request->has('filter_transaction_id') && $request->get('filter_transaction_id')) {
                $query->whereHas('transaction', function($q) use ($request) {
                    $q->where('txn_id', 'like', "%{$request->get('filter_transaction_id')}%");
                });
            }
            if ($request->has('filter_order_id') && $request->get('filter_order_id')) {
                $query->whereHas('transaction', function($q) use ($request) {
                    $q->where('order_id', 'like', "%{$request->get('filter_order_id')}%");
                });
            }
            if ($request->has('filter_refund_status') && $request->get('filter_refund_status') !== 'all') {
                $query->where('status', $request->get('filter_refund_status'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'refund_id', 'created_at', 'amount', 'status'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $refunds = $query->paginate($perPage);

            // Format data
            $data = $refunds->map(function($refund) {
                $transaction = $refund->transaction;
                return [
                    'id' => $refund->id,
                    'refund_id' => $refund->refund_id,
                    'merchant_id' => $refund->merchant_id,
                    'merchant_name' => $refund->merchant->name ?? '-',
                    'payment_id' => $transaction->txn_id ?? '-',
                    'customer_ip' => $transaction->ip_address ?? '-',
                    'transaction_sequence_id' => $transaction->id ?? '-',
                    'transaction_id' => $transaction->txn_id ?? '-',
                    'order_id' => $transaction->order_id ?? '-',
                    'payer_name' => $transaction->payment_details['customer_name'] ?? '-',
                    'payer_email' => $transaction->payment_details['customer_email'] ?? '-',
                    'payer_phone' => $transaction->payment_details['customer_phone'] ?? '-',
                    'refund_status' => $refund->status,
                    'refund_description' => $refund->reason ?? '-',
                    'refund_amount' => number_format($refund->amount, 2),
                    'refund_charges' => '0.00',
                    'refund_tax_on_charges' => '0.00',
                    'transaction_amount' => $transaction ? number_format($transaction->amount, 2) : '0.00',
                    'refund_request_date' => $refund->created_at->format('Y-m-d H:i:s'),
                    'refund_initiated_date' => $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : '-',
                    'refund_reference_no' => $refund->gateway_refund_id ?? '-',
                    'is_refund_approved' => $refund->status === 'completed' ? 'Yes' : 'No',
                    'refund_pg_completed' => $refund->status === 'completed' ? 'Yes' : 'No',
                    'latest_api_response' => json_encode($refund->gateway_response ?? []),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $refunds->currentPage(),
                    'per_page' => $refunds->perPage(),
                    'total' => $refunds->total(),
                    'last_page' => $refunds->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching refunds', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch refunds',
            ], 500);
        }
    }

    public function export(Request $request): StreamedResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $query = Refund::with(['merchant', 'transaction'])
                ->whereHas('transaction', function($q) use ($isTestMode) {
                    $q->where('test_mode', $isTestMode);
                });

            // Apply same filters as getData
            if ($request->has('date_range') && !empty($request->get('date_range'))) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2 && !empty(trim($dates[0])) && !empty(trim($dates[1]))) {
                    $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            if ($request->has('filter_refund_id') && $request->get('filter_refund_id')) {
                $query->where('refund_id', 'like', "%{$request->get('filter_refund_id')}%");
            }
            if ($request->has('filter_refund_status') && $request->get('filter_refund_status') !== 'all') {
                $query->where('status', $request->get('filter_refund_status'));
            }

            $refunds = $query->latest()->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="refunds_' . now()->format('Y-m-d_His') . '.csv"',
            ];

            $callback = function() use ($refunds) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'Refund ID', 'Merchant ID', 'Merchant Name', 'Payment ID', 'Transaction ID',
                    'Order ID', 'Refund Status', 'Refund Amount', 'Transaction Amount',
                    'Refund Request Date', 'Refund Initiated Date', 'Refund Reference No',
                    'Is Refund Approved', 'Refund Description'
                ]);

                foreach ($refunds as $refund) {
                    $transaction = $refund->transaction;
                    fputcsv($file, [
                        $refund->refund_id,
                        $refund->merchant_id,
                        $refund->merchant->name ?? '-',
                        $transaction->txn_id ?? '-',
                        $transaction->txn_id ?? '-',
                        $transaction->order_id ?? '-',
                        $refund->status,
                        number_format($refund->amount, 2),
                        $transaction ? number_format($transaction->amount, 2) : '0.00',
                        $refund->created_at->format('Y-m-d H:i:s'),
                        $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : '-',
                        $refund->gateway_refund_id ?? '-',
                        $refund->status === 'completed' ? 'Yes' : 'No',
                        $refund->reason ?? '-',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            $this->logError('Error exporting refunds', ['error' => $e->getMessage()]);
            abort(500, 'Failed to export refunds');
        }
    }
}



