<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(): View
    {
        return view('merchant.reports.index');
    }

    public function getData(Request $request): \Illuminate\Http\JsonResponse
    {
        $merchant = $request->user()->merchant;
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = $merchant->transactions();

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $transactions = $query->get();

        $totalAmount = $transactions->where('status', 'success')->sum('amount');
        $successful = $transactions->where('status', 'success')->count();
        $failed = $transactions->where('status', 'failed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_transactions' => $transactions->count(),
                'total_amount' => $totalAmount,
                'successful' => $successful,
                'failed' => $failed,
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $merchant = $request->user()->merchant;

        // Generate CSV
        $transactions = $merchant->transactions()
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->get();

        $csv = $this->generateCsv($transactions);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transactions_' . now()->format('Y-m-d') . '.csv"');
    }

    public function indexAdmin(): View
    {
        return view('admin.reports.index');
    }

    public function exportAdmin(Request $request): StreamedResponse
    {
        try {
            // Log ALL incoming parameters for debugging
            \Log::info('CSV Export - Raw Request', [
                'all_params' => $request->all(),
                'query_string' => $request->getQueryString(),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'merchant_id' => $request->get('merchant_id'),
                'status' => $request->get('status'),
                'payment_method' => $request->get('payment_method'),
            ]);
            
            // Use EXACT same parameter extraction as getDataAdmin
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $merchantId = $request->get('merchant_id');
            $status = $request->get('status');
            $paymentMethod = $request->get('payment_method');

            // Normalize date formats - handle various input formats
            if ($fromDate) {
                $parsedFrom = strtotime($fromDate);
                if ($parsedFrom !== false) {
                    $fromDate = date('Y-m-d', $parsedFrom);
                } else {
                    $fromDate = null;
                }
            }
            
            if ($toDate) {
                $parsedTo = strtotime($toDate);
                if ($parsedTo !== false) {
                    $toDate = date('Y-m-d', $parsedTo);
                } else {
                    $toDate = null;
                }
            }

            // Validate dates (same as getDataAdmin)
            if ($fromDate && $toDate && strtotime($fromDate) > strtotime($toDate)) {
                abort(400, 'From date must be before or equal to To date');
            }

            // Get admin's viewing mode from session (EXACT same as getDataAdmin)
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            // Use EXACT same query logic as getDataAdmin
            $query = \App\Models\Transaction::with(['merchant', 'order']);

            // Filter by test mode (CRITICAL: Must match UI)
            $query->where('test_mode', $isTestMode);

            // Merchant filter (same as getDataAdmin)
            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }

            // Date range filter - EXACT same as getDataAdmin
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate . ' 00:00:00');
            }

            if ($toDate) {
                $query->where('created_at', '<=', $toDate . ' 23:59:59');
            }

            // Status filter (same as getDataAdmin)
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // Payment method filter (same as getDataAdmin)
            if ($paymentMethod && $paymentMethod !== 'all') {
                $query->where('payment_method', $paymentMethod);
            }

            // Get total count before fetching
            $totalCount = $query->count();
            
            // Log query details with test_mode info
            \Log::info('CSV Export Query', [
                'total_count' => $totalCount,
                'test_mode' => $isTestMode,
                'admin_view_mode' => $adminViewMode,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $filename = 'admin_transactions_' . ($fromDate ? $fromDate : 'all') . '_' . ($toDate ? $toDate : 'all') . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
                'Content-Encoding' => 'identity', // Disable compression
            ];

            // Clone the query to avoid issues with chunking
            $exportQuery = clone $query;

            // Use StreamedResponse to avoid memory issues
            $callback = function() use ($exportQuery) {
                // Disable ALL output buffering - do this FIRST
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // Disable compression and buffering at PHP level
                @ini_set('zlib.output_compression', '0');
                @ini_set('output_buffering', '0');
                @ini_set('implicit_flush', '1');
                
                // Disable Apache/Nginx buffering if possible
                if (function_exists('apache_setenv')) {
                    @apache_setenv('no-gzip', 1);
                }
                
                // Set time limit
                @set_time_limit(300);
                
                // Open output stream
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8 (Excel compatibility)
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Force immediate flush
                fflush($file);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Write header row immediately
                fputcsv($file, [
                    'Transaction ID',
                    'Order ID',
                    'Merchant ID',
                    'Merchant Name',
                    'Amount',
                    'Fee Amount',
                    'Net Amount',
                    'Currency',
                    'Payment Method',
                    'Status',
                    'Customer Email',
                    'Customer Phone',
                    'Created At (YYYY-MM-DD HH:MM:SS)',
                ]);
                
                // Force immediate flush after headers
                fflush($file);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Stream transactions one at a time and flush after EACH transaction for immediate download
                $exportQuery->orderBy('created_at', 'desc')->chunk(1, function($transactions) use ($file) {
                    foreach ($transactions as $txn) {
                        try {
                            // Load relationships if not already loaded
                            if (!$txn->relationLoaded('order')) {
                                $txn->load('order');
                            }
                            if (!$txn->relationLoaded('merchant')) {
                                $txn->load('merchant');
                            }

                            // Safely get order ID
                            $orderId = '-';
                            if ($txn->order) {
                                $orderId = $txn->order->order_id ?? '-';
                            } elseif ($txn->order_id) {
                                $orderId = $txn->order_id;
                            }

                            // Safely get merchant name
                            $merchantName = '-';
                            if ($txn->merchant) {
                                $merchantName = $txn->merchant->name ?? '-';
                            }

                            // Safely get customer email
                            $customerEmail = '-';
                            if ($txn->payment_details && is_array($txn->payment_details)) {
                                $customerEmail = $txn->payment_details['customer_email'] ?? '-';
                            }
                            if ($customerEmail === '-' && $txn->customer_email) {
                                $customerEmail = $txn->customer_email;
                            }

                            // Safely get customer phone
                            $customerPhone = '-';
                            if ($txn->payment_details && is_array($txn->payment_details)) {
                                $customerPhone = $txn->payment_details['customer_phone'] ?? '-';
                            }
                            if ($customerPhone === '-' && $txn->customer_phone) {
                                $customerPhone = $txn->customer_phone;
                            }

                            // Safely format dates - Use Excel-friendly format with leading tab to force text
                            $createdAt = '-';
                            if ($txn->created_at) {
                                // Add tab character at the start to force Excel to treat it as text
                                // This prevents Excel from trying to auto-format the date and showing ####
                                $createdAt = "\t" . $txn->created_at->format('Y-m-d H:i:s');
                            }

                            fputcsv($file, [
                                $txn->txn_id ?? '-',
                                $orderId,
                                $txn->merchant_id ?? '-',
                                $merchantName,
                                number_format($txn->amount ?? 0, 2, '.', ''),
                                number_format($txn->fee_amount ?? 0, 2, '.', ''),
                                number_format($txn->net_amount ?? 0, 2, '.', ''),
                                $txn->currency ?? '-',
                                ucfirst($txn->payment_method ?? '-'),
                                ucfirst($txn->status ?? '-'),
                                $customerEmail,
                                $customerPhone,
                                $createdAt,
                            ]);
                            
                            // FLUSH AFTER EACH TRANSACTION - this is critical for immediate download
                            fflush($file);
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                        } catch (\Exception $e) {
                            // Log error but continue
                            \Log::error('Error processing transaction for CSV', [
                                'transaction_id' => $txn->id ?? 'unknown',
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });

                fclose($file);
            };

            // Log for debugging
            \Log::info('Export CSV Results', [
                'query_count' => $totalCount,
                'filters' => $request->all()
            ]);

            if ($totalCount === 0) {
                // Log why no transactions found
                \Log::warning('No transactions found for export', [
                    'filters' => $request->all(),
                    'total_in_db' => \App\Models\Transaction::count(),
                ]);
            }

            // Create StreamedResponse with proper configuration
            $response = new StreamedResponse($callback, 200, $headers);
            
            // Ensure no buffering
            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Content-Encoding', 'identity');
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('CSV Export Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            abort(500, 'Error exporting report: ' . $e->getMessage());
        }
    }

    public function getDataAdmin(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $merchantId = $request->get('merchant_id');
            $status = $request->get('status');
            $paymentMethod = $request->get('payment_method');
            $page = $request->get('page', 1);
            $perPage = min($request->get('per_page', 25), 100);

            // Validate dates
            if ($fromDate && $toDate) {
                if (strtotime($fromDate) > strtotime($toDate)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'From date must be before or equal to To date',
                    ], 400);
                }
            }

            // Get admin's viewing mode from session (same as other admin controllers)
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $query = \App\Models\Transaction::with(['merchant', 'order']);

            // Filter by test mode (IMPORTANT: Match what's shown in UI)
            $query->where('test_mode', $isTestMode);

            // Merchant filter
            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }

            // Date range filter - use proper datetime comparison
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate . ' 00:00:00');
            }

            if ($toDate) {
                $query->where('created_at', '<=', $toDate . ' 23:59:59');
            }

            // Status filter
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // Payment method filter
            if ($paymentMethod && $paymentMethod !== 'all') {
                $query->where('payment_method', $paymentMethod);
            }

            // Get total counts before pagination
            $totalTransactions = $query->count();
            $totalAmount = (clone $query)->where('status', 'success')->sum('amount');
            $successful = (clone $query)->where('status', 'success')->count();
            $failed = (clone $query)->where('status', 'failed')->count();
            $pending = (clone $query)->whereIn('status', ['pending', 'initiated', 'authorized'])->count();

            // Paginate transactions
            $transactions = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Format transaction data
            $formattedTransactions = $transactions->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'txn_id' => $txn->txn_id,
                    'order_id' => $txn->order->order_id ?? '-',
                    'merchant_id' => $txn->merchant_id,
                    'merchant_name' => $txn->merchant->name ?? '-',
                    'amount' => number_format($txn->amount, 2),
                    'fee_amount' => number_format($txn->fee_amount ?? 0, 2),
                    'net_amount' => number_format($txn->net_amount, 2),
                    'currency' => $txn->currency,
                    'payment_method' => ucfirst($txn->payment_method ?? '-'),
                    'status' => $txn->status,
                    'customer_email' => $txn->payment_details['customer_email'] ?? $txn->customer_email ?? '-',
                    'customer_phone' => $txn->payment_details['customer_phone'] ?? $txn->customer_phone ?? '-',
                    'created_at' => $txn->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $txn->created_at->format('d M Y, h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_transactions' => $totalTransactions,
                        'total_amount' => number_format($totalAmount, 2),
                        'successful' => $successful,
                        'failed' => $failed,
                        'pending' => $pending,
                    ],
                    'transactions' => $formattedTransactions,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'last_page' => $transactions->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function generateCsv($transactions): string
    {
        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'Transaction ID',
            'Order ID',
            'Amount',
            'Fee',
            'Net Amount',
            'Currency',
            'Payment Method',
            'Status',
            'Created At',
        ]);

        // Data rows
        foreach ($transactions as $txn) {
            fputcsv($output, [
                $txn->txn_id,
                $txn->order->order_id ?? '',
                $txn->amount,
                $txn->fee_amount,
                $txn->net_amount,
                $txn->currency,
                $txn->payment_method,
                $txn->status,
                $txn->created_at->toDateTimeString(),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    protected function generateCsvAdmin($transactions): string
    {
        $output = fopen('php://temp', 'r+');

        // Add BOM for UTF-8 to ensure Excel opens it correctly
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header row with all details
        fputcsv($output, [
            'Transaction ID',
            'Order ID',
            'Merchant ID',
            'Merchant Name',
            'Amount',
            'Fee Amount',
            'Net Amount',
            'Currency',
            'Payment Method',
            'Status',
            'Customer Email',
            'Customer Phone',
            'Created At',
        ]);

        // Data rows
        foreach ($transactions as $txn) {
            try {
                // Safely get order ID
                $orderId = '-';
                if ($txn->order) {
                    $orderId = $txn->order->order_id ?? '-';
                } elseif ($txn->order_id) {
                    $orderId = $txn->order_id;
                }

                // Safely get merchant name
                $merchantName = '-';
                if ($txn->merchant) {
                    $merchantName = $txn->merchant->name ?? '-';
                }

                // Safely get customer email
                $customerEmail = '-';
                if ($txn->payment_details && is_array($txn->payment_details)) {
                    $customerEmail = $txn->payment_details['customer_email'] ?? '-';
                }
                if ($customerEmail === '-' && $txn->customer_email) {
                    $customerEmail = $txn->customer_email;
                }

                // Safely get customer phone
                $customerPhone = '-';
                if ($txn->payment_details && is_array($txn->payment_details)) {
                    $customerPhone = $txn->payment_details['customer_phone'] ?? '-';
                }
                if ($customerPhone === '-' && $txn->customer_phone) {
                    $customerPhone = $txn->customer_phone;
                }

                // Safely format dates
                $createdAt = '-';
                if ($txn->created_at) {
                    $createdAt = $txn->created_at->format('Y-m-d H:i:s');
                }

                fputcsv($output, [
                    $txn->txn_id ?? '-',
                    $orderId,
                    $txn->merchant_id ?? '-',
                    $merchantName,
                    number_format($txn->amount ?? 0, 2, '.', ''),
                    number_format($txn->fee_amount ?? 0, 2, '.', ''),
                    number_format($txn->net_amount ?? 0, 2, '.', ''),
                    $txn->currency ?? '-',
                    ucfirst($txn->payment_method ?? '-'),
                    ucfirst($txn->status ?? '-'),
                    $customerEmail,
                    $customerPhone,
                    $createdAt,
                ]);
            } catch (\Exception $e) {
                // Log error but continue with other transactions
                \Log::error('Error processing transaction for CSV', [
                    'transaction_id' => $txn->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                // Still add a row with available data
                fputcsv($output, [
                    $txn->txn_id ?? 'ERROR',
                    'ERROR',
                    $txn->merchant_id ?? '-',
                    'ERROR',
                    '0.00',
                    '0.00',
                    '0.00',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                ]);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}

