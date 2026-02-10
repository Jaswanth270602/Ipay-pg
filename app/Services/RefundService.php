<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankProviders\BankProviderInterface;
use App\Events\RefundCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    protected BankProviderInterface $bankProvider;

    public function __construct(BankProviderInterface $bankProvider)
    {
        $this->bankProvider = $bankProvider;
    }

    /**
     * Create a refund for a transaction.
     */
    public function createRefund(Transaction $transaction, float $amount, User $initiator, string $reason = null): Refund
    {
        return DB::transaction(function () use ($transaction, $amount, $initiator, $reason) {
            // Validate refund amount
            $refundableAmount = $transaction->refundableAmount();

            if ($amount > $refundableAmount) {
                throw new \Exception("Refund amount exceeds refundable amount. Maximum: {$refundableAmount}");
            }

            if ($amount <= 0) {
                throw new \Exception("Refund amount must be greater than zero");
            }

            // Create refund record
            $refund = Refund::create([
                'transaction_id' => $transaction->id,
                'merchant_id' => $transaction->merchant_id,
                'refund_id' => Refund::generateRefundId(),
                'amount' => $amount,
                'currency' => $transaction->currency,
                'status' => 'pending',
                'reason' => $reason,
                'initiated_by' => $initiator->id,
                'is_partial' => $amount < $transaction->amount,
            ]);

            // Process refund through bank provider
            try {
                $result = $this->bankProvider->processRefund(
                    $transaction->gateway_txn_id ?? $transaction->txn_id,
                    $amount
                );

                if ($result['success']) {
                    $refund->update([
                        'status' => 'completed',
                        'gateway_response' => $result,
                        'gateway_refund_id' => $result['refund_id'] ?? null,
                        'processed_at' => now(),
                    ]);

                    event(new RefundCreated($refund));
                } else {
                    $refund->update([
                        'status' => 'failed',
                        'gateway_response' => $result,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Refund processing error', [
                    'refund_id' => $refund->refund_id,
                    'transaction_id' => $transaction->txn_id,
                    'error' => $e->getMessage(),
                ]);

                $refund->update([
                    'status' => 'failed',
                    'gateway_response' => [
                        'error' => $e->getMessage(),
                    ],
                ]);
            }

            return $refund;
        });
    }

    /**
     * Process a full refund.
     */
    public function fullRefund(Transaction $transaction, User $initiator, string $reason = null): Refund
    {
        return $this->createRefund($transaction, $transaction->amount, $initiator, $reason);
    }

    /**
     * Process a partial refund.
     */
    public function partialRefund(Transaction $transaction, float $amount, User $initiator, string $reason = null): Refund
    {
        return $this->createRefund($transaction, $amount, $initiator, $reason);
    }
}

