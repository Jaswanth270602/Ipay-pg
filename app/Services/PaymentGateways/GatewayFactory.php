<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Merchant;
use App\Models\AcquirerAccount;
use App\Services\GatewayModeService;
use App\Services\PaymentGateways\CashfreeGatewayService;
use App\Services\PaymentGateways\RazorpayGatewayService;
use Illuminate\Support\Facades\Log;

/**
 * Payment Gateway Factory
 *
 * Routes payment requests to the appropriate gateway based on merchant's acquirer configuration.
 * Third-party acquirers are only allowed when gateway mode is LIVE (see GatewayModeService).
 */
class GatewayFactory
{
    /**
     * Create and initialize the appropriate payment gateway.
     * Acquirers are disabled in TEST mode; only internal payment is available.
     *
     * @param Merchant $merchant
     * @param AcquirerAccount|null $acquirerAccount
     * @return PaymentGatewayInterface
     * @throws \RuntimeException If gateway cannot be created or mode is TEST
     */
    public static function make(Merchant $merchant, ?AcquirerAccount $acquirerAccount = null): PaymentGatewayInterface
    {
        // Central mode check: no acquirer API calls in TEST mode
        if (!GatewayModeService::isLive()) {
            throw new \RuntimeException('Acquirers are disabled in TEST mode. Only internal payment is available.');
        }

        // Get acquirer account if not provided
        if (!$acquirerAccount) {
            $acquirerAccount = $merchant->getActiveAcquirerAccount();
        }

        // If no acquirer account, use simulation service
        if (!$acquirerAccount) {
            throw new \RuntimeException('No active acquirer account found for merchant. Payment gateway cannot be initialized.');
        }

        $acquirerName = strtolower(trim($acquirerAccount->acquirer_name ?? ''));

        Log::info('GatewayFactory: Creating gateway', [
            'merchant_id' => $merchant->id,
            'acquirer_name' => $acquirerAccount->acquirer_name,
            'acquirer_id' => $acquirerAccount->id,
        ]);

        // Route to appropriate gateway based on acquirer name
        if (stripos($acquirerName, 'cashfree') !== false) {
            $gateway = new CashfreeGatewayService();
            $gateway->initialize($merchant, $acquirerAccount);
            
            Log::info('GatewayFactory: CashFree gateway initialized', [
                'merchant_id' => $merchant->id,
            ]);
            
            return $gateway;
        } elseif (stripos($acquirerName, 'razorpay') !== false) {
            $gateway = new RazorpayGatewayService();
            $gateway->initialize($merchant, $acquirerAccount);
            
            Log::info('GatewayFactory: Razorpay gateway initialized', [
                'merchant_id' => $merchant->id,
            ]);
            
            return $gateway;
        }

        // Default: throw exception for unsupported gateway
        throw new \RuntimeException("Unsupported payment gateway: {$acquirerAccount->acquirer_name}. Supported gateways: CashFree, Razorpay.");
    }

    /**
     * Check if merchant has a supported gateway configured.
     *
     * @param Merchant $merchant
     * @return bool
     */
    public static function hasSupportedGateway(Merchant $merchant): bool
    {
        $acquirerAccount = $merchant->getActiveAcquirerAccount();
        
        if (!$acquirerAccount) {
            return false;
        }

        $acquirerName = strtolower(trim($acquirerAccount->acquirer_name ?? ''));
        
        return stripos($acquirerName, 'cashfree') !== false 
            || stripos($acquirerName, 'razorpay') !== false;
    }
}

