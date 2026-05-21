<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wallet providers (hosted checkout)
    |--------------------------------------------------------------------------
    |
    | Codes are stored on transactions.payment_details.wallet_provider.
    | Live payments are completed via acquirer SDKs (Razorpay / Cashfree).
    |
    */
    'providers' => [
        ['code' => 'paytm', 'label' => 'Paytm Wallet'],
        ['code' => 'phonepe', 'label' => 'PhonePe Wallet'],
        ['code' => 'mobikwik', 'label' => 'MobiKwik'],
        ['code' => 'freecharge', 'label' => 'Freecharge'],
        ['code' => 'amazonpay', 'label' => 'Amazon Pay Wallet'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test-mode amount shortcuts (wallet only)
    |--------------------------------------------------------------------------
    */
    'test_amounts' => [
        'success' => 101,
        'failed' => 102,
        'pending' => 103,
    ],

];
