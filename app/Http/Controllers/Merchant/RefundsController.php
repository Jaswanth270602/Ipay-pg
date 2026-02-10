<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundsController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    public function index(): View
    {
        return view('merchant.refunds.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $perPage = min($request->get('per_page', 10), 100);
        
        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Filter by current merchant mode through transaction relationship
        $query = $merchant->refunds()
            ->with('transaction')
            ->whereHas('transaction', function ($q) use ($merchant) {
                $q->where('test_mode', $merchant->test_mode);
            })
            ->latest();

        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_id', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function ($tq) use ($search) {
                      $tq->where('txn_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $refunds = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $refunds->items(),
            'pagination' => [
                'current_page' => $refunds->currentPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
                'last_page' => $refunds->lastPage(),
                'from' => $refunds->firstItem(),
                'to' => $refunds->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Refund creation via web interface
        try {
            $merchant = $request->user()->merchant;
            
            // Live (merchant) mode: allow when merchant has an active acquirer (aggregator) or full live credentials
            if (!$merchant->test_mode && !$merchant->canUseLiveMode()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live mode requires an active acquirer or full live credentials. Please assign an acquirer in Settings or configure live API credentials before processing refunds in LIVE mode.',
                    'error_code' => 'LIVE_MODE_NOT_CONFIGURED',
                ], 403);
            }
            
            // Validate input
            $request->validate([
                'transaction_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'reason' => 'nullable|string|max:500',
            ]);
            
            // Find transaction by txn_id (not database id)
            $transaction = $merchant->transactions()
                ->where('txn_id', $request->transaction_id)
                ->where('test_mode', $merchant->test_mode)
                ->firstOrFail();

            // Validate transaction status
            if ($transaction->status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot refund unsuccessful transaction. Only successful transactions can be refunded.',
                ], 400);
            }

            $refund = $this->refundService->createRefund(
                $transaction,
                $request->amount,
                $request->user(),
                $request->reason
            );

            // Align API response with actual refund status so that
            // the UI toast and the refunds grid don't contradict each other.
            $wasSuccessful = $refund->status === 'completed';

            $message = $wasSuccessful
                ? 'Refund created successfully'
                : ($refund->gateway_response['message'] ?? $refund->gateway_response['error'] ?? 'Refund could not be processed. Please check gateway logs for details.');

            return response()->json([
                'success' => $wasSuccessful,
                'message' => $message,
                'data' => [
                    'refund_id' => $refund->refund_id,
                    'transaction_id' => $transaction->txn_id,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'status' => $refund->status,
                    'is_partial' => $refund->is_partial,
                    'created_at' => $refund->created_at->toIso8601String(),
                ],
            ], $wasSuccessful ? 200 : 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found. Please check the transaction ID and ensure you are in the correct mode (TEST/LIVE).',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $merchant = $request->user()->merchant;

        $query = $merchant->refunds()
            ->with('transaction')
            ->whereHas('transaction', function ($q) use ($merchant) {
                $q->where('test_mode', $merchant->test_mode);
            });

        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_id', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function ($tq) use ($search) {
                      $tq->where('txn_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $refunds = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="refunds_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        $callback = function() use ($refunds) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Refund ID', 'Transaction ID', 'Order ID', 'Amount', 'Currency',
                'Status', 'Reason', 'Is Partial', 'Created At', 'Processed At'
            ]);

            foreach ($refunds as $refund) {
                $transaction = $refund->transaction;
                fputcsv($file, [
                    $refund->refund_id,
                    $transaction->txn_id ?? '-',
                    $transaction->order_id ?? '-',
                    number_format($refund->amount, 2),
                    $refund->currency,
                    $refund->status,
                    $refund->reason ?? '-',
                    $refund->is_partial ? 'Yes' : 'No',
                    $refund->created_at->format('Y-m-d H:i:s'),
                    $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

