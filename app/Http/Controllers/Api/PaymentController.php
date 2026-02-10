<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        // Middleware applied in routes instead
    }

    /**
     * Create a new payment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'nullable|in:card,netbanking,upi,wallet,emi',
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'customer_details' => 'nullable|array',
            'customer_details.name' => 'nullable|string',
            'customer_details.email' => 'nullable|email',
            'customer_details.phone' => 'nullable|string',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'webhook_url' => 'nullable|url',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $merchant = $request->get('api_merchant');
            // Get validated data
            $data = $validator->validated();
            
            // Force effective mode from API key for this request only
            $effectiveMode = $request->get('api_key_mode');
            if ($effectiveMode) {
                $merchant->setAttribute('test_mode', $effectiveMode === 'test');
            }

            // Create a PaymentLink for this payment
            $paymentLink = PaymentLink::create([
                'merchant_id' => $merchant->id,
                'link_token' => PaymentLink::generateLinkToken(),
                'title' => $data['description'] ?? 'Payment',
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $merchant->default_currency ?? 'USD',
                'allow_partial_payment' => $data['allow_partial_payment'] ?? false,
                'amount_paid' => 0,
                'status' => 'active',
                'test_mode' => $merchant->test_mode,
                'payment_methods' => ['card', 'upi', 'netbanking', 'wallet'],
                'expires_at' => now()->addHours(24),
                'usage_count' => 0,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Generate checkout URL pointing to your payment gateway form
            $checkoutUrl = url('/pay/' . $paymentLink->link_token);
            
            return response()->json([
                'success' => true,
                'payment_url' => $checkoutUrl,
                'checkout_url' => $checkoutUrl,
                'link_token' => $paymentLink->link_token,
                'amount' => $paymentLink->amount,
                'currency' => $paymentLink->currency,
                'expires_at' => $paymentLink->expires_at->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify a payment.
     *
     * @param Request $request
     * @param string $transactionId
     * @return JsonResponse
     */
    public function verifyPayment(Request $request, string $transactionId): JsonResponse
    {
        $merchant = $request->get('api_merchant');

        $transaction = $merchant->transactions()
            ->where('txn_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'error' => 'Transaction not found',
            ], 404);
        }

        try {
            // Ensure verification uses correct provider for this request
            $effectiveMode = $request->get('api_key_mode');
            if ($effectiveMode) {
                $transaction->merchant->setAttribute('test_mode', $effectiveMode === 'test');
            }
            $result = $this->paymentService->verifyPayment($transaction);

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->txn_id,
                    'status' => $transaction->status,
                    'verified' => $result['verified'] ?? false,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Verification failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

