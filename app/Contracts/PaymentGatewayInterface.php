<?php

namespace App\Contracts;

/**
 * Payment Gateway Interface
 * 
 * Standardized interface for all payment gateway implementations.
 * Ensures consistent behavior across different acquirers (CashFree, Razorpay, etc.)
 */
interface PaymentGatewayInterface
{
    /**
     * Initialize the gateway with merchant and acquirer configuration.
     *
     * @param \App\Models\Merchant $merchant
     * @param \App\Models\AcquirerAccount $acquirerAccount
     * @return PaymentGatewayInterface
     */
    public function initialize($merchant, $acquirerAccount): PaymentGatewayInterface;

    /**
     * Create an order with the payment gateway.
     *
     * @param array $orderData
     * @return array ['success' => bool, 'order_id' => string, 'gateway_order_id' => string, ...]
     */
    public function createOrder(array $orderData): array;

    /**
     * Process a payment (charge the customer).
     *
     * @param array $paymentData
     * @return array ['success' => bool, 'transaction_id' => string, 'status' => string, ...]
     */
    public function charge(array $paymentData): array;

    /**
     * Get payment status from gateway.
     *
     * @param string $paymentId
     * @return array ['success' => bool, 'status' => string, ...]
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Get the gateway name (e.g., 'cashfree', 'razorpay').
     *
     * @return string
     */
    public function getGatewayName(): string;

    /**
     * Check if this gateway requires frontend SDK (e.g., Razorpay Checkout.js).
     *
     * @return bool
     */
    public function requiresFrontendSdk(): bool;

    /**
     * Get frontend SDK configuration if required.
     *
     * @return array|null
     */
    public function getFrontendSdkConfig(): ?array;
}

