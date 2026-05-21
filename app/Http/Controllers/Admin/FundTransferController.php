<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FundTransferController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin fund transfer page accessed', ['user_id' => auth()->id()]);
        return view('admin.settlements.fund-transfer');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('fund_transfers')
                ->leftJoin('merchants', 'fund_transfers.merchant_id', '=', 'merchants.id')
                ->select('fund_transfers.*', 'merchants.name as merchant_name', 'merchants.id as merchant_id_val');

            // Filters
            if ($request->has('filter_reference_id') && $request->get('filter_reference_id')) {
                $query->where('fund_transfers.reference_id', 'like', "%{$request->get('filter_reference_id')}%");
            }
            if ($request->has('filter_merchant_id') && $request->get('filter_merchant_id')) {
                $query->where('fund_transfers.merchant_id', $request->get('filter_merchant_id'));
            }
            if ($request->has('filter_merchant_name') && $request->get('filter_merchant_name')) {
                $query->where('merchants.name', 'like', "%{$request->get('filter_merchant_name')}%");
            }
            if ($request->has('filter_transfer_qualifier') && $request->get('filter_transfer_qualifier') !== 'all') {
                $query->where('fund_transfers.transfer_qualifier', $request->get('filter_transfer_qualifier'));
            }
            if ($request->has('filter_fund_received') && $request->get('filter_fund_received') !== 'all') {
                $query->where('fund_transfers.fund_received', $request->get('filter_fund_received'));
            }

            // Date range filter
            if ($request->has('date_range') && $request->get('date_range')) {
                $dates = explode(' - ', $request->get('date_range'));
                if (count($dates) === 2) {
                    $query->whereBetween('fund_transfers.transfer_date', [trim($dates[0]), trim($dates[1])]);
                }
            }

            $transfers = $query->latest('fund_transfers.transfer_date')->paginate($perPage);

            $data = collect($transfers->items())->map(function($transfer) {
                return [
                    'id' => $transfer->id,
                    'reference_id' => $transfer->reference_id ?? '-',
                    'transfer_reference_id' => $transfer->transfer_reference_id ?? '-',
                    'merchant_id' => $transfer->merchant_id ?? '-',
                    'merchant_name' => $transfer->merchant_name ?? '-',
                    'transfer_qualifier' => $transfer->transfer_qualifier ?? '-',
                    'purpose_of_payment' => $transfer->purpose_of_payment ?? '-',
                    'transfer_reference_no' => $transfer->transfer_reference_no ?? '-',
                    'transfer_mode' => $transfer->transfer_mode ?? '-',
                    'transfer_date' => $transfer->transfer_date ? date('Y-m-d', strtotime($transfer->transfer_date)) : '-',
                    'transfer_amount' => number_format($transfer->transfer_amount ?? 0, 2),
                    'credited_amount' => number_format($transfer->credited_amount ?? 0, 2),
                    'debited_amount' => number_format($transfer->debited_amount ?? 0, 2),
                    'to_account' => $transfer->to_account ?? '-',
                    'bank_name_ca' => $transfer->bank_name_ca ?? '-',
                    'fund_received' => $transfer->fund_received ?? 'No',
                    'fund_received_with_commission' => $transfer->fund_received_with_commission ?? 'No',
                    'notes' => $transfer->notes ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $transfers->currentPage(),
                    'per_page' => $transfers->perPage(),
                    'total' => $transfers->total(),
                    'last_page' => $transfers->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fund transfers',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        foreach (['purpose_of_payment', 'transfer_reference_no', 'to_account', 'bank_name_ca'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }

        // Angular date input sends ISO strings; normalize for MySQL DATE column.
        if (array_key_exists('transfer_date', $payload) && $payload['transfer_date'] !== '' && $payload['transfer_date'] !== null) {
            try {
                $payload['transfer_date'] = Carbon::parse($payload['transfer_date'])->toDateString();
            } catch (\Throwable) {
                // Validator will reject invalid dates.
            }
        }

        $validator = Validator::make($payload, [
            'merchant_id' => 'required|exists:merchants,id',
            'transfer_qualifier' => 'required|in:MERCHANT LEDGER,SETTLEMENT,REFUND',
            'transfer_date' => 'required|date',
            'transfer_amount' => 'required|numeric|min:0',
            'purpose_of_payment' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]*$/'],
            'transfer_reference_no' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]*$/'],
            'credited_amount' => 'nullable|numeric|min:0',
            'debited_amount' => 'nullable|numeric|min:0',
            'to_account' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]*$/'],
            'bank_name_ca' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]*$/'],
        ], [
            'transfer_amount.numeric' => 'Transfer amount may contain only numbers and decimal.',
            'transfer_amount.min' => 'Transfer amount cannot be negative.',
            'purpose_of_payment.regex' => 'Purpose of payment may contain only letters, numbers, and spaces.',
            'transfer_reference_no.regex' => 'Transfer reference no may contain only letters, numbers, and spaces.',
            'credited_amount.numeric' => 'Credited amount may contain only numbers and decimal.',
            'credited_amount.min' => 'Credited amount cannot be negative.',
            'debited_amount.numeric' => 'Debited amount may contain only numbers and decimal.',
            'debited_amount.min' => 'Debited amount cannot be negative.',
            'to_account.regex' => 'To account may contain only letters, numbers, and spaces.',
            'bank_name_ca.regex' => 'Bank Name (CA) may contain only letters, numbers, and spaces.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $referenceId = 'FT_' . strtoupper(uniqid());
            
            $data = $validator->validated();

            $fundTransfer = DB::table('fund_transfers')->insertGetId([
                'merchant_id' => $data['merchant_id'],
                'reference_id' => $referenceId,
                'transfer_reference_id' => $request->transfer_reference_id,
                'transfer_qualifier' => $data['transfer_qualifier'],
                'purpose_of_payment' => $data['purpose_of_payment'] ?? null,
                'transfer_reference_no' => $data['transfer_reference_no'] ?? null,
                'transfer_mode' => $request->transfer_mode ?? 'SFTI ADJ',
                'transfer_date' => $data['transfer_date'],
                'transfer_amount' => $data['transfer_amount'],
                'credited_amount' => $data['credited_amount'] ?? 0,
                'debited_amount' => $data['debited_amount'] ?? 0,
                'to_account' => $data['to_account'] ?? null,
                'bank_name_ca' => $data['bank_name_ca'] ?? null,
                'fund_received' => $request->fund_received ?? 'No',
                'fund_received_with_commission' => $request->fund_received_with_commission ?? 'No',
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fund transfer created successfully',
                'id' => $fundTransfer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fund transfer: ' . $e->getMessage(),
            ], 500);
        }
    }
}
