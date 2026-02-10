<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankCodeSuccessRateController extends Controller
{
    /**
     * Display the Bank Code Success Rate page.
     */
    public function index(): View
    {
        return view('admin.reports.success-rate.bankcode-wise');
    }

    /**
     * Get Bank Code Success Rate data.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            // Get admin's viewing mode from session
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $perPage = min($request->integer('per_page', 5), 50);

            // Build base query
            $query = Transaction::query()
                ->select(
                    'banks.code as bank_code',
                    DB::raw('COUNT(CASE WHEN transactions.status = "success" THEN 1 END) as success_count'),
                    DB::raw('COUNT(CASE WHEN transactions.status = "failed" THEN 1 END) as failure_count'),
                    DB::raw('COUNT(CASE WHEN transactions.status = "cancelled" OR transactions.status = "pending" THEN 1 END) as dropped_count'),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('ROUND((COUNT(CASE WHEN transactions.status = "success" THEN 1 END) * 100.0 / COUNT(*)), 2) as success_rate')
                )
                ->leftJoin('banks', 'transactions.bank_id', '=', 'banks.id')
                ->where('transactions.test_mode', $isTestMode)
                ->whereNotNull('banks.code')
                ->groupBy('banks.code');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $request->get('date_range');
                if (strpos($dateRange, ' - ') !== false) {
                    $dates = explode(' - ', $dateRange);
                    if (count($dates) === 2) {
                        $startDate = trim($dates[0]);
                        $endDate = trim($dates[1]);
                        if (!empty($startDate) && !empty($endDate)) {
                            $query->whereBetween('transactions.created_at', [
                                date('Y-m-d 00:00:00', strtotime($startDate)),
                                date('Y-m-d 23:59:59', strtotime($endDate))
                            ]);
                        }
                    }
                }
            }

            // Merchant filter
            $merchantIds = $request->get('merchant_ids');
            if ($merchantIds) {
                // Handle both array and string formats
                if (is_string($merchantIds)) {
                    $merchantIds = json_decode($merchantIds, true);
                }
                if (is_array($merchantIds) && count($merchantIds) > 0) {
                    $merchantIds = array_filter(array_map('intval', $merchantIds));
                    if (count($merchantIds) > 0) {
                        $query->whereIn('transactions.merchant_id', $merchantIds);
                    }
                }
            }

            // Bank code filter
            if ($request->filled('bank_code')) {
                $query->where('banks.code', 'like', '%' . $request->get('bank_code') . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'bank_code');
            $sortDirection = $request->get('sort_direction', 'asc');
            
            // Map sort columns
            $allowedSortColumns = [
                'bank_code' => 'banks.code',
                'success_count' => 'success_count',
                'failure_count' => 'failure_count',
                'dropped_count' => 'dropped_count',
                'success_rate' => 'success_rate',
            ];

            if (isset($allowedSortColumns[$sortBy])) {
                $query->orderBy($allowedSortColumns[$sortBy], $sortDirection);
            } else {
                $query->orderBy('banks.code', 'asc');
            }

            // Get total before pagination
            $totalQuery = clone $query;
            $total = count($totalQuery->get());

            // Apply pagination manually since we're using raw aggregations
            $page = $request->integer('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $results = $query->offset($offset)->limit($perPage)->get();

            // Format results
            $formattedResults = $results->map(function ($item) {
                return [
                    'bank_code' => $item->bank_code ?? 'N/A',
                    'success_count' => (int)$item->success_count,
                    'failure_count' => (int)$item->failure_count,
                    'dropped_count' => (int)$item->dropped_count,
                    'success_rate' => (float)$item->success_rate,
                ];
            });

            $lastPage = ceil($total / $perPage);

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank code success rate: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get bank codes for dropdown.
     */
    public function getBankCodes(): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $bankCodes = Transaction::query()
                ->select('banks.code')
                ->leftJoin('banks', 'transactions.bank_id', '=', 'banks.id')
                ->where('transactions.test_mode', $isTestMode)
                ->whereNotNull('banks.code')
                ->distinct()
                ->orderBy('banks.code')
                ->pluck('code')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $bankCodes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank codes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get merchants for dropdown.
     */
    public function getMerchants(): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $merchants = Merchant::select('id', 'business_name', 'merchant_id', 'name')
                ->where('test_mode', $isTestMode)
                ->orderBy('business_name')
                ->get()
                ->map(function ($merchant) {
                    return [
                        'id' => $merchant->id,
                        'name' => $merchant->business_name ?? $merchant->name ?? $merchant->merchant_id ?? 'N/A',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $merchants,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchants: ' . $e->getMessage(),
            ], 500);
        }
    }
}
