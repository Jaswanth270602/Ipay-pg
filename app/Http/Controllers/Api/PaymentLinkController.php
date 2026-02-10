<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle.api');
    }

    /**
     * Create a payment link.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'allow_partial_payment' => 'nullable|boolean',
            'customer_details' => 'nullable|array',
            'max_usage' => 'nullable|integer|min:1',
            'expires_in' => 'nullable|integer|min:60', // seconds
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $merchant = $request->get('api_merchant');
            // Force effective mode from API key for this request only
            $effectiveMode = $request->get('api_key_mode');
            if ($effectiveMode) {
                $merchant->setAttribute('test_mode', $effectiveMode === 'test');
            }

            $expiresAt = isset($request->expires_in) 
                ? now()->addSeconds($request->expires_in) 
                : now()->addHours(config('badlicash.payment_link_expiry_hours', 24));

            $paymentLink = PaymentLink::create([
                'merchant_id' => $merchant->id,
                'link_token' => PaymentLink::generateLinkToken(),
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => $request->currency ?? $merchant->default_currency,
                'allow_partial_payment' => $request->boolean('allow_partial_payment', false),
                'amount_paid' => 0,
                'customer_details' => $request->customer_details,
                'status' => 'active',
                'max_usage' => $request->max_usage,
                'test_mode' => (bool)$merchant->test_mode,
                'metadata' => $request->metadata,
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'expires_at' => $expiresAt,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'link_token' => $paymentLink->link_token,
                    'payment_url' => $paymentLink->getPaymentUrl(),
                    'title' => $paymentLink->title,
                    'amount' => $paymentLink->amount,
                    'currency' => $paymentLink->currency,
                    'status' => $paymentLink->status,
                    'expires_at' => $paymentLink->expires_at->toIso8601String(),
                    'created_at' => $paymentLink->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment link creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all payment links for the merchant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->get('api_merchant');

        $perPage = min($request->get('per_page', 10), config('badlicash.pagination.max_per_page'));
        $status = $request->get('status');

        $query = $merchant->paymentLinks()->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $paymentLinks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paymentLinks->items(),
            'pagination' => [
                'current_page' => $paymentLinks->currentPage(),
                'per_page' => $paymentLinks->perPage(),
                'total' => $paymentLinks->total(),
                'last_page' => $paymentLinks->lastPage(),
            ],
        ]);
    }
}

