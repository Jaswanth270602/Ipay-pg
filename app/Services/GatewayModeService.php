<?php

namespace App\Services;

/**
 * Central gateway mode check: TEST vs LIVE.
 *
 * Gateway acts as both payment gateway and payment aggregator:
 *
 * - TEST = Acquirer-independent. Only inbuilt dummy/sandbox APIs for payment
 *   simulation. No acquirer API calls (Razorpay, Cashfree, Yapily) regardless
 *   of merchant configuration.
 *
 * - LIVE = Payment aggregator. Use acquirer adapters (Razorpay, Cashfree, etc.).
 *   Same library per acquirer; test vs live keys only (e.g. Razorpay Test and
 *   Razorpay Live both use the same Razorpay adapter).
 *
 * Uses config('badlicash.mode') from APP_PAYMENT_MODE env (test | live).
 */
class GatewayModeService
{
    /**
     * Whether the gateway is in LIVE mode (acquirers allowed).
     *
     * @return bool True only when config badlicash.mode === 'live'
     */
    public static function isLive(): bool
    {
        return strtolower((string) config('badlicash.mode', 'test')) === 'live';
    }

    /**
     * Whether the gateway is in TEST mode (internal payment only, no acquirers).
     *
     * @return bool
     */
    public static function isTest(): bool
    {
        return !self::isLive();
    }

    /**
     * Current mode string: 'test' or 'live'.
     *
     * @return string
     */
    public static function mode(): string
    {
        return strtolower((string) config('badlicash.mode', 'test'));
    }
}
