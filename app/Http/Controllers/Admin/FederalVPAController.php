<?php

namespace App\Http\Controllers\Admin;

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
        $this->logInfo('Admin federal VPA payments page accessed', ['user_id' => auth()->id()]);

        return view('admin.payments.federal-vpa');
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

            $perPage = min($request->get('per_page', 5), 50);

            $query = DB::table('federal_vpa_payments')
                ->leftJoin('merchants', 'federal_vpa_payments.merchant_id', '=', 'merchants.id')
                ->select('federal_vpa_payments.*', 'merchants.name as merchant_name');

            $this->federalVpaList->applyFilters($query, $request, false);

            $payments = $query->latest('federal_vpa_payments.created_at')->paginate($perPage);

            $data = collect($payments->items())->map(fn ($payment) => $this->federalVpaList->mapRow($payment));

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

        $row = DB::table('federal_vpa_payments')
            ->leftJoin('merchants', 'federal_vpa_payments.merchant_id', '=', 'merchants.id')
            ->where('federal_vpa_payments.id', $id)
            ->select('federal_vpa_payments.*', 'merchants.name as merchant_name')
            ->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->federalVpaList->mapDetail($row, $row->merchant_name ?? null),
        ]);
    }
}
