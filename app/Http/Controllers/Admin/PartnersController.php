<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $input = $request->all();
        $organization = $input['organization'] ?? ($input['organization_name'] ?? null);
        $partnerName = $input['partner_name'] ?? ($input['name'] ?? null);
        $mobile = $input['mobile'] ?? ($input['phone'] ?? null);

        $normalize = [
            'organization_name' => is_string($organization) ? trim($organization) : $organization,
            'name' => is_string($partnerName) ? trim($partnerName) : $partnerName,
            'user_name' => isset($input['user_name']) && is_string($input['user_name']) ? trim($input['user_name']) : ($input['user_name'] ?? null),
            'phone' => is_string($mobile) ? trim($mobile) : $mobile,
            'email' => isset($input['email']) && is_string($input['email']) ? strtolower(trim($input['email'])) : ($input['email'] ?? null),
            'team_name' => isset($input['team_name']) && is_string($input['team_name']) ? trim($input['team_name']) : ($input['team_name'] ?? null),
            'team_type' => isset($input['team_type']) && is_string($input['team_type']) && trim($input['team_type']) !== '' ? trim($input['team_type']) : 'Partner',
            'referral_code' => isset($input['referral_code']) && is_string($input['referral_code']) ? strtoupper(trim($input['referral_code'])) : ($input['referral_code'] ?? null),
            'ref' => isset($input['ref']) && is_string($input['ref']) ? trim($input['ref']) : ($input['ref'] ?? null),
            'whitelabel_url' => isset($input['whitelabel_url']) && is_string($input['whitelabel_url']) ? trim($input['whitelabel_url']) : ($input['whitelabel_url'] ?? null),
            'registration_date' => isset($input['registration_date']) && is_string($input['registration_date']) ? trim($input['registration_date']) : ($input['registration_date'] ?? null),
            'notes' => isset($input['notes']) && is_string($input['notes']) ? trim($input['notes']) : ($input['notes'] ?? null),
            'is_approved' => $input['is_approved'] ?? null,
            'is_internal' => $input['is_internal'] ?? null,
        ];

        $validator = Validator::make($normalize, [
            'organization_name' => 'required|string|max:255',
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'user_name' => 'required|alpha_dash|max:100|unique:users,name',
            'team_name' => 'nullable|string|max:255',
            'team_type' => 'nullable|string|max:255',
            'phone' => ['required', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:partners,phone'],
            'email' => 'required|email|max:255|unique:partners,email',
            'is_approved' => 'required|boolean',
            'is_internal' => 'required|boolean',
            'referral_code' => ['nullable', 'string', 'size:8', 'regex:/^[A-Z0-9]{8}$/', 'unique:partners,referral_code'],
            'whitelabel_url' => 'nullable|url|max:500',
            'registration_date' => 'nullable|date_format:d-m-Y',
            'ref' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ], [
            'organization_name.required' => 'Organization is required.',
            'name.required' => 'Partner name is required.',
            'name.regex' => 'Partner name may contain only letters and spaces.',
            'user_name.required' => 'User name is required.',
            'user_name.alpha_dash' => 'User name may only contain letters, numbers, dashes and underscores.',
            'user_name.max' => 'User name may not be greater than 100 characters.',
            'user_name.unique' => 'User name already exists.',
            'phone.required' => 'Mobile number is required.',
            'phone.regex' => 'Mobile must be in valid.',
            'phone.unique' => 'Mobile number already exists.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email already exists.',
            'is_approved.required' => 'Approval status is required.',
            'is_internal.required' => 'Internal status is required.',
            'referral_code.size' => 'Referral code must be exactly 8 characters.',
            'referral_code.regex' => 'Referral code must contain only uppercase letters and numbers.',
            'referral_code.unique' => 'Referral code already exists.',
            'registration_date.date_format' => 'Registration date must be in d-m-Y format.',
            'ref.max' => 'Ref may not be greater than 100 characters.',
            'notes.max' => 'Notes may not be greater than 1000 characters.',
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
            do {
                $data['referral_code'] = strtoupper(Str::random(8));
            } while (Partner::where('referral_code', $data['referral_code'])->exists());
        }

        $data['team_type'] = $data['team_type'] ?? 'Partner';
        $data['registration_date'] = !empty($data['registration_date'])
            ? Carbon::createFromFormat('d-m-Y', $data['registration_date'])->toDateString()
            : now()->toDateString();

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
