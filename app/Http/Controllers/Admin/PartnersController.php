<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PartnersController extends Controller
{
    /**
     * Display the partners management page.
     */
    public function index(): View
    {
        return view('admin.partners.index');
    }


    /**
     * Get partners data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 5), 50);

        $query = Partner::query();

        // Filters
        if ($request->filled('id')) {
            $query->where('id', $request->get('id'));
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }

        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', '%' . $request->get('team_name') . '%');
        }

        if ($request->filled('team_type')) {
            $query->where('team_type', 'like', '%' . $request->get('team_type') . '%');
        }

        if ($request->filled('organization_name') && $request->get('organization_name') !== 'all') {
            $query->where('organization_name', 'like', '%' . $request->get('organization_name') . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->get('phone') . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->get('email') . '%');
        }

        if ($request->filled('is_approved') && $request->get('is_approved') !== 'all') {
            $isApproved = in_array(strtolower($request->get('is_approved')), ['y', 'yes', '1', 'true']);
            $query->where('is_approved', $isApproved);
        }

        if ($request->filled('is_internal') && $request->get('is_internal') !== 'all') {
            $isInternal = in_array(strtolower($request->get('is_internal')), ['y', 'yes', '1', 'true']);
            $query->where('is_internal', $isInternal);
        }

        if ($request->filled('referral_code')) {
            $query->where('referral_code', 'like', '%' . $request->get('referral_code') . '%');
        }

        if ($request->filled('whitelabel_url')) {
            $query->where('whitelabel_url', 'like', '%' . $request->get('whitelabel_url') . '%');
        }

        if ($request->filled('registration_date')) {
            $query->whereDate('registration_date', $request->get('registration_date'));
        }

        if ($request->filled('ref')) {
            $query->where('ref', 'like', '%' . $request->get('ref') . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $partners = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $partners->items(),
            'pagination' => [
                'current_page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
                'last_page' => $partners->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created partner.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_name' => 'nullable|string|max:255',
            'team_name' => 'nullable|string|max:255',
            'team_type' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:partners,email',
            'is_approved' => 'boolean',
            'is_internal' => 'boolean',
            'referral_code' => 'nullable|string|max:255|unique:partners,referral_code',
            'whitelabel_url' => 'nullable|url|max:500',
            'registration_date' => 'nullable|date',
            'ref' => 'nullable|string|max:255',
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

        // Generate referral code if not provided
        if (empty($data['referral_code'])) {
            $data['referral_code'] = Partner::generateReferralCode();
        }

        // Set default registration date if not provided
        if (empty($data['registration_date'])) {
            $data['registration_date'] = now()->toDateString();
        }

        $partner = Partner::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Partner created successfully',
            'data' => $partner,
        ]);
    }

    /**
     * Update the specified partner.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $partner = Partner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'user_name' => 'nullable|string|max:255',
            'team_name' => 'nullable|string|max:255',
            'team_type' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255|unique:partners,email,' . $id,
            'is_approved' => 'boolean',
            'is_internal' => 'boolean',
            'referral_code' => 'nullable|string|max:255|unique:partners,referral_code,' . $id,
            'whitelabel_url' => 'nullable|url|max:500',
            'registration_date' => 'nullable|date',
            'ref' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $partner->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Partner updated successfully',
            'data' => $partner->fresh(),
        ]);
    }

    /**
     * Remove the specified partner.
     */
    public function destroy($id): JsonResponse
    {
        $partner = Partner::findOrFail($id);
        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner deleted successfully',
        ]);
    }

    /**
     * Get a single partner by ID.
     */
    public function show($id): JsonResponse
    {
        $partner = Partner::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $partner,
        ]);
    }
}
