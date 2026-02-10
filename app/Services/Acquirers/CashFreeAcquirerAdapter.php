<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * CashFree Acquirer Adapter
 * 
 * Implements CashFree payment gateway integration.
 * This adapter handles all CashFree-specific operations and normalizes them
 * to the gateway-level interface.
 */
class CashFreeAcquirerAdapter implements AcquirerInterface
{
    protected ?AcquirerAccount $acquirerAccount = null;
    protected bool $isTestMode = true;
    protected string $baseUrl = '';
    protected string $appId = '';
    protected string $secretKey = '';

    /**
     * Initialize the adapter with an AcquirerAccount.
     */
    public function initialize(AcquirerAccount $acquirerAccount): self
    {
        $this->acquirerAccount = $acquirerAccount;
        $this->isTestMode = strtoupper($acquirerAccount->mode) === 'TEST';

        // CashFree credentials mapping:
        // - App ID: account_id OR additional_key_1 (fallback)
        // - Secret Key: secret_key
        // Note: CashFree App ID is typically a long alphanumeric string (e.g., TEST10951662c183ab5792bbaa968fb426615901)
        // If account_id contains "cashfree" or looks like a label, use additional_key_1 instead
        $accountId = $acquirerAccount->account_id ?? '';
        if (empty($accountId) || stripos($accountId, 'cashfree') !== false || strlen($accountId) < 20) {
            // If account_id looks like a label or is too short, try additional_key_1
            $this->appId = $acquirerAccount->additional_key_1 ?? $accountId;
        } else {
            $this->appId = $accountId;
        }
        $this->secretKey = $acquirerAccount->secret_key ?? '';

        // Set base URL based on mode
        // CashFree test URL: https://sandbox.cashfree.com/pg
        // CashFree live URL: https://api.cashfree.com/pg
        if ($this->isTestMode) {
            // Ensure test URL is always https://sandbox.cashfree.com/pg
            $testUrl = $acquirerAccount->test_request_url ?? 'https://sandbox.cashfree.com/pg';
            // Normalize URL - remove trailing slash if present
            $this->baseUrl = rtrim($testUrl, '/');
            // Ensure it ends with /pg (compatible with PHP < 8.0)
            if (substr($this->baseUrl, -3) !== '/pg') {
                $this->baseUrl = rtrim($this->baseUrl, '/') . '/pg';
            }
        } else {
            // Ensure live URL is always https://api.cashfree.com/pg
            $liveUrl = $acquirerAccount->live_request_url ?? 'https://api.cashfree.com/pg';
            // Normalize URL - remove trailing slash if present
            $this->baseUrl = rtrim($liveUrl, '/');
            // Ensure it ends with /pg (compatible with PHP < 8.0)
            if (substr($this->baseUrl, -3) !== '/pg') {
                $this->baseUrl = rtrim($this->baseUrl, '/') . '/pg';
            }
        }

        if (!$this->appId || !$this->secretKey) {
            Log::error('CashFree credentials missing', [
                'acquirer_account_id' => $acquirerAccount->id,
                'acquirer_name' => $acquirerAccount->acquirer_name,
                'has_account_id' => !empty($acquirerAccount->account_id),
                'has_additional_key_1' => !empty($acquirerAccount->additional_key_1),
                'has_secret_key' => !empty($acquirerAccount->secret_key),
                'app_id_value' => $this->appId ? 'SET' : 'EMPTY',
            ]);
            throw new \RuntimeException('CashFree credentials not configured in AcquirerAccount. Please ensure App ID is in Account Id (or Additional Key 1) and Secret Key is in Secret Key field.');
        }

        Log::debug('CashFree adapter initialized', [
            'acquirer_account_id' => $acquirerAccount->id,
            'mode' => $acquirerAccount->mode,
            'base_url' => $this->baseUrl,
        ]);

        return $this;
    }

