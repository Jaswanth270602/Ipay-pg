<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SettlementEngine
{
    /**
     * Process daily settlements for all merchants.
     * This runs every day at configured time (default: 11 PM).
     */
    public function processDailySettlements(Carbon $date = null): array
    {
        $date = $date ?? Carbon::yesterday(); // Process previous day's transactions
        $results = [];

        Log::info('Starting daily settlement processing', ['date' => $date->toDateString()]);

        try {
            // Get all merchants
            $merchants = Merchant::where('status', 'active')->get();

            foreach ($merchants as $merchant) {
                $result = $this->processSettlementForMerchant($merchant, $date);
                $results[] = $result;
            }

            Log::info('Daily settlement processing completed', [
                'date' => $date->toDateString(),
                'merchants_processed' => count($results),
                'settlements_created' => collect($results)->where('created', true)->count()
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Error in daily settlement processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process settlement for a specific merchant.
     */
    public function processSettlementForMerchant(Merchant $merchant, Carbon $date): array
    {
        // Get merchant's settlement cycles
        $domesticCycle = $merchant->settlement_cycle_domestic ?? 1;
        $internationalCycle = $merchant->settlement_cycle_international ?? 7;

        // Calculate the transaction date that should be settled today
        // For T+X cycle, transactions from (X days ago) should be settled today
        $domesticCutoffDate = now()->subDays($domesticCycle);
        $internationalCutoffDate = now()->subDays($internationalCycle);

        // Get transactions that are ready for settlement based on their cycle
        $transactions = Transaction::where('merchant_id', $merchant->id)
            ->where('status', 'success')
            ->where('settlement_status', 'pending')
            ->where(function($query) use ($domesticCutoffDate, $internationalCutoffDate) {
                // Domestic transactions (INR): captured_at <= (today - domestic_cycle)
                $query->where(function($q) use ($domesticCutoffDate) {
                    $q->where('currency', 'INR')
                      ->where('captured_at', '<=', $domesticCutoffDate);
                })
                // International transactions: captured_at <= (today - international_cycle)
                ->orWhere(function($q) use ($internationalCutoffDate) {
                    $q->where('currency', '!=', 'INR')
                      ->where('captured_at', '<=', $internationalCutoffDate);
                });
            })
            ->get();

        if ($transactions->isEmpty()) {
            return [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->name,
                'created' => false,
                'message' => 'No transactions ready for settlement (based on T+' . $domesticCycle . '/T+' . $internationalCycle . ' cycles)'
            ];
        }

        // Separate transactions by type for proper settlement date calculation
        $domesticTransactions = $transactions->where('currency', 'INR');
        $internationalTransactions = $transactions->where('currency', '!=', 'INR');

        // Process settlements separately if we have both types, or combined if only one type
        if ($domesticTransactions->isNotEmpty() && $internationalTransactions->isNotEmpty()) {
            // Create separate settlements for domestic and international
            $domesticSettlement = $this->createSettlement($merchant, $domesticTransactions, 
                $this->calculateSettlementAmounts($domesticTransactions), 
                $domesticCutoffDate, 'domestic');
            $this->markTransactionsAsSettled($domesticTransactions, $domesticSettlement);

            $internationalSettlement = $this->createSettlement($merchant, $internationalTransactions, 
                $this->calculateSettlementAmounts($internationalTransactions), 
                $internationalCutoffDate, 'international');
            $this->markTransactionsAsSettled($internationalTransactions, $internationalSettlement);

            return [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->name,
                'created' => true,
                'settlement_id' => $domesticSettlement->settlement_id . ', ' . $internationalSettlement->settlement_id,
                'transaction_count' => $transactions->count(),
                'net_amount' => $domesticSettlement->net_amount + $internationalSettlement->net_amount
            ];
        } else {
            // Single settlement for all transactions (all domestic or all international)
            $transactionType = $domesticTransactions->isNotEmpty() ? 'domestic' : 'international';
            $cutoffDate = $transactionType === 'domestic' ? $domesticCutoffDate : $internationalCutoffDate;
            
            $calculation = $this->calculateSettlementAmounts($transactions);
            $settlement = $this->createSettlement($merchant, $transactions, $calculation, $cutoffDate, $transactionType);
            $this->markTransactionsAsSettled($transactions, $settlement);

            return [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->name,
                'created' => true,
                'settlement_id' => $settlement->settlement_id,
                'transaction_count' => $transactions->count(),
                'net_amount' => $settlement->net_amount
            ];
        }
    }

    /**
     * Calculate settlement amounts including fees, GST, refunds.
     */
    protected function calculateSettlementAmounts($transactions): array
    {
        $grossAmount = $transactions->sum('amount');
        $feeAmount = $transactions->sum('fee_amount');
        $gstAmount = $transactions->sum('gst_amount');
        $otherFees = $transactions->sum('other_fees');

        // Get refunds for these transactions
        $transactionIds = $transactions->pluck('id');
        $refundAmount = DB::table('refunds')
            ->whereIn('transaction_id', $transactionIds)
            ->where('status', 'completed')
            ->sum('amount');

        $refundCount = DB::table('refunds')
            ->whereIn('transaction_id', $transactionIds)
            ->where('status', 'completed')
            ->count();

        // Net amount = Gross - Fees - GST - Other Fees - Refunds
        $netAmount = $grossAmount - $feeAmount - $gstAmount - $otherFees - $refundAmount;

        return [
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'gst_amount' => $gstAmount,
            'other_fees' => $otherFees,
            'refund_amount' => $refundAmount,
            'refund_count' => $refundCount,
            'net_amount' => $netAmount,
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Create settlement record.
     */
    protected function createSettlement(Merchant $merchant, $transactions, array $calculation, Carbon $cutoffDate, string $transactionType = 'domestic'): Settlement
    {
        // Get the appropriate settlement cycle
        $settlementCycle = $transactionType === 'domestic' 
            ? ($merchant->settlement_cycle_domestic ?? 1)
            : ($merchant->settlement_cycle_international ?? 7);

        // Settlement date is today (when the settlement is being processed)
        $settlementDate = now();

        $firstTransaction = $transactions->first();
        $lastTransaction = $transactions->last();

        $settlement = Settlement::create([
            'merchant_id' => $merchant->id,
            'settlement_id' => $this->generateSettlementId($merchant, $settlementDate),
            'amount' => $calculation['gross_amount'],
            'fee_amount' => $calculation['fee_amount'] + $calculation['gst_amount'] + $calculation['other_fees'],
            'refund_amount' => $calculation['refund_amount'],
            'net_amount' => $calculation['net_amount'],
            'payout_amount' => $calculation['net_amount'],
            'currency' => $firstTransaction->currency ?? 'INR',
            'transaction_count' => $calculation['transaction_count'],
            'refund_count' => $calculation['refund_count'],
            'period_start' => $firstTransaction->captured_at ?? now(),
            'period_end' => $lastTransaction->captured_at ?? now(),
            'payment_start_date' => $firstTransaction->captured_at ? $firstTransaction->captured_at->toDateString() : now()->toDateString(),
            'payment_end_date' => $lastTransaction->captured_at ? $lastTransaction->captured_at->toDateString() : now()->toDateString(),
            'settlement_date' => $settlementDate->toDateString(),
            'status' => 'pending',
            'settlement_status' => 'pending',
            'bank_details' => [
                'account_name' => $merchant->bank_account_holder_name,
                'account_number' => $merchant->bank_account_number,
                'ifsc_code' => $merchant->bank_ifsc_code,
                'bank_name' => $merchant->bank_name,
                'branch' => $merchant->bank_branch,
            ],
            'account_name' => $merchant->bank_account_holder_name,
            'account_number' => $merchant->bank_account_number,
            'ifsc_code' => $merchant->bank_ifsc_code,
            'bank_name' => $merchant->bank_name,
            'bank_branch' => $merchant->bank_branch,
            'settlement_description' => ucfirst($transactionType) . " settlement (T+{$settlementCycle}) processed on {$settlementDate->toDateString()}",
        ]);

        Log::info('Settlement created', [
            'settlement_id' => $settlement->settlement_id,
            'merchant_id' => $merchant->id,
            'transaction_type' => $transactionType,
            'settlement_cycle' => "T+{$settlementCycle}",
            'net_amount' => $settlement->net_amount
        ]);

        return $settlement;
    }

    /**
     * Mark transactions as settled.
     */
    protected function markTransactionsAsSettled($transactions, Settlement $settlement): void
    {
        Transaction::whereIn('id', $transactions->pluck('id'))
            ->update([
                'settlement_id' => $settlement->id,
                'settlement_status' => 'settled',
                'settled_at' => now(),
            ]);
    }

    /**
     * Generate unique settlement ID.
     */
    protected function generateSettlementId(Merchant $merchant, Carbon $date): string
    {
        return 'STL_' . strtoupper($date->format('Ymd')) . '_M' . $merchant->id . '_' . strtoupper(\Illuminate\Support\Str::random(8));
    }
}


