<?php

namespace App\Services\PaymentOrchestration;

use App\Models\Transaction;

class PaymentResponseNormalizer
{
    public static function fromTransaction(Transaction $transaction, string $message, ?string $gateway = null): array
    {
        $status = TestModePaymentSimulator::normalizedUpper($transaction->status);

        return [
            'status' => $status,
            'message' => $message,
            'transaction_id' => $transaction->txn_id,
            'amount' => (string) $transaction->amount,
            'gateway' => $gateway ?? ($transaction->gateway ?? 'unknown'),
        ];
    }

    public static function error(string $message): array
    {
        return [
            'status' => 'FAILED',
            'message' => $message,
        ];
    }
}
