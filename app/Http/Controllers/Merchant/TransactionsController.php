<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionsController extends Controller
{
    use LogsConditionally;
    /**
     * Display transactions page.
     */
    public function index(): View
    {
        return view('merchant.transactions.index');
    }

    /**
     * Get transactions data for Angular.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;

            $perPage = min($request->get('per_page', 10), 50);
            
            // Filter by current merchant mode (test or live)
            $query = $merchant->transactions()
                ->where('test_mode', $merchant->test_mode)
                ->with(['order.paymentLink', 'settlement'])
                ->latest();

            // Date range filter (only apply if provided)
            if ($request->has('date_range') && !empty($request->get('date_range'))) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2 && !empty(trim($dates[0])) && !empty(trim($dates[1]))) {
                    $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            // Status filter - be very permissive
            $status = $request->get('status');
            if (!empty($status) && $status !== 'all' && $status !== '') {
                $query->where('status', $status);
            }
            // Otherwise show ALL transactions

            // Column filters (matching admin structure)
            if ($request->has('filter_transaction_id') && $request->get('filter_transaction_id')) {
                $query->where('txn_id', 'like', "%{$request->get('filter_transaction_id')}%");
            }
            if ($request->has('filter_order_id') && $request->get('filter_order_id')) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('order_id', 'like', "%{$request->get('filter_order_id')}%");
                });
            }
            if ($request->has('filter_payment_status') && $request->get('filter_payment_status') !== 'all') {
                $query->where('status', $request->get('filter_payment_status'));
            }
            if ($request->has('filter_amount_paid') && $request->get('filter_amount_paid')) {
                $amount = floatval($request->get('filter_amount_paid'));
                $query->where('amount', $amount);
            }
            if ($request->has('filter_payment_mode') && $request->get('filter_payment_mode')) {
                $query->where('payment_method', 'like', "%{$request->get('filter_payment_mode')}%");
            }
            if ($request->filled('filter_payment_channel')) {
                $channel = trim((string) $request->get('filter_payment_channel'));
                $query->where('gateway_response->channel', 'like', '%' . $channel . '%');
            }
            if ($request->filled('filter_merc_approved') && $request->get('filter_merc_approved') !== 'all') {
                $isApproved = strtolower((string) $request->get('filter_merc_approved')) === 'yes';
                if ($isApproved) {
                    $query->where('status', 'success');
                } else {
                    $query->where('status', '!=', 'success');
                }
            }
            if ($request->filled('filter_currency_code')) {
                $currency = strtoupper(trim((string) $request->get('filter_currency_code')));
                $query->where('currency', 'like', '%' . $currency . '%');
            }
            if ($request->filled('filter_bank_reference_number')) {
                $bankRef = trim((string) $request->get('filter_bank_reference_number'));
                $query->where('gateway_response->bank_reference', 'like', '%' . $bankRef . '%');
            }
            if ($request->filled('filter_acq_payment_id')) {
                $acqPaymentId = trim((string) $request->get('filter_acq_payment_id'));
                $query->where('gateway_response->acq_payment_id', 'like', '%' . $acqPaymentId . '%');
            }
            if ($request->filled('filter_acq_transaction_id')) {
                $acqTransactionId = trim((string) $request->get('filter_acq_transaction_id'));
                $query->where('gateway_response->acq_transaction_id', 'like', '%' . $acqTransactionId . '%');
            }
            if ($request->filled('filter_provider_name')) {
                $provider = trim((string) $request->get('filter_provider_name'));
                $query->where('gateway_response->provider', 'like', '%' . $provider . '%');
            }
            if ($request->filled('filter_account_id')) {
                $accountId = trim((string) $request->get('filter_account_id'));
                $query->where('gateway_response->account_id', 'like', '%' . $accountId . '%');
            }
            if ($request->filled('filter_tdr_amount')) {
                $tdrAmount = (float) $request->get('filter_tdr_amount');
                $query->where('fee_amount', $tdrAmount);
            }
            if ($request->filled('filter_gst_amount')) {
                $vatAmount = (float) $request->get('filter_gst_amount');
                $query->whereRaw('ROUND(COALESCE(fee_amount, 0) * 0.18, 2) = ?', [$vatAmount]);
            }
            if ($request->filled('filter_is_updated_by_recon') && $request->get('filter_is_updated_by_recon') !== 'all') {
                // Merchant table currently renders this as static "No"
                $reconFilter = strtolower(trim((string) $request->get('filter_is_updated_by_recon')));
                if ($reconFilter === 'yes') {
                    $query->whereRaw('1 = 0');
                }
            }
            if ($request->filled('filter_tdr_amount_paid_by_merchant')) {
                $tdrMerchant = (float) $request->get('filter_tdr_amount_paid_by_merchant');
                $query->where('fee_amount', $tdrMerchant);
            }
            if ($request->filled('filter_tdr_amount_paid_by_customer')) {
                $tdrCustomer = (float) $request->get('filter_tdr_amount_paid_by_customer');
                if (abs($tdrCustomer) > 0.00001) {
                    $query->whereRaw('1 = 0');
                }
            }
            if ($request->filled('filter_gst_paid_by_merchant')) {
                $vatMerchant = (float) $request->get('filter_gst_paid_by_merchant');
                $query->whereRaw('ROUND(COALESCE(fee_amount, 0) * 0.18, 2) = ?', [$vatMerchant]);
            }
            if ($request->filled('filter_gst_paid_by_customer')) {
                $vatCustomer = (float) $request->get('filter_gst_paid_by_customer');
                if (abs($vatCustomer) > 0.00001) {
                    $query->whereRaw('1 = 0');
                }
            }
            if ($request->filled('filter_net_settlements_amount')) {
                $netAmount = (float) $request->get('filter_net_settlements_amount');
                $query->whereRaw('COALESCE(net_amount, amount) = ?', [$netAmount]);
            }
            if ($request->filled('filter_customer_ip_address')) {
                $ipAddress = trim((string) $request->get('filter_customer_ip_address'));
                $query->where('ip_address', 'like', '%' . $ipAddress . '%');
            }
            if ($request->filled('filter_card_holder_name')) {
                $cardHolder = trim((string) $request->get('filter_card_holder_name'));
                $query->where(function ($q) use ($cardHolder) {
                    $q->where('payment_details->card_holder_name', 'like', '%' . $cardHolder . '%')
                      ->orWhere('payment_details->card_holder', 'like', '%' . $cardHolder . '%');
                });
            }
            if ($request->filled('filter_card_number')) {
                $cardLast4 = preg_replace('/\D+/', '', (string) $request->get('filter_card_number'));
                if (!empty($cardLast4)) {
                    $query->where('payment_details->last4', 'like', '%' . $cardLast4 . '%');
                }
            }
            foreach (['udf1', 'udf2', 'udf3', 'udf4', 'udf5'] as $udfKey) {
                $requestKey = 'filter_' . $udfKey;
                if ($request->filled($requestKey)) {
                    $udfVal = trim((string) $request->get($requestKey));
                    $query->where('payment_details->' . $udfKey, 'like', '%' . $udfVal . '%');
                }
            }
            if ($request->filled('filter_upi_id')) {
                $upiId = trim((string) $request->get('filter_upi_id'));
                $query->where('gateway_response->upi_id', 'like', '%' . $upiId . '%');
            }
            // NOTE: We intentionally ignore filter_transaction_datetime and
            // filter_transaction_initiation_time on the backend now.
            // Dates are handled entirely on the client (Angular) so that the
            // date-input format and the displayed "dd-mm-yyyy HH:mm:ss" format
            // are always consistent for the user.
            if ($request->has('filter_transaction_sequence_id') && $request->get('filter_transaction_sequence_id')) {
                $query->where('id', $request->get('filter_transaction_sequence_id'));
            }
            if ($request->filled('filter_settlement_status') && $request->get('filter_settlement_status') !== 'all') {
                $query->where('settlement_status', $request->get('filter_settlement_status'));
            }
            if ($request->filled('filter_settlement_batch_id')) {
                $batch = trim((string) $request->get('filter_settlement_batch_id'));
                $query->whereHas('settlement', function ($q) use ($batch) {
                    $q->where('settlement_id', 'like', '%'.$batch.'%');
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'txn_id', 'created_at', 'amount', 'status', 'settlement_status'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $page = max(1, (int) $request->get('page', 1));
            $total = (clone $query)->count();
            $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
            if ($page > $lastPage) {
                $page = $lastPage;
            }

            $transactions = $query
                ->forPage($page, $perPage)
                ->get();

        // Format data with all required columns (matching admin format)
        // PCI-DSS: Use sanitized payment details (no card data)
        $data = $transactions->map(function($transaction) {
            try {
                $paymentDetails = $transaction->getSanitizedPaymentDetails() ?? [];
                $gatewayResponse = $transaction->getSanitizedGatewayResponse() ?? [];
            } catch (\Exception $e) {
                // Fallback if sanitization fails - log but don't break
                \Log::warning('Transaction sanitization failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                $paymentDetails = [];
                $gatewayResponse = [];
            }
            
            try {
                return [
                    'id' => $transaction->id,
                    'merchant_id' => $transaction->merchant_id,
                    'transaction_initiation_time' => $transaction->created_at->format('d-m-Y H:i:s'),
                    'merchant_name' => $transaction->merchant->name ?? '-',
                    'transaction_sequence_id' => $transaction->id,
                    'transaction_order_id' => $transaction->order_id ?? '-',
                    'transaction_datetime' => $transaction->created_at->format('d-m-Y H:i:s'),
                    'transaction_id' => $transaction->txn_id,
                    'amount_paid_by_customer' => number_format($transaction->amount, 2),
                    'payment_status' => $transaction->status,
                    // Internal PSP/routing diagnostics — admin-only (see admin transactions API)
                    'failure_reason' => null,
                    'payment_mode' => $transaction->payment_method ?? '-',
                    'payment_channel' => $gatewayResponse['channel'] ?? '-',
                    'merc_approved' => $transaction->status === 'success' ? 'Yes' : 'No',
                    'currency_code' => $transaction->currency ?? 'INR',
                    'bank_reference_number' => $gatewayResponse['bank_reference'] ?? '-',
                    'acq_payment_id' => $gatewayResponse['acq_payment_id'] ?? '-',
                    'acq_transaction_id' => $gatewayResponse['acq_transaction_id'] ?? '-',
                    'provider_name' => $gatewayResponse['provider'] ?? '-',
                    'account_id' => $gatewayResponse['account_id'] ?? '-',
                    'tdr_amount' => number_format($transaction->fee_amount ?? 0, 2),
                    'gst_amount' => number_format(($transaction->fee_amount ?? 0) * 0.18, 2),
                    'is_updated_by_recon' => 'No',
                    'tdr_amount_paid_by_merchant' => number_format($transaction->fee_amount ?? 0, 2),
                    'tdr_amount_paid_by_customer' => '0.00',
                    'gst_paid_by_merchant' => number_format(($transaction->fee_amount ?? 0) * 0.18, 2),
                    'gst_paid_by_customer' => '0.00',
                    'net_settlements_amount' => number_format($transaction->net_amount ?? $transaction->amount, 2),
                    'admin_merchant_rate_pct' => $transaction->admin_fee_percentage_snapshot !== null
                        ? number_format((float) $transaction->admin_fee_percentage_snapshot, 4)
                        : '-',
                    'settlement_batch_id' => $transaction->status === 'success' && $transaction->relationLoaded('settlement') && $transaction->settlement
                        ? $transaction->settlement->settlement_id
                        : '-',
                    'settlement_txn_status' => $transaction->status === 'success'
                        ? ($transaction->settlement_status ?? 'pending')
                        : '-',
                    'settlement_txn_status_label' => $transaction->status === 'success'
                        ? $this->merchantTxnSettlementLabel($transaction->settlement_status ?? 'pending')
                        : '—',
                    'card_holder_name' => $paymentDetails['card_holder_name'] ?? $paymentDetails['card_holder'] ?? '-',
                    // PCI-DSS: Use last4 from sanitized data (card_number never stored)
                    'card_number' => isset($paymentDetails['last4']) ? '****' . $paymentDetails['last4'] : '-',
                    'customer_ip_address' => $transaction->ip_address ?? '-',
                    'udf1' => $paymentDetails['udf1'] ?? '-',
                    'udf2' => $paymentDetails['udf2'] ?? '-',
                    'udf3' => $paymentDetails['udf3'] ?? '-',
                    'udf4' => $paymentDetails['udf4'] ?? '-',
                    'udf5' => $paymentDetails['udf5'] ?? '-',
                    'upi_id' => $gatewayResponse['upi_id'] ?? '-',
                ];
            } catch (\Exception $e) {
                // If transaction data access fails, return minimal data
                \Log::error('Transaction data formatting failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                return [
                    'id' => $transaction->id ?? 0,
                    'transaction_id' => $transaction->txn_id ?? '-',
                    'amount_paid_by_customer' => number_format($transaction->amount ?? 0, 2),
                    'payment_status' => $transaction->status ?? '-',
                    'error' => 'Data formatting failed'
                ];
            }
        });

            return response()->json([
                'success' => true,
                'data' => $data->values()->all(), // Ensure array is properly indexed
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => (int) $perPage,
                    'total' => (int) $total,
                    'last_page' => (int) $lastPage,
                    'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
                    'to' => $total > 0 ? min($page * $perPage, $total) : null,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching merchant transactions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
            ], 500);
        }
    }

    /**
     * Admin view for all transactions.
     */
    public function indexAdmin(): View
    {
        $this->logInfo('Admin transactions page accessed', ['user_id' => auth()->id()]);
        return view('admin.transactions.index');
    }

    /**
     * Get all transactions data for admin.
     */
    public function getDataAdmin(Request $request): JsonResponse
    {
        try {
            $this->logInfo('Admin transactions data requested', [
                'user_id' => auth()->id(),
                'filters' => $request->only(['merchant_id', 'status', 'per_page'])
            ]);

            $perPage = min($request->get('per_page', 10), 50);
            $merchantId = $request->get('merchant_id');
            $status = $request->get('status');

            $query = \App\Models\Transaction::with(['order.paymentLink', 'merchant'])->latest();

            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $transactions = $query->paginate($perPage);

            $this->logDebug('Admin transactions retrieved', [
                'count' => $transactions->count(),
                'total' => $transactions->total()
            ]);

            // PCI-DSS: Sanitize transaction data before returning
            $sanitizedData = $transactions->map(function($transaction) {
                try {
                    $data = $transaction->toArray();
                    // Replace payment_details and gateway_response with sanitized versions
                    $data['payment_details'] = $transaction->getSanitizedPaymentDetails();
                    $data['gateway_response'] = $transaction->getSanitizedGatewayResponse();
                    return $data;
                } catch (\Exception $e) {
                    // Fallback: if sanitization fails, return transaction without sensitive fields
                    $data = $transaction->toArray();
                    unset($data['payment_details'], $data['gateway_response']);
                    return $data;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $sanitizedData->all(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
            ], 500);
        }
    }

    public function export(Request $request, FileLifecycleService $fileLifecycleService): BinaryFileResponse
    {
        $merchant = $request->user()->merchant;

        $query = $merchant->transactions()
            ->where('test_mode', $merchant->test_mode)
            ->with(['order.paymentLink']);

        $status = $request->get('status');
        $paymentMethod = $request->get('payment_method');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $search = $request->get('search');

        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($paymentMethod && $paymentMethod !== 'all' && $paymentMethod !== '') {
            $query->where('payment_method', $paymentMethod);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('txn_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->latest()->get();

        $fileName = 'transactions_' . now()->format('Y-m-d_His') . '.csv';
        $relativePath = $fileLifecycleService->createCsvReport($fileName, function ($file) use ($transactions): void {
            fputcsv($file, [
                'Transaction ID', 'Order ID', 'Amount', 'Fee Amount', 'Net Amount',
                'Currency', 'Payment Method', 'Status', 'Customer Email', 'Customer Phone',
                'Created At', 'Failure Reason'
            ]);

            foreach ($transactions as $transaction) {
                // PCI-DSS: Use sanitized payment details (no card data)
                $paymentDetails = $transaction->getSanitizedPaymentDetails() ?? [];
                fputcsv($file, [
                    $transaction->txn_id,
                    $transaction->order_id ?? '-',
                    number_format($transaction->amount, 2),
                    number_format($transaction->fee_amount ?? 0, 2),
                    number_format($transaction->net_amount ?? $transaction->amount, 2),
                    $transaction->currency ?? 'INR',
                    $transaction->payment_method ?? '-',
                    $transaction->status,
                    $paymentDetails['customer_email'] ?? '-',
                    $paymentDetails['customer_phone'] ?? '-',
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    '-',
                ]);
            }
        });

        return $fileLifecycleService->downloadAndDelete(
            $relativePath,
            $fileName,
            ['Content-Type' => 'text/csv']
        );
    }

    protected function merchantTxnSettlementLabel(string $status): string
    {
        return match ($status) {
            'settled' => 'Settled',
            'pending' => 'Pending settlement',
            'on_hold' => 'On hold',
            'excluded' => 'Excluded',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

