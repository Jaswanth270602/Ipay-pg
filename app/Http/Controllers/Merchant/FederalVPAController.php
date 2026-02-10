<?php

namespace App\Http\Controllers\Merchant;

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
        return view('merchant.payments.federal-vpa');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('federal_vpa_payments')
                ->where('merchant_id', $merchant->id)
                ->latest('created_at');

            // Filters
            if ($request->has('filter_reference_id') && $request->get('filter_reference_id')) {
                $query->where('reference_id', 'like', "%{$request->get('filter_reference_id')}%");
            }
            if ($request->has('filter_payment_status') && $request->get('filter_payment_status') !== 'all') {
                $query->where('payment_status', $request->get('filter_payment_status'));
            }
            if ($request->has('filter_response_received') && $request->get('filter_response_received') !== 'all') {
                $query->where('response_received', $request->get('filter_response_received'));
            }

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
                }
            }

            $payments = $query->paginate($perPage);

            $data = collect($payments->items())->map(function($payment) use ($merchant) {
                return [
                    'id' => $payment->id,
                    'reference_id' => $payment->reference_id ?? '-',
                    'merchant_id' => $payment->merchant_id ?? '-',
                    'merchant_name' => $merchant->name ?? '-',
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


