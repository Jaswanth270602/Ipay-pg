<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRoutingMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentRoutingMonitorsController extends Controller
{
    public function index(): View
    {
        return view('admin.monitoring.payment-routing.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $q = PaymentRoutingMonitor::query()
            ->with(['merchant:id,name,merchant_unique_id'])
            ->latest('id');

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $q->where('status', $request->get('status'));
        }
        if ($request->filled('merchant_id')) {
            $q->where('merchant_id', (int) $request->get('merchant_id'));
        }
        if ($request->filled('txn_id')) {
            $q->where('txn_id', 'like', '%' . trim((string) $request->get('txn_id')) . '%');
        }

        $paginator = $q->paginate($perPage);
        $data = collect($paginator->items())->map(function (PaymentRoutingMonitor $row) {
            return [
                'id' => $row->id,
                'merchant_id' => $row->merchant_id,
                'merchant_name' => $row->merchant?->name,
                'txn_id' => $row->txn_id,
                'customer_name' => $row->customer_name,
                'customer_email' => $row->customer_email,
                'payment_method' => $row->payment_method,
                'final_acquirer_name' => $row->final_acquirer_name,
                'status' => $row->status,
                'test_mode' => $row->test_mode,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(int $id): View
    {
        $monitor = PaymentRoutingMonitor::query()
            ->with(['merchant:id,name,merchant_unique_id'])
            ->findOrFail($id);

        return view('admin.monitoring.payment-routing.show', [
            'monitor' => $monitor,
        ]);
    }
}
