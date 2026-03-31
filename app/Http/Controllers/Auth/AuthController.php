<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * Also send cache-control headers so that browsers do not cache this page.
     * This prevents the Back button from showing a stale login screen after
     * the user has already logged in.
     */
    public function showLogin()
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Ensure login is not rate limited (too many attempts).
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        $key = 'login:' . strtolower((string) $request->input('login')) . '|' . $request->ip();

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'login' => [__('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:100',
        ]);

        // Throttle brute-force attempts
        $key = 'login:' . strtolower((string) $request->input('login')) . '|' . $request->ip();
        $this->ensureIsNotRateLimited($request);

        $login = (string) $validated['login'];
        $password = (string) $validated['password'];
        $remember = $request->filled('remember');

        // Try merchant/admin user login (email-based)
        if (filter_var($login, FILTER_VALIDATE_EMAIL) && Auth::attempt(['email' => $login, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            // Clear rate limiter on successful login
            RateLimiter::clear($key);

            $user = Auth::user();

            // Update last login time
            $user->last_login_at = now();
            $user->save();

            // Check role_id and redirect accordingly
            if ($user->role_id === 1) {
                return redirect()->intended(route('admin.dashboard'));
            }

            // For merchants or normal users
            return redirect()->intended(route('dashboard'));
        }

        // Try vendor login using vendor_login_id on the same form
        if (Auth::guard('vendor')->attempt([
            'vendor_login_id' => $login,
            'password' => $password,
            'status' => 'approved',
        ], $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            return redirect()->intended(route('vendor.dashboard'));
        }

        // Also allow vendor login by vendor email in the same form
        if (Auth::guard('vendor')->attempt([
            'vendor_email' => $login,
            'password' => $password,
            'status' => 'approved',
        ], $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            return redirect()->intended(route('vendor.dashboard'));
        }

        // Record a failed attempt
        RateLimiter::hit($key, 60); // lock key for up to 60 seconds

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

