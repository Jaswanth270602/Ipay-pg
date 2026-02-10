<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /**
     * Show the merchant self-signup page.
     */
    public function showSignup(): View
    {
        return view('auth.signup');
    }

    /**
     * Handle merchant self-registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // Business basics
            'business_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'business_email' => 'required|email|unique:merchants,email',
            'business_phone' => 'required|string|regex:/^[0-9]{10,15}$/',
            'website_link' => 'nullable|url|max:255',
            'merchant_category' => 'required|string|max:100',
            'business_country' => 'required|string|max:100',
            'business_state' => 'required|string|max:100',
            'business_city' => 'required|string|max:100',
            'business_postal_code' => 'required|string|max:10',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',

            // Tax / compliance
            'merchant_pan_number' => 'required|string|size:10',
            'name_on_pan_card' => 'required|string|max:255',
            'gst_identification_no' => 'nullable|string|max:20',
            'gstin_state' => 'nullable|string|max:100',
            'tan_no' => 'nullable|string|max:20',

            // Contact
            'contact_name' => 'required|string|max:255',
            'contact_mobile' => 'required|string|regex:/^[0-9]{10,15}$/',
            'contact_email' => 'required|email',

            // Bank
            'bank_account_holder_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|min:8|max:25',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'required|string|in:Savings Account,Current Account',
            'bank_branch' => 'required|string|max:255',
            'bank_ifsc_code' => 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',

            // Login
            'login_name' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'password_confirmation' => 'required|same:password',
        ], [
            'business_phone.regex' => 'Business phone must be 10-15 digits.',
            'contact_mobile.regex' => 'Contact mobile must be 10-15 digits.',
            'merchant_pan_number.size' => 'PAN must be exactly 10 characters.',
            'bank_account_number.min' => 'Bank account number looks too short.',
            'bank_account_number.max' => 'Bank account number looks too long.',
            'bank_ifsc_code.regex' => 'Please enter a valid IFSC code (e.g. HDFC0001234).',
            'password.regex' => 'Password must have minimum 12 characters and include at least 1 uppercase, 1 lowercase, 1 number and 1 special character.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create merchant (starts in sandbox mode, pending live approval)
            $merchant = Merchant::create([
                'name' => $request->business_name,
                'legal_name' => $request->legal_name,
                'email' => $request->business_email,
                'phone' => $request->business_phone,
                'company_name' => $request->business_name,
                'organization_name' => $request->business_name,
                'merchant_category' => $request->merchant_category,
                'business_website' => $request->website_link,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'business_address' => $request->address_line_1 . ($request->address_line_2 ? ', ' . $request->address_line_2 : ''),
                'business_country' => $request->business_country,
                'business_state' => $request->business_state,
                'business_city' => $request->business_city,
                'business_postal_code' => $request->business_postal_code,
                'merchant_pan_number' => strtoupper($request->merchant_pan_number),
                'name_on_pan_card' => $request->name_on_pan_card,
                'gst_identification_no' => $request->gst_identification_no,
                'gstin_state' => $request->gstin_state,
                'tan_no' => $request->tan_no,
                'contact_name' => $request->contact_name,
                'contact_mobile' => $request->contact_mobile,
                'contact_email' => $request->contact_email,
                'bank_account_holder_name' => $request->bank_account_holder_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_name' => $request->bank_name,
                'account_type' => $request->account_type,
                'bank_branch' => $request->bank_branch,
                'bank_ifsc_code' => strtoupper($request->bank_ifsc_code),
                'merchant_type' => 'merchant',
                'approval_status' => 'not_approved',
                'status' => 'inactive',
                'registration_date' => now(),
                'default_currency' => 'INR',
                'test_mode' => true,
            ]);

            // Create merchant user
            $merchantRole = Role::where('name', 'merchant')->first();

            $user = User::create([
                'name' => $request->contact_name,
                'email' => $request->login_name,
                'password' => Hash::make($request->password),
                'role_id' => $merchantRole ? $merchantRole->id : null,
                'merchant_id' => $merchant->id,
                'status' => 'active',
                'email_verified_at' => now(), // Auto-verify merchant emails so they can log in immediately
                'timezone' => 'Asia/Kolkata', // Default timezone
            ]);

            DB::commit();

            Log::info('Merchant self-registration successful', [
                'merchant_id' => $merchant->id,
                'user_id' => $user->id,
            ]);

            return redirect()
                ->route('login')
                ->with('success', 'Your ' . config('app.name') . ' merchant account has been created. You can log in now and use the full sandbox environment while our team reviews and enables live payments.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Merchant self-registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong while creating your account. Please try again or contact support.');
        }
    }
}


