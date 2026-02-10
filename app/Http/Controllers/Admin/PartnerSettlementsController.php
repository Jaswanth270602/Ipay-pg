<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerSettlement;
use App\Models\PartnerSettlementDetail;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PartnerSettlementsController extends Controller
{
    /**
     * Display the Partner Settlement Summary page.
     */
    public function index(): View
    {
        return view('admin.partner-settlements.summary');
    }

    /**
     * Display the Partner Settlement Details page.
     */
    public function details(): View
    {
        return view('admin.partner-settlements.details');
    }

    /**
     * Get Partner Settlement Summary data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 5), 50);

        $query = PartnerSettlement::query()->with('partner');

        // Filters
        if ($request->filled('settlement_id')) {
            $query->where('settlement_id', 'like', '%' . $request->get('settlement_id') . '%');
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->get('partner_id'));
        }

        if ($request->filled('partner_name')) {
            $query->where('partner_name', 'like', '%' . $request->get('partner_name') . '%');
        }

        if ($request->filled('organization_name') && $request->get('organization_name') !== 'all') {
            $query->where('organization_name', 'like', '%' . $request->get('organization_name') . '%');
        }

        if ($request->filled('settlement_status') && $request->get('settlement_status') !== 'all') {
            $query->where('settlement_status', $request->get('settlement_status'));
        }

        if ($request->filled('settlement_date')) {
            $query->whereDate('settlement_date', $request->get('settlement_date'));
        }

        if ($request->filled('bank_reference_id')) {
            $query->where('bank_reference_id', 'like', '%' . $request->get('bank_reference_id') . '%');
        }

        if ($request->filled('account_holder_name')) {
            $query->where('account_holder_name', 'like', '%' . $request->get('account_holder_name') . '%');
        }

        if ($request->filled('account_number')) {
            $query->where('account_number', 'like', '%' . $request->get('account_number') . '%');
        }

        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%' . $request->get('bank_name') . '%');
        }

        if ($request->filled('bank_ifsc')) {
            $query->where('bank_ifsc', 'like', '%' . $request->get('bank_ifsc') . '%');
        }

        // Amount filters
        if ($request->filled('settlement_amount_min')) {
            $query->where('settlement_amount', '>=', $request->get('settlement_amount_min'));
        }

        if ($request->filled('settlement_amount_max')) {
            $query->where('settlement_amount', '<=', $request->get('settlement_amount_max'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $settlements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $settlements->items(),
            'pagination' => [
                'current_page' => $settlements->currentPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
                'last_page' => $settlements->lastPage(),
            ],
        ]);
    }

    /**
     * Get Partner Settlement Details data.
     */
    public function getDetails(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 5), 50);

        $query = PartnerSettlementDetail::query()->with(['partner', 'merchant', 'transaction']);

        // Filters
        if ($request->filled('settlement_detail_id')) {
            $query->where('id', $request->get('settlement_detail_id'));
        }

        if ($request->filled('partner_name')) {
            $query->where('partner_name', 'like', '%' . $request->get('partner_name') . '%');
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->get('partner_id'));
        }

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->get('merchant_id'));
        }

        if ($request->filled('merchant_name')) {
            $query->where('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
        }

        if ($request->filled('transaction_id')) {
            $query->where('transaction_txn_id', 'like', '%' . $request->get('transaction_id') . '%');
        }

        if ($request->filled('settlement_record_id')) {
            $query->where('settlement_record_id', 'like', '%' . $request->get('settlement_record_id') . '%');
        }

        if ($request->filled('merchant_category') && $request->get('merchant_category') !== 'all') {
            $query->where('merchant_category', $request->get('merchant_category'));
        }

        if ($request->filled('payment_mode') && $request->get('payment_mode') !== 'all') {
            $query->where('payment_mode', $request->get('payment_mode'));
        }

        if ($request->filled('bank_code')) {
            $query->where('bank_code', 'like', '%' . $request->get('bank_code') . '%');
        }

        if ($request->filled('organization_name') && $request->get('organization_name') !== 'all') {
            $query->where('organization_name', 'like', '%' . $request->get('organization_name') . '%');
        }

        if ($request->filled('payment_datetime_start')) {
            $query->where('payment_datetime', '>=', $request->get('payment_datetime_start'));
        }

        if ($request->filled('payment_datetime_end')) {
            $query->where('payment_datetime', '<=', $request->get('payment_datetime_end'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $details = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $details->items(),
            'pagination' => [
                'current_page' => $details->currentPage(),
                'per_page' => $details->perPage(),
                'total' => $details->total(),
                'last_page' => $details->lastPage(),
            ],
        ]);
    }

    /**
     * Get organizations for dropdown.
     */
    public function getOrganizations(): JsonResponse
    {
        $organizations = PartnerSettlement::whereNotNull('organization_name')
            ->distinct()
            ->pluck('organization_name')
            ->filter()
            ->values();

        // Also get from details
        $detailOrgs = PartnerSettlementDetail::whereNotNull('organization_name')
            ->distinct()
            ->pluck('organization_name')
            ->filter()
            ->values();

        $allOrgs = $organizations->merge($detailOrgs)->unique()->values();

        return response()->json([
            'success' => true,
            'data' => $allOrgs,
        ]);
    }

    /**
     * Get merchant categories for dropdown.
     */
    public function getMerchantCategories(): JsonResponse
    {
        $categories = PartnerSettlementDetail::whereNotNull('merchant_category')
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
        $modes = PartnerSettlementDetail::whereNotNull('payment_mode')
            ->distinct()
            ->pluck('payment_mode')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $modes,
        ]);
    }

    /**
     * Get partners for dropdown.
     */
    public function getPartners(): JsonResponse
    {
        $partners = Partner::select('id', 'name', 'organization_name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    /**
     * Mark settlements as settled.
     */
    public function markAsSettled(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'integer|exists:partner_settlements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = PartnerSettlement::whereIn('id', $request->get('settlement_ids'))
            ->update([
                'settlement_status' => 'settled',
                'settlement_date' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} settlement(s) marked as settled",
        ]);
    }

    /**
     * Transfer amount by IMPS.
     */
    public function transferByIMPS(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'integer|exists:partner_settlements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settlements = PartnerSettlement::whereIn('id', $request->get('settlement_ids'))->get();

        foreach ($settlements as $settlement) {
            $settlement->update([
                'transfer_method' => 'IMPS',
                'transfer_status' => 'initiated',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'IMPS transfer initiated for ' . count($settlements) . ' settlement(s)',
        ]);
    }

    /**
     * Transfer amount by NEFT.
     */
    public function transferByNEFT(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'integer|exists:partner_settlements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settlements = PartnerSettlement::whereIn('id', $request->get('settlement_ids'))->get();

        foreach ($settlements as $settlement) {
            $settlement->update([
                'transfer_method' => 'NEFT',
                'transfer_status' => 'initiated',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'NEFT transfer initiated for ' . count($settlements) . ' settlement(s)',
        ]);
    }

    /**
     * Check transfer status.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'integer|exists:partner_settlements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settlements = PartnerSettlement::whereIn('id', $request->get('settlement_ids'))
            ->select('id', 'settlement_id', 'transfer_status', 'transfer_method', 'bank_reference_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $settlements,
        ]);
    }

    /**
     * Get a single settlement by ID.
     */
    public function show($id): JsonResponse
    {
        $settlement = PartnerSettlement::with('partner')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $settlement,
        ]);
    }

    /**
     * Store a new settlement.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:partners,id',
            'organization_name' => 'nullable|string|max:255',
            'settlement_amount' => 'required|numeric|min:0',
            'net_settlement_amount' => 'nullable|numeric|min:0',
            'tds_percentage' => 'nullable|numeric|min:0|max:100',
            'tds_amount' => 'nullable|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'settlement_status' => 'nullable|in:pending,settled,bounced,processing,failed',
            'settlement_date' => 'nullable|date',
            'bank_reference_id' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $partner = Partner::find($data['partner_id']);
        $data['partner_name'] = $partner->name ?? null;
        $data['settlement_id'] = PartnerSettlement::generateSettlementId();

        if (!isset($data['net_settlement_amount'])) {
            $data['net_settlement_amount'] = $data['settlement_amount'] 
                - ($data['tds_amount'] ?? 0) 
                - ($data['gst_amount'] ?? 0);
        }

        $settlement = PartnerSettlement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Partner settlement created successfully',
            'data' => $settlement->fresh('partner'),
        ]);
    }

    /**
     * Update a settlement.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $settlement = PartnerSettlement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'partner_id' => 'sometimes|required|exists:partners,id',
            'organization_name' => 'nullable|string|max:255',
            'settlement_amount' => 'sometimes|numeric|min:0',
            'net_settlement_amount' => 'nullable|numeric|min:0',
            'tds_percentage' => 'nullable|numeric|min:0|max:100',
            'tds_amount' => 'nullable|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'settlement_status' => 'nullable|in:pending,settled,bounced,processing,failed',
            'settlement_date' => 'nullable|date',
            'bank_reference_id' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['partner_id'])) {
            $partner = Partner::find($data['partner_id']);
            $data['partner_name'] = $partner->name ?? null;
        }

        $settlement->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Partner settlement updated successfully',
            'data' => $settlement->fresh('partner'),
        ]);
    }

    /**
     * Delete a settlement.
     */
    public function destroy($id): JsonResponse
    {
        $settlement = PartnerSettlement::findOrFail($id);
        $settlement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner settlement deleted successfully',
        ]);
    }
}
