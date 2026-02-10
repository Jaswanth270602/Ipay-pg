<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Embedded Payment Service
 * 
 * This service creates embedded payment pages (iframe-based) for white-label integration.
 * Uses Razorpay Payment Pages API which can be embedded in iframe without full redirect.
 */
class RazorpayEmbeddedPayment
{
    protected ?Api $razorpay = null;
    protected ?AcquirerAccount $acquirerAccount = null;
    protected bool $isTestMode = true;

    public function initialize(AcquirerAccount $acquirerAccount): self
    {
        $this->acquirerAccount = $acquirerAccount;
        $this->isTestMode = strtoupper($acquirerAccount->mode) === 'TEST';

        $keyId = $acquirerAccount->additional_key_1 ?? $acquirerAccount->secret_key;
        $keySecret = $acquirerAccount->additional_key_2 ?? $acquirerAccount->secret_key ?? $acquirerAccount->salt;

        if (!$keyId || !$keySecret) {
            throw new \RuntimeException('Razorpay credentials not configured in AcquirerAccount');
        }

        $this->razorpay = new Api($keyId, $keySecret);
        return $this;
    }

    /**
     * Create an embedded payment page that can be displayed in iframe.
     * 
     * @param array $paymentData
     * @return array
     */
    public function createEmbeddedPaymentPage(array $paymentData): array
    {
        try {
            // First create a Razorpay order
            $orderData = [
                'receipt' => $paymentData['order_id'] ?? 'order_' . uniqid(),
                'amount' => $this->convertToPaise($paymentData['amount']),
                'currency' => $paymentData['currency'] ?? 'INR',
                'payment_capture' => 1, // Auto capture
            ];

            if (isset($paymentData['customer_details'])) {
                $customer = $paymentData['customer_details'];
                $orderData['notes'] = [
                    'customer_name' => $customer['name'] ?? null,
                    'customer_email' => $customer['email'] ?? null,
                    'customer_phone' => $customer['phone'] ?? null,
                ];
            }

            $razorpayOrder = $this->razorpay->order->create($orderData);

            Log::info('Razorpay embedded payment order created', [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $paymentData['amount'],
            ]);

            // Create payment page URL that can be embedded in iframe
            // Note: Razorpay doesn't have a direct "embedded page" API, but we can use:
            // 1. Razorpay Payment Links (can be embedded)
            // 2. Custom checkout with Cards SDK (iframe-based card inputs)
            // 3. Smart Collect (minimal branding)
            
            // For white-label, we'll use Payment Links API which can be embedded
            $paymentLinkData = [
                'amount' => $this->convertToPaise($paymentData['amount']),
                'currency' => $paymentData['currency'] ?? 'INR',
                'description' => $paymentData['description'] ?? 'Payment',
                'receipt' => $razorpayOrder['id'],
                'callback_url' => $paymentData['callback_url'] ?? null,
                'callback_method' => 'get', // or 'post'
            ];

            if (isset($paymentData['customer_details'])) {
                $customer = $paymentData['customer_details'];
                $paymentLinkData['customer'] = [
                    'name' => $customer['name'] ?? null,
                    'email' => $customer['email'] ?? null,
                    'contact' => $customer['phone'] ?? null,
                ];
            }

            // Add notes for tracking
            $paymentLinkData['notes'] = [
                'order_id' => $paymentData['order_id'] ?? null,
                'payment_link_id' => $paymentData['payment_link_id'] ?? null,
                'internal_order_id' => $razorpayOrder['id'],
            ];

            $paymentLink = $this->razorpay->paymentLink->create($paymentLinkData);

            Log::info('Razorpay embedded payment link created', [
                'link_id' => $paymentLink['id'],
                'short_url' => $paymentLink['short_url'],
            ]);

            return [
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'gateway_order_id' => $razorpayOrder['id'],
                'payment_page_url' => $paymentLink['short_url'], // Can be embedded in iframe
                'payment_page_id' => $paymentLink['id'],
                'embed_url' => $paymentLink['short_url'], // URL to embed in iframe
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'INR',
                'status' => 'created',
                'raw_response' => [
                    'order' => $razorpayOrder,
                    'payment_link' => $paymentLink,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay embedded payment page creation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $this->sanitizeLogData($paymentData),
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_EMBEDDED_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create embedded payment page URL that can be iframed.
     * Uses Razorpay Payment Links API which can be embedded as iframe.
     * 
     * @param array $paymentData
     * @return array
     */
    public function createEmbeddedPaymentPageUrl(array $paymentData): array
    {
        try {
            // Create Razorpay Payment Link that can be embedded as iframe
            $paymentLinkData = [
                'amount' => $this->convertToPaise($paymentData['amount']),
                'currency' => $paymentData['currency'] ?? 'INR',
                'description' => $paymentData['description'] ?? 'Payment',
                'callback_url' => $paymentData['callback_url'] ?? null,
                'callback_method' => 'get', // Use GET for callbacks
            ];

            // Add customer details
            if (isset($paymentData['customer_details'])) {
                $customer = $paymentData['customer_details'];
                $paymentLinkData['customer'] = [
                    'name' => $customer['name'] ?? null,
                    'email' => $customer['email'] ?? null,
                    'contact' => $customer['phone'] ?? null,
                ];
            }

            // Add notes for tracking
            $paymentLinkData['notes'] = [
                'order_id' => $paymentData['order_id'] ?? null,
                'payment_link_id' => $paymentData['payment_link_id'] ?? null,
                'internal_reference' => $paymentData['order_id'] ?? 'ref_' . uniqid(),
            ];

            // Add expiry (optional)
            if (isset($paymentData['expires_in'])) {
                $paymentLinkData['expire_by'] = now()->addSeconds($paymentData['expires_in'])->timestamp;
            }

            // Create payment link
            $paymentLink = $this->razorpay->paymentLink->create($paymentLinkData);

            Log::info('Razorpay embedded payment link created', [
                'link_id' => $paymentLink['id'],
                'short_url' => $paymentLink['short_url'],
                'amount' => $paymentData['amount'],
            ]);

            return [
                'success' => true,
                'payment_page_id' => $paymentLink['id'],
                'embed_url' => $paymentLink['short_url'], // URL to embed in iframe
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'INR',
                'status' => $this->normalizeStatus($paymentLink['status'] ?? 'created'),
                'raw_response' => $paymentLink,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay embedded payment page creation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $this->sanitizeLogData($paymentData),
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_EMBEDDED_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get configuration for Cards SDK (for reference, but we'll use Payment Pages).
     * 
     * @param array $orderData
     * @return array
     */
    public function getCardsSDKConfig(array $orderData): array
    {
        try {
            // Create order first
            $razorpayOrderData = [
                'receipt' => $orderData['order_id'] ?? 'order_' . uniqid(),
                'amount' => $this->convertToPaise($orderData['amount']),
                'currency' => $orderData['currency'] ?? 'INR',
                'payment_capture' => 1,
            ];

            $razorpayOrder = $this->razorpay->order->create($razorpayOrderData);

            $keyId = $this->acquirerAccount->additional_key_1 ?? $this->acquirerAccount->secret_key;

            return [
                'success' => true,
                'razorpay_key_id' => $keyId,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency'] ?? 'INR',
                'order_id' => $orderData['order_id'] ?? null,
                'raw_response' => $razorpayOrder,
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay Cards SDK config generation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_code' => 'RAZORPAY_CARDS_SDK_ERROR',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize Razorpay status to gateway status.
     */
    protected function normalizeStatus(string $razorpayStatus): string
    {
        $statusMap = [
            'created' => 'pending',
            'paid' => 'success',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[strtolower($razorpayStatus)] ?? 'pending';
    }

    /**
     * Verify payment using Razorpay's signature verification.
     */
    public function verifyPaymentSignature(array $paymentData, string $signature): bool
    {
        try {
            $payload = $paymentData['razorpay_order_id'] . '|' . $paymentData['razorpay_payment_id'];
            
            $keySecret = $this->acquirerAccount->additional_key_2 ?? $this->acquirerAccount->secret_key ?? $this->acquirerAccount->salt;
            
            $expectedSignature = hash_hmac('sha256', $payload, $keySecret);
            
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Razorpay signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function convertToPaise(float $amount): int
    {
        return (int) round($amount * 100);
    }

    protected function convertFromPaise(int $paise): float
    {
        return round($paise / 100, 2);
    }

    protected function sanitizeLogData(array $data): array
    {
        $sanitized = $data;
        unset($sanitized['card_number'], $sanitized['cvv'], $sanitized['pin']);
        return $sanitized;
    }
}

