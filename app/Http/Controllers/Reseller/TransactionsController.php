<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionsController extends Controller
{
    public function index(Request $request): View
    {
        $reseller = $request->user()->reseller;
        $merchantOptions = $reseller
            ? $reseller->merchants()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('reseller.transactions', [
            'user' => $request->user(),
            'reseller' => $reseller,
            'merchantOptions' => $merchantOptions,
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller not found',
            ], 403);
        }

        $merchantIds = $reseller->merchants()->pluck('id');
        if ($merchantIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1,
                    'from' => null,
                    'to' => null,
                ],
            ]);
        }

        $perPage = min((int) $request->get('per_page', 10), 50);
        $query = Transaction::query()
            ->with(['merchant', 'order.paymentLink'])
            ->whereIn('merchant_id', $merchantIds)
            ->latest();

        if ($request->filled('merchant_id')) {
            $mid = (int) $request->get('merchant_id');
            if ($merchantIds->contains($mid)) {
                $query->where('merchant_id', $mid);
            }
        }

        if ($request->has('date_range') && $request->get('date_range') !== '') {
            $dates = explode(' - ', $request->get('date_range'));
            if (count($dates) === 2 && trim($dates[0]) !== '' && trim($dates[1]) !== '') {
                $query->whereBetween('created_at', [trim($dates[0]), trim($dates[1])]);
            }
        }

        $status = $request->get('status');
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $page = max(1, (int) $request->get('page', 1));
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $transactions = $query->forPage($page, $perPage)->get();

        $data = $transactions->map(function (Transaction $transaction) {
            try {
                $paymentDetails = $transaction->getSanitizedPaymentDetails() ?? [];
                $gatewayResponse = $transaction->getSanitizedGatewayResponse() ?? [];
            } catch (\Throwable $e) {
                $paymentDetails = [];
                $gatewayResponse = [];
            }

            return [
                'id' => $transaction->id,
                'merchant_id' => $transaction->merchant_id,
                'merchant_name' => $transaction->merchant->name ?? '-',
                'transaction_initiation_time' => $transaction->created_at->format('d-m-Y H:i:s'),
                'transaction_datetime' => $transaction->created_at->format('d-m-Y H:i:s'),
                'transaction_id' => $transaction->txn_id,
                'transaction_order_id' => $transaction->order_id ?? '-',
                'amount_paid_by_customer' => number_format((float) $transaction->amount, 2),
                'payment_status' => $transaction->status,
                'payment_mode' => $transaction->payment_method ?? '-',
                'payment_channel' => $gatewayResponse['channel'] ?? '-',
                'currency_code' => $transaction->currency ?? 'INR',
                'card_holder_name' => $paymentDetails['card_holder_name'] ?? $paymentDetails['card_holder'] ?? '-',
                'card_number' => isset($paymentDetails['last4']) ? '****' . $paymentDetails['last4'] : '-',
                'upi_id' => $gatewayResponse['upi_id'] ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->all(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
                'to' => $total > 0 ? min($page * $perPage, $total) : null,
            ],
        ]);
    }
}
