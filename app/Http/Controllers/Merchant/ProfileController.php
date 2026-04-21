<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Support\SensitiveDataMasker;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the merchant profile page.
     */
    public function index(): View
    {
        $user = auth()->user();
        $merchant = $user->merchant;

        $maskedBankAccountNumber = SensitiveDataMasker::maskBankAccountNumber($merchant->bank_account_number);
        $maskedBankIfsc = SensitiveDataMasker::maskIfsc($merchant->bank_ifsc_code);

        return view('merchant.profile.index', compact('user', 'merchant', 'maskedBankAccountNumber', 'maskedBankIfsc'));
    }

    /**
     * Update the merchant profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $merchant = $user->merchant;

        $validator = Validator::make($request->all(), [
            // User fields
            'name' => 'required|string|min:3|max:256',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            
            // Merchant basic fields
            'company_name' => 'nullable|string|min:3|max:256',
            'phone' => ['nullable', 'string', 'min:7', 'max:16', 'regex:/^\+?[1-9][0-9]{6,14}$/'],
            'contact_email' => 'nullable|email|max:255',
            'contact_mobile' => ['nullable', 'string', 'min:7', 'max:16', 'regex:/^\+?[1-9][0-9]{6,14}$/'],
            'contact_landline' => ['nullable', 'string', 'min:7', 'max:16', 'regex:/^\+?[1-9][0-9]{6,14}$/'],
            'contact_name' => 'nullable|string|min:3|max:256',
            
            // Business Details
            'business_type' => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:500',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'business_city' => 'nullable|string|max:255',
            'business_state' => 'nullable|string|max:255',
            'business_country' => 'nullable|string|max:255',
            'business_postal_code' => ['nullable', 'string', 'min:4', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'business_website' => 'nullable|url|max:255',
            
            // Tax & Legal
            'merchant_pan_number' => 'nullable|string|max:20',
            'name_on_pan_card' => 'nullable|string|max:255',
            'gst_identification_no' => 'nullable|string|max:20',
            'gstin_state' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'business_registration_number' => 'nullable|string|max:100',
            
            // Bank Details
            'bank_account_holder_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc_code' => ['nullable', 'string', 'min:7', 'max:15', 'regex:/^[A-Za-z]{4}[A-Za-z0-9]{3,11}$/'],
            'bank_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Update user
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            // Update merchant
            $merchant->company_name = $request->company_name;
            $merchant->phone = $request->phone;
            $merchant->contact_email = $request->contact_email;
            $merchant->contact_mobile = $request->contact_mobile;
            $merchant->contact_landline = $request->contact_landline;
            $merchant->contact_name = $request->contact_name;
            $merchant->business_type = $request->business_type;
            $merchant->business_address = $request->business_address;
            $merchant->address_line_1 = $request->address_line_1;
            $merchant->address_line_2 = $request->address_line_2;
            $merchant->business_city = $request->business_city;
            $merchant->business_state = $request->business_state;
            $merchant->business_country = $request->business_country;
            $merchant->business_postal_code = $request->business_postal_code;
            $merchant->business_website = $request->business_website;
            $merchant->merchant_pan_number = $request->merchant_pan_number;
            $merchant->name_on_pan_card = $request->name_on_pan_card;
            $merchant->gst_identification_no = $request->gst_identification_no;
            $merchant->gstin_state = $request->gstin_state;
            $merchant->tax_id = $request->tax_id;
            $merchant->business_registration_number = $request->business_registration_number;
            $merchant->bank_account_holder_name = $request->bank_account_holder_name;
            // TC_03: do not overwrite bank fields with empty/masked values when not explicitly changing
            if ($request->filled('bank_account_number')) {
                $merchant->bank_account_number = $request->bank_account_number;
            }
            if ($request->filled('bank_ifsc_code')) {
                $merchant->bank_ifsc_code = $request->bank_ifsc_code;
            }
            $merchant->bank_name = $request->bank_name;
            $merchant->bank_branch = $request->bank_branch;
            $merchant->save();

            return back()->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update profile: ' . $e->getMessage())->withInput();
        }
    }
}
