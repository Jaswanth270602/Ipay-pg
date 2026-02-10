<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;

/**
 * Acquirer Interface
 * 
 * All acquirer adapters must implement this interface.
 * This ensures a consistent API across all payment gateway integrations.
 */
interface AcquirerInterface
{
    /**
     * Initialize the acquirer adapter with an AcquirerAccount.
     * 
     * @param AcquirerAccount $acquirerAccount
     * @return self
     */
    public function initialize(AcquirerAccount $acquirerAccount): self;

    /**
     * Create a payment order.
     * 
     * @param array $orderData Order data (amount, currency, customer details, etc.)
     * @return array Response with order_id, gateway_order_id, and other order details
     */
    public function createOrder(array $orderData): array;

    /**
     * Initiate a payment.
     * 
     * @param array $paymentData Payment data (order_id, payment_method, card details, etc.)
     * @return array Response with payment_id, gateway_payment_id, status, and redirect_url if needed
     */
    public function initiatePayment(array $paymentData): array;

    /**
     * Verify a payment using signature validation.
     * 
     * @param array $paymentData Payment data from callback
     * @param string $signature Signature to verify
     * @return array Verification result with verified status and payment details
     */
    public function verifyPayment(array $paymentData, string $signature): array;

    /**
     * Process a refund.
     * 
     * @param string $paymentId Gateway payment ID
     * @param float $amount Refund amount
     * @param array $options Additional refund options
     * @return array Response with refund_id, gateway_refund_id, status
     */
    public function processRefund(string $paymentId, float $amount, array $options = []): array;

    /**
     * Get payment status.
     * 
     * @param string $paymentId Gateway payment ID
     * @return array Payment status and details
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Get order status.
     * 
     * @param string $orderId Gateway order ID
     * @return array Order status and details
     */
    public function getOrderStatus(string $orderId): array;

    /**
     * Verify webhook/callback signature.
     * 
     * @param array $payload Raw payload from webhook
     * @param string $signature Signature header value
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Normalize provider-specific event to gateway-level event type.
     * 
     * @param array $payload Raw webhook payload
     * @return string Normalized event type (payment.success, payment.failed, refund.created, etc.)
     */
    public function normalizeEventType(array $payload): string;

    /**
     * Normalize provider-specific status to gateway-level status.
     * 
     * @param string $providerStatus Provider-specific status
     * @return string Normalized status (success, failed, pending, etc.)
     */
    public function normalizeStatus(string $providerStatus): string;

    /**
     * Extract reference IDs from webhook payload.
     * 
     * @param array $payload Raw webhook payload
     * @return array Array with keys: payment_id, order_id, refund_id, etc.
     */
    public function extractReferenceIds(array $payload): array;

    /**
     * Get provider name (e.g., 'razorpay', 'paytm', etc.).
     * 
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Create payment link (if supported by provider).
     * 
     * @param array $linkData Payment link data
     * @return array Response with link_id, short_url, etc.
     */
    public function createPaymentLink(array $linkData): array;

    /**
     * Get settlement details (if supported by provider).
     * 
     * @param array $filters Settlement filters (date range, etc.)
     * @return array Settlement details
     */
    public function getSettlements(array $filters = []): array;
}

