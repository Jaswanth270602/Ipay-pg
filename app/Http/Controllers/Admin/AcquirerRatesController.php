<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcquirerRate;
use App\Models\AcquirerAccount;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AcquirerRatesController extends Controller
{
    use LogsConditionally;

    /**
     * Display the acquirer rates page.
     */
    public function index(): View
    {
        $this->logInfo('Admin acquirer rates page accessed', ['user_id' => auth()->id()]);
        return view('admin.acquirer.rates');
    }

    /**
     * Get acquirer rates data for the table.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 100);
            
            $query = AcquirerRate::query()->with('acquirerAccount');

            // Filters
            if ($request->has('payment_mode') && $request->get('payment_mode') !== 'all') {
                $query->where('payment_mode', $request->get('payment_mode'));
            }

            if ($request->has('bank_code') && $request->get('bank_code')) {
                $query->where('bank_code', 'like', "%{$request->get('bank_code')}%");
            }

            if ($request->has('acquirer_name') && $request->get('acquirer_name') !== 'all') {
                $query->where('acquirer_name', $request->get('acquirer_name'));
            }

            if ($request->has('sector') && $request->get('sector') !== 'all') {
                $query->where('sector', $request->get('sector'));
            }

            // Search
            if ($request->has('search') && $request->get('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('account_id', 'like', "%{$search}%")
                      ->orWhere('account_description', 'like', "%{$search}%")
                      ->orWhere('bank_code', 'like', "%{$search}%")
                      ->orWhere('bank_description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            if (in_array($sortBy, ['id', 'payment_mode', 'bank_code', 'acquirer_name', 'account_id', 'sector', 'settlement_time_frame', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->latest();
            }

            $rates = $query->paginate($perPage);

            $data = $rates->items()->map(function($rate) {
                return [
                    'id' => $rate->id,
                    'payment_mode' => $rate->payment_mode,
                    'bank_code' => $rate->bank_code,
                    'bank_description' => $rate->bank_description,
                    'acquirer_name' => $rate->acquirer_name,
                    'account_id' => $rate->account_id,
                    'account_description' => $rate->account_description,
                    'sector' => $rate->sector,
                    'settlement_time_frame' => $rate->settlement_time_frame,
                    'settlement_time_of_day' => $rate->settlement_time_of_day,
                    'fixed_fee_mdr' => $rate->fixed_fee_mdr,
                    'percentage_mdr' => $rate->percentage_mdr,
                    'service_tax_rates' => $rate->service_tax_rates,
                    'min_amount' => $rate->min_amount,
                    'max_amount' => $rate->max_amount,
                    'min_transaction_charge' => $rate->min_transaction_charge,
                    'max_transaction_charge' => $rate->max_transaction_charge,
                    'is_enabled' => $rate->is_enabled,
                    'part_paid_id' => $rate->part_paid_id,
                    'acquirer_account_id' => $rate->acquirer_account_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
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
                'message' => 'Failed to fetch acquirer rates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get acquirer accounts for dropdown.
     */
    public function getAcquirerAccounts(): JsonResponse
    {
        $accounts = AcquirerAccount::select('id', 'account_id', 'acquirer_name', 'description')
            ->where('is_active', true)
            ->orderBy('acquirer_name')
            ->orderBy('account_id')
            ->get()
            ->map(function($account) {
                return [
                    'id' => $account->id,
                    'account_id' => $account->account_id,
                    'acquirer_name' => $account->acquirer_name,
                    'description' => $account->description,
                    'display' => $account->acquirer_name . ' - ' . $account->account_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get acquirer names for dropdown.
     */
    public function getAcquirerNames(): JsonResponse
    {
        $names = AcquirerAccount::select('acquirer_name')
            ->distinct()
            ->whereNotNull('acquirer_name')
            ->orderBy('acquirer_name')
            ->pluck('acquirer_name');

        // Add common acquirer names
        $commonNames = [
            'A2Pay', 'ABCMoney', 'AbhiJack', 'AblePay', 'Accosis', 'AccureUpi',
            'AfrimoneyDrc', 'Aggrepay', 'AggrePayDirect', 'AirPay', 'AirtelRwanda',
            'AirtelRwandaV2', 'AirtelUpi', 'AKGPay', 'ALLAHABAD', 'AmazonPay', 'ApexPayUni',
            'Paytm', 'Switch', 'HDFC', 'ICICI', 'Razorpay', 'PayU'
        ];

        $allNames = collect($commonNames)->merge($names)->unique()->sort()->values();

        return response()->json([
            'success' => true,
            'data' => $allNames,
        ]);
    }

    /**
     * Get payment modes.
     */
    public function getPaymentModes(): JsonResponse
    {
        $modes = [
            'ATM Card',
            'Bank Transfer',
            'BBPS',
            'Bharat QR',
            'Bharat QR(Static)',
            'Cardless EMI',
            'Cash Card',
            'Commercial Credit Card',
            'Credit Card',
            'Debit Card',
            'Debit Pin',
            'Direct EMI',
            'E-Collect',
            'EazyPay',
            'EMI',
            'Enach',
            'International Credit Card',
            'International Debit Card',
            'Netbanking',
            'PayLater',
            'Peer to Peer',
            'Pharmarack Credit Card',
            'POS',
            'Prepaid Card',
            'UPI',
            'Wallet',
            'WhatsApp',
        ];

        return response()->json([
            'success' => true,
            'data' => $modes,
        ]);
    }

    /**
     * Get banks for dropdown.
     */
    public function getBanks(Request $request): JsonResponse
    {
        // Get unique bank codes and descriptions from rates
        $banks = AcquirerRate::select('bank_code', 'bank_description')
            ->whereNotNull('bank_code')
            ->distinct()
            ->get()
            ->map(function($rate) {
                return [
                    'code' => $rate->bank_code,
                    'name' => $rate->bank_description ?: $rate->bank_code,
                ];
            });

        // Also include common banks
        $commonBanks = [
            ['code' => 'HDFC', 'name' => 'HDFC Bank'],
            ['code' => 'ICICI', 'name' => 'ICICI Bank'],
            ['code' => 'SBI', 'name' => 'State Bank of India'],
            ['code' => 'AXIS', 'name' => 'Axis Bank'],
            ['code' => 'PNB', 'name' => 'Punjab National Bank'],
            ['code' => 'BOB', 'name' => 'Bank of Baroda'],
            ['code' => 'BOI', 'name' => 'Bank of India'],
            ['code' => 'UBI', 'name' => 'Union Bank of India'],
            ['code' => 'CAN', 'name' => 'Canara Bank'],
            ['code' => 'KOTAK', 'name' => 'Kotak Mahindra Bank'],
            ['code' => 'YES', 'name' => 'Yes Bank'],
            ['code' => 'IDBI', 'name' => 'IDBI Bank'],
            ['code' => 'FEDERAL', 'name' => 'Federal Bank'],
            ['code' => 'RBL', 'name' => 'RBL Bank'],
            ['code' => 'INDB', 'name' => 'IndusInd Bank'],
            ['code' => 'IDFC', 'name' => 'IDFC First Bank'],
        ];

        // Merge and deduplicate
        $mergedBanks = [];
        foreach ($commonBanks as $bank) {
            $mergedBanks[$bank['code']] = $bank;
        }
        foreach ($banks as $bank) {
            if (!isset($mergedBanks[$bank['code']])) {
                $mergedBanks[$bank['code']] = $bank;
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($mergedBanks),
        ]);
    }

    /**
     * Store a newly created acquirer rate.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'acquirer_account_id' => 'required|exists:acquirer_accounts,id',
            'payment_mode' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'bank_description' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'settlement_time_frame' => 'required|string|max:20',
            'settlement_time_of_day' => 'required|string|max:50',
            'fixed_fee_mdr' => 'required|numeric|min:0',
            'percentage_mdr' => 'required|numeric|min:0|max:100',
            'service_tax_rates' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0|gte:min_amount',
            'min_transaction_charge' => 'required|numeric|min:0',
            'max_transaction_charge' => 'required|numeric|min:0|gte:min_transaction_charge',
            'is_enabled' => 'boolean',
            'part_paid_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get acquirer account details
            $acquirerAccount = AcquirerAccount::findOrFail($request->acquirer_account_id);

            $rate = AcquirerRate::create([
                'acquirer_account_id' => $request->acquirer_account_id,
                'payment_mode' => $request->payment_mode,
                'bank_code' => $request->bank_code,
                'bank_description' => $request->bank_description,
                'acquirer_name' => $acquirerAccount->acquirer_name,
                'account_id' => $acquirerAccount->account_id,
                'account_description' => $acquirerAccount->description,
                'sector' => $request->sector ?? $acquirerAccount->sector,
                'settlement_time_frame' => $request->settlement_time_frame,
                'settlement_time_of_day' => $request->settlement_time_of_day,
                'fixed_fee_mdr' => $request->fixed_fee_mdr,
                'percentage_mdr' => $request->percentage_mdr,
                'service_tax_rates' => $request->service_tax_rates ?? 0,
                'min_amount' => $request->min_amount,
                'max_amount' => $request->max_amount,
                'min_transaction_charge' => $request->min_transaction_charge,
                'max_transaction_charge' => $request->max_transaction_charge,
                'is_enabled' => $request->boolean('is_enabled', true),
                'part_paid_id' => $request->part_paid_id,
            ]);

            $this->logInfo('Acquirer rate created', [
                'user_id' => auth()->id(),
                'rate_id' => $rate->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acquirer rate created successfully',
                'data' => $rate,
            ], 201);
        } catch (\Exception $e) {
            $this->logError('Failed to create acquirer rate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create acquirer rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified acquirer rate.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $rate = AcquirerRate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'acquirer_account_id' => 'sometimes|exists:acquirer_accounts,id',
            'payment_mode' => 'sometimes|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'bank_description' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'settlement_time_frame' => 'sometimes|string|max:20',
            'settlement_time_of_day' => 'sometimes|string|max:50',
            'fixed_fee_mdr' => 'sometimes|numeric|min:0',
            'percentage_mdr' => 'sometimes|numeric|min:0|max:100',
            'service_tax_rates' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric|min:0|gte:min_amount',
            'min_transaction_charge' => 'sometimes|numeric|min:0',
            'max_transaction_charge' => 'sometimes|numeric|min:0|gte:min_transaction_charge',
            'is_enabled' => 'boolean',
            'part_paid_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update acquirer account details if account changed
            if ($request->has('acquirer_account_id') && $request->acquirer_account_id != $rate->acquirer_account_id) {
                $acquirerAccount = AcquirerAccount::findOrFail($request->acquirer_account_id);
                $rate->acquirer_name = $acquirerAccount->acquirer_name;
                $rate->account_id = $acquirerAccount->account_id;
                $rate->account_description = $acquirerAccount->description;
            }

            $rate->update($validator->validated());

            $this->logInfo('Acquirer rate updated', [
                'user_id' => auth()->id(),
                'rate_id' => $rate->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acquirer rate updated successfully',
                'data' => $rate->fresh(),
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to update acquirer rate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update acquirer rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified acquirer rate.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $rate = AcquirerRate::findOrFail($id);
            $rate->delete();

            $this->logInfo('Acquirer rate deleted', [
                'user_id' => auth()->id(),
                'rate_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acquirer rate deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete acquirer rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate an acquirer rate.
     */
    public function duplicate(string $id): JsonResponse
    {
        try {
            $originalRate = AcquirerRate::findOrFail($id);
            
            $newRate = $originalRate->replicate();
            $newRate->account_id = $originalRate->account_id . '_copy';
            $newRate->save();

            $this->logInfo('Acquirer rate duplicated', [
                'user_id' => auth()->id(),
                'original_rate_id' => $id,
                'new_rate_id' => $newRate->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acquirer rate duplicated successfully',
                'data' => $newRate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate acquirer rate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
