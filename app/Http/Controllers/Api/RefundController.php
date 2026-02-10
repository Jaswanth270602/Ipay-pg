<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
        $this->middleware('throttle.api');
    }

    /**
     * Create a refund.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $merchant = $request->get('api_merchant');
            $apiKeyMode = $request->get('api_key_mode');

            $transaction = $merchant->transactions()
                ->where('txn_id', $request->transaction_id)
                ->where('test_mode', $apiKeyMode === 'test')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'error' => 'Transaction not found',
                    'message' => 'Transaction not found or does not match your API key mode.',
                ], 404);
            }

            if ($transaction->status !== 'success') {
                return response()->json([
                    'error' => 'Cannot refund unsuccessful transaction',
                ], 400);
            }

            // Get first user of merchant for initiator
            $initiator = $merchant->users()->first();

            if (!$initiator) {
                return response()->json([
                    'error' => 'No authorized user found for this merchant',
                ], 500);
            }

            $amount = $request->amount ?? $transaction->amount;

            $refund = $this->refundService->createRefund(
                $transaction,
                $amount,
                $initiator,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'refund_id' => $refund->refund_id,
                    'transaction_id' => $transaction->txn_id,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'status' => $refund->status,
                    'is_partial' => $refund->is_partial,
                    'created_at' => $refund->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Refund creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all refunds for the merchant.
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

        // Filter refunds by test_mode through transaction relationship
        $query = $merchant->refunds()
            ->with('transaction')
            ->whereHas('transaction', function ($q) use ($apiKeyMode) {
                $q->where('test_mode', $apiKeyMode === 'test');
            })
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $refunds = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'mode' => $apiKeyMode,
            'data' => $refunds->items(),
            'pagination' => [
                'current_page' => $refunds->currentPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
                'last_page' => $refunds->lastPage(),
            ],
        ]);
    }
}

