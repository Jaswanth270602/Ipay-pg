<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReseller
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        if (! auth()->user()->isReseller() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Reseller access required.');
        }

        return $next($request);
    }
}

