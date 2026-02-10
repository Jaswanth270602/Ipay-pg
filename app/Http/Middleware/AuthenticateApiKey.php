<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->bearerToken();

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required',
                'message' => 'Please provide an API key in X-API-Key header or Authorization Bearer token',
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key || !$key->isValid()) {
            return response()->json([
                'error' => 'Invalid or expired API key',
                'message' => 'The provided API key is invalid, revoked, or expired',
            ], 401);
        }

        // Validate mode matching: test keys should only work with test mode merchants
        $merchantMode = $key->merchant->test_mode ? 'test' : 'live';
        
        if ($key->mode !== $merchantMode) {
            $expectedKeyType = $merchantMode === 'test' ? 'Test mode API key (pk_test_...)' : 'Live mode API key (pk_live_...)';
            $currentMerchantMode = $merchantMode === 'test' ? 'TEST MODE' : 'LIVE MODE';
            
            return response()->json([
                'error' => 'API key mode mismatch',
                'message' => "Your merchant account is in {$currentMerchantMode}. {$expectedKeyType} is required. Please switch to the correct mode or use the appropriate API key.",
                'merchant_mode' => $merchantMode,
                'api_key_mode' => $key->mode,
            ], 403);
        }

        // Mark API key as used
        $key->markAsUsed();

        // Attach merchant to request
        $request->merge([
            'api_merchant' => $key->merchant,
            'api_key_mode' => $key->mode,
        ]);

        // Set authenticated user for logging
        auth()->onceUsingId($key->merchant->users()->first()?->id);

        return $next($request);
    }
}

