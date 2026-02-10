<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantRegistrationKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MerchantRegistrationKeysController extends Controller
{
    /**
     * Show the registration keys page.
     */
    public function index(): View
    {
        return view('admin.merchants.registration-keys');
    }

    /**
     * List data for table (with filters).
     */
    public function getData(Request $request): JsonResponse
    {
        $adminViewMode = session('admin_view_mode', 'test');
        $isTestMode = $adminViewMode === 'test';

        $perPage = min($request->integer('per_page', 5), 50);

        $query = MerchantRegistrationKey::query()->with('merchant');

        // Filter by merchant's test_mode based on admin view mode
        // When in live mode, only show keys for merchants with test_mode = false
        // When in test mode, only show keys for merchants with test_mode = true
        $query->whereHas('merchant', function ($q) use ($isTestMode) {
            $q->where('test_mode', $isTestMode);
        });

        if ($request->filled('id')) {
            $query->where('id', $request->get('id'));
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status') === 'Active' ? 'active' : 'not_active');
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->get('ip_address') . '%');
        }

        if ($request->filled('key_description')) {
            $query->where('key_description', 'like', '%' . $request->get('key_description') . '%');
        }

        $keys = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $keys->items(),
            'pagination' => [
                'current_page' => $keys->currentPage(),
                'per_page' => $keys->perPage(),
                'total' => $keys->total(),
                'last_page' => $keys->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new registration key.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'key_description' => 'required|string|max:255',
            'status' => 'required|in:Active,Not-Active',
            'ip_address' => 'nullable|string|max:255',
            'copy_merchant_params' => 'boolean',
            'copy_velocity_checks' => 'boolean',
            'copy_routing_randomize' => 'boolean',
            'copy_account_whitelisting' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] === 'Active' ? 'active' : 'not_active';
        $data['registration_key'] = Str::uuid()->toString();

        $key = MerchantRegistrationKey::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Merchant registration key created successfully',
            'data' => $key->fresh('merchant'),
        ]);
    }

    /**
     * Update existing registration key.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $key = MerchantRegistrationKey::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'key_description' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:Active,Not-Active',
            'ip_address' => 'nullable|string|max:255',
            'copy_merchant_params' => 'boolean',
            'copy_velocity_checks' => 'boolean',
            'copy_routing_randomize' => 'boolean',
            'copy_account_whitelisting' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['status'])) {
            $data['status'] = $data['status'] === 'Active' ? 'active' : 'not_active';
        }

        $key->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Merchant registration key updated successfully',
            'data' => $key->fresh('merchant'),
        ]);
    }

    /**
     * Fetch merchants for dropdown.
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


