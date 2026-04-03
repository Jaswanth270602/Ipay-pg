<?php

namespace App\Services;

use App\Models\AcquirerAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AcquirerCredentialValidator
{
    /**
     * Validate gateway credentials by making an authenticated lightweight API call.
     *
     * @return array{ok: bool, message: string}
     */
    public function validate(?AcquirerAccount $acquirer): array
    {
        if (!$acquirer || !$acquirer->is_active) {
            return ['ok' => false, 'message' => 'Acquirer not configured'];
        }

        $name = strtolower((string) ($acquirer->acquirer_name ?? ''));
        if (str_contains($name, 'razorpay')) {
            return $this->validateRazorpay($acquirer);
        }
        if (str_contains($name, 'cashfree')) {
            return $this->validateCashfree($acquirer);
        }

        return ['ok' => false, 'message' => 'Acquirer not configured'];
    }

    private function validateRazorpay(AcquirerAccount $a): array
    {
        $keyId = $a->additional_key_1 ?? $a->secret_key;
        $secret = $a->additional_key_2 ?? $a->secret_key ?? $a->salt;

        if (!$keyId || !$secret) {
            return ['ok' => false, 'message' => 'Enter valid API keys'];
        }

        try {
            $resp = Http::withBasicAuth($keyId, $secret)
                ->timeout(8)
                ->get('https://api.razorpay.com/v1/payments', ['count' => 1]);

            if (in_array($resp->status(), [401, 403], true)) {
                return ['ok' => false, 'message' => 'Enter valid API keys'];
            }

            return ['ok' => true, 'message' => 'OK'];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'No response from acquirer'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Enter valid API keys'];
        }
    }

    private function validateCashfree(AcquirerAccount $a): array
    {
        $appId = $a->account_id ?: $a->additional_key_1;
        $secret = $a->secret_key;
        $base = strtoupper((string) $a->mode) === 'TEST'
            ? ($a->test_request_url ?: 'https://sandbox.cashfree.com/pg')
            : ($a->live_request_url ?: 'https://api.cashfree.com/pg');
        $base = rtrim((string) $base, '/');
        if (!str_ends_with($base, '/pg')) {
            $base .= '/pg';
        }

        if (!$appId || !$secret) {
            return ['ok' => false, 'message' => 'Enter valid API keys'];
        }

        try {
            // 404 means auth is accepted but order doesn't exist; that's enough for credential validity.
            $resp = Http::withHeaders([
                'x-client-id' => $appId,
                'x-client-secret' => $secret,
                'x-api-version' => '2023-08-01',
            ])->timeout(8)->get($base . '/orders/cred_check_' . uniqid());

            if (in_array($resp->status(), [401, 403], true)) {
                return ['ok' => false, 'message' => 'Enter valid API keys'];
            }

            return ['ok' => true, 'message' => 'OK'];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'No response from acquirer'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Enter valid API keys'];
        }
    }
}

