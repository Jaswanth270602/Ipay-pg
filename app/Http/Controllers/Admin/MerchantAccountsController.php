<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Role;
use App\Models\AcquirerAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MerchantAccountsController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin merchant accounts page accessed', ['user_id' => auth()->id()]);
        return view('admin.merchants.accounts');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $this->logInfo('Admin merchant accounts data requested', [
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            $perPage = min($request->get('per_page', 5), 50);
            
            $query = Merchant::query();

            // Approval status filter
            $approvalStatus = $request->get('approval_status');
            if (!empty($approvalStatus) && $approvalStatus !== 'all' && $approvalStatus !== '') {
                $query->where('approval_status', $approvalStatus);
            }

            // Merchant type filter
            $merchantType = $request->get('merchant_type');
            if (!empty($merchantType) && $merchantType !== 'all' && $merchantType !== '') {
                $query->where('merchant_type', $merchantType);
            }

            // Column filters
            if ($request->has('filter_id') && $request->get('filter_id')) {
                $query->where('id', 'like', "%{$request->get('filter_id')}%");
            }
            if ($request->has('filter_name') && $request->get('filter_name')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->get('filter_name')}%")
                      ->orWhere('legal_name', 'like', "%{$request->get('filter_name')}%");
                });
            }
            if ($request->has('filter_email') && $request->get('filter_email')) {
                $query->where('email', 'like', "%{$request->get('filter_email')}%");
            }
            if ($request->has('filter_phone') && $request->get('filter_phone')) {
                $query->where('phone', 'like', "%{$request->get('filter_phone')}%");
            }
            if ($request->has('filter_status') && $request->get('filter_status') !== 'all') {
                $query->where('approval_status', $request->get('filter_status'));
            }
            if ($request->has('filter_partner') && $request->get('filter_partner')) {
                $query->where('partner_name', 'like', "%{$request->get('filter_partner')}%");
            }
            if ($request->has('filter_organization') && $request->get('filter_organization')) {
                $query->where('organization_name', 'like', "%{$request->get('filter_organization')}%");
            }
            if ($request->has('filter_category') && $request->get('filter_category') !== 'all') {
                $query->where('merchant_category', $request->get('filter_category'));
            }
            if ($request->has('filter_registration_date') && $request->get('filter_registration_date')) {
                $query->whereDate('registration_date', $request->get('filter_registration_date'));
            }
            if ($request->has('filter_challan_urn') && $request->get('filter_challan_urn')) {
                $query->where('challan_urn', 'like', "%{$request->get('filter_challan_urn')}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'name', 'email', 'phone', 'approval_status', 'partner_name', 'organization_name', 'merchant_category', 'registration_date', 'challan_urn'])) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->latest();
            }

            $merchants = $query->with('acquirerAccount')->paginate($perPage);

            $this->logDebug('Admin merchant accounts retrieved', [
                'count' => $merchants->count(),
                'total' => $merchants->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $merchants->items(),
                'pagination' => [
                    'current_page' => $merchants->currentPage(),
                    'per_page' => $merchants->perPage(),
                    'total' => $merchants->total(),
                    'last_page' => $merchants->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin merchant accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchant accounts',
            ], 500);
        }
    }

    public function getAcquirersForSelect(): JsonResponse
    {
        try {
            $acquirers = AcquirerAccount::where('is_active', true)
                ->orderBy('acquirer_name')
                ->get(['id', 'acquirer_name', 'account_id']);
            return response()->json([
                'success' => true,
                'data' => $acquirers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load acquirers',
                'data' => [],
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $merchant = Merchant::with('acquirerAccount')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $merchant,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'legal_name' => 'required|string|max:255',
                'email' => 'required|email|unique:merchants,email',
                'phone' => 'required|string|max:20',
                'merchant_category' => 'required|string',
                'team_id' => 'required_if:is_partner_merchant,true',
                'address_line_1' => 'required|string',
                'business_country' => 'required|string',
                'business_state' => 'required|string',
                'business_city' => 'required|string',
                'business_postal_code' => 'required|string',
                'merchant_pan_number' => 'required|string|max:10',
                'name_on_pan_card' => 'required|string',
                'contact_name' => 'required|string',
                'contact_mobile' => 'required|string',
                'contact_email' => 'required|email',
                'bank_account_holder_name' => 'required|string',
                'bank_account_number' => 'required|string',
                'bank_name' => 'required|string',
                'account_type' => 'required|string',
                'bank_branch' => 'required|string',
                'bank_ifsc_code' => 'required|string',
                'acquirer_account_id' => 'nullable|exists:acquirer_accounts,id',
                'login_name' => 'nullable|email|unique:users,email',
                'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'retype_password' => 'nullable|same:password',
            ], [
                'password.regex' => 'Password must have minimum 12 characters and should include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $this->logInfo('Creating new merchant account', [
                'user_id' => auth()->id(),
                'email' => $request->email
            ]);

            DB::beginTransaction();

            // Create merchant
            $merchant = Merchant::create([
                'name' => $request->name,
                'legal_name' => $request->legal_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->organization_name,
                'organization_name' => $request->organization_name,
                'merchant_category' => $request->merchant_category,
                'merchant_category_code' => $request->merchant_category_code,
                'ownership_type' => $request->ownership_type,
                'business_website' => $request->website_link,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'business_address' => $request->address_line_1 . ($request->address_line_2 ? ', ' . $request->address_line_2 : ''),
                'business_country' => $request->business_country,
                'business_state' => $request->business_state,
                'business_city' => $request->business_city,
                'business_postal_code' => $request->business_postal_code,
                'merchant_pan_number' => $request->merchant_pan_number,
                'name_on_pan_card' => $request->name_on_pan_card,
                'gst_identification_no' => $request->gst_identification_no,
                'gstin_state' => $request->gstin_state,
                'tan_no' => $request->tan_no,
                'contact_name' => $request->contact_name,
                'contact_mobile' => $request->contact_mobile,
                'contact_landline' => $request->contact_landline,
                'contact_email' => $request->contact_email,
                'is_partner_merchant' => $request->boolean('is_partner_merchant'),
                'partner_id' => $request->partner_id,
                'partner_name' => $request->partner_name,
                'team_id' => $request->team_id,
                'team_name' => $request->team_name,
                'bank_account_holder_name' => $request->bank_account_holder_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_name' => $request->bank_name,
                'account_type' => $request->account_type,
                'bank_branch' => $request->bank_branch,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'is_dummy_account' => $request->boolean('is_dummy_account'),
                'merchant_type' => in_array($request->get('merchant_type'), ['merchant', 'vendor_merchant']) ? $request->get('merchant_type') : 'merchant',
                'approval_status' => 'not_approved',
                'status' => 'inactive',
                'registration_date' => now(),
                'default_currency' => 'INR',
                'test_mode' => true,
                'settlement_cycle_domestic' => $request->get('settlement_cycle_domestic', 1),
                'settlement_cycle_international' => $request->get('settlement_cycle_international', 7),
                'acquirer_account_id' => $request->filled('acquirer_account_id') ? (int) $request->acquirer_account_id : null,
            ]);

            $merchant->acquirerAccounts()->sync($request->filled('acquirer_account_id') ? [(int) $request->acquirer_account_id] : []);

            // Always create user login for merchant
            $merchantRole = Role::where('name', 'merchant')->first();
            if (!$merchantRole) {
                throw new \RuntimeException('Merchant role not found. Please run database seeders.');
            }

            // Use login_name if provided, otherwise use merchant email
            $userEmail = $request->login_name ?? $request->email;
            
            // Check if user with this email already exists
            $existingUser = User::where('email', $userEmail)->first();
            if ($existingUser) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'A user with this email already exists. Please use a different email.',
                    'errors' => ['login_name' => ['This email is already taken']],
                ], 422);
            }

            // Generate password if not provided
            $userPassword = $request->password;
            if (empty($userPassword)) {
                // Generate a secure random password that meets requirements
                $userPassword = Str::random(10) . 'A1!'; // Ensure it meets requirements
            }
            
            $user = User::create([
                'name' => $request->name,
                'email' => $userEmail,
                'password' => Hash::make($userPassword),
                'role_id' => $merchantRole->id,
                'merchant_id' => $merchant->id,
                'status' => 'active',
                'email_verified_at' => now(), // Auto-verify merchant emails so they can log in immediately
                'timezone' => 'Asia/Kolkata', // Default timezone
            ]);
            
            $this->logInfo('User created for merchant', [
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'email' => $user->email,
                'email_verified' => false,
            ]);

            DB::commit();

            $this->logInfo('Merchant account created successfully', [
                'merchant_id' => $merchant->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant account created successfully',
                'data' => $merchant,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error creating merchant account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create merchant account: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'legal_name' => 'required|string|max:255',
                'email' => 'required|email|unique:merchants,email,' . (int) $id,
                'phone' => 'required|string|max:20',
                'merchant_category' => 'required|string',
                'team_id' => 'required_if:is_partner_merchant,true',
                'address_line_1' => 'required|string',
                'business_country' => 'required|string',
                'business_state' => 'required|string',
                'business_city' => 'required|string',
                'business_postal_code' => 'required|string',
                'merchant_pan_number' => 'required|string|max:10',
                'name_on_pan_card' => 'required|string',
                'contact_name' => 'required|string',
                'contact_mobile' => 'required|string',
                'contact_email' => 'required|email',
                'bank_account_holder_name' => 'required|string',
                'bank_account_number' => 'required|string',
                'bank_name' => 'required|string',
                'account_type' => 'required|string',
                'bank_branch' => 'required|string',
                'bank_ifsc_code' => 'required|string',
                'acquirer_account_id' => 'nullable|exists:acquirer_accounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $merchant->name = $request->name;
            $merchant->legal_name = $request->legal_name;
            $merchant->email = $request->email;
            $merchant->phone = $request->phone;
            $merchant->company_name = $request->organization_name;
            $merchant->organization_name = $request->organization_name;
            $merchant->merchant_category = $request->merchant_category;
            $merchant->merchant_category_code = $request->merchant_category_code;
            $merchant->ownership_type = $request->ownership_type;
            $merchant->business_website = $request->website_link;
            $merchant->address_line_1 = $request->address_line_1;
            $merchant->address_line_2 = $request->address_line_2;
            $merchant->business_address = $request->address_line_1 . ($request->address_line_2 ? ', ' . $request->address_line_2 : '');
            $merchant->business_country = $request->business_country;
            $merchant->business_state = $request->business_state;
            $merchant->business_city = $request->business_city;
            $merchant->business_postal_code = $request->business_postal_code;
            $merchant->merchant_pan_number = $request->merchant_pan_number;
            $merchant->name_on_pan_card = $request->name_on_pan_card;
            $merchant->gst_identification_no = $request->gst_identification_no;
            $merchant->gstin_state = $request->gstin_state;
            $merchant->tan_no = $request->tan_no;
            $merchant->contact_name = $request->contact_name;
            $merchant->contact_mobile = $request->contact_mobile;
            $merchant->contact_landline = $request->contact_landline;
            $merchant->contact_email = $request->contact_email;
            $merchant->is_partner_merchant = $request->boolean('is_partner_merchant');
            $merchant->partner_id = $request->partner_id;
            $merchant->partner_name = $request->partner_name;
            $merchant->team_id = $request->team_id;
            $merchant->team_name = $request->team_name;
            $merchant->bank_account_holder_name = $request->bank_account_holder_name;
            $merchant->bank_account_number = $request->bank_account_number;
            $merchant->bank_name = $request->bank_name;
            $merchant->account_type = $request->account_type;
            $merchant->bank_branch = $request->bank_branch;
            $merchant->bank_ifsc_code = $request->bank_ifsc_code;
            $merchant->is_dummy_account = $request->boolean('is_dummy_account');
            $merchant->merchant_type = in_array($request->get('merchant_type'), ['merchant', 'vendor_merchant']) ? $request->get('merchant_type') : 'merchant';
            $merchant->settlement_cycle_domestic = $request->get('settlement_cycle_domestic', 1);
            $merchant->settlement_cycle_international = $request->get('settlement_cycle_international', 7);
            $merchant->acquirer_account_id = $request->filled('acquirer_account_id') ? (int) $request->acquirer_account_id : null;
            $merchant->save();

            $merchant->acquirerAccounts()->sync($request->filled('acquirer_account_id') ? [(int) $request->acquirer_account_id] : []);

            $this->logInfo('Merchant account updated', ['merchant_id' => $merchant->id]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant updated successfully',
                'data' => $merchant->fresh(['acquirerAccount']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logError('Error updating merchant account', [
                'merchant_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update merchant: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);
            
            // Update approval_status if provided
            if ($request->has('approval_status')) {
                $merchant->approval_status = $request->input('approval_status');
            }
            
            // Update status (active/inactive) if provided
            if ($request->has('status')) {
                $merchant->status = $request->input('status');
            }
            
            $merchant->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    public function updateSettings(Request $request, $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'settlement_cycle_domestic' => 'nullable|integer|min:1|max:7',
                'settlement_cycle_international' => 'nullable|integer|min:1|max:7',
                'fee_percentage' => 'nullable|numeric|min:0|max:100',
                'fee_flat' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->has('settlement_cycle_domestic')) {
                $merchant->settlement_cycle_domestic = $request->input('settlement_cycle_domestic');
            }
            
            if ($request->has('settlement_cycle_international')) {
                $merchant->settlement_cycle_international = $request->input('settlement_cycle_international');
            }

            if ($request->has('fee_percentage')) {
                $merchant->fee_percentage = $request->input('fee_percentage');
            }

            if ($request->has('fee_flat')) {
                $merchant->fee_flat = $request->input('fee_flat');
            }

            $merchant->save();

            $this->logInfo('Merchant settings updated', [
                'merchant_id' => $merchant->id,
                'settings' => $request->only(['settlement_cycle_domestic', 'settlement_cycle_international', 'fee_percentage', 'fee_flat'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant settings updated successfully',
                'data' => $merchant->fresh(),
            ]);
        } catch (\Exception $e) {
            $this->logError('Error updating merchant settings', [
                'merchant_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update merchant settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate($id): JsonResponse
    {
        try {
            $original = Merchant::findOrFail($id);
            
            $duplicate = $original->replicate();
            $duplicate->name = $original->name . ' (Copy)';
            $duplicate->email = 'copy_' . time() . '_' . $original->email;
            $duplicate->approval_status = 'not_approved';
            $duplicate->status = 'inactive';
            $duplicate->registration_date = now();
            $duplicate->save();

            $this->logInfo('Merchant account duplicated', [
                'original_id' => $id,
                'duplicate_id' => $duplicate->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant account duplicated successfully',
                'data' => $duplicate,
            ]);
        } catch (\Exception $e) {
            $this->logError('Error duplicating merchant account', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate merchant account',
            ], 500);
        }
    }
}



