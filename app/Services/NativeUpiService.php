<?php

namespace App\Services;

use App\Models\Merchant;

/**
 * Build-only UPI “collect” style intents (upi://pay) using your own receive VPA.
 * No Razorpay/Cashfree/NPCI API — reconciliation is outside this app unless you add it.
 */
class NativeUpiService
{
    /**
     * Receive VPA: env NATIVE_UPI_RECEIVE_VPA or merchants.settings.receive_upi_vpa / native_upi_vpa
     */
    public function resolveReceiveVpa(Merchant $merchant): string
    {
        $fromSettings = (string) (data_get($merchant->settings, 'receive_upi_vpa')
            ?: data_get($merchant->settings, 'native_upi_vpa')
            ?: '');

        $fromEnv = (string) (config('ipay.native_upi.receive_vpa') ?? '');

        return trim($fromSettings !== '' ? $fromSettings : $fromEnv);
    }

    public function resolvePayeeName(Merchant $merchant): string
    {
        $fromSettings = trim((string) data_get($merchant->settings, 'receive_upi_payee_name', ''));
        if ($fromSettings !== '') {
            return $fromSettings;
        }

        $fromEnv = trim((string) (config('ipay.native_upi.payee_name') ?? ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        return (string) ($merchant->company_name ?: $merchant->name ?: config('app.name', 'Merchant'));
    }

    public function hasReceiveVpa(Merchant $merchant): bool
    {
        return $this->resolveReceiveVpa($merchant) !== '';
    }

    /**
     * NPCI-style deep link (amount in INR).
     *
     * @param  string  $transactionRef  Shown as tr (keep alphanumeric; max ~35 chars for compatibility)
     */
    public function buildPayIntentUrl(
        string $receiveVpa,
        string $payeeName,
        float $amountInr,
        string $transactionRef,
        string $note
    ): string {
        $tr = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $transactionRef), 0, 35);
        if ($tr === '') {
            $tr = 'REF' . substr(sha1($transactionRef), 0, 28);
        }

        $params = [
            'pa' => $receiveVpa,
            'pn' => $payeeName,
            'am' => number_format($amountInr, 2, '.', ''),
            'cu' => 'INR',
            'tr' => $tr,
            'tn' => mb_substr($note, 0, 80),
        ];

        return 'upi://pay?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
