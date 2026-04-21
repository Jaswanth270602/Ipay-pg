<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Services\Settlements\SettlementDetailSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SettlementEngine
{
    public function __construct(
        protected SettlementDetailSyncService $settlementDetailSync,
        protected BillingFeeEngineService $billingFeeEngine
    ) {}

    /**
     * Process daily settlements for all merchants.
     * This runs every day at configured time (default: 11 PM).
     */
    public function processDailySettlements(
        Carbon $date = null,
        ?int $merchantId = null,
        ?string $mode = null,
        bool $dryRun = false
    ): array
    {
        $date = ($date ?? Carbon::now())->copy()->endOfDay();
        $results = [];

        Log::info('Starting daily settlement processing', [
            'date' => $date->toDateString(),
            'merchant_id' => $merchantId,
            'mode' => $mode,
            'dry_run' => $dryRun,
        ]);

        try {
            $merchantQuery = Merchant::where('status', 'active');
            if ($merchantId) {
                $merchantQuery->where('id', $merchantId);
            }
            $merchants = $merchantQuery->get();

            foreach ($merchants as $merchant) {
                $result = $this->processSettlementForMerchant($merchant, $date, $mode, $dryRun);
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
    public function processSettlementForMerchant(
        Merchant $merchant,
        Carbon $date,
        ?string $mode = null,
        bool $dryRun = false
    ): array
    {
        $this->billingFeeEngine->releaseDueReservesForMerchant($merchant->id, $date);

        // Get merchant's settlement cycles
        $domesticCycle = $merchant->settlement_cycle_domestic ?? 1;
        $internationalCycle = $merchant->settlement_cycle_international ?? 7;

        // Calculate the transaction date that should be settled today
        // For T+X cycle, transactions from (X days ago) should be settled today
        $domesticCutoffDate = $date->copy()->subDays($domesticCycle);
        $internationalCutoffDate = $date->copy()->subDays($internationalCycle);

        // Get transactions that are ready for settlement based on their cycle
        $transactionsQuery = Transaction::where('merchant_id', $merchant->id)
            ->where('status', 'success')
            ->where('settlement_status', 'pending')
            ->where(function ($query) use ($domesticCutoffDate, $internationalCutoffDate) {
                // Domestic transactions (INR): captured_at <= (today - domestic_cycle)
                $query->where(function ($q) use ($domesticCutoffDate) {
                    $q->where('currency', 'INR')
                        ->where(function ($timeQuery) use ($domesticCutoffDate) {
                            $timeQuery->where('captured_at', '<=', $domesticCutoffDate)
                                ->orWhere(function ($fallbackQuery) use ($domesticCutoffDate) {
                                    $fallbackQuery->whereNull('captured_at')
                                        ->where('created_at', '<=', $domesticCutoffDate);
                                });
                        });
                })
                // International transactions: captured_at <= (today - international_cycle)
                ->orWhere(function ($q) use ($internationalCutoffDate) {
                    $q->where('currency', '!=', 'INR')
                        ->where(function ($timeQuery) use ($internationalCutoffDate) {
                            $timeQuery->where('captured_at', '<=', $internationalCutoffDate)
                                ->orWhere(function ($fallbackQuery) use ($internationalCutoffDate) {
                                    $fallbackQuery->whereNull('captured_at')
                                        ->where('created_at', '<=', $internationalCutoffDate);
                                });
                        });
                });
            });

        if ($mode === 'test') {
            $transactionsQuery->where('test_mode', true);
        } elseif ($mode === 'live') {
            $transactionsQuery->where('test_mode', false);
        }

        $transactions = $transactionsQuery->orderBy('captured_at')->orderBy('created_at')->get();

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
            if ($dryRun) {
                return [
                    'merchant_id' => $merchant->id,
                    'merchant_name' => $merchant->name,
                    'created' => false,
                    'message' => 'Dry run: ' . $transactions->count() . ' transactions would be settled',
                    'transaction_count' => $transactions->count(),
                    'net_amount' => $this->calculateSettlementAmounts($transactions)['net_amount'],
                ];
            }

            $domesticSettlement = $this->createSettlement($merchant, $domesticTransactions,
                $this->calculateSettlementAmounts($domesticTransactions),
                $date, 'domestic');
            $this->markTransactionsAsSettled($domesticTransactions, $domesticSettlement);
            $this->syncSettlementDetails($merchant, $domesticTransactions, $domesticSettlement);

            $internationalSettlement = $this->createSettlement($merchant, $internationalTransactions,
                $this->calculateSettlementAmounts($internationalTransactions),
                $date, 'international');
            $this->markTransactionsAsSettled($internationalTransactions, $internationalSettlement);
            $this->syncSettlementDetails($merchant, $internationalTransactions, $internationalSettlement);

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
            if ($dryRun) {
                return [
                    'merchant_id' => $merchant->id,
                    'merchant_name' => $merchant->name,
                    'created' => false,
                    'message' => 'Dry run: ' . $transactions->count() . ' transactions would be settled',
                    'transaction_count' => $transactions->count(),
                    'net_amount' => $this->calculateSettlementAmounts($transactions)['net_amount'],
                ];
            }

            $calculation = $this->calculateSettlementAmounts($transactions);
            $settlement = $this->createSettlement($merchant, $transactions, $calculation, $date, $transactionType);
            $this->markTransactionsAsSettled($transactions, $settlement);
            $this->syncSettlementDetails($merchant, $transactions, $settlement);

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

        $refundFeeAmount = DB::table('refunds')
            ->whereIn('transaction_id', $transactionIds)
            ->where('status', 'completed')
            ->sum('fee_amount');

        $refundCount = DB::table('refunds')
            ->whereIn('transaction_id', $transactionIds)
            ->where('status', 'completed')
            ->count();

        $feeBreakdown = $this->billingFeeEngine->buildSettlementFeeBreakdown($transactionIds);
        $reserveHeldAmount = (float) collect($feeBreakdown)
            ->where('fee_code', 'rolling_reserve_hold')
            ->sum('amount');
        $reserveReleasedAmount = (float) collect($feeBreakdown)
            ->where('fee_code', 'rolling_reserve_release')
            ->sum('amount');

        // Net amount = Gross - Fees - GST - Other Fees - Refunds - Refund Fees + Reserve Releases
        $netAmount = $grossAmount - $feeAmount - $gstAmount - $otherFees - $refundAmount - $refundFeeAmount + $reserveReleasedAmount;

        return [
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'gst_amount' => $gstAmount,
            'other_fees' => $otherFees,
            'refund_amount' => $refundAmount,
            'refund_fee_amount' => $refundFeeAmount,
            'refund_count' => $refundCount,
            'net_amount' => $netAmount,
            'transaction_count' => $transactions->count(),
            'fee_breakdown' => $feeBreakdown,
            'reserve_held_amount' => $reserveHeldAmount,
            'reserve_released_amount' => $reserveReleasedAmount,
        ];
    }

    /**
     * Create settlement record.
     */
    protected function createSettlement(Merchant $merchant, $transactions, array $calculation, Carbon $processDate, string $transactionType = 'domestic'): Settlement
    {
        // Get the appropriate settlement cycle
        $settlementCycle = $transactionType === 'domestic' 
            ? ($merchant->settlement_cycle_domestic ?? 1)
            : ($merchant->settlement_cycle_international ?? 7);

        // Settlement date is today (when the settlement is being processed)
        $settlementDate = $processDate->copy();

        $firstTransaction = $transactions->first();
        $lastTransaction = $transactions->last();

        $settlement = Settlement::create([
            'merchant_id' => $merchant->id,
            'test_mode' => (bool) ($firstTransaction->test_mode ?? false),
            'settlement_id' => $this->generateSettlementId($merchant, $settlementDate),
            'amount' => $calculation['gross_amount'],
            'fee_amount' => $calculation['fee_amount'] + $calculation['gst_amount'] + $calculation['other_fees'] + ($calculation['refund_fee_amount'] ?? 0),
            'refund_amount' => $calculation['refund_amount'],
            'net_amount' => $calculation['net_amount'],
            'payout_amount' => $calculation['net_amount'],
            'fee_breakdown' => $calculation['fee_breakdown'] ?? [],
            'reserve_held_amount' => $calculation['reserve_held_amount'] ?? 0,
            'reserve_released_amount' => $calculation['reserve_released_amount'] ?? 0,
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

    protected function syncSettlementDetails(Merchant $merchant, $transactions, Settlement $settlement): void
    {
        $settlement->refresh();
        $this->settlementDetailSync->replaceDetailsForSettlement($settlement, $transactions, $merchant);
    }

    /**
     * Generate unique settlement ID.
     */
    protected function generateSettlementId(Merchant $merchant, Carbon $date): string
    {
        return 'STL_' . strtoupper($date->format('Ymd')) . '_M' . $merchant->id . '_' . strtoupper(\Illuminate\Support\Str::random(8));
    }
}


