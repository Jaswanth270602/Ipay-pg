<?php

namespace App\Http\Middleware;

use App\Services\ApiCredentialValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict API authentication (shared logic with ApiCredentialValidator).
 */
class ApiKeyAuthMiddleware
{
    public function __construct(
        protected ApiCredentialValidator $credentialValidator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->credentialValidator->authenticateApiRequest($request);

        if (!$result['ok']) {
            return response()->json([
                'status' => 'ERROR',
                'message' => $result['error'],
            ], $result['http_status']);
        }

        $request->merge([
            'api_merchant' => $result['merchant'],
            'api_key_mode' => $result['mode'],
            'payment_auth_mode' => $result['mode'],
        ]);

        auth()->onceUsingId($result['merchant']->users()->first()?->id);

        return $next($request);
    }
}
