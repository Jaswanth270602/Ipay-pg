<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantVendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MerchantVendorsController extends Controller
{
    /**
     * List page.
     */
    public function index(): View
    {
        return view('admin.merchants.vendors');
    }

    /**
     * Data for table (with filters / pagination).
     */
    public function getData(Request $request): JsonResponse
    {
        $adminViewMode = session('admin_view_mode', 'test');
        $isTestMode = $adminViewMode === 'test';

        $perPage = min($request->integer('per_page', 5), 50);

        $query = MerchantVendor::query()->with('merchant');

        // Filter by merchant's test_mode based on admin view mode
        // When in live mode, only show vendors for merchants with test_mode = false
        // When in test mode, only show vendors for merchants with test_mode = true
        $query->whereHas('merchant', function ($q) use ($isTestMode) {
            $q->where('test_mode', $isTestMode);
        });

        if ($request->filled('vendor_id')) {
            $query->where('id', $request->get('vendor_id'));
        }

        if ($request->filled('vendor_name')) {
            $query->where('vendor_name', 'like', '%' . $request->get('vendor_name') . '%');
        }

        if ($request->filled('vendor_code')) {
            $query->where('vendor_code', 'like', '%' . $request->get('vendor_code') . '%');
        }

        if ($request->filled('merchant_name')) {
            $query->whereHas('merchant', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->get('merchant_name') . '%');
            });
        }

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->get('merchant_id'));
        }

        if ($request->filled('vendor_email')) {
            $query->where('vendor_email', 'like', '%' . $request->get('vendor_email') . '%');
        }

        if ($request->filled('vendor_phone')) {
            $query->where('vendor_phone', 'like', '%' . $request->get('vendor_phone') . '%');
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        $vendors = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $vendors->items(),
            'pagination' => [
                'current_page' => $vendors->currentPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
                'last_page' => $vendors->lastPage(),
            ],
        ]);
    }

    /**
     * Create vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'vendor_code' => 'required|string|max:255|unique:merchant_vendors,vendor_code',
            'vendor_name' => 'required|string|max:255',
            'vendor_email' => 'required|email|max:255',
            'vendor_phone' => 'required|string|max:20',
            'vendor_address' => 'required|string|max:500',
            'vendor_pan_no' => 'required|string|max:20',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_ifsc' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_holder_name' => 'required|string|max:255',
            'account_type' => 'required|in:Savings Account,Current Account',
            'upi_id' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'vendor_description_1' => 'nullable|string|max:255',
            'vendor_description_2' => 'nullable|string|max:255',
            'reference_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vendor = MerchantVendor::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Merchant vendor created successfully',
            'data' => $vendor->fresh('merchant'),
        ]);
    }

    /**
     * Update vendor.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $vendor = MerchantVendor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'sometimes|string|max:255',
            'vendor_email' => 'sometimes|email|max:255',
            'vendor_phone' => 'sometimes|string|max:20',
            'vendor_address' => 'sometimes|string|max:500',
            'vendor_pan_no' => 'sometimes|string|max:20',
            'bank_account_number' => 'sometimes|string|max:50',
            'bank_account_ifsc' => 'sometimes|string|max:20',
            'bank_name' => 'sometimes|string|max:255',
            'bank_branch' => 'sometimes|string|max:255',
            'bank_account_holder_name' => 'sometimes|string|max:255',
            'account_type' => 'sometimes|in:Savings Account,Current Account',
            'upi_id' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'vendor_description_1' => 'nullable|string|max:255',
            'vendor_description_2' => 'nullable|string|max:255',
            'reference_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vendor->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Merchant vendor updated successfully',
            'data' => $vendor->fresh('merchant'),
        ]);
    }

    /**
     * Delete vendor.
     */
    public function destroy($id): JsonResponse
    {
        $vendor = MerchantVendor::findOrFail($id);
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Merchant vendor deleted successfully',
        ]);
    }

    /**
     * Approve / disapprove vendors.
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        try {
            // Normalise vendor_ids to a plain array
            $ids = $request->input('vendor_ids', []);
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            $data = [
                'vendor_ids' => $ids,
                'status' => $request->input('status'),
            ];

            $validator = Validator::make($data, [
                'vendor_ids' => 'required|array',
                'vendor_ids.*' => 'integer',
                'status' => 'required|in:approved,disapproved',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No vendors selected',
                ], 400);
            }

            MerchantVendor::whereIn('id', $ids)
                ->update(['status' => $data['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper endpoint to fetch merchants for the "Merchant Name" select.
     */
    public function getMerchants(): JsonResponse
    {
        $adminViewMode = session('admin_view_mode', 'test');
        $isTestMode = $adminViewMode === 'test';

        // Filter merchants based on admin view mode
        $merchants = Merchant::select('id', 'name')
            ->where('test_mode', $isTestMode)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $merchants,
        ]);
    }
}


