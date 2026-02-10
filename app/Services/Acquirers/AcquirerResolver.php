<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;
use Illuminate\Support\Facades\Log;

/**
 * Acquirer Resolver
 * 
 * Resolves and initializes the appropriate acquirer adapter
 * based on the AcquirerAccount configuration.
 */
class AcquirerResolver
{
    /**
     * Resolve acquirer adapter from AcquirerAccount.
     * 
     * @param AcquirerAccount $acquirerAccount
     * @return AcquirerInterface
     * @throws \RuntimeException If adapter cannot be resolved
     */
    public function resolve(AcquirerAccount $acquirerAccount): AcquirerInterface
    {
        $acquirerName = strtolower($acquirerAccount->acquirer_name);

        // Map acquirer names to adapter classes
        $adapterMap = [
            'razorpay' => RazorpayAcquirerAdapter::class,
            'razorpay_test' => RazorpayAcquirerAdapter::class,
            'razorpay_live' => RazorpayAcquirerAdapter::class,
            'cashfree' => CashFreeAcquirerAdapter::class,
            'cashfree_test' => CashFreeAcquirerAdapter::class,
            'cashfree_live' => CashFreeAcquirerAdapter::class,
            // Yapily sandbox acquirer (open-banking style, simulated payments)
            'yapily' => YapilyAcquirerAdapter::class,
            'yapily_test' => YapilyAcquirerAdapter::class,
            'yapily_live' => YapilyAcquirerAdapter::class,
            // Add more acquirers here as they are implemented
            // 'paytm' => PaytmAcquirerAdapter::class,
            // 'hdfc' => HdfcAcquirerAdapter::class,
        ];

        // Check if adapter exists for this acquirer
        if (!isset($adapterMap[$acquirerName])) {
            throw new \RuntimeException("No adapter found for acquirer: {$acquirerAccount->acquirer_name}");
        }

        $adapterClass = $adapterMap[$acquirerName];

        // Check if adapter class exists
        if (!class_exists($adapterClass)) {
            throw new \RuntimeException("Adapter class not found: {$adapterClass}");
        }

        // Instantiate and initialize adapter
        $adapter = new $adapterClass();
        
        if (!$adapter instanceof AcquirerInterface) {
            throw new \RuntimeException("Adapter does not implement AcquirerInterface: {$adapterClass}");
        }

        // Initialize adapter with acquirer account
        $adapter->initialize($acquirerAccount);

        Log::debug('Acquirer adapter resolved', [
            'acquirer_name' => $acquirerAccount->acquirer_name,
            'adapter_class' => $adapterClass,
            'acquirer_account_id' => $acquirerAccount->id,
        ]);

        return $adapter;
    }

    /**
     * Resolve acquirer adapter by provider name.
     * 
     * @param string $providerName Provider name (e.g., 'razorpay')
     * @param string $mode Mode ('TEST' or 'LIVE')
     * @return AcquirerInterface|null Returns null if no active account found
     */
    public function resolveByProvider(string $providerName, string $mode = 'TEST'): ?AcquirerInterface
    {
        // Find active acquirer account for this provider and mode
        $acquirerAccount = AcquirerAccount::where('acquirer_name', $providerName)
            ->where('mode', $mode)
            ->where('is_active', true)
            ->first();

        if (!$acquirerAccount) {
            Log::warning('No active acquirer account found', [
                'provider' => $providerName,
                'mode' => $mode,
            ]);
            return null;
        }

        return $this->resolve($acquirerAccount);
    }

    /**
     * Detect provider from webhook payload.
     * 
     * @param array $payload Webhook payload
     * @return string|null Provider name or null if cannot be detected
     */
    public function detectProvider(array $payload): ?string
    {
        // Razorpay webhooks have 'event' key at root level
        if (isset($payload['event']) && isset($payload['payload'])) {
            return 'razorpay';
        }

        // Add detection logic for other providers here
        // Paytm might have 'MID' or 'TXNID' keys
        // HDFC might have specific structure

        return null;
    }
}

