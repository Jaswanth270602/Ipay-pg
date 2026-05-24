<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundsController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    public function index(): View
    {
        return view('merchant.refunds.index', [
            'merchant' => auth()->user()->merchant,
            'refundApprovalThreshold' => RefundService::APPROVAL_THRESHOLD,
        ]);
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

    public function lookupTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:120',
        ]);

        $merchant = $request->user()->merchant;
        $transaction = $merchant->transactions()
            ->where('test_mode', $merchant->test_mode)
            ->where('txn_id', trim($request->input('q')))
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found. Check the transaction ID and your current Test/Live mode.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'txn_id' => $transaction->txn_id,
                'currency' => $transaction->currency,
                'amount' => (float) $transaction->amount,
                'refundable_amount' => $transaction->refundableAmount(),
                'status' => $transaction->status,
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
            
            $request->validate([
                'transaction_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'reason' => 'nullable|string|max:500',
            ]);
            
            $refund = $this->refundService->createRefundByTransactionId(
                $request->user(),
                $request->transaction_id,
                (float) $request->amount,
                $request->reason,
                $merchant->id,
                (bool) $merchant->test_mode
            );
            $transaction = $refund->transaction;

            $wasSuccessful = $refund->status !== 'failed';

            $message = match ($refund->status) {
                'pending_approval' => 'Refund request submitted and is pending admin approval.',
                'pending_processing' => 'Refund initiated; final status will be confirmed by the bank.',
                'completed' => 'Refund created successfully',
                'cancelled' => 'Refund request was cancelled.',
                default => $refund->gateway_response['message']
                    ?? $refund->gateway_response['error']
                    ?? ($wasSuccessful ? 'Refund request recorded.' : 'Refund could not be processed. Please check gateway logs for details.'),
            };

            return response()->json([
                'success' => $wasSuccessful,
                'message' => $message,
                'data' => [
                    'refund_id' => $refund->refund_id,
                    'transaction_id' => $transaction->txn_id,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'status' => $refund->status,
                    'mode' => $refund->mode,
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

    public function export(Request $request, FileLifecycleService $fileLifecycleService): BinaryFileResponse
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

        $fileName = 'refunds_' . now()->format('Y-m-d_His') . '.csv';
        $relativePath = $fileLifecycleService->createCsvReport($fileName, function ($file) use ($refunds): void {
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
        });

        return $fileLifecycleService->downloadAndDelete(
            $relativePath,
            $fileName,
            ['Content-Type' => 'text/csv']
        );
    }
}

