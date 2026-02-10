<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerTDR;
use App\Models\Partner;
use App\Models\Merchant;
use App\Models\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PartnerTDRController extends Controller
{
    /**
     * Display the Partner TDR management page.
     */
    public function index(): View
    {
        return view('admin.partners.tdr');
    }

    /**
     * Get Partner TDR data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 5), 50);

        $query = PartnerTDR::query()->with(['partner', 'merchant']);

        // Filters
        if ($request->filled('partner_tdr_id')) {
            $query->where('id', $request->get('partner_tdr_id'));
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->get('partner_id'));
        }

        if ($request->filled('partner_name')) {
            $query->where('partner_name', 'like', '%' . $request->get('partner_name') . '%');
        }

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->get('merchant_id'));
        }

        if ($request->filled('merchant_name')) {
            $query->where('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
        }

        if ($request->filled('category') && $request->get('category') !== 'all') {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('payment_mode') && $request->get('payment_mode') !== 'all') {
            $query->where('payment_mode', $request->get('payment_mode'));
        }

        if ($request->filled('payment_channel') && $request->get('payment_channel') !== 'all') {
            $query->where('payment_channel', $request->get('payment_channel'));
        }

        if ($request->filled('bank_code')) {
            $query->where('bank_code', 'like', '%' . $request->get('bank_code') . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $tdrs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tdrs->items(),
            'pagination' => [
                'current_page' => $tdrs->currentPage(),
                'per_page' => $tdrs->perPage(),
                'total' => $tdrs->total(),
                'last_page' => $tdrs->lastPage(),
            ],
        ]);
    }

    /**
     * Get partners for dropdown.
     */
    public function getPartners(): JsonResponse
    {
        $partners = Partner::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    /**
     * Search merchants.
     */
    public function searchMerchants(Request $request): JsonResponse
    {
        $search = $request->get('search', '');

        $query = Merchant::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $merchants = $query->select('id', 'name', 'email', 'phone')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $merchants,
        ]);
    }

    /**
     * Get categories for dropdown.
     */
    public function getCategories(): JsonResponse
    {
        $categories = Merchant::whereNotNull('merchant_category')
            ->distinct()
            ->pluck('merchant_category')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get payment modes for dropdown.
     */
    public function getPaymentModes(): JsonResponse
    {
        $paymentModes = PartnerTDR::whereNotNull('payment_mode')
            ->distinct()
            ->pluck('payment_mode')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $paymentModes,
        ]);
    }

    /**
     * Get banks for dropdown.
     */
    public function getBanks(Request $request): JsonResponse
    {
        $paymentMode = $request->get('payment_mode');
        
        $query = Bank::query()->where('is_active', true);

        if ($paymentMode) {
            // You can add logic here to filter banks by payment mode if needed
        }

        $banks = $query->select('id', 'code', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $banks,
        ]);
    }

    /**
     * Store a newly created Partner TDR.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:partners,id',
            'merchant_id' => 'required|exists:merchants,id',
            'category' => 'nullable|string|max:255',
            'payment_mode' => 'nullable|string|max:255',
            'payment_channel' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:255',
            'bank_description' => 'nullable|string|max:500',
            'tdr_fixed_fee' => 'nullable|numeric|min:0',
            'tdr_percentage' => 'nullable|numeric|min:0|max:100',
            'tdr_min_amount' => 'nullable|numeric|min:0',
            'tdr_max_amount' => 'nullable|numeric|min:0',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'min_transaction_charge' => 'nullable|numeric|min:0',
            'max_transaction_charge' => 'nullable|numeric|min:0',
            'overall_profit_share_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Get partner and merchant names
        $partner = Partner::find($data['partner_id']);
        $merchant = Merchant::find($data['merchant_id']);

        $data['partner_name'] = $partner->name ?? null;
        $data['merchant_name'] = $merchant->name ?? null;

        // Set defaults
        $data['tdr_min_amount'] = $data['tdr_min_amount'] ?? 0;
        $data['tdr_max_amount'] = $data['tdr_max_amount'] ?? 99999999.99;
        $data['min_transaction_amount'] = $data['min_transaction_amount'] ?? 0;
        $data['max_transaction_amount'] = $data['max_transaction_amount'] ?? 99999999.99;
        $data['min_transaction_charge'] = $data['min_transaction_charge'] ?? 0;
        $data['max_transaction_charge'] = $data['max_transaction_charge'] ?? 99999999.99;

        $tdr = PartnerTDR::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Partner TDR created successfully',
            'data' => $tdr->fresh(['partner', 'merchant']),
        ]);
    }

    /**
     * Update the specified Partner TDR.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $tdr = PartnerTDR::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'partner_id' => 'sometimes|required|exists:partners,id',
            'merchant_id' => 'sometimes|required|exists:merchants,id',
            'category' => 'nullable|string|max:255',
            'payment_mode' => 'nullable|string|max:255',
            'payment_channel' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:255',
            'bank_description' => 'nullable|string|max:500',
            'tdr_fixed_fee' => 'nullable|numeric|min:0',
            'tdr_percentage' => 'nullable|numeric|min:0|max:100',
            'tdr_min_amount' => 'nullable|numeric|min:0',
            'tdr_max_amount' => 'nullable|numeric|min:0',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'min_transaction_charge' => 'nullable|numeric|min:0',
            'max_transaction_charge' => 'nullable|numeric|min:0',
            'overall_profit_share_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Update partner and merchant names if IDs changed
        if (isset($data['partner_id'])) {
            $partner = Partner::find($data['partner_id']);
            $data['partner_name'] = $partner->name ?? null;
        }

        if (isset($data['merchant_id'])) {
            $merchant = Merchant::find($data['merchant_id']);
            $data['merchant_name'] = $merchant->name ?? null;
        }

        $tdr->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Partner TDR updated successfully',
            'data' => $tdr->fresh(['partner', 'merchant']),
        ]);
    }

    /**
     * Remove the specified Partner TDR.
     */
    public function destroy($id): JsonResponse
    {
        $tdr = PartnerTDR::findOrFail($id);
        $tdr->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner TDR deleted successfully',
        ]);
    }

    /**
     * Duplicate a Partner TDR.
     */
    public function duplicate($id): JsonResponse
    {
        $originalTdr = PartnerTDR::findOrFail($id);
        
        $newTdr = $originalTdr->replicate();
        $newTdr->save();

        return response()->json([
            'success' => true,
            'message' => 'Partner TDR duplicated successfully',
            'data' => $newTdr->fresh(['partner', 'merchant']),
        ]);
    }

    /**
     * Get a single Partner TDR by ID.
     */
    public function show($id): JsonResponse
    {
        $tdr = PartnerTDR::with(['partner', 'merchant'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tdr,
        ]);
    }
}
