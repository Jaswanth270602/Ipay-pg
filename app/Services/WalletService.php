<?php

namespace App\Services;

use App\Models\PaymentLink;

class WalletService
{
    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function providersForLink(?PaymentLink $paymentLink = null): array
    {
        $all = config('wallet.providers', []);

        if ($paymentLink === null) {
            return $all;
        }

        $allowed = $paymentLink->payment_methods;
        if (is_array($allowed) && $allowed !== [] && ! in_array('wallet', $allowed, true)) {
            return [];
        }

        return $all;
    }

    public function isValidProvider(string $code): bool
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return false;
        }

        foreach (config('wallet.providers', []) as $provider) {
            if (strtolower((string) ($provider['code'] ?? '')) === $code) {
                return true;
            }
        }

        return false;
    }

    public function labelFor(string $code): string
    {
        $code = strtolower(trim($code));
        foreach (config('wallet.providers', []) as $provider) {
            if (strtolower((string) ($provider['code'] ?? '')) === $code) {
                return (string) ($provider['label'] ?? $code);
            }
        }

        return ucfirst($code);
    }

    /**
     * Resolve wallet test outcome from payment amount (101 / 102 / 103).
     *
     * @return 'success'|'failed'|'pending'|null
     */
    public function resolveAmountTestOutcome(float $amount): ?string
    {
        $map = config('wallet.test_amounts', []);
        $success = (float) ($map['success'] ?? 101);
        $failed = (float) ($map['failed'] ?? 102);
        $pending = (float) ($map['pending'] ?? 103);

        if ($this->amountMatches($amount, $success)) {
            return 'success';
        }
        if ($this->amountMatches($amount, $failed)) {
            return 'failed';
        }
        if ($this->amountMatches($amount, $pending)) {
            return 'pending';
        }

        return null;
    }

    public function generateTestTxnId(): string
    {
        return 'TESTWALLET'.strtoupper(substr(uniqid('', true), -10));
    }

    /**
     * Realistic wallet gateway payload for test mode / reports.
     *
     * @return array{status: string, wallet: string, txn: string, amount: string}
     */
    public function buildGatewayPayload(string $outcome, string $walletLabel, string $txnId, float $amount): array
    {
        $status = match ($outcome) {
            'success' => 'SUCCESS',
            'failed' => 'FAILED',
            'pending' => 'PENDING',
            default => 'PENDING',
        };

        return [
            'status' => $status,
            'wallet' => $walletLabel,
            'txn' => $txnId,
            'amount' => number_format($amount, 2, '.', ''),
        ];
    }

    protected function amountMatches(float $amount, float $target): bool
    {
        return abs($amount - $target) < 0.01;
    }
}
