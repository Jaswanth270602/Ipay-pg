<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogPaymentOperations
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log payment-related operations
        if ($request->is('api/v1/payment*') || $request->is('api/v1/refunds*')) {
            $data = [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'merchant_id' => $request->user()?->merchant_id,
                'status_code' => $response->getStatusCode(),
                'timestamp' => now()->toIso8601String(),
            ];

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::channel('payments')->info('Payment Operation Success', $data);
            } else {
                Log::channel('payments')->error('Payment Operation Failed', array_merge($data, [
                    'error' => $response->getContent(),
                ]));
            }
        }

        return $response;
    }
}

