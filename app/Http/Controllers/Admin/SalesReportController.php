<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    /**
     * Display Sales Date and Merchant Report page.
     */
    public function dateAndMerchant(): View
    {
        return view('admin.reports.sales.date-and-merchant');
    }

    /**
     * Display Sales Date and Acquirer Report page.
     */
    public function dateAndAcquirer(): View
    {
        return view('admin.reports.sales.date-and-acquirer');
    }

    /**
     * Display Sales Date and TID Report page.
     */
    public function dateAndTid(): View
    {
        return view('admin.reports.sales.date-and-tid');
    }

    /**
     * Display Sales Month and Merchant Report page.
     */
    public function monthAndMerchant(): View
    {
        return view('admin.reports.sales.month-and-merchant');
    }

    /**
     * Display Sales Month and Acquirer Report page.
     */
    public function monthAndAcquirer(): View
    {
        return view('admin.reports.sales.month-and-acquirer');
    }

    /**
     * Display Sales Month and TID Report page.
     */
    public function monthAndTid(): View
    {
        return view('admin.reports.sales.month-and-tid');
    }

    /**
     * Get Sales Date and Merchant data.
     */
    public function getDateAndMerchantData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    merchants.business_name as merchant_name,
                    DATE(transactions.created_at) as transaction_date,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->join('merchants', 'transactions.merchant_id', '=', 'merchants.id')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('merchants.business_name', 'transaction_date');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('merchant_name')) {
                $query->having('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }
            if ($request->filled('transaction_date')) {
                $query->having('transaction_date', $request->get('transaction_date'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get Sales Date and Acquirer data.
     */
    public function getDateAndAcquirerData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    COALESCE(transactions.gateway, "N/A") as transaction_provider,
                    DATE(transactions.created_at) as transaction_date,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('transaction_provider', 'transaction_date');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('transaction_provider')) {
                $query->having('transaction_provider', 'like', '%' . $request->get('transaction_provider') . '%');
            }
            if ($request->filled('transaction_date')) {
                $query->having('transaction_date', $request->get('transaction_date'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Sales Date and TID data.
     */
    public function getDateAndTidData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    COALESCE(transactions.gateway, "N/A") as transaction_provider,
                    COALESCE(transactions.gateway_txn_id, "N/A") as mid_name,
                    DATE(transactions.created_at) as transaction_date,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('transaction_provider', 'mid_name', 'transaction_date');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('transaction_provider')) {
                $query->having('transaction_provider', 'like', '%' . $request->get('transaction_provider') . '%');
            }
            if ($request->filled('mid_name')) {
                $query->having('mid_name', 'like', '%' . $request->get('mid_name') . '%');
            }
            if ($request->filled('transaction_date')) {
                $query->having('transaction_date', $request->get('transaction_date'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Sales Month and Merchant data.
     */
    public function getMonthAndMerchantData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    merchants.business_name as merchant_name,
                    DATE_FORMAT(transactions.created_at, "%Y-%m") as transaction_month,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->join('merchants', 'transactions.merchant_id', '=', 'merchants.id')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('merchants.business_name', 'transaction_month');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('merchant_name')) {
                $query->having('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }
            if ($request->filled('transaction_month')) {
                $query->having('transaction_month', $request->get('transaction_month'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_month');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Sales Month and Acquirer data.
     */
    public function getMonthAndAcquirerData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    COALESCE(transactions.gateway, "N/A") as transaction_provider,
                    DATE_FORMAT(transactions.created_at, "%Y-%m") as transaction_month,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('transaction_provider', 'transaction_month');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('transaction_provider')) {
                $query->having('transaction_provider', 'like', '%' . $request->get('transaction_provider') . '%');
            }
            if ($request->filled('transaction_month')) {
                $query->having('transaction_month', $request->get('transaction_month'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_month');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Sales Month and TID data.
     */
    public function getMonthAndTidData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';
            $perPage = min($request->integer('per_page', 5), 50);

            $query = Transaction::selectRaw('
                    COALESCE(transactions.gateway, "N/A") as transaction_provider,
                    COALESCE(transactions.gateway_txn_id, "N/A") as mid_name,
                    DATE_FORMAT(transactions.created_at, "%Y-%m") as transaction_month,
                    COUNT(transactions.id) as transaction_count,
                    COALESCE(SUM(transactions.amount), 0) as transaction_total_amount
                ')
                ->where('transactions.test_mode', $isTestMode)
                ->where('transactions.status', 'success')
                ->groupBy('transaction_provider', 'mid_name', 'transaction_month');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $this->parseDateRange($request->get('date_range'));
                if ($dateRange) {
                    $query->whereBetween('transactions.created_at', $dateRange);
                }
            }

            // Apply filters
            if ($request->filled('transaction_provider')) {
                $query->having('transaction_provider', 'like', '%' . $request->get('transaction_provider') . '%');
            }
            if ($request->filled('mid_name')) {
                $query->having('mid_name', 'like', '%' . $request->get('mid_name') . '%');
            }
            if ($request->filled('transaction_month')) {
                $query->having('transaction_month', $request->get('transaction_month'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_month');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse date range string to array.
     */
    private function parseDateRange(?string $dateRange): ?array
    {
        if (!$dateRange) {
            return null;
        }

        if (strpos($dateRange, ' - ') !== false) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $startDate = trim($dates[0]);
                $endDate = trim($dates[1]);
                if (!empty($startDate) && !empty($endDate)) {
                    return [
                        Carbon::createFromFormat('m/d/Y', $startDate)->startOfDay(),
                        Carbon::createFromFormat('m/d/Y', $endDate)->endOfDay()
                    ];
                }
            }
        }

        return null;
    }
}
