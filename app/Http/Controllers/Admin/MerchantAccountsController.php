<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use App\Models\Partner;
use App\Models\Reseller;
use App\Models\User;
use App\Models\Role;
use App\Models\AcquirerAccount;
use App\Support\SensitiveDataMasker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
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
            $hasResellerTables = $this->hasResellerTables();
            $this->logInfo('Admin merchant accounts data requested', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'has_reseller_tables' => $hasResellerTables,
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
            if ($request->has('filter_merchant_unique_id') && $request->get('filter_merchant_unique_id')) {
                $query->where('merchant_unique_id', 'like', "%{$request->get('filter_merchant_unique_id')}%");
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
            if ($request->has('filter_account_status') && $request->get('filter_account_status') !== 'all') {
                $query->where('status', $request->get('filter_account_status'));
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
            if ($request->has('filter_acquirer') && $request->get('filter_acquirer')) {
                $search = $request->get('filter_acquirer');
                $query->whereHas('acquirerAccount', function ($q) use ($search) {
                    $q->where('acquirer_name', 'like', "%{$search}%")
                      ->orWhere('mode', 'like', "%{$search}%");
                });
            }
            // Registration date filter (single date from calendar)
            if ($request->has('filter_registration_date') && $request->get('filter_registration_date')) {
                try {
                    $rawDate = (string) $request->get('filter_registration_date');
                    // Prefer literal YYYY-MM-DD to avoid timezone shifts from ISO datetime strings.
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) === 1) {
                        $date = $rawDate;
                    } else {
                        $date = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
                    }
                    $query->whereDate('registration_date', $date);
                } catch (\Exception $e) {
                    // Ignore invalid date
                }
            }
            if ($request->has('filter_challan_urn') && $request->get('filter_challan_urn')) {
                $query->where('challan_urn', 'like', "%{$request->get('filter_challan_urn')}%");
            }
            if ($hasResellerTables && $request->filled('filter_reseller_id')) {
                $resellerFilter = $request->get('filter_reseller_id');
                if ($resellerFilter === 'none') {
                    $query->whereDoesntHave('resellers');
                } elseif (is_numeric($resellerFilter)) {
                    $rid = (int) $resellerFilter;
                    $query->whereHas('resellers', function ($q) use ($rid) {
                        $q->where('resellers.id', $rid);
                    });
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'merchant_unique_id', 'name', 'email', 'phone', 'approval_status', 'partner_name', 'organization_name', 'merchant_category', 'registration_date', 'challan_urn'])) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->latest();
            }

            $relations = ['acquirerAccount'];
            if ($hasResellerTables) {
                $relations[] = 'resellers';
                $relations[] = 'reseller';
            }
            $merchants = $query->with($relations)->paginate($perPage);

            // TC_03: mask bank details in list payload (full values via show() for edit)
            $merchants->getCollection()->transform(function (Merchant $merchant) use ($hasResellerTables) {
                $data = SensitiveDataMasker::maskMerchantAttributes($merchant);
                $data['reseller'] = $hasResellerTables && $merchant->relationLoaded('reseller') && $merchant->reseller
                    ? [
                        'id' => $merchant->reseller->id,
                        'name' => $merchant->reseller->name,
                        'company_name' => $merchant->reseller->company_name,
                        'email' => $merchant->reseller->email,
                    ]
                    : null;
                $data['resellers'] = $hasResellerTables && $merchant->relationLoaded('resellers')
                    ? $merchant->resellers->map(function (Reseller $r) {
                        return [
                            'id' => $r->id,
                            'name' => $r->name,
                            'company_name' => $r->company_name,
                            'email' => $r->email,
                        ];
                    })->values()->all()
                    : [];

                return $data;
            });

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
                    'from' => $merchants->firstItem(),
                    'to' => $merchants->lastItem(),
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

    public function getResellersForSelect(): JsonResponse
    {
        try {
            if (! $this->hasResellerTables()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $resellers = Reseller::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'company_name']);

            return response()->json([
                'success' => true,
                'data' => $resellers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load resellers',
                'data' => [],
            ], 500);
        }
    }

    public function getLocationsForSelect(): JsonResponse
    {
        try {
            $path = database_path('data/merchant_locations_dataset.json');
            $locations = [];

            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    $locations = $decoded;
                }
            }

            if ($locations === []) {
                $fallback = config('merchant_locations', []);
                $locations = is_array($fallback) ? $fallback : [];
            }

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locations',
                'data' => [],
            ], 500);
        }
    }

    public function getPartnersForSelect(): JsonResponse
    {
        try {
            if (! Schema::hasTable('partners')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $partners = Partner::query()
                ->orderBy('name')
                ->get(['id', 'name', 'team_name']);

            $data = $partners->map(function (Partner $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'team_name' => $p->team_name,
                    'teams' => $this->parsePartnerTeamNames($p->team_name),
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load partners',
                'data' => [],
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $relations = ['acquirerAccount'];
            if ($this->hasResellerTables()) {
                $relations[] = 'resellers';
                $relations[] = 'reseller';
            }
            $merchant = Merchant::with($relations)->findOrFail($id);
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
            $request->merge([
                'bank_account_holder_name' => $request->filled('bank_account_holder_name')
                    ? (string) $request->input('bank_account_holder_name')
                    : $request->input('bank_account_holder_name'),
            ]);

            $hasResellerTables = $this->hasResellerTables();
            $rules = $this->merchantAccountValidationRules(null);
            $rules['login_name'] = 'nullable|email|unique:users,email';
            $rules['password'] = 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/';
            $rules['retype_password'] = 'nullable|same:password';
            if ($hasResellerTables) {
                $rules['reseller_id'] = 'nullable|required_if:is_reseller_merchant,true|exists:resellers,id';
            }

            $validator = Validator::make($request->all(), $rules, $this->merchantAccountValidationMessages());
            $validator->after(function ($validator) use ($request) {
                $this->validatePartnerTeamSelection($validator, $request);
            });

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

            $partnerName = $request->input('partner_name');
            if ($request->filled('partner_id') && Schema::hasTable('partners')) {
                $partnerRow = Partner::find($request->partner_id);
                if ($partnerRow) {
                    $partnerName = $partnerRow->name;
                }
            }
            $teamSelection = trim((string) ($request->input('team_name') ?: $request->input('team_id')));

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
                'partner_name' => $partnerName,
                'team_id' => $teamSelection !== '' ? $teamSelection : null,
                'team_name' => $teamSelection !== '' ? $teamSelection : null,
                'bank_account_holder_name' => $request->bank_account_holder_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_name' => $request->bank_name,
                'account_type' => $request->account_type,
                'bank_branch' => $request->bank_branch,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'is_dummy_account' => $request->boolean('is_dummy_account'),
                'merchant_type' => 'merchant',
                'approval_status' => 'not_approved',
                'status' => 'inactive',
                'registration_date' => now(),
                'default_currency' => 'USD',
                'test_mode' => true,
                'settlement_cycle_domestic' => $request->get('settlement_cycle_domestic', 1),
                'settlement_cycle_international' => $request->get('settlement_cycle_international', 7),
                'acquirer_account_id' => $request->filled('acquirer_account_id') ? (int) $request->acquirer_account_id : null,
                'reseller_id' => $hasResellerTables && $request->boolean('is_reseller_merchant') && $request->filled('reseller_id')
                    ? (int) $request->reseller_id
                    : null,
            ]);

            $merchant->acquirerAccounts()->sync($request->filled('acquirer_account_id') ? [(int) $request->acquirer_account_id] : []);
            if ($hasResellerTables && $request->boolean('is_reseller_merchant') && $request->filled('reseller_id')) {
                $merchant->resellers()->sync([
                    (int) $request->reseller_id => [
                        'status' => 'active',
                        'assigned_by' => auth()->id(),
                    ],
                ]);
            } elseif ($hasResellerTables) {
                $merchant->resellers()->detach();
            }

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
                'data' => SensitiveDataMasker::maskMerchantAttributes($merchant->fresh(['acquirerAccount', 'reseller'])),
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
            $request->merge([
                'bank_account_holder_name' => $request->filled('bank_account_holder_name')
                    ? (string) $request->input('bank_account_holder_name')
                    : $request->input('bank_account_holder_name'),
            ]);

            $merchant = Merchant::findOrFail($id);
            $hasResellerTables = $this->hasResellerTables();

            $rules = $this->merchantAccountValidationRules((int) $id);
            if ($hasResellerTables) {
                $rules['reseller_id'] = 'nullable|required_if:is_reseller_merchant,true|exists:resellers,id';
            }

            $validator = Validator::make($request->all(), $rules, $this->merchantAccountValidationMessages());
            $validator->after(function ($validator) use ($request) {
                $this->validatePartnerTeamSelection($validator, $request);
            });

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
            if ($request->filled('partner_id') && Schema::hasTable('partners')) {
                $partnerRow = Partner::find($request->partner_id);
                $merchant->partner_name = $partnerRow ? $partnerRow->name : $request->partner_name;
            } else {
                $merchant->partner_name = $request->partner_name;
            }
            $teamSelection = trim((string) ($request->input('team_name') ?: $request->input('team_id')));
            $merchant->team_id = $teamSelection !== '' ? $teamSelection : null;
            $merchant->team_name = $teamSelection !== '' ? $teamSelection : null;
            $merchant->bank_account_holder_name = $request->bank_account_holder_name;
            $merchant->bank_account_number = $request->bank_account_number;
            $merchant->bank_name = $request->bank_name;
            $merchant->account_type = $request->account_type;
            $merchant->bank_branch = $request->bank_branch;
            $merchant->bank_ifsc_code = $request->bank_ifsc_code;
            $merchant->is_dummy_account = $request->boolean('is_dummy_account');
            $merchant->merchant_type = 'merchant';
            $merchant->settlement_cycle_domestic = $request->get('settlement_cycle_domestic', 1);
            $merchant->settlement_cycle_international = $request->get('settlement_cycle_international', 7);
            $merchant->acquirer_account_id = $request->filled('acquirer_account_id') ? (int) $request->acquirer_account_id : null;
            $merchant->reseller_id = $hasResellerTables && $request->boolean('is_reseller_merchant') && $request->filled('reseller_id')
                ? (int) $request->reseller_id
                : null;
            $merchant->save();

            $merchant->acquirerAccounts()->sync($request->filled('acquirer_account_id') ? [(int) $request->acquirer_account_id] : []);
            if ($hasResellerTables && $request->boolean('is_reseller_merchant') && $request->filled('reseller_id')) {
                $merchant->resellers()->sync([
                    (int) $request->reseller_id => [
                        'status' => 'active',
                        'assigned_by' => auth()->id(),
                    ],
                ]);
            } elseif ($hasResellerTables) {
                $merchant->resellers()->detach();
            }

            $this->logInfo('Merchant account updated', ['merchant_id' => $merchant->id]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant updated successfully',
                'data' => SensitiveDataMasker::maskMerchantAttributes($merchant->fresh(['acquirerAccount', 'reseller'])),
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
                $newApprovalStatus = $request->input('approval_status');
                $currentApprovalStatus = $merchant->approval_status;

                // TC_169: approved merchant cannot be rejected
                if ($currentApprovalStatus === 'approved' && $newApprovalStatus === 'rejected') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Approved merchants cannot be rejected.',
                    ], 422);
                }

                $merchant->approval_status = $newApprovalStatus;
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
                'fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2', 'regex:/^\d+(\.\d{1,2})?$/'],
                'fee_flat' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'regex:/^\d+(\.\d{1,2})?$/'],
            ], [
                'fee_percentage.decimal' => 'Fee Percentage may have at most 2 decimal places.',
                'fee_percentage.regex' => 'Fee Percentage may have at most 2 decimal places.',
                'fee_flat.decimal' => 'Flat Fee may have at most 2 decimal places.',
                'fee_flat.regex' => 'Flat Fee may have at most 2 decimal places.',
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
                'data' => SensitiveDataMasker::maskMerchantAttributes($merchant->fresh()),
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

            $duplicate = DB::transaction(function () use ($original) {
                $copy = $original->replicate();

                $copy->name = $original->name . ' (Copy)';
                $copy->email = 'copy_' . time() . '_' . $original->email;
                $copy->approval_status = 'not_approved';
                $copy->status = 'inactive';
                $copy->registration_date = now();
                $copy->reseller_id = null;

                // Do not copy columns with UNIQUE constraints — replicate() would otherwise reuse values.
                $copy->merchant_unique_id = null;
                $copy->test_public_key = null;
                $copy->test_secret_key = null;
                $copy->live_public_key = null;
                $copy->live_secret_key = null;

                $copy->save();

                // Pivot is not copied by replicate(); mirror acquirer assignment from the source merchant.
                $pivotIds = $original->acquirerAccounts()->get()->pluck('id');
                if ($pivotIds->isEmpty() && $original->acquirer_account_id) {
                    $pivotIds = collect([(int) $original->acquirer_account_id]);
                }
                if ($pivotIds->isNotEmpty()) {
                    $copy->acquirerAccounts()->sync(
                        $pivotIds->filter()->unique()->values()->all()
                    );
                }

                return $copy;
            });

            $this->logInfo('Merchant account duplicated', [
                'original_id' => $id,
                'duplicate_id' => $duplicate->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant account duplicated successfully',
                'data' => SensitiveDataMasker::maskMerchantAttributes($duplicate->fresh()),
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

    protected function hasResellerTables(): bool
    {
        return Schema::hasTable('resellers') && Schema::hasTable('reseller_merchant');
    }

    /**
     * Split partner.team_name into individual team labels (comma, semicolon, or pipe).
     *
     * @return list<string>
     */
    protected function parsePartnerTeamNames(?string $teamName): array
    {
        if ($teamName === null || trim($teamName) === '') {
            return [];
        }

        $parts = preg_split('/[,;|]/', $teamName);

        return array_values(array_filter(array_map('trim', is_array($parts) ? $parts : []), fn ($s) => $s !== ''));
    }

    protected function validatePartnerTeamSelection(\Illuminate\Validation\Validator $validator, Request $request): void
    {
        if (! $request->boolean('is_partner_merchant')) {
            return;
        }
        if (! Schema::hasTable('partners')) {
            return;
        }
        if (! $request->filled('partner_id')) {
            $validator->errors()->add('partner_id', 'Please select a partner.');

            return;
        }
        $partner = Partner::find($request->partner_id);
        if (! $partner) {
            $validator->errors()->add('partner_id', 'Selected partner is invalid.');

            return;
        }
        $teams = $this->parsePartnerTeamNames($partner->team_name);
        $selected = trim((string) ($request->input('team_name') ?: $request->input('team_id')));
        if (count($teams) > 0) {
            if ($selected === '') {
                $validator->errors()->add('team_name', 'Please select a team.');
            } elseif (! in_array($selected, $teams, true)) {
                $validator->errors()->add('team_name', 'Selected team is not valid for this partner.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function merchantAccountValidationRules(?int $merchantId): array
    {
        $emailRule = $merchantId === null
            ? 'required|email|unique:merchants,email'
            : 'required|email|unique:merchants,email,' . $merchantId;

        $partnerIdRule = Schema::hasTable('partners')
            ? 'nullable|exists:partners,id'
            : 'nullable|string|max:255';

        return [
            'name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
            'legal_name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
            'email' => $emailRule,
            'phone' => ['required', 'string', 'min:6', 'max:16', 'regex:/^\+?[0-9]{6,15}$/'],
            'merchant_category' => 'required|string',
            'address_line_1' => 'required|string|max:250',
            'business_country' => 'required|string',
            'business_state' => 'required|string',
            'business_city' => 'required|string',
            'business_postal_code' => ['required', 'string', 'min:4', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'merchant_pan_number' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'name_on_pan_card' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'gst_identification_no' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]*$/'],
            'gstin_state' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z ]*$/'],
            'tan_no' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ]*$/'],
            'contact_name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
            'contact_mobile' => ['required', 'string', 'min:6', 'max:16', 'regex:/^\+?[0-9]{6,15}$/'],
            'contact_landline' => ['nullable', 'string', 'min:6', 'max:16', 'regex:/^\+?[0-9]{6,15}$/'],
            'contact_email' => 'required|email|max:120',
            'bank_account_holder_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'bank_account_number' => ['required', 'string', 'max:34', 'regex:/^[0-9]+$/'],
            'bank_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'account_type' => 'required|string',
            'bank_branch' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'bank_ifsc_code' => ['required', 'string', 'min:7', 'max:15', 'regex:/^[A-Za-z]{4}[A-Za-z0-9]{3,11}$/'],
            'acquirer_account_id' => 'nullable|exists:acquirer_accounts,id',
            'is_reseller_merchant' => 'nullable|boolean',
            'is_partner_merchant' => 'nullable|boolean',
            'partner_id' => $partnerIdRule,
            'partner_name' => 'nullable|string|max:255',
            'team_id' => 'nullable|string|max:255',
            'team_name' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'merchant_category_code' => 'nullable|string|max:255',
            'ownership_type' => 'nullable|string|max:255',
            'website_link' => 'nullable|url|max:500',
            'address_line_2' => 'nullable|string|max:250',
            'is_dummy_account' => 'nullable|boolean',
            'settlement_cycle_domestic' => 'nullable|integer|min:1|max:30',
            'settlement_cycle_international' => 'nullable|integer|min:1|max:30',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function merchantAccountValidationMessages(): array
    {
        return [
            'password.regex' => 'Password must have minimum 12 characters and should include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character.',
            'name.regex' => 'Merchant name may contain only letters and spaces.',
            'legal_name.regex' => 'Merchant legal name may contain only letters and spaces.',
            'phone.regex' => 'Merchant phone must be 6-15 digits with optional leading +.',
            'phone.min' => 'Merchant phone must be at least 6 characters.',
            'phone.max' => 'Merchant phone may not be greater than 16 characters.',
            'business_postal_code.regex' => 'Zip code may contain only letters and numbers.',
            'business_postal_code.min' => 'Zip code must be at least 4 characters.',
            'business_postal_code.max' => 'Zip code may not be greater than 12 characters.',
            'merchant_pan_number.regex' => 'Merchant PAN number may contain only letters and numbers.',
            'name_on_pan_card.regex' => 'Name on PAN card may contain only letters and spaces.',
            'gst_identification_no.regex' => 'GST identification number may contain only letters and numbers.',
            'gstin_state.regex' => 'GSTIN state may contain only letters and spaces.',
            'tan_no.regex' => 'TAN number may contain only letters, numbers, and spaces.',
            'contact_name.regex' => 'Contact name may contain only letters and spaces.',
            'contact_mobile.regex' => 'Contact mobile must be 6-15 digits with optional leading +.',
            'contact_mobile.min' => 'Contact mobile must be at least 6 characters.',
            'contact_mobile.max' => 'Contact mobile may not be greater than 16 characters.',
            'contact_landline.regex' => 'Contact landline must be 6-15 digits with optional leading +.',
            'contact_landline.min' => 'Contact landline must be at least 6 characters.',
            'contact_landline.max' => 'Contact landline may not be greater than 16 characters.',
            'bank_account_holder_name.regex' => 'Account holder name may contain only letters and spaces.',
            'bank_name.regex' => 'Bank name may contain only letters and spaces.',
            'bank_branch.regex' => 'Bank branch may contain only letters and spaces.',
            'bank_account_number.regex' => 'Bank account number may contain only numbers.',
            'bank_ifsc_code.min' => 'IFSC code must be at least 7 characters.',
            'bank_ifsc_code.max' => 'IFSC code may not be greater than 15 characters.',
            'bank_ifsc_code.regex' => 'IFSC code must start with 4 letters followed by letters or numbers (e.g., ABCD0001234).',
            'website_link.url' => 'Website link must be a valid URL (include http:// or https://).',
        ];
    }
}



