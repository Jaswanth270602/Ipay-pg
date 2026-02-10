<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle.api');
    }

    /**
     * Unified status lookup for orders, transactions, refunds and payment links.
     *
     * Query params (any combination):
     * - order_id
     * - transaction_id
     * - refund_id
     * - payment_link_token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->get('api_merchant');
        $apiKeyMode = $request->get('api_key_mode');

        $orderId = $request->get('order_id');
        $transactionId = $request->get('transaction_id');
        $refundId = $request->get('refund_id');
        $paymentLinkToken = $request->get('payment_link_token');

        if (! $orderId && ! $transactionId && ! $refundId && ! $paymentLinkToken) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => [
                    'identifier' => ['Provide at least one of: order_id, transaction_id, refund_id, payment_link_token.'],
                ],
            ], 422);
        }

        $result = [
            'mode' => $apiKeyMode,
        ];

        if ($orderId) {
            $order = $merchant->orders()
                ->where('order_id', $orderId)
                ->where('test_mode', $apiKeyMode === 'test')
                ->first();

            $result['order'] = $order ? [
                'found' => true,
                'order_id' => $order->order_id,
                'status' => $order->status,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'test_mode' => $order->test_mode,
                'created_at' => $order->created_at->toIso8601String(),
            ] : [
                'found' => false,
            ];
        }

        if ($transactionId) {
            $transaction = $merchant->transactions()
                ->where('txn_id', $transactionId)
                ->where('test_mode', $apiKeyMode === 'test')
                ->first();

            $result['transaction'] = $transaction ? [
                'found' => true,
                'transaction_id' => $transaction->txn_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'test_mode' => $transaction->test_mode,
                'created_at' => $transaction->created_at->toIso8601String(),
            ] : [
                'found' => false,
            ];
        }

        if ($refundId) {
            $refund = $merchant->refunds()
                ->where('refund_id', $refundId)
                ->whereHas('transaction', function ($q) use ($apiKeyMode) {
                    $q->where('test_mode', $apiKeyMode === 'test');
                })
                ->first();

            $result['refund'] = $refund ? [
                'found' => true,
                'refund_id' => $refund->refund_id,
                'transaction_id' => $refund->transaction->txn_id ?? null,
                'status' => $refund->status,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'is_partial' => $refund->is_partial,
                'created_at' => $refund->created_at->toIso8601String(),
            ] : [
                'found' => false,
            ];
        }

        if ($paymentLinkToken) {
            $paymentLink = $merchant->paymentLinks()
                ->where('link_token', $paymentLinkToken)
                ->first();

            $result['payment_link'] = $paymentLink ? [
                'found' => true,
                'link_token' => $paymentLink->link_token,
                'status' => $paymentLink->status,
                'amount' => $paymentLink->amount,
                'currency' => $paymentLink->currency,
                'expires_at' => optional($paymentLink->expires_at)->toIso8601String(),
                'created_at' => $paymentLink->created_at->toIso8601String(),
            ] : [
                'found' => false,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Convenience endpoint: get transaction status by ID.
     *
     * @param Request $request
     * @param string $transactionId
     * @return JsonResponse
     */
    public function transaction(Request $request, string $transactionId): JsonResponse
    {
        $request->merge(['transaction_id' => $transactionId]);

        return $this->index($request);
    }

    /**
     * Convenience endpoint: get order status by ID.
     *
     * @param Request $request
     * @param string $orderId
     * @return JsonResponse
     */
    public function order(Request $request, string $orderId): JsonResponse
    {
        $request->merge(['order_id' => $orderId]);

        return $this->index($request);
    }

    /**
     * Convenience endpoint: get refund status by ID.
     *
     * @param Request $request
     * @param string $refundId
     * @return JsonResponse
     */
    public function refund(Request $request, string $refundId): JsonResponse
    {
        $request->merge(['refund_id' => $refundId]);

        return $this->index($request);
    }

    /**
     * Convenience endpoint: get payment link status by token.
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function paymentLink(Request $request, string $token): JsonResponse
    {
        $request->merge(['payment_link_token' => $token]);

        return $this->index($request);
    }
}


