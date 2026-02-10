<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\S2SCallbackLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class S2SCallbackLogController extends Controller
{
    /**
     * Display the S2S Callback Logs page.
     */
    public function index(): View
    {
        return view('admin.s2s-callback-logs.index');
    }

    /**
     * Get S2S Callback Logs data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = S2SCallbackLog::query()->with(['merchant', 'order']);

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $request->get('date_range');
                if (strpos($dateRange, ' - ') !== false) {
                    $dates = explode(' - ', $dateRange);
                    if (count($dates) === 2) {
                        $startDate = trim($dates[0]);
                        $endDate = trim($dates[1]);
                        if (!empty($startDate) && !empty($endDate)) {
                            // Parse MM/DD/YYYY format
                            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $startDate, $startMatches) &&
                                preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $endDate, $endMatches)) {
                                $start = $startMatches[3] . '-' . $startMatches[1] . '-' . $startMatches[2] . ' 00:00:00';
                                $end = $endMatches[3] . '-' . $endMatches[1] . '-' . $endMatches[2] . ' 23:59:59';
                                $query->whereBetween('payment_datetime', [$start, $end]);
                            }
                        }
                    }
                }
            }

            // Id filter
            if ($request->filled('id')) {
                $query->where('id', $request->get('id'));
            }

            // Merchant ID filter
            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            // Merchant Name filter
            if ($request->filled('merchant_name')) {
                $query->where('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }

            // Order ID filter
            if ($request->filled('order_id')) {
                $query->where('order_id', $request->get('order_id'));
            }

            // Tran Id filter
            if ($request->filled('tran_id')) {
                $query->where('tran_id', 'like', '%' . $request->get('tran_id') . '%');
            }

            // Transaction Id filter
            if ($request->filled('transaction_id')) {
                $query->where('transaction_id', 'like', '%' . $request->get('transaction_id') . '%');
            }

            // CallBack URL filter
            if ($request->filled('callback_url')) {
                $query->where('callback_url', 'like', '%' . $request->get('callback_url') . '%');
            }

            // Payment Datetime filter
            if ($request->filled('payment_datetime')) {
                $paymentDatetime = $request->get('payment_datetime');
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $paymentDatetime, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('payment_datetime', $date);
                } else {
                    $query->whereDate('payment_datetime', $paymentDatetime);
                }
            }

            // Http Status Code filter
            if ($request->filled('http_status_code')) {
                $query->where('http_status_code', $request->get('http_status_code'));
            }

            // Initiated By filter
            if ($request->filled('initiated_by')) {
                $query->where('initiated_by', 'like', '%' . $request->get('initiated_by') . '%');
            }

            // Callback Datetime filter
            if ($request->filled('callback_datetime')) {
                $callbackDatetime = $request->get('callback_datetime');
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $callbackDatetime, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('callback_datetime', $date);
                } else {
                    $query->whereDate('callback_datetime', $callbackDatetime);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $logs = $query->paginate($perPage);

            // Transform data
            $data = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'merchant_id' => $log->merchant_id ?? 'N/A',
                    'merchant_name' => $log->merchant_name ?? 'N/A',
                    'order_id' => $log->order_id ?? 'N/A',
                    'tran_id' => $log->tran_id ?? 'N/A',
                    'transaction_id' => $log->transaction_id ?? 'N/A',
                    'callback_url' => $log->callback_url ?? 'N/A',
                    'payment_datetime' => $log->payment_datetime ? $log->payment_datetime->format('m/d/Y H:i:s') : 'N/A',
                    'http_status_code' => $log->http_status_code ?? 'N/A',
                    'initiated_by' => $log->initiated_by ?? 'N/A',
                    'callback_datetime' => $log->callback_datetime ? $log->callback_datetime->format('m/d/Y H:i:s') : 'N/A',
                    'request_log' => $log->request_log ?? 'N/A',
                    'response_log' => $log->response_log ?? 'N/A',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch S2S callback logs: ' . $e->getMessage(),
            ], 500);
        }
    }
}
