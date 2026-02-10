<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Services\PaymentSimulationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class PaymentPageController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentSimulationService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment page for a payment link
     *
     * @param string $token
     * @return View
     */
    public function show(string $token): View
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();

        // Check if link is still active
        if (!$paymentLink->isActive()) {
            abort(410, 'This payment link has expired or is no longer available.');
        }

        // Check if already paid
        if ($paymentLink->status === 'paid') {
            return view('payment.already_paid', compact('paymentLink'));
        }

        return view('payment.page', compact('paymentLink'));
    }

    /**
     * Process payment
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function process(Request $request, string $token): JsonResponse
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();

            // Check if link is still active
            if (!$paymentLink->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link has expired or is no longer available.',
                ], 410);
            }

            // Check if already paid
            if ($paymentLink->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link has already been used.',
                ], 400);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|in:card,upi,netbanking,wallet',
                'customer_details' => 'required|array',
                'customer_details.name' => 'required|string|max:255',
                'customer_details.email' => 'required|email',
                'customer_details.phone' => 'required|string|regex:/^[0-9]{10}$/',
                'payment_details' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validate payment method specific details
            $paymentMethod = $request->payment_method;
            $paymentDetails = $request->payment_details;

            if ($paymentMethod === 'card') {
                $cardValidator = Validator::make($paymentDetails, [
                    'card_number' => 'required|string',
                    'card_holder' => 'required|string|max:255',
                    'expiry_month' => 'required|digits:2',
                    'expiry_year' => 'required|digits:4',
                    'cvv' => 'required|digits:3',
                ]);

                if ($cardValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid card details',
                        'errors' => $cardValidator->errors(),
                    ], 422);
                }
            } elseif ($paymentMethod === 'upi') {
                $upiValidator = Validator::make($paymentDetails, [
                    'upi_id' => 'required_without:upi_app|string',
                    'upi_app' => 'required_without:upi_id|string',
                ]);

                if ($upiValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid UPI details',
                        'errors' => $upiValidator->errors(),
                    ], 422);
                }
            } elseif ($paymentMethod === 'netbanking') {
                $nbValidator = Validator::make($paymentDetails, [
                    'bank_code' => 'required|string',
                ]);

                if ($nbValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a bank',
                        'errors' => $nbValidator->errors(),
                    ], 422);
                }
            } elseif ($paymentMethod === 'wallet') {
                $walletValidator = Validator::make($paymentDetails, [
                    'wallet_provider' => 'required|string',
                ]);

                if ($walletValidator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a wallet',
                        'errors' => $walletValidator->errors(),
                    ], 422);
                }
            }

            // Prepare payment data
            $paymentData = [
                'merchant_id' => $paymentLink->merchant_id,
                'payment_link_id' => $paymentLink->id,
                'amount' => $paymentLink->amount,
                'currency' => $paymentLink->currency,
                'payment_method' => $paymentMethod,
                'payment_details' => $paymentDetails,
                'customer_details' => $request->customer_details,
                'test_mode' => $paymentLink->test_mode,
                'description' => $paymentLink->title,
            ];

            // Process payment through simulation service
            $result = $this->paymentService->processPayment($paymentData);

            return response()->json($result, $result['success'] ? 200 : 402);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get test card information
     *
     * @return JsonResponse
     */
    public function getTestCards(): JsonResponse
    {
        return response()->json([
            'test_cards' => PaymentSimulationService::getTestCards(),
        ]);
    }
}
