<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FederalVpaPaymentsListService
{
    /**
     * Apply list filters for federal_vpa_payments (admin or merchant-scoped query).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function applyFilters($query, Request $request, bool $merchantScoped = false): void
    {
        if ($request->filled('filter_reference_id')) {
            $term = $request->get('filter_reference_id');
            $query->where(function ($q) use ($term) {
                $q->where('federal_vpa_payments.reference_id', 'like', "%{$term}%")
                    ->orWhere('federal_vpa_payments.reference_number', 'like', "%{$term}%")
                    ->orWhere('federal_vpa_payments.statement_id', 'like', "%{$term}%");
            });
        }

        if (! $merchantScoped && $request->filled('filter_merchant_id')) {
            $query->where('federal_vpa_payments.merchant_id', $request->get('filter_merchant_id'));
        }

        if (! $merchantScoped && $request->filled('filter_merchant_name')) {
            $query->where('merchants.name', 'like', '%'.$request->get('filter_merchant_name').'%');
        }

        if ($request->get('filter_payment_status') && $request->get('filter_payment_status') !== 'all') {
            $query->where('federal_vpa_payments.status', $request->get('filter_payment_status'));
        }

        $resp = $request->get('filter_response_received');
        if ($resp && $resp !== 'all') {
            if ($resp === 'Yes') {
                $query->where('federal_vpa_payments.response_received', true);
            } elseif ($resp === 'No') {
                $query->where(function ($q) {
                    $q->where('federal_vpa_payments.response_received', false)
                        ->orWhereNull('federal_vpa_payments.response_received');
                });
            }
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->get('date_range'));
            if (count($dates) === 2) {
                $query->whereBetween('federal_vpa_payments.created_at', [trim($dates[0]), trim($dates[1])]);
            }
        }
    }

    /**
     * Map a raw federal_vpa_payments row (+ optional merchant_name) to API row.
     *
     * @param  object  $payment
     */
    public function mapRow($payment): array
    {
        $ref = $payment->reference_id ?? null;
        if ($ref === null || $ref === '') {
            $ref = $payment->reference_number ?? $payment->statement_id ?? '-';
        }

        $received = isset($payment->response_received) ? (bool) $payment->response_received : false;

        return [
            'id' => $payment->id,
            'reference_id' => $ref,
            'merchant_id' => $payment->merchant_id ?? '-',
            'merchant_name' => $payment->merchant_name ?? '-',
            'payment_status' => $payment->status ?? 'pending',
            'response_received' => $received ? 'Yes' : 'No',
            'response_data' => $payment->response_data ?? '-',
            'created_at' => $payment->created_at ? date('Y-m-d H:i:s', strtotime($payment->created_at)) : '-',
        ];
    }

    public function tableExists(): bool
    {
        return Schema::hasTable('federal_vpa_payments');
    }

    /**
     * Full detail for view modal (read-only).
     *
     * @param  object  $row  Row from federal_vpa_payments (optional merchant_name from join)
     */
    public function mapDetail(object $row, ?string $merchantNameOverride = null): array
    {
        $ref = $row->reference_id ?? null;
        if ($ref === null || $ref === '') {
            $ref = $row->reference_number ?? $row->statement_id ?? '-';
        }

        $responseData = $row->response_data ?? null;
        if (is_string($responseData) && $responseData !== '' && $responseData !== '-') {
            $decoded = json_decode($responseData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $responseData = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        $merchantName = $merchantNameOverride ?? ($row->merchant_name ?? null);

        return [
            'id' => $row->id,
            'reference_id' => $ref,
            'merchant_id' => $row->merchant_id,
            'merchant_name' => $merchantName ?? '-',
            'statement_id' => $row->statement_id ?? '-',
            'statement_date' => $row->statement_date ?? '-',
            'vpa_id' => $row->vpa_id ?? '-',
            'transaction_id' => $row->transaction_id ?? '-',
            'order_id' => $row->order_id ?? '-',
            'amount' => $row->amount ?? null,
            'currency' => $row->currency ?? 'INR',
            'transaction_type' => $row->transaction_type ?? '-',
            'reference_number' => $row->reference_number ?? '-',
            'utr_number' => $row->utr_number ?? '-',
            'value_date' => $row->value_date ?? '-',
            'description' => $row->description ?? '-',
            'status' => $row->status ?? 'pending',
            'file_path' => $row->file_path ?? '-',
            'response_received' => ! empty($row->response_received) ? 'Yes' : 'No',
            'response_data' => $responseData ?? '-',
            'created_at' => $row->created_at ? date('Y-m-d H:i:s', strtotime($row->created_at)) : '-',
            'updated_at' => $row->updated_at ? date('Y-m-d H:i:s', strtotime($row->updated_at)) : '-',
        ];
    }
}