    /**
     * Create a CashFree order.
     */
    public function createOrder(array $orderData): array
    {
        try {
            $orderId = $orderData['order_id'] ?? 'order_' . uniqid();
            $amount = (float) ($orderData['amount'] ?? 0);
            $currency = $orderData['currency'] ?? 'INR';

            // CashFree PG v2023-08-01: order_amount is in rupees (decimal), e.g. 110.00 for INR 110
            $orderAmountRupees = round($amount, 2);

            $requestData = [
                'order_id' => $orderId,
                'order_amount' => $orderAmountRupees,
                'order_currency' => $currency,
                'order_note' => $orderData['description'] ?? 'Payment',
            ];

            // Add return URLs for proper modal behavior (required for _modal redirectTarget)
            if (isset($orderData['return_url'])) {
                $requestData['return_url'] = $orderData['return_url'];
            }
            if (isset($orderData['notify_url'])) {
                $requestData['notify_url'] = $orderData['notify_url'];
            }

            // Add customer details if provided
            if (isset($orderData['customer_details'])) {
                $customer = $orderData['customer_details'];
                $requestData['customer_details'] = [
                    'customer_id' => $customer['id'] ?? uniqid('cust_'),
                    'customer_name' => $customer['name'] ?? '',
                    'customer_email' => $customer['email'] ?? '',
                    'customer_phone' => $customer['phone'] ?? '',
                ];
            }

            // Make API call to CashFree with timeout and SSL configuration
            // Use API version 2023-08-01 as per CashFree PG v3 documentation
            $response = Http::withOptions([
                'timeout' => 30, // 30 seconds timeout
                'verify' => true, // Verify SSL certificate
                'connect_timeout' => 10, // 10 seconds connection timeout
            ])->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => '2023-08-01',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/orders', $requestData);

            $responseData = $response->json();
            // Support wrapped response (e.g. array of orders or data key)
            if (isset($responseData[0]) && is_array($responseData[0])) {
                $responseData = $responseData[0];
            }
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $responseData = array_merge($responseData, $responseData['data']);
            }

            // CashFree returns:
            // - order_id: the ID WE sent in the create request (our own order reference)
            // - cf_order_id: CashFree's internal numeric order reference
            //
            // IMPORTANT:
            // The /orders/{order_id} and /orders/{order_id}/payments APIs expect the ORIGINAL
            // order_id that we sent (e.g. "ORD_ABC123..."), NOT the numeric cf_order_id.
            //
            // So we:
            // - keep $orderId (our original order_id) as the identifier to use for all future
            //   status/verification calls and store it as gateway_order_id
            // - expose cf_order_id only as additional metadata (for logging/debugging)
            $cfOrderId = $responseData['cf_order_id'] ?? null;
            $paymentSessionId = $responseData['payment_session_id'] ?? $responseData['payment_sessions_id'] ?? null;
            $paymentSessionId = is_string($paymentSessionId) ? trim($paymentSessionId) : null;
            if ($paymentSessionId === '') {
                $paymentSessionId = null;
            }

            if (!$response->successful() || !$cfOrderId) {
                Log::error('CashFree order creation failed', [
                    'response' => $responseData,
                    'status' => $response->status(),
                    'acquirer_account_id' => $this->acquirerAccount->id,
                ]);

                return [
                    'success' => false,
                    'error_code' => 'CASHFREE_ORDER_ERROR',
                    'message' => $responseData['message'] ?? 'Failed to create CashFree order',
                ];
            }

            if (empty($paymentSessionId)) {
                Log::error('CashFree order created but payment_session_id missing', [
                    'response' => $responseData,
                    'acquirer_account_id' => $this->acquirerAccount->id,
                ]);
                return [
                    'success' => false,
                    'error_code' => 'CASHFREE_SESSION_MISSING',
                    'message' => 'CashFree did not return a payment session. Please try again.',
                ];
            }

            Log::info('CashFree order created successfully', [
                'our_order_id' => $orderId,
                'cf_order_id' => $cfOrderId,
                'cf_order_id_type' => gettype($cfOrderId),
                'cf_order_id_length' => is_string($cfOrderId) ? strlen($cfOrderId) : null,
                'acquirer_account_id' => $this->acquirerAccount->id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_session_id_present' => true,
                'full_response' => $responseData, // Log full response for debugging
            ]);

            return [
                'success' => true,
                // Our original order_id (e.g. "ORD_ABC123...") – this is what CashFree
                // expects in /orders/{order_id} & /orders/{order_id}/payments
                'order_id' => $orderId,
                'gateway_order_id' => $orderId,
                // Expose CashFree's internal cf_order_id only as metadata
                'cf_order_id' => $cfOrderId,
                'amount' => (float) ($responseData['order_amount'] ?? $orderAmountRupees),
                'currency' => $responseData['order_currency'] ?? $currency,
                'status' => 'created',
                'payment_session_id' => $paymentSessionId,
                'raw_response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('CashFree order creation failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
                'order_data' => $this->sanitizeLogData($orderData),
            ]);

