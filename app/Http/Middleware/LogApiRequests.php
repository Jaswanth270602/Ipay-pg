<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log request
        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'merchant_id' => $request->user()?->merchant_id,
            'timestamp' => now()->toIso8601String(),
        ]);

        $response = $next($request);

        // Calculate processing time
        $processingTime = (microtime(true) - $startTime) * 1000; // in milliseconds

        // Log response
        Log::channel('api')->info('API Response', [
            'status_code' => $response->getStatusCode(),
            'processing_time_ms' => round($processingTime, 2),
            'url' => $request->fullUrl(),
        ]);

        return $response;
    }
}

