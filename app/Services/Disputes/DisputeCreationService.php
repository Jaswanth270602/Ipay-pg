<?php

namespace App\Services\Disputes;

use App\Models\Dispute;
use App\Models\Merchant;
use App\Models\Transaction;
use Carbon\Carbon;

class DisputeCreationService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function createForMerchant(int $merchantId, array $validated): Dispute
    {
        Merchant::query()->findOrFail($merchantId);

        $transactionId = null;
        if (! empty($validated['transaction_id'])) {
            $txnInput = trim((string) $validated['transaction_id']);
            $transaction = Transaction::query()
                ->where('merchant_id', $merchantId)
                ->where('txn_id', $txnInput)
                ->first();

            if (! $transaction && is_numeric($txnInput)) {
                $transaction = Transaction::query()
                    ->where('merchant_id', $merchantId)
                    ->where('id', (int) $txnInput)
                    ->first();
            }

            if ($transaction) {
                $transactionId = $transaction->id;
            }
        }

        $dueBy = isset($validated['due_by'])
            ? Carbon::parse($validated['due_by'])
            : Carbon::now()->addDays(7);

        return Dispute::create([
            'merchant_id' => $merchantId,
            'transaction_id' => $transactionId,
            'payment_id' => $validated['payment_id'] ?? null,
            'order_id' => $validated['order_id'] ?? null,
            'card_network' => $validated['card_network'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'action_required',
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'INR',
            'due_by' => $dueBy,
            'evidence_submitted' => false,
            'frozen_amount' => $validated['amount'],
            'dispute_fee' => 0,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);
    }
}
