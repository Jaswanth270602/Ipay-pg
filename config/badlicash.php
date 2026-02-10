<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company/Brand Name
    |--------------------------------------------------------------------------
    |
    | The company or brand name used throughout the application.
    | This can be changed via APP_NAME in .env file for easy rebranding.
    | Use config('app.name') or brand_name() helper for easy access.
    |
    */
    'company_name' => env('APP_NAME', 'Payment Gateway'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Mode (Gateway = PG + Aggregator)
    |--------------------------------------------------------------------------
    |
    | TEST = Acquirer-independent. Only inbuilt dummy/sandbox APIs for payment
    |        simulation. No Razorpay/Cashfree/etc. calls regardless of merchant.
    | LIVE = Payment aggregator: use acquirer adapters (Razorpay, Cashfree, etc.).
    |        Same library per acquirer; test vs live keys only (e.g. Razorpay Test
    |        and Razorpay Live both use the same Razorpay adapter).
    | Set BADLICASH_MODE=live to enable acquirers.
    |
    */
    // Prefer APP_PAYMENT_MODE; fall back to legacy BADLICASH_MODE for backwards compatibility
    'mode' => env('APP_PAYMENT_MODE', env('BADLICASH_MODE', 'test')),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current API version.
    |
    */
    // API version for the payment gateway
    'api_version' => env('APP_PAYMENT_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Default fee structure for transactions.
    | percentage: Percentage fee (e.g., 2.5 for 2.5%)
    | flat: Flat fee per transaction
    |
    */
    'fee' => [
        'percentage' => env('APP_PAYMENT_FEE_PERCENTAGE', 2.5),
        'flat' => env('APP_PAYMENT_FEE_FLAT', 0.30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for transactions.
    |
    */
    'default_currency' => env('APP_PAYMENT_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Bank Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for bank provider endpoints and credentials.
    |
    */
    'bank_provider' => [
        'sandbox_url' => env('BANK_PROVIDER_SANDBOX_URL', 'http://localhost/api/sandbox/bank'),
        'live_url' => env('BANK_PROVIDER_LIVE_URL', 'https://api.bank-provider.com'),
        'api_key' => env('BANK_PROVIDER_API_KEY'),
        'api_secret' => env('BANK_PROVIDER_API_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook delivery and retry logic.
    |
    */
    'webhook' => [
        'max_retry_attempts' => env('WEBHOOK_MAX_RETRY_ATTEMPTS', 5),
        'retry_delay_seconds' => env('WEBHOOK_RETRY_DELAY_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | API rate limiting configuration per API key.
    |
    */
    'rate_limit' => [
        'per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 60),
        'per_hour' => env('API_RATE_LIMIT_PER_HOUR', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default and maximum pagination limits.
    |
    */
    'pagination' => [
        'default_per_page' => env('DEFAULT_PER_PAGE', 10),
        'max_per_page' => env('MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Link Expiry
    |--------------------------------------------------------------------------
    |
    | Default expiry time for payment links in hours.
    |
    */
    'payment_link_expiry_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Settlement Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for settlement processing.
    |
    */
    'settlement' => [
        'batch_size' => 100,
        'min_amount' => 10.00,
    ],
];

