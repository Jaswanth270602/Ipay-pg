<?php

namespace App\Support;

use App\Models\Merchant;

/**
 * TC_03 — Sensitive bank data masking for list/view responses.
 */
class SensitiveDataMasker
{
    /**
     * Mask bank account number: show last 4 digits only (spaces stripped).
     */
    public static function maskBankAccountNumber(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $digits = preg_replace('/\s+/', '', $value);
        $len = strlen($digits);

        if ($len === 0) {
            return '';
        }

        if ($len <= 4) {
            return str_repeat('*', max(0, $len - 1)) . substr($digits, -1);
        }

        return str_repeat('*', $len - 4) . substr($digits, -4);
    }

    /**
     * Mask IFSC: preserve bank code (first 4) and last 3 branch digits; mask the middle.
     */
    public static function maskIfsc(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $v = strtoupper(preg_replace('/\s+/', '', $value));
        $len = strlen($v);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        if ($len >= 11) {
            return substr($v, 0, 4) . '****' . substr($v, -3);
        }

        return substr($v, 0, 2) . str_repeat('*', max(1, $len - 4)) . substr($v, -2);
    }

    /**
     * Merchant payload for list/grid APIs (masked bank fields).
     *
     * @return array<string, mixed>
     */
    public static function maskMerchantAttributes(Merchant $merchant): array
    {
        $arr = $merchant->toArray();
        $arr['bank_account_number'] = self::maskBankAccountNumber($merchant->bank_account_number);
        $arr['bank_ifsc_code'] = self::maskIfsc($merchant->bank_ifsc_code);

        return $arr;
    }

}
