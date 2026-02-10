<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Acquirer Adapter
 * 
 * Implements Razorpay payment gateway integration using official Razorpay PHP SDK.
 * This adapter handles all Razorpay-specific operations and normalizes them
 * to the gateway-level interface.
 */
class RazorpayAcquirerAdapter implements AcquirerInterface
{
    protected ?Api $razorpay = null;
    protected ?AcquirerAccount $acquirerAccount = null;
    protected bool $isTestMode = true;

    /**
     * Initialize the adapter with an AcquirerAccount.
     */
    public function initialize(AcquirerAccount $acquirerAccount): self
    {
        $this->acquirerAccount = $acquirerAccount;
        $this->isTestMode = strtoupper($acquirerAccount->mode) === 'TEST';

        // Extract Razorpay credentials from AcquirerAccount
        // For Razorpay: 
        // - Key ID: additional_key_1 OR secret_key
        // - Key Secret: additional_key_2 OR secret_key OR salt
        $keyId = $acquirerAccount->additional_key_1 ?? $acquirerAccount->secret_key;
        $keySecret = $acquirerAccount->additional_key_2 ?? $acquirerAccount->secret_key ?? $acquirerAccount->salt;

        // Log credential extraction for debugging
        Log::debug('Razorpay credential extraction', [
            'acquirer_account_id' => $acquirerAccount->id,
            'additional_key_1' => $acquirerAccount->additional_key_1 ? 'SET' : 'EMPTY',
            'secret_key' => $acquirerAccount->secret_key ? 'SET' : 'EMPTY',
            'additional_key_2' => $acquirerAccount->additional_key_2 ? 'SET' : 'EMPTY',
            'salt' => $acquirerAccount->salt ? 'SET' : 'EMPTY',
            'keyId_found' => !empty($keyId),
            'keySecret_found' => !empty($keySecret),
        ]);

        if (!$keyId || !$keySecret) {
            Log::error('Razorpay credentials missing', [
                'acquirer_account_id' => $acquirerAccount->id,
                'acquirer_name' => $acquirerAccount->acquirer_name,
                'has_additional_key_1' => !empty($acquirerAccount->additional_key_1),
                'has_secret_key' => !empty($acquirerAccount->secret_key),
                'has_additional_key_2' => !empty($acquirerAccount->additional_key_2),
                'has_salt' => !empty($acquirerAccount->salt),
            ]);
            throw new \RuntimeException('Razorpay credentials not configured in AcquirerAccount. Please ensure Key ID is in Additional Key 1 and Secret Key is in Secret Key field.');
        }

        // Initialize Razorpay API client
        $this->razorpay = new Api($keyId, $keySecret);

        return $this;
    }

