<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin users page accessed', ['user_id' => auth()->id()]);
        return view('admin.users.index');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = User::with(['role', 'merchant'])->latest();

            // Column filters
            if ($request->has('filter_id') && $request->get('filter_id')) {
                $query->where('id', $request->get('filter_id'));
            }
            if ($request->has('filter_name') && $request->get('filter_name')) {
                $query->where('name', 'like', "%{$request->get('filter_name')}%");
            }
            if ($request->has('filter_email') && $request->get('filter_email')) {
                $query->where('email', 'like', "%{$request->get('filter_email')}%");
            }
            if ($request->has('filter_active') && $request->get('filter_active') !== 'all') {
                $query->where('status', $request->get('filter_active'));
            }
            if ($request->has('filter_email_verified') && $request->get('filter_email_verified') !== 'all') {
                if ($request->get('filter_email_verified') === 'yes') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            if ($request->has('filter_roles') && $request->get('filter_roles')) {
                $query->whereHas('role', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->get('filter_roles')}%");
                });
            }
            if ($request->has('filter_time_zone') && $request->get('filter_time_zone') !== 'all') {
                // Assuming timezone is stored somewhere, adjust as needed
                // $query->where('timezone', $request->get('filter_time_zone'));
            }

            // Organization/Vendor filters (from merchant)
            if ($request->has('filter_organization_name') && $request->get('filter_organization_name')) {
                $query->whereHas('merchant', function($q) use ($request) {
                    $q->where('organization_name', 'like', "%{$request->get('filter_organization_name')}%");
                });
            }
            if ($request->has('filter_vendor_codes') && $request->get('filter_vendor_codes')) {
                // Add vendor code filter if exists
            }
            if ($request->has('filter_2factor_auth') && $request->get('filter_2factor_auth') !== 'all') {
                if ($request->get('filter_2factor_auth') === 'yes') {
                    $query->where('two_factor_enabled', true);
                } else {
                    $query->where('two_factor_enabled', false);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'name', 'email', 'status', 'email_verified_at', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $users = $query->paginate($perPage);

            $data = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => $user->status === 'active',
                    'status' => $user->status,
                    'email_verified' => $user->email_verified_at !== null,
                    'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
                    'roles' => $user->role ? $user->role->name : '-',
                    'role_id' => $user->role_id,
                    'merchant_id' => $user->merchant_id,
                    'merchant_name' => $user->merchant ? $user->merchant->name : '-',
                    'organization_name' => $user->merchant ? ($user->merchant->organization_name ?? '-') : '-',
                    'vendor_codes' => $user->merchant ? ($user->merchant->vendor_codes ?? '-') : '-',
                    'time_zone' => $user->timezone ?? 'Asia/Kolkata',
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '-',
                    'two_factor_enabled' => $user->two_factor_enabled ?? false,
                    'two_factor_auth' => $user->two_factor_enabled ? 'y' : 'n',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching users', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
            ], 500);
        }
    }

    public function toggleEmailVerification(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->email_verified_at) {
                // Unverify email
                $user->email_verified_at = null;
                $message = 'Email verification removed';
            } else {
                // Verify email
                $user->email_verified_at = now();
                $message = 'Email verified successfully';
            }

            $user->save();

            $this->logInfo('User email verification toggled', [
                'user_id' => $user->id,
                'email' => $user->email,
                'verified' => $user->email_verified_at !== null,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error toggling email verification', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update email verification',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,inactive,suspended',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::findOrFail($id);
            $user->status = $request->status;
            $user->save();

            $this->logInfo('User status updated', [
                'user_id' => $user->id,
                'status' => $user->status,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'status' => $user->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'role_id' => 'required|exists:roles,id',
                'merchant_id' => 'nullable|exists:merchants,id',
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

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'merchant_id' => $request->merchant_id,
                'status' => $request->status ?? 'active',
                'email_verified_at' => $request->boolean('email_verified') ? now() : null,
            ]);

            $this->logInfo('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'timezone' => 'nullable|string|max:50',
                'currency_code' => 'nullable|string|max:3',
                'team_name' => 'nullable|string|max:255',
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

            DB::beginTransaction();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->timezone = $request->timezone ?? $user->timezone ?? 'Asia/Kolkata';

            // Update status based on active checkbox
            if ($request->has('active')) {
                $user->status = $request->boolean('active') ? 'active' : 'inactive';
            }

            // Update email verification - always process if present in request
            if ($request->has('email_verified')) {
                $emailVerified = $request->input('email_verified');
                // Handle boolean, string, or integer values
                if (is_bool($emailVerified)) {
                    $user->email_verified_at = $emailVerified ? now() : null;
                } elseif (is_string($emailVerified)) {
                    $user->email_verified_at = in_array(strtolower($emailVerified), ['true', '1', 'yes', 'on']) ? now() : null;
                } else {
                    $user->email_verified_at = (bool)$emailVerified ? now() : null;
                }
            }

            // Update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Update merchant details if user has merchant
            if ($user->merchant_id && $user->merchant) {
                $merchant = $user->merchant;
                
                if ($request->has('currency_code')) {
                    $merchant->default_currency = $request->currency_code ?? 'INR';
                } else {
                    $merchant->default_currency = $merchant->default_currency ?? 'INR';
                }
                
                if ($request->has('team_name')) {
                    $merchant->team_name = $request->team_name ?? '';
                }
                
                $merchant->save();
            }

            DB::commit();

            $this->logInfo('User updated', [
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'email_verified' => $user->email_verified_at !== null,
                'active' => $user->status === 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => $user->status === 'active',
                    'email_verified' => $user->email_verified_at !== null,
                    'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $user = User::with(['merchant', 'role'])->findOrFail($id);
            
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->status === 'active',
                'email_verified' => $user->email_verified_at !== null,
                'timezone' => $user->timezone ?? 'Asia/Kolkata',
                'currency_code' => $user->merchant ? ($user->merchant->default_currency ?? 'INR') : 'INR',
                'team_name' => $user->merchant ? ($user->merchant->team_name ?? '') : '',
                'merchant_id' => $user->merchant_id,
                'merchant_name' => $user->merchant ? $user->merchant->name : null,
                'role_id' => $user->role_id,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTeams(): JsonResponse
    {
        try {
            // Get unique team names from merchants - simplified approach
            $teams = DB::table('merchants')
                ->select('team_name')
                ->whereNotNull('team_name')
                ->where('team_name', '!=', '')
                ->distinct()
                ->orderBy('team_name')
                ->get()
                ->pluck('team_name')
                ->filter()
                ->unique()
                ->values()
                ->all();
            
            return response()->json([
                'success' => true,
                'data' => array_values($teams),
            ]);
        } catch (\Exception $e) {
            // Log error safely
            \Log::error('Error fetching teams: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teams: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            $this->logInfo('User deleted', [
                'user_id' => $id,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }

    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::select('id', 'name')->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
            ], 500);
        }
    }

    public function getMerchants(): JsonResponse
    {
        try {
            $merchants = Merchant::select('id', 'name', 'email', 'organization_name')
                ->orderBy('name')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $merchants,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchants',
            ], 500);
        }
    }

    public function toggle2FA(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->two_factor_enabled) {
                // Disable 2FA
                $user->two_factor_enabled = false;
                $user->two_factor_secret = null;
                $user->two_factor_confirmed_at = null;
                $message = '2FA disabled successfully';
            } else {
                // Enable 2FA
                $user->two_factor_enabled = true;
                // Generate a simple secret (in production, use proper 2FA library like Google Authenticator)
                $user->two_factor_secret = bin2hex(random_bytes(16));
                $user->two_factor_confirmed_at = now();
                $message = '2FA enabled successfully';
            }

            $user->save();

            $this->logInfo('User 2FA toggled', [
                'user_id' => $user->id,
                'email' => $user->email,
                'two_factor_enabled' => $user->two_factor_enabled,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'two_factor_enabled' => $user->two_factor_enabled,
                'two_factor_auth' => $user->two_factor_enabled ? 'y' : 'n',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error toggling 2FA', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update 2FA status: ' . $e->getMessage(),
            ], 500);
        }
    }
}

