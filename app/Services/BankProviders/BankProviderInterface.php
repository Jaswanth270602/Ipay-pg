<?php

namespace App\Services\BankProviders;

interface BankProviderInterface
{
    /**
     * Process a payment.
     *
     * @param array $paymentData
     * @return array
     */
    public function processPayment(array $paymentData): array;

    /**
     * Verify a payment status.
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array;

    /**
     * Process a refund.
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function processRefund(string $transactionId, float $amount): array;

    /**
     * Get payment status.
     *
     * @param string $gatewayTxnId
     * @return array
     */
    public function getPaymentStatus(string $gatewayTxnId): array;

    /**
     * Verify webhook signature.
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;
}

