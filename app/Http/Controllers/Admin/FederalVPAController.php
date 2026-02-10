<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class FederalVPAController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin federal VPA payments page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.federal-vpa');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('federal_vpa_payments')
                ->leftJoin('merchants', 'federal_vpa_payments.merchant_id', '=', 'merchants.id')
                ->select('federal_vpa_payments.*', 'merchants.name as merchant_name', 'merchants.id as merchant_id_val');

            // Filters
            if ($request->has('filter_reference_id') && $request->get('filter_reference_id')) {
                $query->where('federal_vpa_payments.reference_id', 'like', "%{$request->get('filter_reference_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('federal_vpa_payments.merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->where('merchants.name', 'like', "%{$request->get('filter_merchant_name')}%");
            }
            if ($request->has('filter_payment_status') && $request->get('filter_payment_status') !== 'all') {
                $query->where('federal_vpa_payments.payment_status', $request->get('filter_payment_status'));
            }
            if ($request->has('filter_response_received') && $request->get('filter_response_received') !== 'all') {
                $query->where('federal_vpa_payments.response_received', $request->get('filter_response_received'));
            }

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('federal_vpa_payments.created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            $payments = $query->latest('federal_vpa_payments.created_at')->paginate($perPage);

            $data = collect($payments->items())->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'reference_id' => $payment->reference_id ?? '-',
                    'merchant_id' => $payment->merchant_id ?? '-',
                    'merchant_name' => $payment->merchant_name ?? '-',
                    'payment_status' => $payment->payment_status ?? 'pending',
                    'response_received' => $payment->response_received ?? 'No',
                    'response_data' => $payment->response_data ?? '-',
                    'created_at' => $payment->created_at ? date('Y-m-d H:i:s', strtotime($payment->created_at)) : '-',
                ];
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
}
