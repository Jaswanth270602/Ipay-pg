<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantVendor;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $merchant = $user->merchant;
        $apiKeys = $merchant->apiKeys()->latest()->get();
        $vendors = MerchantVendor::where('merchant_id', $merchant->id)
            ->where('status', 'approved')
            ->orderBy('vendor_name')
            ->get();
        $settings = $merchant->settings ?? [];

        return view('merchant.settings.index', compact('merchant', 'apiKeys', 'vendors', 'settings'));
    }

    public function switchMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:test,live']);
        $merchant = $request->user()->merchant;
        $merchant->test_mode = $request->mode === 'test';
        $merchant->save();

        return response()->json(['success' => true, 'mode' => $request->mode]);
    }

    public function updateWebhook(Request $request)
    {
        $request->validate(['webhook_url' => 'required|url|max:500']);
        $merchant = $request->user()->merchant;
        $merchant->webhook_url = $request->webhook_url;
        $merchant->save();

        return back()->with('success', 'Webhook URL saved');
    }

    /**
     * Default split: primary merchant + share to an approved vendor (stored in merchant.settings).
     */
    public function updateSplit(Request $request)
    {
        $merchant = $request->user()->merchant;

        $request->validate([
            'split_merchant_vendor_id' => 'nullable|integer',
            'split_mode' => 'required|in:percentage,fixed,none',
            'split_secondary_percentage' => 'nullable|numeric|min:0.01|max:100',
            'split_secondary_amount' => 'nullable|numeric|min:0.01',
        ]);

        $settings = is_array($merchant->settings) ? $merchant->settings : [];

        unset($settings['split_merchant_vendor_id'], $settings['split_secondary_percentage'], $settings['split_secondary_amount']);

        if ($request->input('split_mode') === 'none' || ! $request->filled('split_merchant_vendor_id')) {
            $merchant->settings = $settings;
            $merchant->save();

            return back()->with('success', 'Vendor split disabled. New payments will be 100% to your merchant account.');
        }

        $vendorId = (int) $request->input('split_merchant_vendor_id');
        $owns = MerchantVendor::where('id', $vendorId)
            ->where('merchant_id', $merchant->id)
            ->where('status', 'approved')
            ->exists();

        if (! $owns) {
            return back()->withErrors(['split_merchant_vendor_id' => 'Select a valid approved vendor.'])->withInput();
        }

        $settings['split_merchant_vendor_id'] = $vendorId;

        if ($request->input('split_mode') === 'fixed') {
            $request->validate(['split_secondary_amount' => 'required|numeric|min:0.01']);
            $settings['split_secondary_amount'] = (float) $request->input('split_secondary_amount');
        } elseif ($request->input('split_mode') === 'percentage') {
            $request->validate(['split_secondary_percentage' => 'required|numeric|min:0.01|max:100']);
            $settings['split_secondary_percentage'] = (float) $request->input('split_secondary_percentage');
        }

        $merchant->settings = $settings;
        $merchant->save();

        return back()->with('success', 'Payment split settings saved. New successful payments will allocate to the vendor as configured.');
    }
}