            return [
                'success' => false,
                'error_code' => 'CASHFREE_ORDER_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * REMOVED: createPaymentSession()
     * 
     * CashFree does NOT have a separate payment session endpoint.
     * The payment_session_id is returned directly from order creation.
     * This method was calling a non-existent API endpoint.
     * 
     * The payment_session_id comes from the order creation response.
     */

    /**
     * REMOVED: initiatePayment()
     * 
     * CashFree does NOT support server-side payment initiation.
     * Payments MUST be initiated through the frontend checkout SDK.
     * This method was incorrectly trying to POST to /orders/{id}/payments
     * which does not support card/UPI initiation.
     * 
     * For CashFree, the flow is:
     * 1. Create order (returns payment_session_id)
     * 2. Frontend calls Cashfree.checkout() with payment_session_id
     * 3. User completes payment in CashFree checkout
     * 4. Webhook/status API updates payment status
     */
    public function initiatePayment(array $paymentData): array
    {
        // CashFree does NOT support server-side payment initiation
        // This method should NEVER be called for CashFree
        // Payments are initiated via frontend checkout SDK only
        
        Log::error('CashFree initiatePayment() called - this should not happen', [
            'payment_data_keys' => array_keys($paymentData),
            'acquirer_account_id' => $this->acquirerAccount->id,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);
        
        return [
            'success' => false,
            'error_code' => 'CASHFREE_NO_SERVER_INITIATION',
            'message' => 'CashFree does not support server-side payment initiation. Use frontend checkout SDK.',
        ];
    }
    public function verifyPayment(array $paymentData, string $signature): array
    {
        try {
            // CashFree signature verification
            $orderId = $paymentData['orderId'] ?? $paymentData['order_id'] ?? null;
            $orderAmount = $paymentData['orderAmount'] ?? $paymentData['order_amount'] ?? null;
            $referenceId = $paymentData['referenceId'] ?? $paymentData['reference_id'] ?? null;
            $txStatus = $paymentData['txStatus'] ?? $paymentData['tx_status'] ?? null;
            $paymentMode = $paymentData['paymentMode'] ?? $paymentData['payment_mode'] ?? null;
            $txMsg = $paymentData['txMsg'] ?? $paymentData['tx_msg'] ?? null;
            $txTime = $paymentData['txTime'] ?? $paymentData['tx_time'] ?? null;

            // Verify signature
            $dataToVerify = $orderId . $orderAmount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
            $calculatedSignature = hash_hmac('sha256', $dataToVerify, $this->secretKey);

            $isValid = hash_equals($calculatedSignature, $signature);

            if (!$isValid) {
                Log::warning('CashFree signature verification failed', [
                    'acquirer_account_id' => $this->acquirerAccount->id,
                    'order_id' => $orderId,
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Signature verification failed',
                ];
            }

            return [
                'success' => true,
                'verified' => true,
                'payment_id' => $referenceId,
                'gateway_payment_id' => $referenceId,
                'gateway_txn_id' => $referenceId,
                'status' => $this->normalizeStatus($txStatus),
                'order_id' => $orderId,
                'amount' => $orderAmount ? $this->convertFromPaise($orderAmount) : null,
                'payment_method' => $paymentMode,
                'message' => $txMsg,
                'transaction_time' => $txTime,
            ];

        } catch (\Exception $e) {
            Log::error('CashFree payment verification failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'verified' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a refund.
     */
    public function processRefund(string $paymentId, float $amount, array $options = []): array
    {
        try {
            $refundAmount = $this->convertToPaise($amount);
            $refundId = $options['refund_id'] ?? 'refund_' . uniqid();
            $refundNote = $options['refund_note'] ?? 'Refund';

            $requestData = [
                'refund_id' => $refundId,
                'refund_amount' => $refundAmount,
                'refund_note' => $refundNote,
            ];

            $response = Http::withOptions([
                'timeout' => 30, // 30 seconds timeout
                'verify' => true, // Verify SSL certificate
                'connect_timeout' => 10, // 10 seconds connection timeout
            ])->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => '2022-09-01',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/orders/' . $paymentId . '/refunds', $requestData);

            $responseData = $response->json();

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error_code' => 'CASHFREE_REFUND_ERROR',
                    'message' => $responseData['message'] ?? 'Refund failed',
                ];
            }

            return [
                'success' => true,
                'refund_id' => $responseData['cf_refund_id'] ?? $refundId,
                'gateway_refund_id' => $responseData['cf_refund_id'] ?? $refundId,
                'status' => $this->normalizeStatus($responseData['refund_status'] ?? 'pending'),
                'amount' => $this->convertFromPaise($responseData['refund_amount'] ?? $refundAmount),
            ];

        } catch (\Exception $e) {
            Log::error('CashFree refund failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => false,
                'error_code' => 'CASHFREE_REFUND_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            // CashFree: Get order status which includes payment information
            // The paymentId can be either order_id or payment_id
            // Use /orders/{order_id} endpoint to get order details with payment status
            $endpoint = $this->baseUrl . '/orders/' . $paymentId;
            
            Log::info('CashFree getPaymentStatus: Querying order', [
                'payment_id' => $paymentId,
                'payment_id_type' => gettype($paymentId),
                'payment_id_length' => is_string($paymentId) ? strlen($paymentId) : null,
                'endpoint' => $endpoint,
                'acquirer_account_id' => $this->acquirerAccount->id,
                'base_url' => $this->baseUrl,
            ]);
            
            $response = Http::withOptions([
                'timeout' => 30, // 30 seconds timeout
                'verify' => true, // Verify SSL certificate
                'connect_timeout' => 10, // 10 seconds connection timeout
            ])->withHeaders([
                'x-client-id' => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version' => '2023-08-01',
            ])->get($endpoint);

            $responseData = $response->json() ?? [];

            // Handle null or invalid response
            if ($responseData === null || !is_array($responseData)) {
                Log::warning('CashFree getPaymentStatus returned invalid response', [
                    'payment_id' => $paymentId,
                    'response_type' => gettype($responseData),
                    'http_status' => $response->status(),
                    'acquirer_account_id' => $this->acquirerAccount->id,
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid response from CashFree API',
                ];
            }

            if (!$response->successful()) {
                // If order endpoint returns 404, try the payments endpoint as fallback
                // Sometimes orders exist but the order endpoint might not be immediately available
                if ($response->status() === 404) {
                    Log::info('CashFree getPaymentStatus: Order endpoint returned 404, trying payments endpoint', [
                        'payment_id' => $paymentId,
                        'acquirer_account_id' => $this->acquirerAccount->id,
                    ]);
                    
                    $paymentsEndpoint = $this->baseUrl . '/orders/' . $paymentId . '/payments';
                    $paymentsResponse = Http::withOptions([
                        'timeout' => 30,
                        'verify' => true,
                        'connect_timeout' => 10,
                    ])->withHeaders([
                        'x-client-id' => $this->appId,
                        'x-client-secret' => $this->secretKey,
                        'x-api-version' => '2023-08-01',
                    ])->get($paymentsEndpoint);
                    
                    $paymentsData = $paymentsResponse->json() ?? [];
                    
                    Log::info('CashFree getPaymentStatus: Payments endpoint response', [
                        'payment_id' => $paymentId,
                        'http_status' => $paymentsResponse->status(),
                        'response_data' => $paymentsData,
                        'is_array' => is_array($paymentsData),
                        'count' => is_array($paymentsData) ? count($paymentsData) : 0,
                        'acquirer_account_id' => $this->acquirerAccount->id,
                    ]);
                    
                    // If payments endpoint succeeds, extract payment status from first payment
                    if ($paymentsResponse->successful() && is_array($paymentsData) && count($paymentsData) > 0) {
                        $firstPayment = $paymentsData[0] ?? null;
                        if ($firstPayment && is_array($firstPayment)) {
                            $paymentStatus = $firstPayment['payment_status'] 
                                ?? $firstPayment['txStatus'] 
                                ?? $firstPayment['tx_status']
                                ?? $firstPayment['status']
                                ?? 'unknown';
                            
                            $paymentIdFromResponse = $firstPayment['cf_payment_id'] 
                                ?? $firstPayment['payment_id'] 
                                ?? $firstPayment['id']
                                ?? $paymentId;
                            
                            Log::info('CashFree getPaymentStatus: Successfully retrieved from payments endpoint', [
                                'payment_id' => $paymentId,
                                'payment_status' => $paymentStatus,
                                'normalized_status' => $this->normalizeStatus($paymentStatus),
                            ]);
                            
                            return [
                                'success' => true,
                                'payment_id' => $paymentIdFromResponse,
                                'status' => $this->normalizeStatus($paymentStatus),
                                'amount' => isset($firstPayment['payment_amount']) ? $this->convertFromPaise($firstPayment['payment_amount']) : null,
                                'currency' => $firstPayment['payment_currency'] ?? null,
                                'raw_response' => $firstPayment,
                            ];
                        }
                    }
                }
                
                $errorMessage = $responseData['message'] ?? 
                               $responseData['error'] ?? 
                               $responseData['error_description'] ?? 
                               'Failed to get payment status';
                
                Log::warning('CashFree getPaymentStatus failed', [
                    'payment_id' => $paymentId,
                    'http_status' => $response->status(),
                    'response' => $responseData,
                    'error_message' => $errorMessage,
                    'acquirer_account_id' => $this->acquirerAccount->id,
                    'base_url' => $this->baseUrl,
                ]);
                
                // Determine error code based on HTTP status
                $errorCode = null;
                if ($response->status() === 404) {
                    $errorCode = 'ORDER_NOT_FOUND';
                } elseif ($response->status() === 401 || $response->status() === 403) {
                    $errorCode = 'AUTHENTICATION_FAILED';
                } elseif ($response->status() >= 500) {
                    $errorCode = 'CASHFREE_SERVER_ERROR';
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => $errorCode,
                    'http_status' => $response->status(),
                ];
            }

            // CashFree order status response contains payment information
            // Check for payment_status in the order response
            $paymentStatus = $responseData['payment_status'] 
                ?? $responseData['order_status']
                ?? $responseData['orderStatus']
                ?? $responseData['txStatus']
                ?? $responseData['tx_status']
                ?? 'unknown';
            
            $paymentIdFromResponse = $responseData['cf_payment_id'] 
                ?? $responseData['payment_id'] 
                ?? $responseData['reference_id']
                ?? $responseData['id']
                ?? null;
            
            // If order has payments array, get status from first payment
            // Check if payments array exists and is not empty to avoid "Undefined array key 0" error
            if (isset($responseData['payments']) && is_array($responseData['payments']) && count($responseData['payments']) > 0) {
                // Double-check that index 0 exists before accessing (defensive programming)
                if (isset($responseData['payments'][0])) {
                    $firstPayment = $responseData['payments'][0];
                    if (is_array($firstPayment)) {
                        $paymentStatus = $firstPayment['payment_status'] 
                            ?? $firstPayment['txStatus'] 
                            ?? $firstPayment['tx_status']
                            ?? $firstPayment['status']
                            ?? $paymentStatus;
                        $paymentIdFromResponse = $firstPayment['cf_payment_id'] 
                            ?? $firstPayment['payment_id'] 
                            ?? $firstPayment['id']
                            ?? $paymentIdFromResponse;
                    }
                }
            }
            
            // If responseData itself is an array of payments (GET /orders/{order_id}/payments returns array)
            // This happens when we call GET payments endpoint directly
            if (is_array($responseData) && count($responseData) > 0 && !isset($responseData['order_id']) && !isset($responseData['order_status'])) {
                // This is likely a direct array of payments from GET /orders/{order_id}/payments
                // Make sure index 0 exists before accessing (defensive programming)
                if (isset($responseData[0]) && is_array($responseData[0])) {
                    $firstPayment = $responseData[0];
                    $paymentStatus = $firstPayment['payment_status'] 
                        ?? $firstPayment['txStatus'] 
                        ?? $firstPayment['tx_status']
                        ?? $firstPayment['status']
                        ?? $firstPayment['payment_status']
                        ?? $paymentStatus;
                    $paymentIdFromResponse = $firstPayment['cf_payment_id'] 
                        ?? $firstPayment['payment_id'] 
                        ?? $firstPayment['id']
                        ?? $paymentIdFromResponse;
                }
            }
            
            // Also check if order_status indicates payment success
            // CashFree orders can have order_status = 'PAID' when payment is successful
            if (isset($responseData['order_status']) && strtoupper($responseData['order_status']) === 'PAID') {
                $paymentStatus = 'SUCCESS';
            }

            Log::debug('CashFree payment status retrieved', [
                'payment_id' => $paymentId,
                'payment_id_from_response' => $paymentIdFromResponse,
                'status' => $paymentStatus,
                'normalized_status' => $this->normalizeStatus($paymentStatus),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => true,
                'payment_id' => $paymentIdFromResponse ?? $paymentId,
                'status' => $this->normalizeStatus($paymentStatus),
                'amount' => isset($responseData['order_amount']) ? $this->convertFromPaise($responseData['order_amount']) : null,
                'currency' => $responseData['order_currency'] ?? null,
                'raw_response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('CashFree getPaymentStatus exception', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order status.
     */
    public function getOrderStatus(string $orderId): array
    {
        return $this->getPaymentStatus($orderId);
    }

    /**
     * Verify webhook/callback signature.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        try {
            $orderId = $payload['orderId'] ?? $payload['order_id'] ?? '';
            $orderAmount = $payload['orderAmount'] ?? $payload['order_amount'] ?? '';
            $referenceId = $payload['referenceId'] ?? $payload['reference_id'] ?? '';
            $txStatus = $payload['txStatus'] ?? $payload['tx_status'] ?? '';
            $paymentMode = $payload['paymentMode'] ?? $payload['payment_mode'] ?? '';
            $txMsg = $payload['txMsg'] ?? $payload['tx_msg'] ?? '';
            $txTime = $payload['txTime'] ?? $payload['tx_time'] ?? '';

            $dataToVerify = $orderId . $orderAmount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
            $calculatedSignature = hash_hmac('sha256', $dataToVerify, $this->secretKey);

            return hash_equals($calculatedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('CashFree webhook signature verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Normalize provider-specific event to gateway-level event type.
     */
    public function normalizeEventType(array $payload): string
    {
        $txStatus = $payload['txStatus'] ?? $payload['tx_status'] ?? '';

        if (in_array(strtolower($txStatus), ['success', 'completed'])) {
            return 'payment.success';
        } elseif (in_array(strtolower($txStatus), ['failed', 'error'])) {
            return 'payment.failed';
        }

        return 'payment.pending';
    }

    /**
     * Normalize provider-specific status to gateway-level status.
     * 
     * CashFree PG v3 status mapping:
     * - ACTIVE -> pending (order created, awaiting payment)
     * - PAID -> success (payment completed)
     * - EXPIRED -> failed (order expired)
     * - CANCELLED -> failed (order cancelled)
     */
    public function normalizeStatus(string $providerStatus): string
    {
        $status = strtoupper(trim($providerStatus)); // CashFree uses uppercase

        $statusMap = [
            // Success states
            'PAID' => 'success',
            'SUCCESS' => 'success',
            'COMPLETED' => 'success',
            'CAPTURED' => 'success',
            'SUCCESSFUL' => 'success',
            
            // Failed states
            'EXPIRED' => 'failed',
            'CANCELLED' => 'failed',
            'CANCELED' => 'failed',
            'FAILED' => 'failed',
            'ERROR' => 'failed',
            'REJECTED' => 'failed',
            'VOID' => 'failed',
            
            // Pending states
            'ACTIVE' => 'pending',  // Order created, awaiting payment
            'PENDING' => 'pending',
            'PROCESSING' => 'pending',
            'INITIATED' => 'pending',
            'AWAITING' => 'pending',
            'INCOMPLETE' => 'pending',
            'USER_DROPPED' => 'pending',
            'FLAGGED' => 'pending',  // Payment held by risk system
        ];

        return $statusMap[$status] ?? 'pending';
    }

    /**
     * Extract reference IDs from webhook payload.
     */
    public function extractReferenceIds(array $payload): array
    {
        return [
            'payment_id' => $payload['referenceId'] ?? $payload['reference_id'] ?? null,
            'order_id' => $payload['orderId'] ?? $payload['order_id'] ?? null,
            'refund_id' => $payload['refundId'] ?? $payload['refund_id'] ?? null,
        ];
    }

    /**
     * Get provider name.
     */
    public function getProviderName(): string
    {
        return 'cashfree';
    }

    /**
     * Create payment link (if supported by provider).
     */
    public function createPaymentLink(array $linkData): array
    {
        // CashFree doesn't have a direct payment link API
        // Payment links are handled by our system
        return [
            'success' => false,
            'message' => 'Payment links are handled by BadliCash system',
        ];
    }

    /**
     * Get settlement details (if supported by provider).
     */
    public function getSettlements(array $filters = []): array
    {
        // Implement if CashFree provides settlement API
        return [
            'success' => false,
            'message' => 'Settlement API not implemented',
        ];
    }

    /**
     * Map payment method to CashFree format.
     */
    protected function mapPaymentMethod(string $method): string
    {
        $methodMap = [
            'card' => 'card',
            'upi' => 'upi',
            'netbanking' => 'netbanking',
            'wallet' => 'wallet',
        ];

        return $methodMap[strtolower($method)] ?? 'card';
    }

    /**
     * Convert amount to paise (smallest currency unit).
     */
    protected function convertToPaise(float $amount): int
    {
        return (int)round($amount * 100);
    }

    /**
     * Convert from paise to rupees.
     */
    protected function convertFromPaise(int $paise): float
    {
        return round($paise / 100, 2);
    }

    /**
     * Sanitize log data (remove sensitive information).
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['card_number', 'cvv', 'secret_key', 'password'];
        $sanitized = $data;

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***REDACTED***';
            }
        }

        return $sanitized;
    }
}

