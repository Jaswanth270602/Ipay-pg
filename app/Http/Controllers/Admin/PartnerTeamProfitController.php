<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerTeamProfit;
use App\Models\Partner;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PartnerTeamProfitController extends Controller
{
    /**
     * Display the Partner Team Profit Report page.
     */
    public function index(): View
    {
        return view('admin.reports.profitability.partner-team-profit');
    }

    /**
     * Get Partner Team Profit data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            // Get admin's viewing mode from session
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $perPage = min($request->integer('per_page', 5), 50);

            $query = PartnerTeamProfit::query()
                ->join('merchants', 'partner_team_profit.merchant_id', '=', 'merchants.id')
                ->where('merchants.test_mode', $isTestMode)
                ->select('partner_team_profit.*');

            // Date range filter
            if ($request->filled('date_range')) {
                $dateRange = $request->get('date_range');
                if (strpos($dateRange, ' - ') !== false) {
                    $dates = explode(' - ', $dateRange);
                    if (count($dates) === 2) {
                        $startDate = trim($dates[0]);
                        $endDate = trim($dates[1]);
                        if (!empty($startDate) && !empty($endDate)) {
                            $query->whereBetween('partner_team_profit.payment_datetime', [
                                date('Y-m-d 00:00:00', strtotime($startDate)),
                                date('Y-m-d 23:59:59', strtotime($endDate))
                            ]);
                        }
                    }
                }
            }

            // Filters
            if ($request->filled('partner_id')) {
                $query->where('partner_team_profit.partner_id', $request->get('partner_id'));
            }

            if ($request->filled('partner_name')) {
                $query->where('partner_team_profit.partner_name', 'like', '%' . $request->get('partner_name') . '%');
            }

            if ($request->filled('merchant_id')) {
                $query->where('partner_team_profit.merchant_id', $request->get('merchant_id'));
            }

            if ($request->filled('merchant_name')) {
                $query->where('partner_team_profit.merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }

            if ($request->filled('transaction_sequence_id')) {
                $query->where('partner_team_profit.transaction_sequence_id', 'like', '%' . $request->get('transaction_sequence_id') . '%');
            }

            if ($request->filled('transaction_id')) {
                $query->where('partner_team_profit.transaction_txn_id', 'like', '%' . $request->get('transaction_id') . '%');
            }

            if ($request->filled('order_id')) {
                $query->where('partner_team_profit.order_order_id', 'like', '%' . $request->get('order_id') . '%');
            }

            if ($request->filled('payment_datetime')) {
                $query->whereDate('partner_team_profit.payment_datetime', $request->get('payment_datetime'));
            }

            if ($request->filled('payment_mode') && $request->get('payment_mode') !== 'all') {
                $query->where('partner_team_profit.payment_mode', $request->get('payment_mode'));
            }

            if ($request->filled('payment_channel') && $request->get('payment_channel') !== 'all') {
                $query->where('partner_team_profit.payment_channel', $request->get('payment_channel'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy('partner_team_profit.' . $sortBy, $sortDirection);

            $profits = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $profits->items(),
                'pagination' => [
                    'current_page' => $profits->currentPage(),
                    'per_page' => $profits->perPage(),
                    'total' => $profits->total(),
                    'last_page' => $profits->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch partner team profit: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get partners for dropdown.
     */
    public function getPartners(): JsonResponse
    {
        try {
            $partners = Partner::select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $partners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch partners: ' . $e->getMessage(),
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

    /**
     * Get payment modes for dropdown.
     */
    public function getPaymentModes(): JsonResponse
    {
        try {
            $modes = PartnerTeamProfit::whereNotNull('payment_mode')
                ->distinct()
                ->pluck('payment_mode')
                ->filter()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $modes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment modes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment channels for dropdown.
     */
    public function getPaymentChannels(): JsonResponse
    {
        try {
            $channels = PartnerTeamProfit::whereNotNull('payment_channel')
                ->distinct()
                ->pluck('payment_channel')
                ->filter()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $channels,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment channels: ' . $e->getMessage(),
            ], 500);
        }
    }
}
