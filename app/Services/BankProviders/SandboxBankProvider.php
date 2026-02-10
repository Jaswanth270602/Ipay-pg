<?php

namespace App\Services\BankProviders;

use Illuminate\Support\Str;

/**
 * Sandbox Bank Provider
 * 
 * This is a mock implementation for testing purposes.
 * It simulates payment processing with configurable success rates and delays.
 */
class SandboxBankProvider implements BankProviderInterface
{
    protected float $successRate = 0.9; // 90% success rate by default
    protected int $minDelayMs = 500;
    protected int $maxDelayMs = 2000;

    /**
     * Process a payment in sandbox mode.
     */
    public function processPayment(array $paymentData): array
    {
        // Simulate processing delay
        usleep(rand($this->minDelayMs, $this->maxDelayMs) * 1000);

        $gatewayTxnId = 'SBOX_' . strtoupper(Str::random(20));
        $isSuccessful = $this->shouldSucceed();

        if ($isSuccessful) {
            return [
                'success' => true,
                'status' => 'success',
                'gateway_txn_id' => $gatewayTxnId,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'payment_method' => $paymentData['payment_method'] ?? 'card',
                'message' => 'Payment processed successfully',
                'timestamp' => now()->toIso8601String(),
                'payment_details' => $this->generatePaymentDetails($paymentData['payment_method'] ?? 'card'),
            ];
        } else {
            $errorCode = $this->getRandomErrorCode();
            return [
                'success' => false,
                'status' => 'failed',
                'gateway_txn_id' => $gatewayTxnId,
                'error_code' => $errorCode,
                'message' => $this->getErrorMessage($errorCode),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Verify a payment status.
     */
    public function verifyPayment(string $transactionId): array
    {
        usleep(rand(200, 500) * 1000);

        return [
            'success' => true,
            'status' => 'success',
            'transaction_id' => $transactionId,
            'verified' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Process a refund.
     * In test/sandbox mode, refunds always succeed for easier testing.
     */
    public function processRefund(string $transactionId, float $amount): array
    {
        usleep(rand($this->minDelayMs, $this->maxDelayMs) * 1000);

        $refundId = 'SBOX_RFD_' . strtoupper(Str::random(18));
        
        // In sandbox/test mode, refunds always succeed to make testing easier
        return [
            'success' => true,
            'status' => 'completed',
            'refund_id' => $refundId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'message' => 'Refund processed successfully',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get payment status.
     */
    public function getPaymentStatus(string $gatewayTxnId): array
    {
        usleep(rand(200, 500) * 1000);

        return [
            'success' => true,
            'gateway_txn_id' => $gatewayTxnId,
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Verify webhook signature (always true in sandbox).
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // In sandbox mode, we always verify as true for testing
        return true;
    }

    /**
     * Determine if the operation should succeed based on success rate.
     */
    protected function shouldSucceed(): bool
    {
        return (rand(1, 100) / 100) <= $this->successRate;
    }

    /**
     * Get a random error code for failed transactions.
     */
    protected function getRandomErrorCode(): string
    {
        $errorCodes = [
            'INSUFFICIENT_FUNDS',
            'CARD_DECLINED',
            'INVALID_CVV',
            'CARD_EXPIRED',
            'TRANSACTION_LIMIT_EXCEEDED',
            'SUSPECTED_FRAUD',
        ];

        return $errorCodes[array_rand($errorCodes)];
    }

    /**
     * Get error message for error code.
     */
    protected function getErrorMessage(string $errorCode): string
    {
        $messages = [
            'INSUFFICIENT_FUNDS' => 'Test card used for failed payments - Insufficient funds',
            'CARD_DECLINED' => 'Test card used for failed payments - Card declined by issuer',
            'INVALID_CVV' => 'Test card used for failed payments - Invalid CVV code',
            'CARD_EXPIRED' => 'Test card used for failed payments - Card has expired',
            'TRANSACTION_LIMIT_EXCEEDED' => 'Test card used for failed payments - Transaction limit exceeded',
            'SUSPECTED_FRAUD' => 'Test card used for failed payments - Transaction suspected as fraudulent',
        ];

        return $messages[$errorCode] ?? 'Test card used for failed payments - Payment failed';
    }

    /**
     * Generate mock payment details based on payment method.
     */
    protected function generatePaymentDetails(string $paymentMethod): array
    {
        switch ($paymentMethod) {
            case 'card':
                return [
                    'card_type' => ['visa', 'mastercard', 'amex'][array_rand(['visa', 'mastercard', 'amex'])],
                    'last4' => str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'expiry_month' => str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT),
                    'expiry_year' => rand(2024, 2030),
                    'card_holder' => 'Test Customer',
                ];

            case 'upi':
                return [
                    'upi_id' => 'testuser@' . ['oksbi', 'okaxis', 'okicici'][array_rand(['oksbi', 'okaxis', 'okicici'])],
                    'provider' => 'UPI',
                ];

            case 'netbanking':
                return [
                    'bank_name' => ['HDFC Bank', 'ICICI Bank', 'SBI'][array_rand(['HDFC Bank', 'ICICI Bank', 'SBI'])],
                    'account_type' => 'savings',
                ];

            default:
                return [];
        }
    }

    /**
     * Set custom success rate for testing.
     */
    public function setSuccessRate(float $rate): void
    {
        $this->successRate = min(1.0, max(0.0, $rate));
    }
}

