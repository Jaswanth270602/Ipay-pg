<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Merchant;
use App\Models\AcquirerAccount;
use App\Services\Acquirers\AcquirerResolver;
use Illuminate\Support\Facades\Log;

/**
 * CashFree Payment Gateway Service
 * 
 * Handles all CashFree payment operations.
 * Completely isolated from Razorpay logic.
 */
class CashfreeGatewayService implements PaymentGatewayInterface
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

        // Resolve CashFree adapter
        $resolver = app(AcquirerResolver::class);
        $this->adapter = $resolver->resolve($acquirerAccount);

        Log::info('CashfreeGatewayService initialized', [
            'merchant_id' => $merchant->id,
            'acquirer_account_id' => $acquirerAccount->id,
        ]);

        return $this;
    }

    /**
     * Create an order with CashFree.
     */
    public function createOrder(array $orderData): array
    {
        $result = $this->adapter->createOrder($orderData);

        if (!$result['success']) {
            Log::error('CashfreeGatewayService: Order creation failed', [
                'merchant_id' => $this->merchant->id,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }

        return $result;
    }

    /**
     * REMOVED: charge() method for CashFree
     * 
     * CashFree does NOT support server-side payment processing.
     * This method should NEVER be called for CashFree.
     * 
     * CashFree flow:
     * 1. Create order (returns payment_session_id) - done in controller
     * 2. Frontend calls Cashfree.checkout() with payment_session_id
     * 3. User completes payment in CashFree checkout
     * 4. Webhook/status API updates payment status
     */
    public function charge(array $paymentData): array
    {
        // This method should never be called for CashFree
        // Payments are initiated via frontend checkout SDK only
        
        Log::error('CashfreeGatewayService: charge() called - this should not happen', [
            'merchant_id' => $this->merchant->id,
            'payment_data_keys' => array_keys($paymentData),
        ]);
        
        return [
            'success' => false,
            'gateway' => 'cashfree',
            'status' => 'failed',
            'message' => 'CashFree does not support server-side payment processing. Use frontend checkout SDK.',
            'error_code' => 'CASHFREE_NO_SERVER_CHARGE',
        ];
    }

    /**
     * Get payment status from CashFree.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $result = $this->adapter->getPaymentStatus($paymentId);

        return [
            'success' => $result['success'] ?? false,
            'gateway' => 'cashfree',
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
        return 'cashfree';
    }

    /**
     * CashFree requires frontend SDK for checkout.
     */
    public function requiresFrontendSdk(): bool
    {
        return true;
    }

    /**
     * Get frontend SDK configuration for CashFree.
     */
    public function getFrontendSdkConfig(): ?array
    {
        return [
            'sdk_url' => 'https://sdk.cashfree.com/js/v3/cashfree.js',
            'checkout_method' => 'Cashfree.checkout',
        ];
    }
    
    /**
     * Get the adapter instance.
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}

