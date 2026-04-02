<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaseRate;
use App\Models\Merchant;
use App\Models\Bank;
use App\Models\Reseller;
use App\Models\MerchantResellerSplit;
use App\Services\BaseRateService;
use App\Http\Requests\Admin\BaseRates\StoreBaseRateRequest;
use App\Http\Requests\Admin\BaseRates\UpdateBaseRateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class BaseRatesController extends Controller
{
    protected BaseRateService $baseRateService;

    public function __construct(BaseRateService $baseRateService)
    {
        $this->baseRateService = $baseRateService;
    }

    /**
     * Display base rates management page.
     */
    public function index(): View
    {
        return view('admin.base-rates.index');
    }

    /**
     * Get base rates data.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $perPage = min($request->get('per_page', 15), 100);
            
            $query = BaseRate::query();

            // Filter by merchant's test_mode based on admin view mode
            // When in live mode, only show rates for merchants with test_mode = false
            // When in test mode, only show rates for merchants with test_mode = true
            // This applies only to merchant rates (rate_type = 'merchant' and entity_type = 'merchant')
            // For non-merchant rates (bank, receiver, pricer), show them in both modes
            $query->where(function ($q) use ($isTestMode) {
                // Merchant rates: filter by merchant's test_mode
                $q->where(function ($subQ) use ($isTestMode) {
                    $subQ->where('rate_type', 'merchant')
                         ->where('entity_type', 'merchant')
                         ->whereHas('merchant', function ($merchantQ) use ($isTestMode) {
                             $merchantQ->where('test_mode', $isTestMode);
                         });
                })
                // Non-merchant rates: show all (bank, receiver, pricer)
                ->orWhere('rate_type', '!=', 'merchant');
            });

            // Filters
            if ($request->has('rate_type') && $request->get('rate_type') !== 'all') {
                $query->where('rate_type', $request->get('rate_type'));
            }

            if ($request->has('payment_method') && $request->get('payment_method') !== 'all') {
                $query->where('payment_method', $request->get('payment_method'));
            }

            if ($request->has('service_type') && $request->get('service_type') !== 'all') {
                $query->where('service_type', $request->get('service_type'));
            }

            if ($request->filled('payment_mode') && $request->get('payment_mode') !== 'all') {
                $query->where('payment_mode', $request->get('payment_mode'));
            }

            if ($request->filled('sector') && $request->get('sector') !== 'all') {
                $query->where('sector', $request->get('sector'));
            }

            if ($request->filled('currency') && $request->get('currency') !== 'all') {
                $query->where('currency', $request->get('currency'));
            }

            if ($request->filled('merchant_email')) {
                $v = $request->get('merchant_email');
                $query->whereHas('merchant', function ($q) use ($v) {
                    $q->where('email', 'like', "%{$v}%");
                });
            }

            if ($request->filled('reseller_email')) {
                $v = $request->get('reseller_email');
                $query->whereHas('merchantResellerSplit.reseller', function ($q) use ($v) {
                    $q->where('email', 'like', "%{$v}%");
                });
            }

            if ($request->filled('admin_share_pct')) {
                $query->whereHas('merchantResellerSplit', function ($q) use ($request) {
                    $q->where('admin_share_pct', (float) $request->get('admin_share_pct'));
                });
            }
            if ($request->filled('reseller_share_pct')) {
                $query->whereHas('merchantResellerSplit', function ($q) use ($request) {
                    $q->where('reseller_share_pct', (float) $request->get('reseller_share_pct'));
                });
            }
            if ($request->filled('merchant_share_pct')) {
                $query->whereHas('merchantResellerSplit', function ($q) use ($request) {
                    $q->where('merchant_share_pct', (float) $request->get('merchant_share_pct'));
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search
            if ($request->has('search') && $request->get('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('rate_type', 'like', "%{$search}%")
                      ->orWhere('payment_method', 'like', "%{$search}%")
                      ->orWhere('service_type', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $rates = $query->with(['merchant', 'bank', 'merchantResellerSplit.reseller'])->paginate($perPage);

            $rates->getCollection()->transform(function (BaseRate $rate) {
                $split = $rate->merchantResellerSplit;
                $reseller = $split?->reseller;
                $merchant = $rate->merchant;

                $arr = $rate->toArray();
                $arr['merchant_email'] = $merchant?->email;
                $arr['reseller_id'] = $split?->reseller_id;
                $arr['reseller_email'] = $reseller?->email;
                $arr['admin_share_pct'] = $split?->admin_share_pct;
                $arr['reseller_share_pct'] = $split?->reseller_share_pct;
                $arr['merchant_share_pct'] = $split?->merchant_share_pct;
                $arr['split_is_active'] = $split?->is_active;

                return $arr;
            });

            return response()->json([
                'success' => true,
                'data' => $rates->items(),
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                    'last_page' => $rates->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch base rates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created base rate.
     */
    public function store(StoreBaseRateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $splitData = [
                'reseller_id' => $data['reseller_id'] ?? null,
                'admin_share_pct' => $data['admin_share_pct'] ?? null,
                'reseller_share_pct' => $data['reseller_share_pct'] ?? null,
                'merchant_share_pct' => $data['merchant_share_pct'] ?? null,
                'is_active' => array_key_exists('split_is_active', $data) ? (bool) $data['split_is_active'] : true,
            ];
            unset($data['reseller_id'], $data['admin_share_pct'], $data['reseller_share_pct'], $data['merchant_share_pct'], $data['split_is_active']);
            // Set entity_type based on rate_type if not provided
            if (!isset($data['entity_type']) && isset($data['rate_type'])) {
                if (in_array($data['rate_type'], ['merchant', 'bank'])) {
                    $data['entity_type'] = $data['rate_type'];
                }
            }

            // Use BaseRateService to create or update
            $rate = $this->baseRateService->createOrUpdateRate($data);
            $this->upsertMerchantSplit($rate, $splitData);

            return response()->json([
                'success' => true,
                'message' => 'Base rate created successfully',
                'data' => $rate->load(['merchant', 'bank', 'merchantResellerSplit.reseller']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create base rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified base rate.
     */
    public function update(UpdateBaseRateRequest $request, $id): JsonResponse
    {
        try {
            $rate = BaseRate::findOrFail($id);
            $payload = $request->validated();
            $splitData = [
                'reseller_id' => $payload['reseller_id'] ?? null,
                'admin_share_pct' => $payload['admin_share_pct'] ?? null,
                'reseller_share_pct' => $payload['reseller_share_pct'] ?? null,
                'merchant_share_pct' => $payload['merchant_share_pct'] ?? null,
                'is_active' => array_key_exists('split_is_active', $payload) ? (bool) $payload['split_is_active'] : true,
            ];
            unset($payload['reseller_id'], $payload['admin_share_pct'], $payload['reseller_share_pct'], $payload['merchant_share_pct'], $payload['split_is_active']);

            $rate->update($payload);
            $this->upsertMerchantSplit($rate, $splitData);

            return response()->json([
                'success' => true,
                'message' => 'Base rate updated successfully',
                'data' => $rate->fresh(['merchant', 'bank', 'merchantResellerSplit.reseller']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update base rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified base rate.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $rate = BaseRate::findOrFail($id);
            $rate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Base rate deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete base rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get entities (merchants, banks) for dropdowns.
     */
    public function getEntities(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');

            if ($type === 'merchant') {
                $adminViewMode = session('admin_view_mode', 'test');
                $isTestMode = $adminViewMode === 'test';

                // Filter merchants based on admin view mode
                $entities = Merchant::select('id', 'name', 'email')
                    ->where('status', 'active')
                    ->where('test_mode', $isTestMode)
                    ->get()
                    ->map(function($m) {
                        return ['id' => $m->id, 'name' => $m->name . ' (' . $m->email . ')'];
                    });
            } elseif ($type === 'bank') {
                $entities = Bank::select('id', 'name', 'code')
                    ->get()
                    ->map(function($b) {
                        return ['id' => $b->id, 'name' => $b->name . ' (' . $b->code . ')'];
                    });
            } else {
                $entities = [];
            }

            return response()->json([
                'success' => true,
                'data' => $entities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch entities: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function upsertMerchantSplit(BaseRate $rate, array $splitData): void
    {
        if (! ($rate->rate_type === 'merchant' && $rate->entity_type === 'merchant' && $rate->entity_id)) {
            return;
        }

        $admin = (float) ($splitData['admin_share_pct'] ?? 0);
        $reseller = (float) ($splitData['reseller_share_pct'] ?? 0);
        $merchant = (float) ($splitData['merchant_share_pct'] ?? 0);

        if (abs(($admin + $reseller + $merchant) - 100.0) > 0.0001) {
            throw new \InvalidArgumentException('Admin + Reseller + Merchant shares must sum up to 100%.');
        }

        if (empty($splitData['reseller_id']) && $reseller > 0) {
            throw new \InvalidArgumentException('Reseller share must be 0 when no reseller is selected.');
        }

        MerchantResellerSplit::updateOrCreate(
            ['base_rate_id' => $rate->id],
            [
                'merchant_id' => (int) $rate->entity_id,
                'reseller_id' => $splitData['reseller_id'] ?: null,
                'admin_share_pct' => round($admin, 4),
                'reseller_share_pct' => round($reseller, 4),
                'merchant_share_pct' => round($merchant, 4),
                'is_active' => (bool) ($splitData['is_active'] ?? true),
            ]
        );
    }
}
