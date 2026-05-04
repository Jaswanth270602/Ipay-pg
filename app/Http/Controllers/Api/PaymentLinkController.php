<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentLinkCreated;
use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Services\PaymentLinks\PaymentLinkAttemptService;
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
        $merchant = $request->get('api_merchant');
        $mode = $request->get('api_key_mode') ?? ($merchant?->test_mode ? 'test' : 'live');

        $attemptService = app(PaymentLinkAttemptService::class);
        $attempt = $attemptService->start($merchant, $mode, $request, [
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency'),
            'allow_partial_payment' => $request->input('allow_partial_payment'),
            'max_usage' => $request->input('max_usage'),
            'expires_in' => $request->input('expires_in'),
            'success_url' => $request->input('success_url'),
            'cancel_url' => $request->input('cancel_url'),
            'metadata' => $request->input('metadata'),
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'allow_partial_payment' => 'nullable|boolean',
            'customer_details' => 'nullable|array',
            'max_usage' => 'nullable|integer|min:1',
            'expires_in' => 'nullable|integer|min:60|max:2592000', // 60s to 30 days
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            $payload = [
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ];
            $attemptService->fail($attempt, 'Validation failed', $payload);
            return response()->json($payload, 422);
        }

        try {
            // Force effective mode from API key for this request only
            $effectiveMode = $request->get('api_key_mode');
            if ($effectiveMode) {
                $merchant->setAttribute('test_mode', $effectiveMode === 'test');
            }

            // Payment links are platform records + URL only. Acquirers are resolved when the customer pays (checkout).

            $expiresAt = isset($request->expires_in) 
                ? now()->addSeconds($request->expires_in) 
                : now()->addHours(config('ipay.payment_link_expiry_hours', 24));

            $requestPayload = [
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => $request->currency ?? $merchant->default_currency,
                'allow_partial_payment' => $request->boolean('allow_partial_payment', false),
                'customer_details' => $request->customer_details,
                'max_usage' => $request->max_usage,
                'metadata' => $request->metadata,
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'expires_at' => $expiresAt?->toIso8601String(),
                'mode' => $merchant->test_mode ? 'test' : 'live',
            ];

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
                'request_payload' => $requestPayload,
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'expires_at' => $expiresAt,
            ]);

            event(new PaymentLinkCreated($paymentLink->load('merchant')));

            $payload = [
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
            ];

            $paymentLink->update([
                'response_payload' => [
                    'status' => 'SUCCESS',
                    'message' => 'Payment link created successfully',
                    'mode' => $merchant->test_mode ? 'test' : 'live',
                    'link_token' => $paymentLink->link_token,
                    'payment_url' => $paymentLink->getPaymentUrl(),
                ],
            ]);
            $attemptService->succeed($attempt, $paymentLink, $payload);
            return response()->json($payload, 201);

        } catch (\Exception $e) {
            $payload = [
                'error' => 'Payment link creation failed',
                'message' => $e->getMessage(),
            ];
            $attemptService->fail($attempt, 'Exception', $payload);
            return response()->json($payload, 500);
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
        $effectiveMode = $request->get('api_key_mode');
        if ($effectiveMode) {
            $merchant->setAttribute('test_mode', $effectiveMode === 'test');
        }

        $perPage = min($request->get('per_page', 10), config('ipay.pagination.max_per_page'));
        $status = $request->get('status');

        $query = $merchant->paymentLinks()
            ->where('test_mode', (bool) $merchant->test_mode)
            ->latest();

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

