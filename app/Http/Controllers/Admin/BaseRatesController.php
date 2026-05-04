<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaseRate;
use App\Models\Merchant;
use App\Models\Bank;
use App\Models\Reseller;
use App\Models\MerchantResellerSplit;
use App\Models\BillingFeeDefinition;
use App\Models\BillingFeeRule;
use App\Models\Partner;
use App\Services\BaseRateService;
use App\Http\Requests\Admin\BaseRates\StoreBaseRateRequest;
use App\Http\Requests\Admin\BaseRates\UpdateBaseRateRequest;
use App\Support\BaseRateCalculationNormalizer;
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

            if ($request->filled('team_id')) {
                $query->where('team_id', $request->get('team_id'));
            }

            if ($request->filled('team_name')) {
                $query->where('team_name', 'like', '%' . $request->get('team_name') . '%');
            }

            if ($request->filled('bank_code')) {
                $query->where('bank_code', 'like', '%' . $request->get('bank_code') . '%');
            }

            if ($request->filled('bank_description')) {
                $query->where('bank_description', 'like', '%' . $request->get('bank_description') . '%');
            }

            if ($request->filled('flat_fee')) {
                $query->where('flat_fee', (float) $request->get('flat_fee'));
            }

            if ($request->filled('percentage_fee')) {
                $query->where('percentage_fee', (float) $request->get('percentage_fee'));
            }

            if ($request->filled('min_amount')) {
                $query->where('min_amount', (float) $request->get('min_amount'));
            }

            if ($request->filled('max_amount')) {
                $query->where('max_amount', (float) $request->get('max_amount'));
            }

            if ($request->filled('min_share')) {
                $query->where('min_share', (float) $request->get('min_share'));
            }

            if ($request->filled('max_share')) {
                $query->where('max_share', (float) $request->get('max_share'));
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
            $data = BaseRateCalculationNormalizer::normalize($request->validated());
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
                if (in_array($data['rate_type'], ['merchant', 'bank', 'receiver', 'pricer'])) {
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
            $payload = BaseRateCalculationNormalizer::normalize($request->validated());
            $splitData = [
                'reseller_id' => $payload['reseller_id'] ?? null,
                'admin_share_pct' => $payload['admin_share_pct'] ?? null,
                'reseller_share_pct' => $payload['reseller_share_pct'] ?? null,
                'merchant_share_pct' => $payload['merchant_share_pct'] ?? null,
                'is_active' => array_key_exists('split_is_active', $payload) ? (bool) $payload['split_is_active'] : true,
            ];
            unset($payload['reseller_id'], $payload['admin_share_pct'], $payload['reseller_share_pct'], $payload['merchant_share_pct'], $payload['split_is_active']);

            if (!isset($payload['entity_type']) && isset($payload['rate_type'])) {
                if (in_array($payload['rate_type'], ['merchant', 'bank', 'receiver', 'pricer'])) {
                    $payload['entity_type'] = $payload['rate_type'];
                }
            }

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
     * Get entities (merchants, banks, receivers, pricers) for dropdowns.
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
            } elseif ($type === 'receiver') {
                $entities = Partner::select('id', 'name')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($partner) {
                        return ['id' => $partner->id, 'name' => $partner->name];
                    });
            } elseif ($type === 'pricer') {
                $entities = Reseller::select('id', 'name', 'email')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($reseller) {
                        $label = $reseller->name ?: ('Reseller #' . $reseller->id);
                        if (!empty($reseller->email)) {
                            $label .= ' (' . $reseller->email . ')';
                        }
                        return ['id' => $reseller->id, 'name' => $label];
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

    public function getBillingFeeData(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 10), 100);
            $query = BillingFeeRule::query()->with(['definition', 'merchant', 'partner']);

            if ($request->filled('event_type') && $request->get('event_type') !== 'all') {
                $query->where('event_type', $request->get('event_type'));
            }
            if ($request->filled('applies_to_status') && $request->get('applies_to_status') !== 'all') {
                $query->where('applies_to_status', $request->get('applies_to_status'));
            }
            if ($request->filled('payment_method') && $request->get('payment_method') !== 'all') {
                $query->where('payment_method', $request->get('payment_method'));
            }
            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', (int) $request->get('merchant_id'));
            }
            if ($request->filled('search')) {
                $s = $request->get('search');
                $query->where(function ($q) use ($s) {
                    $q->whereHas('definition', function ($d) use ($s) {
                        $d->where('name', 'like', "%{$s}%")
                            ->orWhere('code', 'like', "%{$s}%");
                    })->orWhere('currency', 'like', "%{$s}%");
                });
            }

            $rules = $query->orderByDesc('id')->paginate($perPage);

            $data = collect($rules->items())->map(function (BillingFeeRule $rule) {
                return [
                    'id' => $rule->id,
                    'fee_definition_id' => $rule->fee_definition_id,
                    'fee_name' => $rule->definition?->name,
                    'fee_code' => $rule->definition?->code,
                    'merchant_id' => $rule->merchant_id,
                    'merchant_name' => $rule->merchant?->name,
                    'partner_id' => $rule->partner_id,
                    'partner_name' => $rule->partner?->name,
                    'event_type' => $rule->event_type,
                    'applies_to_status' => $rule->applies_to_status,
                    'payment_method' => $rule->payment_method,
                    'currency' => $rule->currency,
                    'pricing_model' => $rule->pricing_model,
                    'percentage_rate' => $rule->percentage_rate,
                    'fixed_amount' => $rule->fixed_amount,
                    'minimum_amount' => $rule->minimum_amount,
                    'maximum_amount' => $rule->maximum_amount,
                    'hold_days' => $rule->hold_days,
                    'rolling_reserve_cap' => $rule->rolling_reserve_cap,
                    'bill_to' => $rule->bill_to,
                    'referral_commission_percentage' => $rule->referral_commission_percentage,
                    'referral_commission_fixed' => $rule->referral_commission_fixed,
                    'effective_from' => optional($rule->effective_from)->format('Y-m-d\TH:i'),
                    'effective_to' => optional($rule->effective_to)->format('Y-m-d\TH:i'),
                    'priority' => $rule->priority,
                    'is_active' => $rule->is_active,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $rules->currentPage(),
                    'per_page' => $rules->perPage(),
                    'total' => $rules->total(),
                    'last_page' => $rules->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch billing fee rules: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getBillingFeeMeta(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'definitions' => BillingFeeDefinition::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'category']),
                'merchants' => Merchant::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
                'partners' => Partner::query()->orderBy('name')->get(['id', 'name']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch billing fee meta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeBillingFeeRule(Request $request): JsonResponse
    {
        return $this->saveBillingFeeRule($request);
    }

    public function storeBillingFeeDefinition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:billing_fee_definitions,code',
            'name' => 'required|string|max:128',
            'category' => 'required|string|max:32',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        try {
            $definition = BillingFeeDefinition::create($validated);
            return response()->json([
                'success' => true,
                'message' => 'Fee definition created successfully',
                'data' => $definition,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee definition: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateBillingFeeRule(Request $request, int $id): JsonResponse
    {
        return $this->saveBillingFeeRule($request, $id);
    }

    public function destroyBillingFeeRule(int $id): JsonResponse
    {
        try {
            $rule = BillingFeeRule::findOrFail($id);
            $rule->delete();
            return response()->json(['success' => true, 'message' => 'Billing fee rule deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete billing fee rule: ' . $e->getMessage()], 500);
        }
    }

    protected function saveBillingFeeRule(Request $request, ?int $id = null): JsonResponse
    {
        $validated = $request->validate([
            'fee_definition_id' => 'required|exists:billing_fee_definitions,id',
            'merchant_id' => 'nullable|exists:merchants,id',
            'partner_id' => 'nullable|exists:partners,id',
            'event_type' => 'required|string|max:32',
            'applies_to_status' => 'required|in:all,success,failed',
            'payment_method' => 'nullable|string|max:32',
            'currency' => 'nullable|string|max:8',
            'pricing_model' => 'required|in:percentage,fixed,percentage_plus_fixed',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'hold_days' => 'nullable|integer|min:0',
            'rolling_reserve_cap' => 'nullable|numeric|min:0',
            'bill_to' => 'required|in:merchant,partner',
            'referral_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'referral_commission_fixed' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'nullable|integer|min:1|max:1000',
            'is_active' => 'required|boolean',
        ]);

        try {
            if ($id) {
                $rule = BillingFeeRule::findOrFail($id);
                $rule->update($validated);
                return response()->json(['success' => true, 'message' => 'Billing fee rule updated successfully', 'data' => $rule->fresh()]);
            }

            $rule = BillingFeeRule::create($validated);
            return response()->json(['success' => true, 'message' => 'Billing fee rule created successfully', 'data' => $rule], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to save billing fee rule: ' . $e->getMessage()], 500);
        }
    }
}
