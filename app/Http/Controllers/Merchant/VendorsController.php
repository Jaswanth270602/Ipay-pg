<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantVendor;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class VendorsController extends Controller
{
    public function index(): View
    {
        return view('merchant.vendors.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        if (!$merchant) {
            return response()->json(['success' => false, 'message' => 'Merchant not found'], 404);
        }

        $vendors = MerchantVendor::query()
            ->where('merchant_id', $merchant->id)
            ->orderByDesc('id')
            ->get();

        $data = $vendors->map(function (MerchantVendor $vendor) use ($merchant) {
            $paymentLinksCount = PaymentLink::where('merchant_id', $merchant->id)
                ->where('vendor_id', $vendor->id)
                ->count();

            $ordersCount = Order::where('merchant_id', $merchant->id)
                ->whereHas('paymentLink', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->count();

            $collectedAmount = (float) Transaction::where('merchant_id', $merchant->id)
                ->where('status', 'success')
                ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->sum('amount');

            $settledAmount = (float) Transaction::where('merchant_id', $merchant->id)
                ->where('status', 'success')
                ->where('settlement_status', 'settled')
                ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->sum('amount');

            $balanceAmount = max(0, $collectedAmount - $settledAmount);
            $refundsCount = Refund::where('merchant_id', $merchant->id)
                ->whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->count();

            $refundAmount = (float) Refund::where('merchant_id', $merchant->id)
                ->whereIn('status', ['completed', 'approved', 'processing', 'pending', 'pending_approval', 'pending_processing'])
                ->whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->sum('amount');

            $kycComplete = $this->isKycComplete($vendor);

            return [
                'id' => $vendor->id,
                'vendor_code' => $vendor->vendor_code,
                'vendor_name' => $vendor->vendor_name,
                'vendor_email' => $vendor->vendor_email,
                'vendor_phone' => $vendor->vendor_phone,
                'status' => $vendor->status,
                'kyc_complete' => $kycComplete,
                'kyc_verified' => (bool) $vendor->kyc_verified,
                'kyc_verified_at' => optional($vendor->kyc_verified_at)?->toIso8601String(),
                'payment_links_count' => $paymentLinksCount,
                'orders_count' => $ordersCount,
                'collected_amount' => round($collectedAmount, 2),
                'settled_amount' => round($settledAmount, 2),
                'balance_amount' => round($balanceAmount, 2),
                'refunds_count' => $refundsCount,
                'refund_amount' => round($refundAmount, 2),
                'vendor_login_id' => $vendor->vendor_login_id,
                'created_at' => optional($vendor->created_at)?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        if (!$merchant) {
            return response()->json(['success' => false, 'message' => 'Merchant not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'vendor_code' => 'required|string|max:255|unique:merchant_vendors,vendor_code',
            'vendor_name' => 'required|string|max:255',
            'vendor_email' => 'required|email|max:255',
            'vendor_phone' => 'required|digits:10',
            'vendor_address' => 'required|string|max:500',
            'vendor_pan_no' => 'required|string|max:20',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_ifsc' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_holder_name' => 'required|string|max:255',
            'account_type' => 'required|in:Savings Account,Current Account',
            'upi_id' => 'nullable|string|max:255',
            'vendor_login_id' => 'required|string|max:255|unique:merchant_vendors,vendor_login_id',
            'vendor_password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $payload['merchant_id'] = $merchant->id;
        $payload['status'] = 'pending';
        // Merchant-entered vendor form itself is the KYC capture step.
        $payload['kyc_verified'] = true;
        $payload['kyc_verified_at'] = now();
        $payload['password'] = $payload['vendor_password'];
        unset($payload['vendor_password']);

        $vendor = MerchantVendor::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully. Awaiting admin approval.',
            'data' => $vendor,
        ]);
    }

    public function verifyKyc(Request $request, int $id): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $vendor = MerchantVendor::where('merchant_id', optional($merchant)->id)->findOrFail($id);

        if (!$this->isKycComplete($vendor)) {
            return response()->json([
                'success' => false,
                'message' => 'KYC details are incomplete. Fill required PAN, bank and contact fields first.',
            ], 422);
        }

        $vendor->kyc_verified = true;
        $vendor->kyc_verified_at = now();
        if ($vendor->status === 'disapproved') {
            $vendor->status = 'pending';
        }
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'KYC marked as verified and submitted for approval.',
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $vendor = MerchantVendor::where('merchant_id', optional($merchant)->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $vendor = MerchantVendor::where('merchant_id', optional($merchant)->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'required|string|max:255',
            'vendor_email' => 'required|email|max:255',
            'vendor_phone' => 'required|digits:10',
            'vendor_address' => 'required|string|max:500',
            'vendor_pan_no' => 'required|string|max:20',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_ifsc' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_holder_name' => 'required|string|max:255',
            'account_type' => 'required|in:Savings Account,Current Account',
            'upi_id' => 'nullable|string|max:255',
            'vendor_login_id' => 'required|string|max:255|unique:merchant_vendors,vendor_login_id,' . $vendor->id,
            'vendor_password' => [
                'nullable',
                'string',
                'min:8',
                'max:100',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        if (!empty($payload['vendor_password'])) {
            $payload['password'] = $payload['vendor_password'];
        }
        unset($payload['vendor_password']);
        $payload['kyc_verified'] = true;
        $payload['kyc_verified_at'] = now();

        $vendor->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully.',
            'data' => $vendor->fresh(),
        ]);
    }

    private function isKycComplete(MerchantVendor $vendor): bool
    {
        return !empty($vendor->vendor_name)
            && !empty($vendor->vendor_email)
            && !empty($vendor->vendor_phone)
            && !empty($vendor->vendor_address)
            && !empty($vendor->vendor_pan_no)
            && !empty($vendor->bank_account_number)
            && !empty($vendor->bank_account_ifsc)
            && !empty($vendor->bank_account_holder_name)
            && !empty($vendor->bank_name)
            && !empty($vendor->bank_branch);
    }
}

