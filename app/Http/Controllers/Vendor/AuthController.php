<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('vendor.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'vendor_login_id' => 'required|string|max:255',
            'password' => 'required|string|max:100',
        ]);

        $key = 'vendor-login:' . strtolower($credentials['vendor_login_id']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'vendor_login_id' => [__('Too many login attempts. Try again in :seconds seconds.', ['seconds' => $seconds])],
            ]);
        }

        if (Auth::guard('vendor')->attempt([
            'vendor_login_id' => $credentials['vendor_login_id'],
            'password' => $credentials['password'],
            'status' => 'approved',
            'kyc_verified' => 1,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            return redirect()->route('vendor.dashboard');
        }

        RateLimiter::hit($key, 60);
        return back()->withErrors([
            'vendor_login_id' => 'Invalid credentials, or vendor not approved/KYC verified yet.',
        ])->onlyInput('vendor_login_id');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

