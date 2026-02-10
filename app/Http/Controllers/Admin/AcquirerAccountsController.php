<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcquirerAccount;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AcquirerAccountsController extends Controller
{
    /**
     * Display the acquirer accounts page.
     */
    public function index(): View
    {
        return view('admin.acquirer.accounts');
    }

    /**
     * Get acquirer accounts data for the table.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 100);
            
            $query = AcquirerAccount::query()->with('merchants');

            // Filters
            if ($request->has('acquirer_name') && $request->get('acquirer_name') !== 'all') {
                $query->where('acquirer_name', $request->get('acquirer_name'));
            }

            if ($request->has('mode') && $request->get('mode') !== 'all') {
                $query->where('mode', $request->get('mode'));
            }

            if ($request->has('sector') && $request->get('sector') !== 'all') {
                $query->where('sector', $request->get('sector'));
            }

            if ($request->has('team') && $request->get('team') !== 'all') {
                $query->where('team', $request->get('team'));
            }

            // Search
            if ($request->has('search') && $request->get('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('account_id', 'like', "%{$search}%")
                      ->orWhere('acquirer_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('hdfc_me_code', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'account_id', 'acquirer_name', 'team', 'mode', 'sector', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->latest();
            }

            $accounts = $query->paginate($perPage);

            $data = collect($accounts->items())->map(function($account) {
                return [
                    'id' => $account->id,
                    'account_id' => $account->account_id,
                    'acquirer_name' => $account->acquirer_name,
                    'team' => $account->team,
                    'description' => $account->description,
                    'whitelist_url' => $account->whitelist_url,
                    'mode' => $account->mode,
                    'sector' => $account->sector,
                    'hdfc_me_code' => $account->hdfc_me_code,
                    'settlement_account_name' => $account->settlement_account_name,
                    'refund_allowed' => $account->refund_allowed,
                    'settlements_to_be_created' => $account->settlements_to_be_created,
                    'mask_pii' => $account->mask_pii,
                    'is_active' => $account->is_active,
                    'email_ids' => $account->email_ids,
                    'secret_key' => $account->secret_key, // Include for editing
                    'salt' => $account->salt, // Include for editing
                    'additional_key_1' => $account->additional_key_1, // Include for editing
                    'additional_key_2' => $account->additional_key_2, // Include for editing
                    'additional_key_3' => $account->additional_key_3, // Include for editing
                    'additional_key_data' => $account->additional_key_data, // Include for editing
                    'live_request_url' => $account->live_request_url,
                    'live_query_url' => $account->live_query_url,
                    'live_refund_url' => $account->live_refund_url,
                    'test_request_url' => $account->test_request_url,
                    'test_query_url' => $account->test_query_url,
                    'test_refund_url' => $account->test_refund_url,
                    'nodal_account' => $account->nodal_account,
                    'merchants' => $account->merchants->pluck('name')->implode(', '),
                    'merchant_ids' => $account->merchants->pluck('id')->toArray(),
                    'created_at' => $account->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $accounts->currentPage(),
                    'per_page' => $accounts->perPage(),
                    'total' => $accounts->total(),
                    'last_page' => $accounts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch acquirer accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created acquirer account.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string|max:255|unique:acquirer_accounts,account_id',
            'acquirer_name' => 'required|string|max:255',
            'team' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'whitelist_url' => 'nullable|url|max:500',
            'mode' => 'required|in:TEST,LIVE',
            'sector' => 'nullable|string|max:255',
            'hdfc_me_code' => 'nullable|string|max:255',
            'settlement_account_name' => 'nullable|string|max:255',
            'refund_allowed' => 'boolean',
            'settlements_to_be_created' => 'boolean',
            'mask_pii' => 'boolean',
            'is_active' => 'boolean',
            'email_ids' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'salt' => 'nullable|string',
            'additional_key_1' => 'nullable|string',
            'additional_key_2' => 'nullable|string',
            'additional_key_3' => 'nullable|string',
            'additional_key_data' => 'nullable|string',
            'live_request_url' => 'nullable|url|max:500',
            'live_query_url' => 'nullable|url|max:500',
            'live_refund_url' => 'nullable|url|max:500',
            'test_request_url' => 'nullable|url|max:500',
            'test_query_url' => 'nullable|url|max:500',
            'test_refund_url' => 'nullable|url|max:500',
            'nodal_account' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            \Log::debug('Creating acquirer account', [
                'account_id' => $request->account_id,
                'acquirer_name' => $request->acquirer_name,
                'has_additional_key_1' => !empty($request->additional_key_1),
                'has_secret_key' => !empty($request->secret_key),
            ]);

            $account = AcquirerAccount::create($validator->validated());
            // Merchants are assigned from Merchant module (many-to-one: one acquirer, many merchants)

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acquirer account created successfully',
                'data' => $account->load('merchants'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create acquirer account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified acquirer account.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $account = AcquirerAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string|max:255|unique:acquirer_accounts,account_id,' . $id,
            'acquirer_name' => 'required|string|max:255',
            'team' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'whitelist_url' => 'nullable|url|max:500',
            'mode' => 'required|in:TEST,LIVE',
            'sector' => 'nullable|string|max:255',
            'hdfc_me_code' => 'nullable|string|max:255',
            'settlement_account_name' => 'nullable|string|max:255',
            'refund_allowed' => 'boolean',
            'settlements_to_be_created' => 'boolean',
            'mask_pii' => 'boolean',
            'is_active' => 'boolean',
            'email_ids' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'salt' => 'nullable|string',
            'additional_key_1' => 'nullable|string',
            'additional_key_2' => 'nullable|string',
            'additional_key_3' => 'nullable|string',
            'additional_key_data' => 'nullable|string',
            'live_request_url' => 'nullable|url|max:500',
            'live_query_url' => 'nullable|url|max:500',
            'live_refund_url' => 'nullable|url|max:500',
            'test_request_url' => 'nullable|url|max:500',
            'test_query_url' => 'nullable|url|max:500',
            'test_refund_url' => 'nullable|url|max:500',
            'nodal_account' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            \Log::debug('Updating acquirer account', [
                'account_id' => $account->id,
                'has_additional_key_1' => !empty($request->additional_key_1),
                'has_secret_key' => !empty($request->secret_key),
            ]);

            $account->update($validator->validated());
            // Merchants are assigned from Merchant module

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acquirer account updated successfully',
                'data' => $account->fresh('merchants'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update acquirer account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified acquirer account.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $account = AcquirerAccount::findOrFail($id);
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Acquirer account deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete acquirer account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get acquirer names for dropdown.
     */
    public function getAcquirerNames(): JsonResponse
    {
        // Get distinct acquirer names from database
        $dbNames = AcquirerAccount::distinct()
            ->whereNotNull('acquirer_name')
            ->pluck('acquirer_name')
            ->filter()
            ->values();
        
        // Add common acquirer names (including Razorpay and Yapily)
        $commonNames = collect([
            'A2Pay', 'Paytm', 'Switch', 'HDFC', 'ICICI', 'Axis', 'SBI',
            'Razorpay', 'razorpay', 'razorpay_test', 'razorpay_live', 'PayU',
            // Yapily sandbox acquirer entries
            'Yapily', 'yapily', 'YapilyTest', 'yapily_test', 'YapilyLive', 'yapily_live',
        ]);
        
        // Merge and ensure Razorpay variants are included
        $allNames = $commonNames->merge($dbNames)
            ->map(function($name) {
                return trim($name);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $allNames,
        ]);
    }

    /**
     * Get merchants list for dropdown.
     */
    public function getMerchants(): JsonResponse
    {
        $merchants = Merchant::select('id', 'name', 'email')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $merchants,
        ]);
    }
}
