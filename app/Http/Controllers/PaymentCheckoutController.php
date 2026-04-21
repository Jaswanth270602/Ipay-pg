<?php

namespace App\Http\Controllers;

use App\Traits\LogsConditionally;
use App\Services\PaymentService;
use App\Services\PaymentSimulationService;
use App\Services\Fraud\FraudEngine;
use App\Services\GatewayModeService;
use App\Services\PaymentGateways\GatewayFactory;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use App\Models\PaymentLink;
use App\Models\Order;
use App\Models\PaymentRoutingMonitor;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentOrchestration\AcquirerRoutingService;
use App\Services\NativeUpiService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PaymentCheckoutController extends Controller
{
    use LogsConditionally;

    /**
     * Shown on payment-link checkout — never name upstream PSPs or internal routing details.
     */
    private const CHECKOUT_PUBLIC_FAILURE_MESSAGE = 'Payment could not be completed. Please try again later or contact support.';

    protected PaymentService $paymentService;
    protected PaymentSimulationService $simulationService;
    protected FraudEngine $fraudEngine;
    protected NativeUpiService $nativeUpiService;

    public function __construct(
        PaymentService $paymentService,
        PaymentSimulationService $simulationService,
        FraudEngine $fraudEngine,
        NativeUpiService $nativeUpiService
    )
    {
        $this->paymentService = $paymentService;
        $this->simulationService = $simulationService;
        $this->fraudEngine = $fraudEngine;
        $this->nativeUpiService = $nativeUpiService;
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
            $checkoutInternalSimulation = ! $this->useAcquirerGatewayForCheckout($paymentLink);

            return view('checkout.payment', compact('paymentLink', 'checkoutInternalSimulation'));
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

    /**
     * True when live acquirer path is used (same rules as process(), excluding explicit simulate flag).
     */
    protected function useAcquirerGatewayForCheckout(PaymentLink $paymentLink): bool
    {
        $merchant = $paymentLink->merchant;
        $routing = app(AcquirerRoutingService::class)->resolve($merchant);
        $hasAcquirerAccount = $routing['account'] !== null;
        $gatewayModeIsLive = GatewayModeService::isLive();
        $merchantIsLive = ! $merchant->test_mode;
        $isTestPaymentLink = (bool) $paymentLink->test_mode;

        return $hasAcquirerAccount
            && $gatewayModeIsLive
            && $merchantIsLive
            && ! $isTestPaymentLink;
    }

    /**
     * Store checkout payload in session and redirect to the dedicated test simulation page
     * (UPI, net banking, wallet — internal simulation only).
     */
    public function storeTestSimulate(Request $request, string $token)
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();

        if (! $paymentLink->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment link is no longer available.',
            ], 410);
        }

        if ($paymentLink->status === 'paid' || $paymentLink->isFullyPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment link has already been fully paid.',
            ], 400);
        }

        if ($this->useAcquirerGatewayForCheckout($paymentLink)) {
            return response()->json([
                'success' => false,
                'message' => 'The simulation page is only available for internal test checkout (test merchant, test payment link, or gateway test mode).',
            ], 422);
        }

        $method = $request->input('payment_method');
        if (! in_array($method, ['netbanking', 'upi', 'wallet'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method for simulation.',
            ], 422);
        }

        $customerDetails = $request->input('customer_details', []);
        if (isset($customerDetails['phone'])) {
            $customerDetails['phone'] = preg_replace('/[^0-9]/', '', (string) $customerDetails['phone']);
        }
        if (isset($customerDetails['name'])) {
            $customerDetails['name'] = trim((string) $customerDetails['name']);
        }

        $baseValidator = Validator::make([
            'customer_details' => $customerDetails,
            'amount' => $request->input('amount'),
        ], [
            'customer_details' => 'required|array',
            'customer_details.name' => ['required', 'string', 'max:50', 'regex:/^(?=.*[A-Za-z])[A-Za-z ]+$/'],
            'customer_details.email' => 'required|email',
            'customer_details.phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'amount' => 'nullable|numeric|min:0.01',
        ], [
            'customer_details.name.regex' => 'Full name may contain only letters and spaces.',
        ]);

        if ($baseValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $baseValidator->errors()->first() ?: 'Validation failed.',
                'errors' => $baseValidator->errors(),
            ], 422);
        }

        $paymentAmount = $paymentLink->amount;

        if ($paymentLink->allow_partial_payment && $request->has('amount') && (float) $request->amount > 0) {
            $customAmount = (float) $request->amount;
            $remainingBalance = $paymentLink->getRemainingBalance();

            if ($customAmount > $remainingBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed remaining balance of '.number_format($remainingBalance, 2).' '.$paymentLink->currency,
                ], 422);
            }

            if ($customAmount < 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount must be at least 0.01',
                ], 422);
            }

            $paymentAmount = $customAmount;
        } elseif (! $paymentLink->allow_partial_payment) {
            $remainingBalance = $paymentLink->getRemainingBalance();
            if ($remainingBalance < $paymentLink->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link does not allow partial payments. Please pay the full amount.',
                ], 422);
            }
        }

        $sessionKey = 'test_simulate_checkout_'.$token;

        if ($method === 'netbanking') {
            $bankCode = strtoupper(trim((string) data_get($request->input('payment_details'), 'bank_code', '')));
            $extra = Validator::make(['bank_code' => $bankCode], [
                'bank_code' => ['required', 'string', 'regex:/^[A-Z0-9]{2,20}$/'],
            ]);
            if ($extra->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $extra->errors()->first() ?: 'Select a valid bank.',
                    'errors' => $extra->errors(),
                ], 422);
            }
            $bankLabel = trim((string) $request->input('payment_details.bank_label', ''));
            $payload = [
                'payment_method' => 'netbanking',
                'customer_details' => $customerDetails,
                'payment_details' => [
                    'bank_code' => $bankCode,
                    'bank_label' => $bankLabel !== '' ? $bankLabel : $bankCode,
                ],
                'amount' => $paymentAmount,
            ];
        } elseif ($method === 'upi') {
            $upiId = strtolower(trim((string) $request->input('payment_details.upi_id', '')));
            $payload = [
                'payment_method' => 'upi',
                'customer_details' => $customerDetails,
                'payment_details' => [
                    'upi_id' => $upiId !== '' ? $upiId : null,
                    'upi_app' => $request->input('payment_details.upi_app') ?: null,
                ],
                'amount' => $paymentAmount,
            ];
        } else {
            $wallet = strtolower(trim((string) $request->input('payment_details.wallet_provider', '')));
            $extra = Validator::make(['wallet_provider' => $wallet], [
                'wallet_provider' => ['required', 'string', 'regex:/^[a-z0-9_-]{2,40}$/'],
            ]);
            if ($extra->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a wallet.',
                    'errors' => $extra->errors(),
                ], 422);
            }
            $payload = [
                'payment_method' => 'wallet',
                'customer_details' => $customerDetails,
                'payment_details' => [
                    'wallet_provider' => $wallet,
                ],
                'amount' => $paymentAmount,
            ];
        }

        Session::put($sessionKey, $payload);

        return response()->json([
            'success' => true,
            'redirect_url' => route('payment.test-simulate', ['token' => $token]),
        ]);
    }

    /**
     * Dedicated test simulation page (success/failure, then POST /pay/{token} with simulate flags).
     */
    public function showTestSimulate(Request $request, string $token)
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();

        if (! $paymentLink->isActive()) {
            abort(404, 'This payment link is no longer available.');
        }

        $sessionKey = 'test_simulate_checkout_'.$token;
        $payload = Session::get($sessionKey);
        if (! is_array($payload) && Session::has('test_simulate_nb_'.$token)) {
            $payload = Session::get('test_simulate_nb_'.$token);
            Session::put($sessionKey, $payload);
            Session::forget('test_simulate_nb_'.$token);
        }

        $allowed = ['netbanking', 'upi', 'wallet'];
        if (! is_array($payload) || ! in_array($payload['payment_method'] ?? '', $allowed, true)) {
            return redirect()
                ->route('payment.checkout', ['token' => $token])
                ->with('error', 'Session expired or invalid. Please choose a payment method and try again.');
        }

        return view('checkout.test-simulate', [
            'paymentLink' => $paymentLink,
            'payload' => $payload,
        ]);
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

            // Gateway / merchant / link mode — used before routing so we skip expensive work in test flows.
            $gatewayModeIsLive = GatewayModeService::isLive();
            $merchantIsLive = ! $merchant->test_mode;
            $isTestPaymentLink = (bool) $paymentLink->test_mode;
            // Frontend "Simulate success/fail" on test links — internal only, no acquirer routing.
            $isSimulationRequest = (bool) $request->input('payment_details.simulate', false);

            // Only resolve acquirers + health checks for live-merchant, non-test-link, non-simulation checkouts.
            // Test payments use internal simulation — no routing monitor rows (avoids stuck "pending" without txn_id).
            $shouldRunAcquirerRouting = $merchantIsLive
                && ! $isTestPaymentLink
                && ! $isSimulationRequest;

            if ($shouldRunAcquirerRouting) {
                $routing = app(AcquirerRoutingService::class)->resolve($merchant);
                $acquirerAccount = $routing['account'];
                $flowTrace = $routing['flow_trace'] ?? [];
            } else {
                $acquirerAccount = null;
                $flowTrace = [];
            }
            $hasAcquirerAccount = $acquirerAccount !== null;

            $shouldPersistRoutingMonitor = $gatewayModeIsLive
                && $merchantIsLive
                && ! $isTestPaymentLink
                && ! $isSimulationRequest;

            if ($shouldPersistRoutingMonitor) {
                $this->persistCheckoutRoutingMonitor(
                    $merchant,
                    $request,
                    $flowTrace,
                    $acquirerAccount,
                    $hasAcquirerAccount ? 'pending' : 'failed',
                    $hasAcquirerAccount ? null : 'Acquirer not configured'
                );
            }

            if ($acquirerAccount) {
                $this->logInfo('Active acquirer selected for checkout', [
                    'merchant_id' => $merchant->id,
                    'acquirer_account_id' => $acquirerAccount->id,
                    'acquirer_name' => $acquirerAccount->acquirer_name,
                    'acquirer_mode' => $acquirerAccount->mode,
                ]);
            }

            // Acquirers (Razorpay, Cashfree) only when BOTH gateway and merchant are LIVE.
            // Gateway TEST = internal only. Merchant TEST = internal only (no Razorpay even if gateway is live).

            $useAcquirerGateway = $hasAcquirerAccount
                && $gatewayModeIsLive
                && $merchantIsLive
                && !$isSimulationRequest
                && !$isTestPaymentLink;

            if ($isTestPaymentLink && $hasAcquirerAccount && $gatewayModeIsLive && $merchantIsLive) {
                $this->logInfo('Test payment link – using internal simulation only (acquirer not called).', [
                    'payment_link_id' => $paymentLink->id,
                    'merchant_id' => $merchant->id,
                ]);
            }

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

            // Never silently simulate LIVE merchant payments when gateway mode is TEST.
            // This prevents false-success redirects and makes misconfiguration explicit.
            if ($hasAcquirerAccount && $merchantIsLive && !$gatewayModeIsLive && !$isSimulationRequest && !$isTestPaymentLink) {
                $adminDetail = $this->buildCheckoutFailureDetailForAdmin(
                    $flowTrace,
                    $acquirerAccount,
                    'Application payment mode is TEST; live acquirer API calls are disabled.'
                );
                $failedTxn = $this->createFailedCheckoutTransaction(
                    $merchant,
                    $paymentLink,
                    $request,
                    $adminDetail,
                    [
                        'routing_flow_trace' => $flowTrace,
                        'failure_class' => 'gateway_mode_test',
                    ]
                );
                $baseUrl = $request->getSchemeAndHttpHost();
                $this->logError('checkout_config_failure', [
                    'merchant_id' => $merchant->id,
                    'payment_link_id' => $paymentLink->id,
                    'failure_class' => 'gateway_mode_test',
                    'routing_flow_trace' => $flowTrace,
                    'transaction_id' => $failedTxn?->txn_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                    'error_code' => 'GATEWAY_MODE_TEST',
                    'redirect_url' => rtrim($baseUrl, '/') . '/failure-simple.html?transaction_id=' . ($failedTxn?->txn_id ?? ''),
                ], 503);
            }

            // For LIVE merchant payments, never fallback to internal simulation when
            // no acquirer passed routing health checks.
            if (!$hasAcquirerAccount && $gatewayModeIsLive && $merchantIsLive && !$isSimulationRequest && !$isTestPaymentLink) {
                $adminDetail = $this->buildCheckoutFailureDetailForAdmin(
                    $flowTrace,
                    null,
                    'No active acquirer account resolved for this merchant after routing.'
                );
                $failedTxn = $this->createFailedCheckoutTransaction(
                    $merchant,
                    $paymentLink,
                    $request,
                    $adminDetail,
                    [
                        'routing_flow_trace' => $flowTrace,
                        'failure_class' => 'acquirer_not_available',
                    ]
                );
                $baseUrl = $request->getSchemeAndHttpHost();
                $this->logError('checkout_config_failure', [
                    'merchant_id' => $merchant->id,
                    'payment_link_id' => $paymentLink->id,
                    'failure_class' => 'acquirer_not_available',
                    'routing_flow_trace' => $flowTrace,
                    'transaction_id' => $failedTxn?->txn_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                    'error_code' => 'ACQUIRER_NOT_AVAILABLE',
                    'redirect_url' => rtrim($baseUrl, '/') . '/failure-simple.html?transaction_id=' . ($failedTxn?->txn_id ?? ''),
                ], 503);
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
            if (isset($customerDetails['name'])) {
                $customerDetails['name'] = trim((string) $customerDetails['name']);
            }

            $allowedMethods = ['card', 'upi', 'netbanking', 'wallet'];
            $validator = Validator::make(array_merge($request->all(), ['customer_details' => $customerDetails]), [
                'payment_method' => ['required', 'in:' . implode(',', $allowedMethods)],
                'customer_details' => 'required|array',
                'customer_details.name' => ['required', 'string', 'max:50', 'regex:/^(?=.*[A-Za-z])[A-Za-z ]+$/'],
                'customer_details.email' => 'required|email',
                'customer_details.phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
                'payment_details' => $paymentDetailsRequired ? 'required|array' : 'nullable|array',
                'amount' => 'nullable|numeric|min:0.01', // Optional custom amount for partial payment
            ], [
                'customer_details.name.regex' => 'Full name may contain only letters and spaces.',
            ]);
            
            // Additional validation for payment_details
            // 1) When a live gateway (e.g. Cashfree) requires full card details
            // 2) When in internal/simulated mode but card details are still provided (to prevent obviously invalid test data)
            //
            // IMPORTANT: When frontend explicitly requests a simulation (test buttons),
            // allow `payment_details` to contain only `{simulate: true, simulate_result: ...}`
            // without requiring card fields.
            if ($request->payment_method === 'card'
                && ($paymentDetailsRequired || $request->filled('payment_details'))
                && !(bool) $request->input('payment_details.simulate', false)
            ) {
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

                // Ensure expiry year is 4 digits and normalised to YYYY
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
                    'card_number' => ['required', 'string', 'regex:/^[0-9]{16}$/'],
                    'cvv' => ['required', 'string', 'regex:/^[0-9]{3}$/'],
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
                if ($paymentMethod === 'card' && ($isRazorpayCard || !$hasAcquirerAccount) && !$isSimulationRequest && !$isTestPaymentLink) {
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
            $storagePaymentMethod = $paymentMethod;

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

            // FDS execution example:
            // Evaluate risk before gateway/simulation processing.
            $country = (string) ($request->header('CF-IPCountry')
                ?? $request->header('X-Country-Code')
                ?? data_get($request->customer_details, 'country')
                ?? '');
            $deviceFingerprint = (string) ($request->header('X-Device-Fingerprint')
                ?? data_get($paymentDetails, 'device_fingerprint')
                ?? '');
            $customerEmail = (string) data_get($request->customer_details, 'email', '');

            $fraudContext = [
                'transaction_id' => null, // Known once payment transaction row is created
                'merchant_id' => $paymentLink->merchant_id,
                'user_id' => null,
                'amount' => $paymentAmount,
                'customer_email' => $customerEmail,
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'country' => strtoupper(trim($country)),
                'device_fingerprint' => $deviceFingerprint,
                'payment_status' => 'attempt',
            ];

            $fraudResult = $this->fraudEngine->evaluate($fraudContext);
            if (($fraudResult['decision'] ?? 'allow') === 'block') {
                $this->logWarning('checkout_fraud_block', [
                    'merchant_id' => $merchant->id,
                    'payment_link_id' => $paymentLink->id,
                    'fraud' => $fraudResult,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                    'error_code' => 'FRAUD_BLOCKED',
                ], 403);
            }

            // Live UPI: native upi://pay only (no Razorpay/Cashfree/other PG SDKs).
            $liveUpiEligible = $gatewayModeIsLive
                && $merchantIsLive
                && ! $isTestPaymentLink
                && ! $isSimulationRequest;

            if ($paymentMethod === 'upi' && $liveUpiEligible) {
                if (strtoupper((string) $paymentLink->currency) !== 'INR') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Native UPI checkout is only available for INR payment links.',
                        'error_code' => 'NATIVE_UPI_INR_ONLY',
                    ], 422);
                }

                if (! $this->nativeUpiService->hasReceiveVpa($merchant)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Receive UPI ID (VPA) is not configured. Set NATIVE_UPI_RECEIVE_VPA in .env or merchants.settings.receive_upi_vpa.',
                        'error_code' => 'NATIVE_UPI_NOT_CONFIGURED',
                    ], 422);
                }

                return $this->processNativeUpiCheckout(
                    $request,
                    $token,
                    $paymentLink,
                    $merchant,
                    $paymentAmount,
                    $customerDetails,
                    $paymentDetails
                );
            }

            // Process payment through GatewayFactory (clean routing architecture)
            try {
                $gatewayName = null;
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

                        $this->linkLatestCheckoutRoutingMonitorToTransaction($merchant, $transaction->txn_id);
                        
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
                        if (!($gatewayResult['success'] ?? false)) {
                            throw new \RuntimeException($gatewayResult['message'] ?? 'Could not create Razorpay order');
                        }
                        $rzpKey = trim((string) ($gatewayResult['razorpay_key'] ?? ''));
                        $rzpOrderId = trim((string) ($gatewayResult['razorpay_order_id'] ?? ''));
                        if ($rzpKey === '' || $rzpOrderId === '') {
                            $this->logError('Razorpay checkout payload incomplete after charge', [
                                'merchant_id' => $merchant->id,
                                'order_id' => $order->id,
                            ]);
                            throw new \RuntimeException(
                                'Razorpay checkout could not be prepared. Verify Key ID and Key Secret on the acquirer account.'
                            );
                        }

                        // Razorpay: Return order details for Checkout.js
                        $order->gateway_order_id = $gatewayResult['razorpay_order_id'] ?? null;
                        $order->save();

                        // IMPORTANT: Create a pending transaction BEFORE opening Razorpay Checkout.
                        // Otherwise, Razorpay failures/cancellations won't be recorded.
                        $baseRateService = app(\App\Services\BaseRateService::class);
                        $bank = $merchant->bank ?? null;
                        $feeCalculation = $baseRateService->calculateFee(
                            $merchant,
                            $order->amount,
                            $paymentMethod,
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
                            'net_amount' => $order->amount - ($feeCalculation['total_fee'] ?? 0),
                            'currency' => $order->currency,
                            'payment_method' => $paymentMethod,
                            'status' => 'pending',
                            // Store Razorpay order id so verifyRazorpay can update this record
                            'gateway_txn_id' => $order->gateway_order_id,
                            'gateway_response' => [
                                'gateway' => 'razorpay',
                                'gateway_order_id' => $order->gateway_order_id,
                                'order_id' => $order->gateway_order_id,
                            ],
                            'test_mode' => $order->test_mode,
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                        try {
                            $snapshot = app(\App\Services\Rates\MerchantRateSnapshotService::class)->createPaymentSnapshot(
                                merchant: $merchant,
                                paymentMethod: (string) $paymentMethod,
                                amount: (float) $order->amount,
                                feeAmount: (float) ($feeCalculation['fee_amount'] ?? 0),
                                percentageFee: (float) ($feeCalculation['percentage_fee'] ?? 0),
                                flatFee: (float) ($feeCalculation['flat_fee'] ?? 0),
                                gstPercentage: (float) ($feeCalculation['gst_percentage'] ?? 18),
                                baseRateId: $feeCalculation['rate_id'] ?? null
                            );
                            $transaction->update([
                                'admin_rate_snapshot_id' => $snapshot->id,
                                'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
                            ]);
                        } catch (\Throwable $e) {
                            $this->logWarning('Could not snapshot rate for pending transaction', [
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $this->linkLatestCheckoutRoutingMonitorToTransaction($merchant, $transaction->txn_id);
                        
                        return response()->json([
                            'success' => true,
                            'gateway' => 'razorpay',
                            'use_razorpay_checkout' => true,
                            'razorpay_key' => $gatewayResult['razorpay_key'] ?? null,
                            'razorpay_order_id' => $gatewayResult['razorpay_order_id'] ?? null,
                            'order_id' => $order->order_id,
                            'transaction_id' => $transaction->txn_id,
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
                    $this->logInfo('Using internal simulation service (no live acquirer path)', [
                        'merchant_id' => $merchant->id,
                        'has_acquirer_account' => $hasAcquirerAccount,
                    ]);

                    $result = $this->simulationService->processPayment($paymentData);

                    if ($request->boolean('payment_details.simulate')
                        && in_array($paymentMethod, ['netbanking', 'upi', 'wallet'], true)) {
                        Session::forget('test_simulate_checkout_'.$token);
                    }
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

                $rawError = (string) $serviceError->getMessage();
                $normalizedError = strtolower($rawError);
                $isAcquirerAuthFailure = str_contains($normalizedError, 'authentication failed')
                    || str_contains($normalizedError, 'invalid api')
                    || str_contains($normalizedError, 'unauthorized')
                    || str_contains($normalizedError, 'forbidden');

                if ($isAcquirerAuthFailure) {
                    $adminDetail = $this->buildCheckoutFailureDetailForAdmin(
                        $flowTrace ?? [],
                        $acquirerAccount ?? null,
                        'Acquirer API rejected credentials or request: ' . $rawError
                    );
                    $this->logError('checkout_acquirer_auth_failure', [
                        'merchant_id' => $merchant->id,
                        'payment_link_id' => $paymentLink->id,
                        'gateway' => $gatewayName ?? null,
                        'acquirer_account_id' => $acquirerAccount?->id,
                        'routing_flow_trace' => $flowTrace ?? [],
                        'technical_error' => $rawError,
                    ]);

                    $failedTxn = $this->createFailedCheckoutTransaction(
                        $merchant,
                        $paymentLink,
                        $request,
                        $adminDetail,
                        [
                            'routing_flow_trace' => $flowTrace ?? [],
                            'failure_class' => 'acquirer_auth_failed',
                            'technical_error' => $rawError,
                        ]
                    );

                    if ($failedTxn) {
                        $this->linkLatestCheckoutRoutingMonitorToTransaction($merchant, $failedTxn->txn_id);
                        $this->finalizeCheckoutRoutingMonitor(
                            $failedTxn->txn_id,
                            'failed',
                            'Acquirer authentication or API failure (see transaction details)'
                        );
                    }

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

                    return response()->json([
                        'success' => false,
                        'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                        'error_code' => 'ACQUIRER_AUTH_FAILED',
                        'redirect_url' => rtrim($baseUrl, '/') . '/failure-simple.html?transaction_id=' . ($failedTxn?->txn_id ?? ''),
                    ], 422);
                }

                $this->logError('checkout_payment_processing_error', [
                    'merchant_id' => $merchant->id ?? null,
                    'payment_link_id' => $paymentLink->id ?? null,
                    'error' => $rawError,
                    'gateway' => $gatewayName ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                    'error' => config('app.debug') ? $rawError : null,
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
            
            return response()->json([
                'success' => false,
                'message' => self::CHECKOUT_PUBLIC_FAILURE_MESSAGE,
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Live UPI without third-party PG: create order + pending txn and return upi://pay for the configured receive VPA.
     */
    protected function processNativeUpiCheckout(
        Request $request,
        string $token,
        PaymentLink $paymentLink,
        $merchant,
        float $paymentAmount,
        array $customerDetails,
        array $paymentDetails
    ) {
        $receiveVpa = $this->nativeUpiService->resolveReceiveVpa($merchant);
        $payeeName = $this->nativeUpiService->resolvePayeeName($merchant);

        $order = $this->paymentService->createOrder($merchant, [
            'amount' => $paymentAmount,
            'currency' => $paymentLink->currency,
            'customer_details' => $customerDetails,
            'description' => $paymentLink->title,
            'metadata' => ['payment_link_id' => $paymentLink->id, 'native_upi' => true],
        ]);

        $order->payment_link_id = $paymentLink->id;
        $order->save();

        $baseRateService = app(\App\Services\BaseRateService::class);
        $bank = $merchant->bank ?? null;
        $feeCalculation = $baseRateService->calculateFee(
            $merchant,
            $order->amount,
            'upi',
            $bank,
            \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
            \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC
        );

        $txnId = Transaction::generateTxnId();
        $trRef = $txnId;

        $note = 'Pay '.$paymentLink->title;
        $intentUrl = $this->nativeUpiService->buildPayIntentUrl(
            $receiveVpa,
            $payeeName,
            (float) $paymentAmount,
            $trRef,
            $note
        );

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'merchant_id' => $order->merchant_id,
            'txn_id' => $txnId,
            'amount' => $order->amount,
            'fee_amount' => $feeCalculation['fee_amount'],
            'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
            'net_amount' => $order->amount - ($feeCalculation['total_fee'] ?? 0),
            'currency' => $order->currency,
            'payment_method' => 'upi',
            'status' => 'pending',
            'gateway' => 'native_upi',
            'gateway_txn_id' => $txnId,
            'gateway_response' => [
                'flow' => 'native_upi',
                'payee_vpa' => $receiveVpa,
                'tr' => substr(preg_replace('/[^A-Za-z0-9_-]/', '', $trRef), 0, 35),
                'payment_link_id' => $paymentLink->id,
            ],
            'payment_details' => array_filter([
                'payer_upi_hint' => isset($paymentDetails['upi_id']) ? strtolower(trim((string) $paymentDetails['upi_id'])) : null,
                'payer_app_hint' => $paymentDetails['upi_app'] ?? null,
            ]),
            'test_mode' => $order->test_mode,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $snapshot = app(\App\Services\Rates\MerchantRateSnapshotService::class)->createPaymentSnapshot(
                merchant: $merchant,
                paymentMethod: 'upi',
                amount: (float) $order->amount,
                feeAmount: (float) ($feeCalculation['fee_amount'] ?? 0),
                percentageFee: (float) ($feeCalculation['percentage_fee'] ?? 0),
                flatFee: (float) ($feeCalculation['flat_fee'] ?? 0),
                gstPercentage: (float) ($feeCalculation['gst_percentage'] ?? 18),
                baseRateId: $feeCalculation['rate_id'] ?? null
            );
            $transaction->update([
                'admin_rate_snapshot_id' => $snapshot->id,
                'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
            ]);
        } catch (\Throwable $e) {
            $this->logWarning('Could not snapshot rate for native UPI transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->logInfo('Native UPI checkout prepared (pending settlement)', [
            'order_id' => $order->order_id,
            'txn_id' => $txnId,
            'merchant_id' => $merchant->id,
        ]);

        return response()->json([
            'success' => true,
            'gateway' => 'native_upi',
            'order_id' => $order->order_id,
            'transaction_id' => $transaction->txn_id,
            'amount' => (float) $paymentAmount,
            'currency' => $paymentLink->currency,
            'payee_vpa' => $receiveVpa,
            'payee_name' => $payeeName,
            'upi_intent_url' => $intentUrl,
            'reference' => $transaction->txn_id,
            'customer_details' => $customerDetails,
            'message' => 'Open your UPI app to pay. This transaction stays pending until you confirm receipt in your dashboard or reconciliation flow.',
        ]);
    }

    /**
     * Optional: customer submits UTR after paying via native UPI (for ops / reconciliation).
     */
    public function submitNativeUpiUtr(Request $request, string $token)
    {
        $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
        $merchant = $paymentLink->merchant;

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:64',
            'utr' => ['required', 'string', 'regex:/^[0-9]{12}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Invalid data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::query()
            ->where('txn_id', $request->transaction_id)
            ->where('merchant_id', $merchant->id)
            ->where('payment_method', 'upi')
            ->where('gateway', 'native_upi')
            ->whereHas('order', function ($q) use ($paymentLink) {
                $q->where('payment_link_id', $paymentLink->id);
            })
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found for this payment.',
            ], 404);
        }

        $gr = $transaction->gateway_response ?? [];
        $gr['customer_submitted_utr'] = $request->utr;
        $gr['customer_submitted_utr_at'] = now()->toIso8601String();

        $transaction->update([
            'gateway_response' => $gr,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'UTR recorded. Your payment will be verified shortly.',
        ]);
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
                    try {
                        $snapshot = app(\App\Services\Rates\MerchantRateSnapshotService::class)->createPaymentSnapshot(
                            merchant: $merchant,
                            paymentMethod: 'card',
                            amount: (float) $order->amount,
                            feeAmount: (float) ($feeCalculation['fee_amount'] ?? 0),
                            percentageFee: (float) ($feeCalculation['percentage_fee'] ?? 0),
                            flatFee: (float) ($feeCalculation['flat_fee'] ?? 0),
                            gstPercentage: (float) ($feeCalculation['gst_percentage'] ?? 18),
                            baseRateId: $feeCalculation['rate_id'] ?? null
                        );
                        $transaction->update([
                            'admin_rate_snapshot_id' => $snapshot->id,
                            'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
                        ]);
                    } catch (\Throwable $e) {
                        $this->logWarning('Could not snapshot rate for verified transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
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

                $this->finalizeCheckoutRoutingMonitor($transaction->txn_id, 'success', null);

                return response()->json($result);
            } else {
                $failMessage = $verifyResult['message'] ?? 'Payment verification failed';
                $failedTxn = $this->markPendingRazorpayTransactionFailedOnVerification(
                    $order,
                    $request->razorpay_order_id,
                    $failMessage
                );
                if ($failedTxn) {
                    $this->finalizeCheckoutRoutingMonitor($failedTxn->txn_id, 'failed', $failMessage);
                }

                return response()->json([
                    'success' => false,
                    'message' => $failMessage,
                ], 400);
            }

        } catch (\Exception $e) {
            $this->logError('Razorpay verification error', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $merchantId = null;
            try {
                $pl = PaymentLink::where('link_token', $token)->first();
                $merchantId = $pl?->merchant_id;
            } catch (\Throwable $ignore) {
            }

            if ($merchantId && $request->filled('order_id') && $request->filled('razorpay_order_id')) {
                try {
                    $orderForFail = Order::where('order_id', $request->order_id)
                        ->where('merchant_id', $merchantId)
                        ->first();
                    if ($orderForFail) {
                        $failedTxn = $this->markPendingRazorpayTransactionFailedOnVerification(
                            $orderForFail,
                            (string) $request->razorpay_order_id,
                            'Payment verification error: ' . $e->getMessage()
                        );
                        if ($failedTxn) {
                            $this->finalizeCheckoutRoutingMonitor(
                                $failedTxn->txn_id,
                                'failed',
                                'Verification error: ' . $e->getMessage()
                            );
                        }
                    }
                } catch (\Throwable $ignored) {
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mark Razorpay transaction as failed/cancelled (called from frontend when Razorpay modal fails or is dismissed).
     */
    public function markRazorpayFailed(Request $request, string $token)
    {
        try {
            $paymentLink = PaymentLink::where('link_token', $token)->firstOrFail();
            $merchant = $paymentLink->merchant;

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'nullable|string',
                'razorpay_order_id' => 'nullable|string',
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid failure payload',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $txnId = $request->get('transaction_id');
            $rzpOrderId = $request->get('razorpay_order_id');
            $reason = $request->get('reason') ?: 'Payment cancelled/failed in Razorpay Checkout';

            $query = Transaction::query()
                ->where('merchant_id', $merchant->id);

            if ($txnId) {
                $query->where('txn_id', $txnId);
            } elseif ($rzpOrderId) {
                $query->where(function ($q) use ($rzpOrderId) {
                    $q->where('gateway_txn_id', $rzpOrderId)
                      ->orWhereJsonContains('gateway_response->gateway_order_id', $rzpOrderId)
                      ->orWhereJsonContains('gateway_response->order_id', $rzpOrderId);
                });
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing transaction reference',
                ], 422);
            }

            $transaction = $query->latest()->first();
            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            // Only downgrade if not already success
            if ($transaction->status !== 'success') {
                $transaction->status = 'failed';
                $transaction->failure_reason = $reason;
                $transaction->gateway_response = array_merge($transaction->gateway_response ?? [], [
                    'failure' => [
                        'reason' => $reason,
                        'at' => now()->toIso8601String(),
                    ],
                ]);
                $transaction->save();

                if ($transaction->order) {
                    $transaction->order->update(['status' => 'failed']);
                }

                event(new \App\Events\PaymentFailed($transaction));

                $this->finalizeCheckoutRoutingMonitor($transaction->txn_id, 'failed', $reason);
            }

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->txn_id,
            ]);
        } catch (\Exception $e) {
            $this->logError('Razorpay mark failed error', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment failure',
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
                    try {
                        $snapshot = app(\App\Services\Rates\MerchantRateSnapshotService::class)->createPaymentSnapshot(
                            merchant: $merchant,
                            paymentMethod: 'card',
                            amount: (float) $order->amount,
                            feeAmount: (float) ($feeCalculation['fee_amount'] ?? 0),
                            percentageFee: (float) ($feeCalculation['percentage_fee'] ?? 0),
                            flatFee: (float) ($feeCalculation['flat_fee'] ?? 0),
                            gstPercentage: (float) ($feeCalculation['gst_percentage'] ?? 18),
                            baseRateId: $feeCalculation['rate_id'] ?? null
                        );
                        $transaction->update([
                            'admin_rate_snapshot_id' => $snapshot->id,
                            'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
                        ]);
                    } catch (\Throwable $e) {
                        $this->logWarning('Could not snapshot rate for embedded callback transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

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

    /**
     * Persist checkout routing trace so admin can inspect health-check decisions.
     *
     * @param  array<int, array<string, mixed>>  $flowTrace
     */
    protected function persistCheckoutRoutingMonitor(
        $merchant,
        Request $request,
        array $flowTrace,
        $acquirerAccount,
        string $status,
        ?string $errorMessage = null
    ): void {
        try {
            $cd = $request->input('customer_details', []);
            if (! is_array($cd)) {
                $cd = [];
            }

            PaymentRoutingMonitor::create([
                'merchant_id' => $merchant->id,
                'txn_id' => null,
                'customer_name' => $cd['name'] ?? null,
                'customer_email' => $cd['email'] ?? null,
                'customer_phone' => isset($cd['phone']) ? (string) $cd['phone'] : null,
                'payment_method' => $request->input('payment_method'),
                'final_acquirer_account_id' => $acquirerAccount?->id,
                'final_acquirer_name' => $acquirerAccount?->acquirer_name,
                'status' => $status,
                'flow_trace' => $flowTrace,
                'error_message' => $errorMessage,
                'test_mode' => (bool) $merchant->test_mode,
                'source' => 'payment_link_checkout',
            ]);
        } catch (\Throwable $e) {
            Log::warning('payment_routing_monitor persist failed (checkout)', [
                'merchant_id' => $merchant->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Human-readable multi-line summary for admin (transaction.failure_reason + gateway_response).
     *
     * @param  array<int, array<string, mixed>>  $flowTrace
     * @param  \App\Models\AcquirerAccount|null  $assignedAccount
     */
    protected function buildCheckoutFailureDetailForAdmin(array $flowTrace, $assignedAccount, string $technicalSummary): string
    {
        $lines = [];
        if (! empty($flowTrace)) {
            foreach ($flowTrace as $idx => $step) {
                $name = $step['acquirer'] ?? 'Acquirer';
                $accId = $step['acquirer_account_id'] ?? '?';
                $health = $step['health'] ?? '—';
                $msg = trim((string) ($step['message'] ?? ''));
                $reason = trim((string) ($step['reason'] ?? ''));
                $mode = trim((string) ($step['mode'] ?? ''));
                $line = sprintf(
                    'Route %d: %s #%s | mode=%s | health=%s',
                    $idx + 1,
                    $name,
                    $accId,
                    $mode !== '' ? $mode : '—',
                    $health
                );
                if ($msg !== '') {
                    $line .= ' | ' . $msg;
                }
                if ($reason !== '') {
                    $line .= ' | reason=' . $reason;
                }
                $lines[] = $line;
            }
        } elseif ($assignedAccount) {
            $lines[] = sprintf(
                'Routing: assigned acquirer #%s (%s), mode=%s',
                $assignedAccount->id,
                $assignedAccount->acquirer_name ?? '',
                $assignedAccount->mode ?? ''
            );
        }
        $lines[] = 'Final: ' . $technicalSummary;

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>|null  $diagnostics  Merged into gateway_response for admin review only
     */
    protected function createFailedCheckoutTransaction($merchant, PaymentLink $paymentLink, Request $request, string $reason, ?array $diagnostics = null): ?Transaction
    {
        try {
            $amount = (float) $paymentLink->amount;
            if ($paymentLink->allow_partial_payment && $request->filled('amount') && (float) $request->amount > 0) {
                $amount = (float) $request->amount;
            }

            $order = $this->paymentService->createOrder($merchant, [
                'amount' => $amount,
                'currency' => $paymentLink->currency,
                'customer_details' => $request->input('customer_details', []),
                'description' => $paymentLink->title . ' (failed attempt)',
                'metadata' => ['payment_link_id' => $paymentLink->id, 'failed_attempt' => true],
            ]);

            $baseRateService = app(\App\Services\BaseRateService::class);
            $bank = $merchant->bank ?? null;
            $method = (string) ($request->input('payment_method') ?: 'card');
            $feeCalculation = $baseRateService->calculateFee(
                $merchant,
                $amount,
                $method,
                $bank,
                \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
                \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC
            );

            $gatewayPayload = array_merge(
                ['error' => $reason],
                is_array($diagnostics) ? $diagnostics : []
            );

            $transaction = Transaction::create([
                'order_id' => $order->id,
                'merchant_id' => $merchant->id,
                'txn_id' => Transaction::generateTxnId(),
                'amount' => $amount,
                'fee_amount' => $feeCalculation['fee_amount'] ?? 0,
                'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
                'net_amount' => $amount - ($feeCalculation['total_fee'] ?? 0),
                'currency' => $paymentLink->currency,
                'payment_method' => $method,
                'status' => 'failed',
                'failure_reason' => $reason,
                'gateway_response' => $gatewayPayload,
                'test_mode' => (bool) $paymentLink->test_mode,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $order->update(['status' => 'failed']);

            return $transaction;
        } catch (\Throwable $e) {
            Log::warning('failed to create failed checkout transaction', [
                'merchant_id' => $merchant->id ?? null,
                'payment_link_id' => $paymentLink->id ?? null,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Attach the latest payment-link checkout routing row (same POST) to this transaction id.
     */
    protected function linkLatestCheckoutRoutingMonitorToTransaction($merchant, string $txnId): void
    {
        try {
            $monitor = PaymentRoutingMonitor::query()
                ->where('merchant_id', $merchant->id)
                ->where('source', 'payment_link_checkout')
                ->whereNull('txn_id')
                ->where('created_at', '>=', now()->subMinutes(45))
                ->orderByDesc('id')
                ->first();

            if ($monitor) {
                $monitor->update(['txn_id' => $txnId]);
            }
        } catch (\Throwable $e) {
            Log::warning('linkLatestCheckoutRoutingMonitorToTransaction failed', [
                'merchant_id' => $merchant->id ?? null,
                'txn_id' => $txnId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update routing monitor row for this checkout once payment outcome is known (success / failed).
     * Note: flow_trace "health: skipped" is routing-time only; this status is the payment outcome.
     */
    protected function finalizeCheckoutRoutingMonitor(string $txnId, string $status, ?string $errorMessage = null): void
    {
        try {
            PaymentRoutingMonitor::query()
                ->where('txn_id', $txnId)
                ->update([
                    'status' => $status,
                    'error_message' => $errorMessage,
                ]);
        } catch (\Throwable $e) {
            Log::warning('finalizeCheckoutRoutingMonitor failed', [
                'txn_id' => $txnId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * When Razorpay signature/API verification fails, mark the pending checkout transaction failed
     * so it does not stay stuck in "pending".
     */
    protected function markPendingRazorpayTransactionFailedOnVerification(Order $order, string $razorpayOrderId, string $message): ?Transaction
    {
        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('merchant_id', $order->merchant_id)
            ->where('status', 'pending')
            ->where(function ($q) use ($razorpayOrderId) {
                $q->where('gateway_txn_id', $razorpayOrderId)
                    ->orWhereJsonContains('gateway_response->gateway_order_id', $razorpayOrderId)
                    ->orWhereJsonContains('gateway_response->order_id', $razorpayOrderId);
            })
            ->orderByDesc('id')
            ->first();

        if (!$transaction) {
            return null;
        }

        $transaction->status = 'failed';
        $transaction->failure_reason = $message;
        $transaction->gateway_response = array_merge($transaction->gateway_response ?? [], [
            'verification_failed' => [
                'message' => $message,
                'at' => now()->toIso8601String(),
            ],
        ]);
        $transaction->save();

        if ($transaction->order) {
            $transaction->order->update(['status' => 'failed']);
        }

        event(new \App\Events\PaymentFailed($transaction));

        return $transaction;
    }
}

 