<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Merchant;
use App\Models\AcquirerAccount;
use App\Services\Acquirers\AcquirerResolver;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Payment Gateway Service
 * 
 * Handles all Razorpay payment operations.
 * Uses Razorpay Checkout.js for card payments.
 */
class RazorpayGatewayService implements PaymentGatewayInterface
{
    protected ?Merchant $merchant = null;
    protected ?AcquirerAccount $acquirerAccount = null;
    protected $adapter = null;

    /**
     * Initialize the gateway with merchant and acquirer.
     */
    public function initialize($merchant, $acquirerAccount): PaymentGatewayInterface
    {
        $this->merchant = $merchant;
        $this->acquirerAccount = $acquirerAccount;

        // Resolve Razorpay adapter
        $resolver = app(AcquirerResolver::class);
        $this->adapter = $resolver->resolve($acquirerAccount);

        Log::info('RazorpayGatewayService initialized', [
            'merchant_id' => $merchant->id,
            'acquirer_account_id' => $acquirerAccount->id,
        ]);

        return $this;
    }

    /**
     * Create an order with Razorpay.
     */
    public function createOrder(array $orderData): array
    {
        $result = $this->adapter->createOrder($orderData);

        if (!$result['success']) {
            Log::error('RazorpayGatewayService: Order creation failed', [
                'merchant_id' => $this->merchant->id,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }

        return $result;
    }

    /**
     * For Razorpay, charge is handled via Checkout.js on frontend.
     * This method prepares the order for frontend processing.
     */
    public function charge(array $paymentData): array
    {
        // Razorpay uses Checkout.js, so we just create the order
        // The actual payment is handled on the frontend
        $orderResult = $this->createOrder($paymentData);

        if (!$orderResult['success']) {
            return [
                'success' => false,
                'gateway' => 'razorpay',
                'status' => 'failed',
                'message' => $orderResult['message'] ?? 'Failed to create Razorpay order',
            ];
        }

        // Get Razorpay API key for frontend
        $razorpayKeyId = $this->acquirerAccount->additional_key_1 ?? $this->acquirerAccount->secret_key;

        return [
            'success' => true,
            'gateway' => 'razorpay',
            'use_razorpay_checkout' => true,
            'razorpay_key' => $razorpayKeyId,
            'razorpay_order_id' => $orderResult['gateway_order_id'] ?? null,
            'order_id' => $orderResult['order_id'] ?? null,
            'amount' => ($paymentData['amount'] ?? 0) * 100, // Razorpay expects paise
            'currency' => $paymentData['currency'] ?? 'INR',
            'status' => 'pending',
            'message' => 'Please complete payment using Razorpay Checkout',
        ];
    }

    /**
     * Get payment status from Razorpay.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $result = $this->adapter->getPaymentStatus($paymentId);

        return [
            'success' => $result['success'] ?? false,
            'gateway' => 'razorpay',
            'status' => $result['status'] ?? 'unknown',
            'payment_id' => $paymentId,
            'message' => $result['message'] ?? null,
        ];
    }

    /**
     * Get gateway name.
     */
    public function getGatewayName(): string
    {
        return 'razorpay';
    }

    /**
     * Razorpay requires frontend SDK (Checkout.js) for card payments.
     */
    public function requiresFrontendSdk(): bool
    {
        return true;
    }

    /**
     * Get Razorpay Checkout.js configuration.
     */
    public function getFrontendSdkConfig(): ?array
    {
        $razorpayKeyId = $this->acquirerAccount->additional_key_1 ?? $this->acquirerAccount->secret_key;

        return [
            'key' => $razorpayKeyId,
            'sdk_url' => 'https://checkout.razorpay.com/v1/checkout.js',
        ];
    }
}

