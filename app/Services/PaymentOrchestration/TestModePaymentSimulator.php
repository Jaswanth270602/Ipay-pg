<?php

namespace App\Services\PaymentOrchestration;

/**
 * Test-mode payment outcome from card number (last 4 digits).
 * 1111 → success, 0000 → failed, else → pending.
 */
class TestModePaymentSimulator
{
    public static function resolveStatusFromPaymentData(array $paymentData): string
    {
        $card = $paymentData['card_number'] ?? '';
        $digits = preg_replace('/\D/', '', (string) $card);
        $last4 = strlen($digits) >= 4 ? substr($digits, -4) : '';

        if ($last4 === '1111') {
            return 'success';
        }
        if ($last4 === '0000') {
            return 'failed';
        }

        return 'pending';
    }

    public static function normalizedUpper(string $dbStatus): string
    {
        return match ($dbStatus) {
            'success' => 'SUCCESS',
            'failed' => 'FAILED',
            'pending' => 'PENDING',
            default => 'PENDING',
        };
    }
}
