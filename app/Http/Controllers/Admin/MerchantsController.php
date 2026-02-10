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

            $query = Merchant::latest();

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
                      ->orWhere('id', 'like', "%{$search}%");
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
}

