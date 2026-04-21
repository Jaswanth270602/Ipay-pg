<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentOrchestration\PaymentRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Payment orchestration API: test/live routing, normalized responses.
 */
class OrchestrationPaymentController extends Controller
{
    public function __construct(
        protected PaymentRouterService $paymentRouter
    ) {}

    /**
     * POST /api/v1/orchestration/payments
     */
    public function initiate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'required|in:card,netbanking,upi,wallet,emi',
            'customer_details' => 'nullable|array',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'idempotency_key' => 'nullable|string|max:255',
            'card_number' => 'nullable|string',
            'cvv' => 'nullable|string',
            'expiry_month' => 'nullable|string',
            'expiry_year' => 'nullable|string',
            'card_holder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $merchant = $request->get('api_merchant');
        $effectiveMode = $request->get('api_key_mode');
        if ($effectiveMode) {
            $merchant->setAttribute('test_mode', $effectiveMode === 'test');
        }

        $data = $validator->validated();

        $orderData = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $merchant->default_currency ?? 'USD',
            'customer_details' => $data['customer_details'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'return_url' => $data['return_url'] ?? null,
            'cancel_url' => $data['cancel_url'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
        ];

        $paymentData = [
            'payment_method' => $data['payment_method'],
            'card_number' => $data['card_number'] ?? null,
            'cvv' => $data['cvv'] ?? null,
            'expiry_month' => $data['expiry_month'] ?? null,
            'expiry_year' => $data['expiry_year'] ?? null,
            'card_holder' => $data['card_holder'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
        ];

        $result = $this->paymentRouter->initiate($merchant, $orderData, $paymentData);

        if (($result['status'] ?? '') === 'FAILED') {
            $msg = (string) ($result['message'] ?? '');
            $code = match (true) {
                str_contains($msg, 'timeout') => 504,
                str_contains($msg, 'No response from acquirer') => 504,
                str_contains($msg, 'Acquirer not configured') => 503,
                str_contains($msg, 'Invalid API credentials') => 502,
                default => 400,
            };

            return response()->json($result, $code);
        }

        return response()->json($result, 201);
    }
}
