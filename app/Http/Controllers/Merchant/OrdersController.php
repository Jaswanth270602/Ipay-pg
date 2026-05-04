<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdersController extends Controller
{
    public function index(): View
    {
        return view('merchant.orders.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $perPage = min($request->get('per_page', 10), 100);
        
        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Filter by current merchant mode (test or live)
        $query = $merchant->orders()
            ->where('test_mode', $merchant->test_mode)
            ->latest();

        if ($status && $status !== 'all' && $status !== '') {
            if ($status === 'created') {
                // Keep backward compatibility for older records stored as "initiated".
                $query->whereIn('status', ['created', 'initiated']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $orders = $query->paginate($perPage);

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
    }

    public function export(Request $request, FileLifecycleService $fileLifecycleService): BinaryFileResponse
    {
        $merchant = $request->user()->merchant;

        $query = $merchant->orders()
            ->where('test_mode', $merchant->test_mode);

        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($status && $status !== 'all' && $status !== '') {
            if ($status === 'created') {
                // Keep backward compatibility for older records stored as "initiated".
                $query->whereIn('status', ['created', 'initiated']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $orders = $query->latest()->get();

        $fileName = 'orders_' . now()->format('Y-m-d_His') . '.csv';
        $relativePath = $fileLifecycleService->createCsvReport($fileName, function ($file) use ($orders): void {
            fputcsv($file, [
                'Order ID', 'Description', 'Amount', 'Currency', 'Status',
                'Payment Link', 'Created At', 'Updated At'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_id,
                    $order->description ?? '-',
                    number_format($order->amount, 2),
                    $order->currency ?? 'INR',
                    $order->status,
                    $order->paymentLink ? 'Yes' : 'No',
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
        });

        return $fileLifecycleService->downloadAndDelete(
            $relativePath,
            $fileName,
            ['Content-Type' => 'text/csv']
        );
    }
}

