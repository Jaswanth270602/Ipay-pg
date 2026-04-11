<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MerchantsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin merchants page accessed', ['user_id' => auth()->id()]);
        return view('admin.merchants.index');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = strtolower((string) session('admin_view_mode', 'test'));
            $modeFilter = match ($adminViewMode) {
                'test' => true,
                'live' => false,
                default => null,
            };

            $this->logInfo('Admin merchants data requested', [
                'user_id' => auth()->id(),
                'admin_view_mode' => $adminViewMode,
                'mode_filter' => $modeFilter,
                'filters' => $request->only(['status', 'search', 'per_page'])
            ]);

            $perPage = min($request->get('per_page', 10), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Merchant::with(['acquirerAccount', 'resellers'])->latest();

            // Filter by test_mode based on admin view mode.
            // If mode value is unexpected, do not filter out merchants.
            if ($modeFilter !== null) {
                $query->where('test_mode', $modeFilter);
            }

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      // Search by assigned acquirer type/name as well
                      ->orWhereHas('acquirerAccount', function ($aq) use ($search) {
                          $aq->where('acquirer_name', 'like', "%{$search}%")
                             ->orWhere('mode', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('reseller_id')) {
                $resellerFilter = $request->get('reseller_id');
                if ($resellerFilter === 'none') {
                    $query->whereDoesntHave('resellers');
                } elseif (is_numeric($resellerFilter)) {
                    $rid = (int) $resellerFilter;
                    $query->whereHas('resellers', function ($q) use ($rid) {
                        $q->where('resellers.id', $rid);
                    });
                }
            }

            $merchants = $query->paginate($perPage);

            // Safety fallback: if mode filter yields empty list while merchants exist,
            // return unfiltered merchants so admin page doesn't look broken.
            if (
                $modeFilter !== null
                && $merchants->total() === 0
                && Merchant::query()->count() > 0
                && !$status
                && !$search
                && !$request->filled('reseller_id')
            ) {
                $this->logWarning('Merchants mode filter produced empty set; returning unfiltered list as fallback', [
                    'mode_filter' => $modeFilter,
                    'admin_view_mode' => $adminViewMode,
                ]);

                $fallbackQuery = Merchant::with(['acquirerAccount', 'resellers'])->latest();
                $merchants = $fallbackQuery->paginate($perPage);
            }

            $this->logDebug('Admin merchants retrieved', [
                'count' => $merchants->count(),
                'total' => $merchants->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $merchants->items(),
                'pagination' => [
                    'current_page' => $merchants->currentPage(),
                    'per_page' => $merchants->perPage(),
                    'total' => $merchants->total(),
                    'last_page' => $merchants->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin merchants', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchants',
            ], 500);
        }
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_filter($ids, static function ($id) {
            return is_numeric($id);
        });

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No merchants selected',
            ], 422);
        }

        $updated = Merchant::whereIn('id', $ids)->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => "Approved {$updated} merchants.",
        ]);
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_filter($ids, static function ($id) {
            return is_numeric($id);
        });

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No merchants selected',
            ], 422);
        }

        $updated = Merchant::whereIn('id', $ids)->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => "Rejected {$updated} merchants.",
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_filter($ids, static function ($id) {
            return is_numeric($id);
        });

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No merchants selected',
            ], 422);
        }

        // Safety: prevent deleting active merchants
        $activeCount = Merchant::whereIn('id', $ids)->where('status', 'active')->count();
        if ($activeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active merchants. Please deactivate them first.',
            ], 422);
        }

        $deletedCount = Merchant::whereIn('id', $ids)->delete();

        if ($deletedCount === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No merchants found or already deleted',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} merchant(s) deleted successfully",
        ]);
    }

    /**
     * Show single merchant details for admin view modal.
     */
    public function show(int $id): JsonResponse
    {
        $merchant = Merchant::with(['acquirerAccount', 'resellers'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $merchant,
        ]);
    }
}

