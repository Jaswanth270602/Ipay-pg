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
        $key = 'login:' . strtolower($request->input('email')) . '|' . $request->ip();

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => [__('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:60',
            'password' => 'required|string|max:50',
        ]);

        // Throttle brute-force attempts
        $key = 'login:' . strtolower($request->input('email')) . '|' . $request->ip();
        $this->ensureIsNotRateLimited($request);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
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

        // Record a failed attempt
        RateLimiter::hit($key, 60); // lock key for up to 60 seconds

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
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

