<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OrdersController extends Controller
{
    use LogsConditionally;

    /**
     * Display orders page
     */
    public function index(): View
    {
        $this->logInfo('Admin orders page accessed', ['user_id' => auth()->id()]);
        return view('admin.orders.index');
    }

    /**
     * Get orders data for Angular
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            // Get admin's viewing mode from session
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $perPage = min($request->get('per_page', 10), 100);
            $status = $request->get('status');
            $merchantId = $request->get('merchant_id');
            $search = $request->get('search');

            // Filter by admin's viewing mode
            $query = Order::with(['merchant', 'paymentLink', 'transactions'])
                ->where('test_mode', $isTestMode)
                ->latest();

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                      ->orWhereJsonContains('customer_details->name', $search)
                      ->orWhereJsonContains('customer_details->email', $search);
                });
            }

            $orders = $query->paginate($perPage);

            $this->logDebug('Admin orders retrieved', [
                'count' => $orders->count(),
                'total' => $orders->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
            ], 500);
        }
    }
}
