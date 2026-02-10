<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Yapily Sandbox Feature Toggle
    |--------------------------------------------------------------------------
    |
    | When false, all /api/sandbox/yapily/* routes return 403 Sandbox Disabled.
    | No API calls to Yapily are made when disabled.
    |
    */
    'enabled' => env('ENABLE_YAPILY_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Yapily API (Sandbox / Dummy Bank)
    |--------------------------------------------------------------------------
    |
    | Load from .env only. Do not hardcode credentials.
    |
    */
    'base_url' => env('YAPILY_BASE_URL', 'https://api.yapily.com'),
    'app_id' => env('YAPILY_APP_ID'),
    'app_secret' => env('YAPILY_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('YAPILY_HTTP_TIMEOUT', 15),
];
