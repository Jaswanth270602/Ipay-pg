<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        // Middleware applied in routes
    }

    /**
     * Get all transactions for the merchant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->get('api_merchant');
        $apiKeyMode = $request->get('api_key_mode');

        $perPage = min($request->get('per_page', 10), config('badlicash.pagination.max_per_page'));
        $status = $request->get('status');
        $paymentMethod = $request->get('payment_method');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Filter transactions by API key mode
        $query = $merchant->transactions()
            ->where('test_mode', $apiKeyMode === 'test')
            ->with('order')
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'mode' => $apiKeyMode,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific transaction.
     *
     * @param Request $request
     * @param string $transactionId
     * @return JsonResponse
     */
    public function show(Request $request, string $transactionId): JsonResponse
    {
        $merchant = $request->get('api_merchant');
        $apiKeyMode = $request->get('api_key_mode');

        $transaction = $merchant->transactions()
            ->where('txn_id', $transactionId)
            ->where('test_mode', $apiKeyMode === 'test')
            ->with('order', 'refunds')
            ->first();

        if (!$transaction) {
            return response()->json([
                'error' => 'Transaction not found',
                'message' => 'Transaction not found or does not match your API key mode.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'mode' => $apiKeyMode,
            'data' => [
                'transaction_id' => $transaction->txn_id,
                'order_id' => $transaction->order->order_id,
                'amount' => $transaction->amount,
                'fee_amount' => $transaction->fee_amount,
                'net_amount' => $transaction->net_amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'status' => $transaction->status,
                'test_mode' => $transaction->test_mode,
                'refunds' => $transaction->refunds->map(function ($refund) {
                    return [
                        'refund_id' => $refund->refund_id,
                        'amount' => $refund->amount,
                        'status' => $refund->status,
                        'created_at' => $refund->created_at->toIso8601String(),
                    ];
                }),
                'created_at' => $transaction->created_at->toIso8601String(),
            ],
        ]);
    }
}

