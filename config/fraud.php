<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fraud Rule Weights
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'ip_reputation' => 25,
        'velocity_ip' => 20,
        'failed_attempts' => 20,
        'device_fingerprint' => 15,
        'amount_anomaly' => 15,
        'geo_mismatch' => 15,
        'historical_flags' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Decision Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'allow_max' => (int) env('FRAUD_ALLOW_MAX', 30),
        'review_max' => (int) env('FRAUD_REVIEW_MAX', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Velocity / Cache Tuning
    |--------------------------------------------------------------------------
    */
    'velocity' => [
        'window_seconds' => (int) env('FRAUD_VELOCITY_WINDOW_SECONDS', 600),
        'max_txns_per_ip' => (int) env('FRAUD_MAX_TXNS_PER_IP', 8),
        'max_failed_attempts' => (int) env('FRAUD_MAX_FAILED_ATTEMPTS', 5),
    ],

    'cache_ttl_seconds' => [
        'user_average' => 600,
        'historical_flags' => 300,
        'country_last_seen' => 86400,
    ],
];
