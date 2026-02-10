<?php

namespace App\Http\Controllers;

use App\Traits\LogsConditionally;
use App\Services\PaymentService;
use App\Services\PaymentSimulationService;
use App\Services\GatewayModeService;
use App\Services\PaymentGateways\GatewayFactory;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use App\Models\PaymentLink;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentCheckoutController extends Controller
{
    use LogsConditionally;

    protected PaymentService $paymentService;
    protected PaymentSimulationService $simulationService;

    public function __construct(PaymentService $paymentService, PaymentSimulationService $simulationService)
    {
        $this->paymentService = $paymentService;
        $this->simulationService = $simulationService;
    }

    public function show(string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            
            // Refresh the model to get latest status
            $paymentLink->refresh();
            
            // Check if link is active - abort if not
            if (!$paymentLink->isActive()) {
                $this->logInfo('Payment link accessed but not active', [
                    'link_token' => $token,
                    'status' => $paymentLink->status,
                    'expires_at' => $paymentLink->expires_at ? $paymentLink->expires_at->toDateTimeString() : null,
                    'now' => now()->toDateTimeString()
                ]);
                
                $message = 'This payment link is no longer available.';
                if ($paymentLink->status === 'expired') {
                    $message = 'This payment link has expired.';
                } elseif ($paymentLink->status === 'paid') {
                    $message = 'This payment link has already been paid.';
                } elseif ($paymentLink->status === 'cancelled') {
                    $message = 'This payment link has been cancelled.';
                }
                
                abort(404, $message);
            }

            $this->logInfo('Payment checkout page accessed', [
                'link_token' => $token,
                'merchant_id' => $paymentLink->merchant_id,
                'status' => $paymentLink->status,
                'expires_at' => $paymentLink->expires_at ? $paymentLink->expires_at->toDateTimeString() : null,
                'allow_partial_payment' => $paymentLink->allow_partial_payment,
                'amount' => $paymentLink->amount,
                'amount_paid' => $paymentLink->amount_paid ?? 0,
            ]);

            // Don't use embedded iframe - use our own payment forms with Razorpay Checkout.js
            // This keeps our UI visible and processes payments through Razorpay API
            $yapilySandboxEnabled = config('yapily.enabled', false);
            return view('checkout.payment', compact('paymentLink', 'yapilySandboxEnabled'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logError('Payment link not found', [
                'token' => $token
            ]);
            abort(404, 'Payment link not found');
        } catch (\Exception $e) {
            $this->logError('Error loading payment checkout', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            abort(404, 'Payment link not found');
        }
    }

    public function process(Request $request, string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            
            if (!$paymentLink->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link is no longer available.',
                ], 410);
            }

            // Check if fully paid (only block if not allowing partial payments)
            if ($paymentLink->status === 'paid' || $paymentLink->isFullyPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link has already been fully paid.',
                ], 400);
            }

            // Get merchant and determine gateway requirements
            $merchant = $paymentLink->merchant;
            $acquirerAccount = $merchant->getActiveAcquirerAccount();
            $hasAcquirerAccount = $acquirerAccount !== null;

            if ($acquirerAccount) {
                $this->logInfo('Active acquirer selected for checkout', [
                    'merchant_id' => $merchant->id,
                    'acquirer_account_id' => $acquirerAccount->id,
                    'acquirer_name' => $acquirerAccount->acquirer_name,
                    'acquirer_mode' => $acquirerAccount->mode,
                ]);
            }

            // Detect Yapily acquirer configured at merchant level
            $acquirerName = $acquirerAccount ? strtolower(trim($acquirerAccount->acquirer_name ?? '')) : '';
            // Treat any acquirer whose name contains "yapily" (e.g. Yapily, YapilyTest, YapilyLive) as Yapily
            $isYapilyAcquirer = $acquirerName !== '' && str_contains($acquirerName, 'yapily');

            // Legacy Yapily sandbox payment-method toggle (now deprecated in favour of acquirer-based Yapily)
            $useYapilySandbox = $request->payment_method === 'yapily' && config('yapily.enabled', false);

            // When customer chooses Yapily Sandbox method OR merchant uses Yapily acquirer,
            // do NOT go through Razorpay/Cashfree gateway path. Instead, fall back to the
            // internal simulation flow, which keeps existing codepaths safe.
            // Acquirers (Razorpay, Cashfree) only when BOTH gateway and merchant are LIVE.
            // Gateway TEST = internal only. Merchant TEST = internal only (no Razorpay even if gateway is live).
            $gatewayModeIsLive = GatewayModeService::isLive();
            $merchantIsLive = !$merchant->test_mode;
            $useAcquirerGateway = $hasAcquirerAccount && !$useYapilySandbox && !$isYapilyAcquirer && $gatewayModeIsLive && $merchantIsLive;

            if ($hasAcquirerAccount && $gatewayModeIsLive && !$merchantIsLive) {
                $this->logInfo('Merchant is in Test mode – using internal simulation only (acquirer not called). Switch merchant to Live to use Razorpay/Cashfree.', [
                    'merchant_id' => $merchant->id,
                    'acquirer_name' => $acquirerAccount->acquirer_name,
                ]);
            }
            if ($hasAcquirerAccount && !$gatewayModeIsLive) {
                $this->logInfo('Gateway mode is TEST – using internal simulation only (acquirer not called). Set APP_PAYMENT_MODE=live to use Razorpay/Cashfree.', [
                    'merchant_id' => $merchant->id,
                    'acquirer_name' => $acquirerAccount->acquirer_name,
                ]);
            }

            // Determine if payment details are required based on gateway
            $paymentDetailsRequired = false;
            $gateway = null;

            if ($useAcquirerGateway && $request->payment_method === 'card') {
                try {
                    $gateway = GatewayFactory::make($merchant, $acquirerAccount);
                    $gatewayName = $gateway->getGatewayName();
                    
                    $this->logInfo('Gateway determined for payment validation', [
                        'merchant_id' => $merchant->id,
                        'acquirer_name' => $acquirerAccount->acquirer_name,
                        'gateway_name' => $gatewayName,
                        'payment_method' => $request->payment_method,
                        'requires_frontend_sdk' => $gateway->requiresFrontendSdk(),
                    ]);
                    
                    // CashFree requires payment_details for server-side processing
                    // Razorpay uses Checkout.js, so payment_details not required
                    if ($gatewayName === 'cashfree') {
                        $paymentDetailsRequired = true;
                    } else {
                        $paymentDetailsRequired = false;
                    }
                } catch (\Exception $e) {
                    $this->logError('Failed to initialize gateway for validation', [
                        'merchant_id' => $merchant->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Fallback: if gateway initialization fails, don't require payment details
                    $paymentDetailsRequired = false;
                }
            }

            $isRazorpayCard = $gateway !== null && $gateway->getGatewayName() === 'razorpay';

            // Sanitize customer phone number before validation
            $customerDetails = $request->customer_details ?? [];
            if (isset($customerDetails['phone'])) {
                $customerDetails['phone'] = preg_replace('/[^0-9]/', '', $customerDetails['phone']);
            }
            
            $allowedMethods = ['card', 'upi', 'netbanking', 'wallet'];
            if (config('yapily.enabled', false)) {
                $allowedMethods[] = 'yapily';
            }
            $validator = Validator::make(array_merge($request->all(), ['customer_details' => $customerDetails]), [
                'payment_method' => ['required', 'in:' . implode(',', $allowedMethods)],
                'customer_details' => 'required|array',
                'customer_details.name' => 'required|string|max:255',
                'customer_details.email' => 'required|email',
                'customer_details.phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
                'payment_details' => $paymentDetailsRequired ? 'required|array' : 'nullable|array',
                'amount' => 'nullable|numeric|min:0.01', // Optional custom amount for partial payment
            ]);
            
            // Additional validation for payment_details when required
            if ($paymentDetailsRequired) {
                if (!$request->has('payment_details') || empty($request->payment_details)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Card details are required for payment processing',
                        'errors' => ['payment_details' => ['Card details are required']],
                    ], 422);
                }
                
                // Sanitize payment details before validation
                $paymentDetails = $request->payment_details;
                
                // Remove spaces and non-numeric characters from card number
                if (isset($paymentDetails['card_number'])) {
                    $paymentDetails['card_number'] = preg_replace('/[^0-9]/', '', $paymentDetails['card_number']);
                }
                
                // Remove non-numeric characters from CVV
                if (isset($paymentDetails['cvv'])) {
                    $paymentDetails['cvv'] = preg_replace('/[^0-9]/', '', $paymentDetails['cvv']);
                }
                
                // Ensure expiry month is 2 digits
                if (isset($paymentDetails['expiry_month'])) {
                    $paymentDetails['expiry_month'] = str_pad(preg_replace('/[^0-9]/', '', $paymentDetails['expiry_month']), 2, '0', STR_PAD_LEFT);
                }
                
                // Ensure expiry year is 4 digits
                if (isset($paymentDetails['expiry_year'])) {
                    $expiryYear = preg_replace('/[^0-9]/', '', $paymentDetails['expiry_year']);
                    if (strlen($expiryYear) == 2) {
                        $expiryYear = '20' . $expiryYear;
                    }
                    $paymentDetails['expiry_year'] = $expiryYear;
                }
                
                // Trim card holder name
                if (isset($paymentDetails['card_holder'])) {
                    $paymentDetails['card_holder'] = trim($paymentDetails['card_holder']);
                }
                
                $paymentDetailsValidator = Validator::make($paymentDetails, [
                    'card_number' => ['required', 'string', 'regex:/^[0-9]{13,19}$/'],
                    'cvv' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
                    'expiry_month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
                    'expiry_year' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
                    'card_holder' => ['required', 'string', 'max:255'],
                ]);
                
                if ($paymentDetailsValidator->fails()) {
                    $errorMessages = [];
                    foreach ($paymentDetailsValidator->errors()->all() as $error) {
                        $errorMessages[] = $error;
                    }
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Card details validation failed: ' . implode(', ', $errorMessages),
                        'errors' => $paymentDetailsValidator->errors(),
                    ], 422);
                }
            }

            if ($validator->fails()) {
                $errorMessages = [];
                foreach ($validator->errors()->all() as $error) {
                    $errorMessages[] = $error;
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $errorMessages),
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validate payment method details
            $paymentMethod = $request->payment_method;
            
            // Use sanitized payment details if available (from validation above), otherwise use raw request
            if ($paymentDetailsRequired && isset($paymentDetails)) {
                // $paymentDetails is already sanitized from validation above
                // No need to validate again - already validated
            } else {
                $paymentDetails = $request->payment_details ?? [];
                
                // For Razorpay Checkout.js or simulation mode, payment_details can be empty
                if ($paymentMethod === 'card' && ($isRazorpayCard || !$hasAcquirerAccount)) {
                    // Razorpay will collect card details securely on the frontend
                    // Simulation service doesn't require real card details
                    $paymentDetails = [];
                }
            }

            // Determine payment amount
            $paymentAmount = $paymentLink->amount; // Default to full amount
            
            // If partial payment is allowed and custom amount is provided
            if ($paymentLink->allow_partial_payment && $request->has('amount') && $request->amount > 0) {
                $customAmount = (float) $request->amount;
                $remainingBalance = $paymentLink->getRemainingBalance();
                
                // Validate custom amount doesn't exceed remaining balance
                if ($customAmount > $remainingBalance) {
                    return response()->json([
                        'success' => false,
                        'message' => "Payment amount cannot exceed remaining balance of " . number_format($remainingBalance, 2) . " " . $paymentLink->currency,
                    ], 422);
                }
                
                // Validate minimum amount (at least 0.01)
                if ($customAmount < 0.01) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment amount must be at least 0.01',
                    ], 422);
                }
                
                $paymentAmount = $customAmount;
            } elseif (!$paymentLink->allow_partial_payment) {
                // If partial payment not allowed, must pay full remaining balance
                $remainingBalance = $paymentLink->getRemainingBalance();
                if ($remainingBalance < $paymentLink->amount) {
                    // Link was partially paid but partial payments are not allowed anymore
                    return response()->json([
                        'success' => false,
                        'message' => 'This payment link does not allow partial payments. Please pay the full amount.',
                    ], 422);
                }
            }

            // Prepare payment data
            // IMPORTANT: Do not introduce new DB enum values for payment_method.
            // Map Yapily sandbox method to an existing DB-safe method (netbanking)
            // while preserving the original method in payment_details for auditing.
            $storagePaymentMethod = $paymentMethod;
            if ($paymentMethod === 'yapily') {
                $storagePaymentMethod = 'netbanking';
                $paymentDetails['original_payment_method'] = 'yapily';
            }

            $paymentData = [
                'merchant_id' => $paymentLink->merchant_id,
                'payment_link_id' => $paymentLink->id,
                'amount' => $paymentAmount,
                'currency' => $paymentLink->currency,
                'payment_method' => $storagePaymentMethod,
                'payment_details' => $paymentDetails,
                'customer_details' => $request->customer_details,
                'test_mode' => $paymentLink->test_mode,
                'description' => $paymentLink->title . ($paymentLink->allow_partial_payment ? ' (Partial Payment)' : ''),
            ];

            // Process payment through GatewayFactory (clean routing architecture)
            try {
                if ($useAcquirerGateway) {
                    // Get or create gateway instance
                    if (!$gateway) {
                        $gateway = GatewayFactory::make($merchant, $acquirerAccount);
                    }
                    
                    $gatewayName = $gateway->getGatewayName();
                    
                    $this->logInfo('Processing payment through gateway', [
                        'merchant_id' => $merchant->id,
                        'gateway' => $gatewayName,
                        'payment_method' => $paymentMethod,
                    ]);
                    
                    // Create order first
                    $order = $this->paymentService->createOrder($merchant, [
                        'amount' => $paymentAmount,
                        'currency' => $paymentLink->currency,
                        'customer_details' => $request->customer_details,
                        'description' => $paymentLink->title,
                        'metadata' => ['payment_link_id' => $paymentLink->id],
                    ]);
                    
                    // Handle CashFree separately - it does NOT support server-side payment initiation
                    if ($gatewayName === 'cashfree') {
                        // Step 1: Create CashFree order (returns payment_session_id)
                        // Include return URLs for proper modal behavior
                        $baseUrl = $request->getSchemeAndHttpHost();
                        $cashfreeOrderResult = $gateway->createOrder([
                            'order_id' => $order->order_id,
                            'amount' => $paymentAmount,
                            'currency' => $paymentLink->currency,
                            'customer_details' => $request->customer_details,
                            'description' => $paymentLink->title,
                            'metadata' => ['payment_link_id' => $paymentLink->id],
                            'return_url' => rtrim($baseUrl, '/') . "/payment/return/{$token}",
                            'notify_url' => rtrim($baseUrl, '/') . "/webhooks/cashfree/{$token}",
                        ]);
                        
                        if (!$cashfreeOrderResult['success']) {
                            throw new \RuntimeException($cashfreeOrderResult['message'] ?? 'Failed to create CashFree order');
                        }
                        
                        // Save gateway order ID
                        $gatewayOrderId = $cashfreeOrderResult['gateway_order_id'] ?? null;
                        $order->gateway_order_id = $gatewayOrderId;
                        $order->save();
                        
                        $this->logInfo('CashFree order: gateway_order_id saved', [
                            'order_id' => $order->order_id,
                            'gateway_order_id' => $gatewayOrderId,
                            'gateway_order_id_type' => gettype($gatewayOrderId),
                            'cashfree_order_result' => $cashfreeOrderResult,
                        ]);
                        
                        // Step 2: Create transaction record (status: pending)
                        $transaction = $this->paymentService->processPayment($order, [
                            'payment_method' => $paymentMethod,
                        ]);
                        
                        $transaction->status = 'pending'; // ACTIVE in CashFree = pending
                        // Note: gateway_order_id is stored on Order model, not Transaction
                        // Transaction uses gateway_txn_id for payment IDs (will be set via webhook)
                        $transaction->save();
                        
                        // Step 3: Return payment_session_id for frontend checkout
                        // CashFree SDK mode must match where the order was created (acquirer test vs live)
                        $acquirerMode = strtoupper($acquirerAccount->mode ?? 'TEST');
                        $cashfreeSdkMode = ($acquirerMode === 'LIVE') ? 'production' : 'sandbox';
                        return response()->json([
                            'success' => true,
                            'gateway' => 'cashfree',
                            'order_id' => $order->order_id,
                            'gateway_order_id' => $order->gateway_order_id,
                            'payment_session_id' => $cashfreeOrderResult['payment_session_id'] ?? null,
                            'cashfree_mode' => $cashfreeSdkMode,
                            'transaction_id' => $transaction->txn_id,
                            'status' => 'pending', // ACTIVE = pending (awaiting checkout)
                            'amount' => $paymentAmount,
                            'currency' => $paymentLink->currency,
                            'customer_details' => $request->customer_details,
                            'message' => 'Order created. Please complete payment...',
                            'return_url' => url("/payment/return/{$token}"),
                            'notify_url' => url("/webhooks/cashfree/{$token}"),
                        ]);
                    }
                    
                    // For Razorpay and other gateways, use standard flow
                    // Prepare payment data for gateway
                    $gatewayPaymentData = [
                        'order_id' => $order->order_id,
                        'amount' => $paymentAmount,
                        'currency' => $paymentLink->currency,
                        'payment_method' => $paymentMethod,
                        'customer_details' => $request->customer_details,
                        'description' => $paymentLink->title,
                        'metadata' => ['payment_link_id' => $paymentLink->id],
                    ];
                    
                    // Add payment details for server-side processing (Razorpay, etc.)
                    if ($paymentMethod === 'card' && $gatewayName !== 'cashfree') {
                        $gatewayPaymentData['card_number'] = $paymentDetails['card_number'] ?? null;
                        $gatewayPaymentData['cvv'] = $paymentDetails['cvv'] ?? null;
                        $gatewayPaymentData['expiry_month'] = $paymentDetails['expiry_month'] ?? null;
                        $gatewayPaymentData['expiry_year'] = $paymentDetails['expiry_year'] ?? null;
                        $gatewayPaymentData['card_holder'] = $paymentDetails['card_holder'] ?? null;
                    }
                    
                    // Process payment through gateway
                    $gatewayResult = $gateway->charge($gatewayPaymentData);
                    
                    // Handle response based on gateway type
                    if ($gatewayName === 'razorpay' && $gateway->requiresFrontendSdk()) {
                        // Razorpay: Return order details for Checkout.js
                        $order->gateway_order_id = $gatewayResult['razorpay_order_id'] ?? null;
                        $order->save();
                        
                        return response()->json([
                            'success' => true,
                            'gateway' => 'razorpay',
                            'use_razorpay_checkout' => true,
                            'razorpay_key' => $gatewayResult['razorpay_key'] ?? null,
                            'razorpay_order_id' => $gatewayResult['razorpay_order_id'] ?? null,
                            'order_id' => $order->order_id,
                            'amount' => $gatewayResult['amount'] ?? ($paymentAmount * 100),
                            'currency' => $paymentLink->currency,
                            'customer_details' => $request->customer_details,
                            'message' => 'Please complete payment using Razorpay Checkout',
                        ]);
                    } else {
                        // Other gateways - generic handling
                        throw new \RuntimeException("Unsupported gateway: {$gatewayName}");
                    }
                } else {
                    // Fallback to simulation service if no acquirer account OR when using Yapily acquirer
                    if ($hasAcquirerAccount && $isYapilyAcquirer) {
                        $this->logInfo('Yapily acquirer configured – using internal simulation service (sandbox flow)', [
                            'merchant_id' => $merchant->id,
                            'acquirer_name' => $acquirerAccount->acquirer_name,
                        ]);
                    } else {
                        $this->logInfo('No acquirer account found, using simulation service', [
                            'merchant_id' => $merchant->id,
                        ]);
                    }

                    $result = $this->simulationService->processPayment($paymentData);
                }
                
                $this->logInfo('Payment processed', [
                    'success' => $result['success'],
                    'order_id' => $result['order_id'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'status' => $result['status'] ?? 'unknown',
                    'gateway_txn_id' => $result['gateway_txn_id'] ?? null,
                ]);

                // Update payment link with partial payment info if successful
                if ($result['success']) {
                    // Refresh payment link to get latest status
                    $paymentLink->refresh();
                    
                    // Add payment link info to response (for both partial and full payments)
                    $result['payment_link'] = [
                        'amount_paid' => $paymentLink->amount_paid ?? 0,
                        'remaining_balance' => $paymentLink->getRemainingBalance(),
                        'is_fully_paid' => $paymentLink->isFullyPaid(),
                        'is_partially_paid' => $paymentLink->isPartiallyPaid(),
                    ];
                }

                // Add redirect URLs - use full request URL with port
                $baseUrl = $request->getSchemeAndHttpHost();
                $port = $request->getPort();
                
                // Ensure port is included in URL
                if ($port && $port != 80 && $port != 443) {
                    // Check if port is already in URL
                    if (strpos($baseUrl, ':') === false || (strpos($baseUrl, ':80') !== false && $port != 80) || (strpos($baseUrl, ':443') !== false && $port != 443)) {
                        // Remove existing port if wrong, then add correct one
                        $baseUrl = preg_replace('/:\d+$/', '', $baseUrl);
                        $baseUrl .= ':' . $port;
                    }
                }
                
                // Fallback to config app.url if baseUrl is invalid
                if (!$baseUrl || $baseUrl === 'http://' || $baseUrl === 'https://') {
                    $baseUrl = config('app.url', 'http://127.0.0.1:8000');
                }
                
                if ($result['success']) {
                    $result['redirect_url'] = rtrim($baseUrl, '/') . '/success-simple.html?transaction_id=' . ($result['transaction_id'] ?? '');
                } else {
                    $result['redirect_url'] = rtrim($baseUrl, '/') . '/failure-simple.html?transaction_id=' . ($result['transaction_id'] ?? '');
                }

                return response()->json($result, $result['success'] ? 200 : 402);
                
            } catch (\Exception $serviceError) {
                $this->logError('Payment simulation service error', [
                    'token' => $token,
                    'error' => $serviceError->getMessage(),
                    'file' => $serviceError->getFile(),
                    'line' => $serviceError->getLine(),
                    'trace' => $serviceError->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment processing failed. Please try again.',
                    'error' => config('app.debug') ? $serviceError->getMessage() : null,
                ], 500);
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() ?: 'An error occurred. Please try again.';
            
            $this->logError('Payment processing error', [
                'token' => $token,
                'error' => $errorMessage,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Always return the actual error message for better debugging
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    public function verifyRazorpay(Request $request, string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            $merchant = $paymentLink->merchant;
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'order_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification data',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get the order
            $order = Order::where('order_id', $request->order_id)
                         ->where('merchant_id', $merchant->id)
                         ->first();
                         
            if (!$order) {
                $this->logError('Order not found for verification', [
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant->id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Get acquirer adapter
            $acquirerAccount = $merchant->getActiveAcquirerAccount();
            if (!$acquirerAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active acquirer account found',
                ], 400);
            }

            $resolver = app(\App\Services\Acquirers\AcquirerResolver::class);
            $adapter = $resolver->resolve($acquirerAccount);

            $this->logInfo('Verifying Razorpay payment', [
                'payment_id' => $request->razorpay_payment_id,
                'order_id' => $request->order_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'token' => $token,
                'merchant_id' => $merchant->id,
            ]);

            // Verify payment signature
            $verifyResult = $adapter->verifyPayment([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_signature' => $request->razorpay_signature,
            ], $request->razorpay_signature);

            if ($verifyResult['success']) {
                $this->logInfo('Payment verification successful, creating transaction', [
                    'order_id' => $order->id,
                    'order_order_id' => $order->order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                ]);
                
                // Calculate fees for the transaction
                $baseRateService = app(\App\Services\BaseRateService::class);
                $bank = $merchant->bank ?? null;
                $feeCalculation = $baseRateService->calculateFee(
                    $merchant,
                    $order->amount,
                    'card',
                    $bank,
                    \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
                    \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC
                );

                // Get or create transaction
                // First try to find by order_id and gateway_txn_id matching razorpay_order_id
                $transaction = Transaction::where('order_id', $order->id)
                    ->where(function($query) use ($request) {
                        $query->where('gateway_txn_id', $request->razorpay_order_id)
                              ->orWhereJsonContains('gateway_response->gateway_order_id', $request->razorpay_order_id)
                              ->orWhereJsonContains('gateway_response->order_id', $request->razorpay_order_id);
                    })
                    ->first();
                    
                $this->logInfo('Transaction lookup result', [
                    'found' => $transaction !== null,
                    'transaction_id' => $transaction ? $transaction->id : null,
                ]);

                if (!$transaction) {
                    // Store gateway_order_id in gateway_response JSON
                    // NOTE: Razorpay SDK returns Razorpay\Api\Payment (Entity), not a plain array.
                    // Normalize raw_response into an array before merging to avoid type errors.
                    $rawResponse = $verifyResult['raw_response'] ?? [];
                    if ($rawResponse instanceof \Razorpay\Api\Entity) {
                        $rawResponse = $rawResponse->toArray();
                    } elseif (!is_array($rawResponse)) {
                        $rawResponse = [];
                    }

                    $gatewayResponse = array_merge($rawResponse, [
                        'gateway_order_id' => $request->razorpay_order_id,
                        'order_id' => $request->razorpay_order_id,
                    ]);
                    
                    $this->logInfo('Creating new transaction', [
                        'order_id' => $order->id,
                        'merchant_id' => $order->merchant_id,
                        'amount' => $order->amount,
                        'fee_amount' => $feeCalculation['fee_amount'],
                        'net_amount' => $order->amount - $feeCalculation['total_fee'],
                    ]);
                    
                    // Create transaction if it doesn't exist
                    $transaction = Transaction::create([
                        'order_id' => $order->id,
                        'merchant_id' => $order->merchant_id,
                        'txn_id' => Transaction::generateTxnId(),
                        'amount' => $order->amount,
                        'fee_amount' => $feeCalculation['fee_amount'],
                        'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
                        'net_amount' => $order->amount - $feeCalculation['total_fee'],
                        'currency' => $order->currency,
                        'payment_method' => 'card',
                        'status' => 'success',
                        'gateway_txn_id' => $request->razorpay_payment_id,
                        'gateway_response' => $gatewayResponse,
                        'test_mode' => $order->test_mode,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'captured_at' => now(),
                    ]);
                    
                    $this->logInfo('Transaction created successfully', [
                        'transaction_id' => $transaction->id,
                        'txn_id' => $transaction->txn_id,
                        'status' => $transaction->status,
                    ]);
                } else {
                    // Update existing transaction
                    // Merge gateway_order_id into gateway_response
                    $currentResponse = $transaction->gateway_response ?? [];
                    $gatewayResponse = array_merge($currentResponse, $verifyResult['raw_response'] ?? [], [
                        'gateway_order_id' => $request->razorpay_order_id,
                        'order_id' => $request->razorpay_order_id,
                    ]);
                    
                    $transaction->update([
                        'status' => 'success',
                        'gateway_txn_id' => $request->razorpay_payment_id,
                        'gateway_response' => $gatewayResponse,
                        'fee_amount' => $feeCalculation['fee_amount'],
                        'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
                        'net_amount' => $order->amount - $feeCalculation['total_fee'],
                        'captured_at' => now(),
                    ]);
                }
                
                // Fire success event
                event(new \App\Events\PaymentSuccess($transaction));

                // Update order
                $order->update(['status' => 'completed']);

                // Update payment link
                $paymentAmount = $transaction->amount;
                if ($paymentLink->allow_partial_payment) {
                    $isFullyPaid = $paymentLink->addPartialPayment($paymentAmount);
                } else {
                    $paymentLink->markAsPaid();
                }

                $paymentLink->refresh();

                // Add redirect URLs
                $baseUrl = $request->getSchemeAndHttpHost();
                $port = $request->getPort();
                
                if ($port && $port != 80 && $port != 443) {
                    if (strpos($baseUrl, ':') === false || (strpos($baseUrl, ':80') !== false && $port != 80) || (strpos($baseUrl, ':443') !== false && $port != 443)) {
                        $baseUrl = preg_replace('/:\d+$/', '', $baseUrl);
                        $baseUrl .= ':' . $port;
                    }
                }
                
                if (!$baseUrl || $baseUrl === 'http://' || $baseUrl === 'https://') {
                    $baseUrl = config('app.url', 'http://127.0.0.1:8000');
                }

                $result = [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'order_id' => $order->order_id,
                    'transaction_id' => $transaction->txn_id,
                    'status' => $transaction->status,
                    'gateway_txn_id' => $transaction->gateway_txn_id,
                    'redirect_url' => rtrim($baseUrl, '/') . '/success-simple.html?transaction_id=' . $transaction->txn_id,
                ];

                // Add payment link info if partial payment
                if ($paymentLink->allow_partial_payment) {
                    $result['payment_link'] = [
                        'amount_paid' => $paymentLink->amount_paid ?? 0,
                        'remaining_balance' => $paymentLink->getRemainingBalance(),
                        'is_fully_paid' => $paymentLink->isFullyPaid(),
                        'is_partially_paid' => $paymentLink->isPartiallyPaid(),
                    ];
                }

                return response()->json($result);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $verifyResult['message'] ?? 'Payment verification failed',
                ], 400);
            }

        } catch (\Exception $e) {
            $this->logError('Razorpay verification error', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle callback from embedded Razorpay Payment Page.
     * This is called when payment succeeds or fails in the iframe.
     */
    public function handleEmbeddedCallback(Request $request, string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            $merchant = $paymentLink->merchant;
            
            // Get callback parameters from Razorpay
            $razorpayPaymentId = $request->get('razorpay_payment_id');
            $razorpayPaymentLinkId = $request->get('razorpay_payment_link_id');
            $razorpayPaymentLinkReferenceId = $request->get('razorpay_payment_link_reference_id');
            $razorpayPaymentLinkStatus = $request->get('razorpay_payment_link_status');
            $razorpaySignature = $request->get('razorpay_signature');
            
            $this->logInfo('Embedded Razorpay callback received', [
                'token' => $token,
                'payment_id' => $razorpayPaymentId,
                'payment_link_id' => $razorpayPaymentLinkId,
                'status' => $razorpayPaymentLinkStatus,
            ]);

            // If payment was successful, verify and process
            if ($razorpayPaymentId && $razorpayPaymentLinkStatus === 'paid') {
                $acquirerAccount = $merchant->getActiveAcquirerAccount();
                if (!$acquirerAccount) {
                    return redirect("/pay/{$token}")->with('error', 'Payment processing error. Please contact support.');
                }

                $resolver = app(AcquirerResolver::class);
                $adapter = $resolver->resolve($acquirerAccount);

                // Fetch payment details from Razorpay
                $razorpayPayment = $adapter->getPaymentStatus($razorpayPaymentId);
                
                if ($razorpayPayment['success']) {
                    // Create or update transaction
                    $order = $this->paymentService->createOrder($merchant, [
                        'amount' => $paymentLink->amount,
                        'currency' => $paymentLink->currency,
                        'customer_details' => [],
                        'description' => $paymentLink->title,
                        'metadata' => ['payment_link_id' => $paymentLink->id],
                    ]);

                    $baseRateService = app(\App\Services\BaseRateService::class);
                    $bank = $merchant->bank ?? null;
                    $feeCalculation = $baseRateService->calculateFee(
                        $merchant,
                        $order->amount,
                        'card',
                        $bank,
                        \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
                        \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC
                    );

                    $transaction = Transaction::create([
                        'order_id' => $order->id,
                        'merchant_id' => $order->merchant_id,
                        'txn_id' => Transaction::generateTxnId(),
                        'amount' => $order->amount,
                        'fee_amount' => $feeCalculation['fee_amount'],
                        'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
                        'net_amount' => $order->amount - $feeCalculation['total_fee'],
                        'currency' => $order->currency,
                        'payment_method' => 'card',
                        'status' => 'success',
                        'gateway_txn_id' => $razorpayPaymentId,
                        'gateway_response' => $razorpayPayment,
                        'test_mode' => $order->test_mode,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'captured_at' => now(),
                    ]);

                    $order->update(['status' => 'completed']);

                    if ($paymentLink->allow_partial_payment) {
                        $paymentLink->addPartialPayment($transaction->amount);
                    } else {
                        $paymentLink->markAsPaid();
                    }

                    event(new \App\Events\PaymentSuccess($transaction));

                    // Return success page with transaction details
                    return redirect("/payment/success/{$token}")->with('transaction_id', $transaction->txn_id);
                }
            }

            // Payment failed or cancelled
            return redirect("/payment/failed/{$token}")->with('error', 'Payment was not completed.');

        } catch (\Exception $e) {
            $this->logError('Error handling embedded Razorpay callback', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect("/pay/{$token}")->with('error', 'Payment processing error. Please try again.');
        }
    }

    public function success(string $token)
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
        return view('checkout.success', compact('paymentLink'));
    }

    public function failed(string $token)
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
        return view('checkout.failed', compact('paymentLink'));
    }

    /**
     * Handle CashFree return URL after payment.
     * For modal flow, this may be called via iframe, so we handle both redirect and JSON responses.
     */
    public function handleReturn(Request $request, string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            
            // Get order_id and payment_status from query parameters
            $gatewayOrderId = $request->query('order_id') ?? $request->query('cf_order_id');
            $paymentStatus = $request->query('payment_status') ?? $request->query('order_status');
            
            if (!$gatewayOrderId) {
                // Check if this is an AJAX request (modal flow)
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order ID missing in return URL',
                    ], 400);
                }
                return redirect("/pay/{$token}")->with('error', 'Order ID missing in return URL');
            }

            // Find order
            $order = Order::where('gateway_order_id', $gatewayOrderId)
                ->where('merchant_id', $paymentLink->merchant_id)
                ->first();

            if (!$order) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found',
                    ], 404);
                }
                return redirect("/pay/{$token}")->with('error', 'Order not found');
            }

            // Get CashFree adapter and verify payment status
            $acquirerAccount = $paymentLink->merchant->acquirerAccounts()
                ->where('acquirer_name', 'cashfree')
                ->where('is_active', true)
                ->first();

            if ($acquirerAccount) {
                $resolver = app(\App\Services\Acquirers\AcquirerResolver::class);
                $adapter = $resolver->resolve($acquirerAccount);
                
                // Verify payment status from CashFree
                $statusResult = $adapter->getPaymentStatus($gatewayOrderId);
                
                if ($statusResult['success']) {
                    $transaction = $order->transactions()->first();
                    if ($transaction) {
                        $transaction->status = $statusResult['status'];
                        if (isset($statusResult['payment_id'])) {
                            $transaction->gateway_txn_id = $statusResult['payment_id'];
                            $transaction->gateway_transaction_id = $statusResult['payment_id'];
                        }
                        $transaction->save();
                        
                        // Update payment link if successful
                        if ($statusResult['status'] === 'success') {
                            if ($paymentLink->allow_partial_payment) {
                                $paymentLink->addPartialPayment($transaction->amount);
                            } else {
                                $paymentLink->markAsPaid();
                            }
                            
                            // For modal flow, return JSON; otherwise redirect
                            if ($request->wantsJson() || $request->ajax()) {
                                return response()->json([
                                    'success' => true,
                                    'status' => 'success',
                                    'transaction_id' => $transaction->txn_id,
                                    'message' => 'Payment successful',
                                ]);
                            }
                            return redirect("/payment/success/{$token}")->with('transaction_id', $transaction->txn_id);
                        } elseif ($statusResult['status'] === 'failed') {
                            if ($request->wantsJson() || $request->ajax()) {
                                return response()->json([
                                    'success' => false,
                                    'status' => 'failed',
                                    'message' => 'Payment failed',
                                ]);
                            }
                            return redirect("/payment/failed/{$token}")->with('error', 'Payment failed');
                        }
                    }
                }
            }

            // If status is still pending
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Payment is being processed. Please wait...',
                ]);
            }
            return redirect("/pay/{$token}")->with('info', 'Payment is being processed. Please wait...');

        } catch (\Exception $e) {
            $this->logError('Error handling CashFree return', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment processing error. Please try again.',
                ], 500);
            }
            return redirect("/pay/{$token}")->with('error', 'Payment verification error. Please try again.');
        }
    }
}

 