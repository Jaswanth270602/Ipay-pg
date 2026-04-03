<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendor
{
    /**
     * Restrict routes to vendor portal users (or admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = auth()->user();
        $isVendorRole = $user->role && strtolower((string) $user->role->name) === 'vendor';

        if (! $user->isAdmin() && ! $isVendorRole) {
            abort(403, 'Unauthorized. Vendor access required.');
        }

        return $next($request);
    }
}