    /**
     * Create a Razorpay order.
     */
    public function createOrder(array $orderData): array
    {
        try {
            $razorpayOrderData = [
                'receipt' => $orderData['order_id'] ?? 'order_' . uniqid(),
                'amount' => $this->convertToPaise($orderData['amount']), // Razorpay uses paise
                'currency' => $orderData['currency'] ?? 'INR',
                'payment_capture' => $orderData['auto_capture'] ?? 1, // Auto capture by default
            ];

            // Add customer details as notes
            if (isset($orderData['customer_details'])) {
                $customer = $orderData['customer_details'];
                $razorpayOrderData['notes'] = [
                    'customer_name' => $customer['name'] ?? null,
                    'customer_email' => $customer['email'] ?? null,
                    'customer_phone' => $customer['phone'] ?? null,
                    'merchant_order_id' => $orderData['order_id'] ?? null,
                ];
            }

            // Add metadata if provided
            if (isset($orderData['metadata'])) {
                $razorpayOrderData['notes'] = array_merge(
                    $razorpayOrderData['notes'] ?? [],
                    $orderData['metadata']
                );
            }

            $razorpayOrder = $this->razorpay->order->create($razorpayOrderData);

            Log::info('Razorpay order created successfully', [
                'razorpay_order_id' => $razorpayOrder['id'],
                'acquirer_account_id' => $this->acquirerAccount->id,
                'amount' => $orderData['amount'],
                'currency' => $razorpayOrder['currency'],
                'status' => $razorpayOrder['status'],
            ]);

            return [
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'gateway_order_id' => $razorpayOrder['id'],
                'amount' => $this->convertFromPaise($razorpayOrder['amount']),
                'currency' => $razorpayOrder['currency'],
                'status' => $this->normalizeStatus($razorpayOrder['status']),
                'receipt' => $razorpayOrder['receipt'],
                'created_at' => $razorpayOrder['created_at'],
                'raw_response' => $razorpayOrder,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
                'order_data' => $this->sanitizeLogData($orderData),
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_ORDER_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a payment (for card/UPI/netbanking).
     */
    public function initiatePayment(array $paymentData): array
    {
        try {
            // For Razorpay, payment is typically initiated on the frontend
            // This method can be used for server-side payment initiation if needed
            // Most Razorpay flows use frontend Checkout.js or Payment Button

            $orderId = $paymentData['order_id'] ?? $paymentData['gateway_order_id'];

            if (!$orderId) {
                throw new \InvalidArgumentException('Order ID is required');
            }

            // Fetch order to get details
            $order = $this->razorpay->order->fetch($orderId);

            // For server-side card payments, Razorpay doesn't support direct card payment creation
            // due to PCI-DSS compliance. We need to use Razorpay's frontend Checkout.js.
            // However, for testing purposes, we can simulate the payment flow.
            // In production, this should redirect to Razorpay Checkout or use their frontend SDK.
            
            if (isset($paymentData['payment_method']) && $paymentData['payment_method'] === 'card') {
                // Note: Razorpay doesn't allow server-side card payment creation for PCI-DSS compliance
                // For testing, we'll simulate the payment and return success
                // In production, you should use Razorpay Checkout.js on the frontend
                
                Log::warning('Server-side card payment attempted - Razorpay requires frontend Checkout.js', [
                    'order_id' => $orderId,
                    'note' => 'For production, use Razorpay Checkout.js on frontend. Simulating for test mode.',
                ]);
                
                // Razorpay doesn't support server-side card payment creation (PCI-DSS compliance)
                // The Payment class doesn't have a public create() method for regular card payments
                // For production, use Razorpay Checkout.js on the frontend
                // For test mode, we'll simulate the payment
                
                if ($this->isTestMode) {
                    // Simulate successful payment for testing
                    // In a real scenario, this would be handled by Razorpay Checkout.js on the frontend
                    $simulatedPaymentId = 'pay_' . strtoupper(substr(uniqid(), 0, 14));
                    
                    Log::info('Simulating Razorpay payment for test mode (server-side card payments not supported)', [
                        'simulated_payment_id' => $simulatedPaymentId,
                        'order_id' => $orderId,
                        'note' => 'For production, use Razorpay Checkout.js on frontend',
                    ]);
                    
                    return [
                        'success' => true,
                        'payment_id' => $simulatedPaymentId,
                        'gateway_payment_id' => $simulatedPaymentId,
                        'gateway_txn_id' => $simulatedPaymentId,
                        'status' => 'captured',
                        'order_id' => $orderId,
                        'amount' => $this->convertFromPaise($order['amount']),
                        'currency' => $order['currency'],
                        'raw_response' => [
                            'id' => $simulatedPaymentId,
                            'status' => 'captured',
                            'amount' => $order['amount'],
                            'currency' => $order['currency'],
                            'method' => 'card',
                        ],
                    ];
                } else {
                    // For live mode, throw error - must use frontend Checkout.js
                    throw new \RuntimeException('Server-side card payments are not supported. Please use Razorpay Checkout.js on the frontend for PCI-DSS compliance.');
                }
            }

            // For other payment methods, return order details for frontend integration
            return [
                'success' => true,
                'order_id' => $orderId,
                'gateway_order_id' => $order['id'],
                'amount' => $this->convertFromPaise($order['amount']),
                'currency' => $order['currency'],
                'status' => 'created',
                'redirect_url' => null, // Frontend handles redirect
                'raw_response' => $order,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay payment initiation failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
                'payment_data' => $this->sanitizeLogData($paymentData),
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_PAYMENT_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment signature.
     */
    public function verifyPayment(array $paymentData, string $signature): array
    {
        try {
            $paymentId = $paymentData['razorpay_payment_id'] ?? $paymentData['payment_id'] ?? null;
            $orderId = $paymentData['razorpay_order_id'] ?? $paymentData['order_id'] ?? null;
            $razorpaySignature = $paymentData['razorpay_signature'] ?? $signature;

            if (!$paymentId || !$orderId || !$razorpaySignature) {
                return [
                    'verified' => false,
                    'error_code' => 'MISSING_PARAMETERS',
                    'message' => 'Payment ID, Order ID, and Signature are required',
                ];
            }

            // Verify signature using Razorpay utility
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $razorpaySignature,
            ];

            $keySecret = $this->acquirerAccount->additional_key_2 ?? $this->acquirerAccount->salt;
            $this->razorpay->utility->verifyPaymentSignature($attributes);

            // Fetch payment details
            $payment = $this->razorpay->payment->fetch($paymentId);

            return [
                // Align with controller expectation: use `success` as primary flag
                'success' => true,
                'verified' => true,
                'payment_id' => $payment['id'],
                'gateway_payment_id' => $payment['id'],
                'order_id' => $payment['order_id'],
                'status' => $this->normalizeStatus($payment['status']),
                'amount' => $this->convertFromPaise($payment['amount']),
                'currency' => $payment['currency'],
                'method' => $payment['method'] ?? null,
                'raw_response' => $payment,
            ];

        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'verified' => false,
                'error_code' => 'SIGNATURE_VERIFICATION_FAILED',
                'message' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay payment verification error', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'verified' => false,
                'error_code' => 'VERIFICATION_ERROR',
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
            $refundData = [
                'amount' => $this->convertToPaise($amount),
            ];

            // Add notes if provided
            if (isset($options['notes'])) {
                $refundData['notes'] = $options['notes'];
            }

            // Add speed if provided (normal, optimum)
            if (isset($options['speed'])) {
                $refundData['speed'] = $options['speed'];
            }

            $refund = $this->razorpay->payment->fetch($paymentId)->refund($refundData);

            Log::info('Razorpay refund processed', [
                'refund_id' => $refund['id'],
                'payment_id' => $paymentId,
                'amount' => $amount,
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'gateway_refund_id' => $refund['id'],
                'payment_id' => $paymentId,
                'amount' => $this->convertFromPaise($refund['amount']),
                'status' => $this->normalizeStatus($refund['status']),
                'speed' => $refund['speed'] ?? 'normal',
                'raw_response' => $refund,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay refund failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_REFUND_ERROR',
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
            $payment = $this->razorpay->payment->fetch($paymentId);

            return [
                'success' => true,
                'payment_id' => $payment['id'],
                'gateway_payment_id' => $payment['id'],
                'status' => $this->normalizeStatus($payment['status']),
                'amount' => $this->convertFromPaise($payment['amount']),
                'currency' => $payment['currency'],
                'order_id' => $payment['order_id'] ?? null,
                'method' => $payment['method'] ?? null,
                'raw_response' => $payment,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay payment status check failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'error_code' => 'STATUS_CHECK_FAILED',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order status.
     */
    public function getOrderStatus(string $orderId): array
    {
        try {
            $order = $this->razorpay->order->fetch($orderId);

            return [
                'success' => true,
                'order_id' => $order['id'],
                'gateway_order_id' => $order['id'],
                'status' => $this->normalizeStatus($order['status']),
                'amount' => $this->convertFromPaise($order['amount']),
                'currency' => $order['currency'],
                'amount_paid' => $this->convertFromPaise($order['amount_paid'] ?? 0),
                'raw_response' => $order,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay order status check failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'error_code' => 'ORDER_STATUS_CHECK_FAILED',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        try {
            $keySecret = $this->acquirerAccount->additional_key_2 ?? $this->acquirerAccount->salt;

            if (!$keySecret) {
                Log::warning('Cannot verify Razorpay webhook signature - secret not configured');
                return false;
            }

            // Razorpay webhook signature verification
            $this->razorpay->utility->verifyWebhookSignature(
                json_encode($payload),
                $signature,
                $keySecret
            );

            return true;

        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay webhook signature verification failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Razorpay webhook verification error', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);
            return false;
        }
    }

    /**
     * Normalize Razorpay event type to gateway-level event type.
     */
    public function normalizeEventType(array $payload): string
    {
        // Razorpay webhook events: payment.authorized, payment.captured, payment.failed, etc.
        $event = $payload['event'] ?? null;

        if (!$event) {
            return 'unknown';
        }

        // Map Razorpay events to gateway events
        $eventMap = [
            'payment.authorized' => 'payment.authorized',
            'payment.captured' => 'payment.success',
            'payment.failed' => 'payment.failed',
            'payment.pending' => 'payment.pending',
            'refund.created' => 'refund.created',
            'refund.processed' => 'refund.success',
            'order.paid' => 'order.completed',
            'settlement.processed' => 'settlement.processed',
            'dispute.created' => 'dispute.created',
            'dispute.resolved' => 'dispute.resolved',
        ];

        return $eventMap[$event] ?? 'unknown';
    }

    /**
     * Normalize Razorpay status to gateway-level status.
     */
    public function normalizeStatus(string $providerStatus): string
    {
        $statusMap = [
            'created' => 'pending',
            'authorized' => 'authorized',
            'captured' => 'success',
            'refunded' => 'refunded',
            'failed' => 'failed',
            'pending' => 'pending',
            'paid' => 'success',
            'attempted' => 'pending',
        ];

        return $statusMap[strtolower($providerStatus)] ?? strtolower($providerStatus);
    }

    /**
     * Extract reference IDs from Razorpay webhook payload.
     */
    public function extractReferenceIds(array $payload): array
    {
        $references = [
            'payment_id' => null,
            'order_id' => null,
            'refund_id' => null,
            'settlement_id' => null,
            'dispute_id' => null,
        ];

        // Razorpay webhook structure: payload contains entity
        $entity = $payload['payload']['payment']['entity'] ?? 
                  $payload['payload']['order']['entity'] ?? 
                  $payload['payload']['refund']['entity'] ?? 
                  $payload['payload']['settlement']['entity'] ?? 
                  $payload['payload']['dispute']['entity'] ?? 
                  [];

        if (isset($entity['id'])) {
            if (isset($payload['payload']['payment'])) {
                $references['payment_id'] = $entity['id'];
            }
            if (isset($payload['payload']['order'])) {
                $references['order_id'] = $entity['id'];
            }
            if (isset($payload['payload']['refund'])) {
                $references['refund_id'] = $entity['id'];
            }
            if (isset($payload['payload']['settlement'])) {
                $references['settlement_id'] = $entity['id'];
            }
            if (isset($payload['payload']['dispute'])) {
                $references['dispute_id'] = $entity['id'];
            }
        }

        // Also check for order_id in payment entity
        if (isset($entity['order_id'])) {
            $references['order_id'] = $entity['order_id'];
        }

        return $references;
    }

    /**
     * Get provider name.
     */
    public function getProviderName(): string
    {
        return 'razorpay';
    }

    /**
     * Create payment link.
     */
    public function createPaymentLink(array $linkData): array
    {
        try {
            $paymentLinkData = [
                'amount' => $this->convertToPaise($linkData['amount']),
                'currency' => $linkData['currency'] ?? 'INR',
                'description' => $linkData['description'] ?? 'Payment Link',
            ];

            // Add customer details
            if (isset($linkData['customer_details'])) {
                $customer = $linkData['customer_details'];
                $paymentLinkData['customer'] = [
                    'name' => $customer['name'] ?? null,
                    'email' => $customer['email'] ?? null,
                    'contact' => $customer['phone'] ?? null,
                ];
            }

            // Add notes
            if (isset($linkData['notes'])) {
                $paymentLinkData['notes'] = $linkData['notes'];
            }

            // Add notify settings
            if (isset($linkData['notify'])) {
                $paymentLinkData['notify'] = $linkData['notify'];
            }

            // Add reminder settings
            if (isset($linkData['reminder_enable'])) {
                $paymentLinkData['reminder_enable'] = $linkData['reminder_enable'];
            }

            // Add expiry
            if (isset($linkData['expire_by'])) {
                $paymentLinkData['expire_by'] = $linkData['expire_by'];
            }

            $paymentLink = $this->razorpay->paymentLink->create($paymentLinkData);

            Log::info('Razorpay payment link created', [
                'link_id' => $paymentLink['id'],
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => true,
                'link_id' => $paymentLink['id'],
                'gateway_link_id' => $paymentLink['id'],
                'short_url' => $paymentLink['short_url'],
                'status' => $this->normalizeStatus($paymentLink['status']),
                'amount' => $this->convertFromPaise($paymentLink['amount']),
                'currency' => $paymentLink['currency'],
                'raw_response' => $paymentLink,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay payment link creation failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_LINK_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get settlement details.
     */
    public function getSettlements(array $filters = []): array
    {
        try {
            $options = [];

            if (isset($filters['count'])) {
                $options['count'] = $filters['count'];
            }

            if (isset($filters['skip'])) {
                $options['skip'] = $filters['skip'];
            }

            $settlements = $this->razorpay->settlement->all($options);

            $normalizedSettlements = [];
            foreach ($settlements['items'] as $settlement) {
                $normalizedSettlements[] = [
                    'settlement_id' => $settlement['id'],
                    'gateway_settlement_id' => $settlement['id'],
                    'amount' => $this->convertFromPaise($settlement['amount']),
                    'currency' => $settlement['currency'],
                    'status' => $this->normalizeStatus($settlement['status']),
                    'settled_at' => $settlement['settled_at'] ?? null,
                    'raw_response' => $settlement,
                ];
            }

            return [
                'success' => true,
                'settlements' => $normalizedSettlements,
                'count' => $settlements['count'] ?? count($normalizedSettlements),
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay settlements fetch failed', [
                'error' => $e->getMessage(),
                'acquirer_account_id' => $this->acquirerAccount->id,
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_SETTLEMENTS_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert amount to paise (Razorpay uses smallest currency unit).
     */
    protected function convertToPaise(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from paise to rupees.
     */
    protected function convertFromPaise(int $paise): float
    {
        return round($paise / 100, 2);
    }

    /**
     * Sanitize data for logging (remove sensitive information).
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['card_number', 'cvv', 'card_holder', 'expiry_month', 'expiry_year'];
        $sanitized = $data;

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***';
            }
        }

        return $sanitized;
    }
}

