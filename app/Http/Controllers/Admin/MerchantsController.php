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
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $this->logInfo('Admin merchants data requested', [
                'user_id' => auth()->id(),
                'admin_view_mode' => $adminViewMode,
                'filters' => $request->only(['status', 'search', 'per_page'])
            ]);

            $perPage = min($request->get('per_page', 10), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Merchant::with('acquirerAccount')->latest();

            // Filter by test_mode based on admin view mode
            // When in live mode, only show merchants with test_mode = false
            // When in test mode, only show merchants with test_mode = true
            $query->where('test_mode', $isTestMode);

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

            $merchants = $query->paginate($perPage);

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
        $merchant = Merchant::with('acquirerAccount')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $merchant,
        ]);
    }
}

