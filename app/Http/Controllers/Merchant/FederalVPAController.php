<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FederalVpaPaymentsListService;
use App\Traits\LogsConditionally;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FederalVPAController extends Controller
{
    use LogsConditionally;

    public function __construct(
        protected FederalVpaPaymentsListService $federalVpaList
    ) {}

    public function index(): View
    {
        return view('merchant.payments.federal-vpa');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            if (! $this->federalVpaList->tableExists()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => min($request->get('per_page', 5), 50),
                        'total' => 0,
                        'last_page' => 1,
                    ],
                ]);
            }

            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);

            $query = DB::table('federal_vpa_payments')
                ->where('merchant_id', $merchant->id);

            $this->federalVpaList->applyFilters($query, $request, true);

            $payments = $query->latest('created_at')->paginate($perPage);

            $data = collect($payments->items())->map(function ($payment) use ($merchant) {
                $row = $this->federalVpaList->mapRow($payment);
                $row['merchant_name'] = $merchant->name ?? '-';

                return $row;
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch federal VPA payments',
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->federalVpaList->tableExists()) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $merchant = $request->user()->merchant;
        $row = DB::table('federal_vpa_payments')
            ->where('id', $id)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->federalVpaList->mapDetail($row, $merchant->name ?? null),
        ]);
    }
}
