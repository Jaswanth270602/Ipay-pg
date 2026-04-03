<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shared API credential checks for ApiKeyAuthMiddleware and merchant portal routes.
 */
class ApiCredentialValidator
{
    /**
     * API routes: resolve merchant from credential (any merchant).
     *
     * @return array{ok: true, merchant: Merchant, mode: string}|array{ok: false, error: string, http_status: int}
     */
    public function authenticateApiRequest(Request $request): array
    {
        $raw = $this->readCredentialToken($request);

        if ($raw === null || $raw === '') {
            Log::warning('api_credential: missing credentials');

            return $this->fail('Invalid API Key', 401);
        }

        $merchant = $this->findMerchantByCredential($raw);

        if (!$merchant) {
            Log::warning('api_credential: no merchant for credential', ['prefix' => $this->prefix($raw)]);

            return $this->fail('Invalid API Key', 401);
        }

        return $this->validateMerchantModeAndSecret($request, $merchant, $raw);
    }

    /**
     * Merchant dashboard: session user is known — credential must belong to this merchant only.
     *
     * @return array{ok: true, mode: string}|array{ok: false, error: string, http_status: int}
     */
    public function validatePortalRequest(Request $request, Merchant $merchant): array
    {
        // Portal: require explicit X-API-KEY (do not fall back to Authorization Bearer — avoids accidental token use)
        $v = $request->header('X-API-KEY') ?? $request->header('X-Api-Key');
        $raw = $v !== null && $v !== '' ? trim($v) : null;

        if ($raw === null || $raw === '') {
            return $this->fail('Invalid API Key', 401);
        }

        $mode = $this->resolveModeForMerchant($merchant, $raw);

        if ($mode === null) {
            Log::warning('api_credential: portal key not for merchant', [
                'merchant_id' => $merchant->id,
                'prefix' => $this->prefix($raw),
            ]);

            return $this->fail('Invalid API Key', 401);
        }

        $inner = $this->validateMerchantModeAndSecret($request, $merchant, $raw, $mode);
        if (!$inner['ok']) {
            return $inner;
        }

        return ['ok' => true, 'mode' => $mode];
    }

    /**
     * @param  'test'|'live'|null  $knownMode  When set, skip resolve (portal path).
     * @return array{ok: true, merchant: Merchant, mode: string}|array{ok: false, error: string, http_status: int}
     */
    private function validateMerchantModeAndSecret(Request $request, Merchant $merchant, string $raw, ?string $knownMode = null): array
    {
        $mode = $knownMode ?? $this->resolveModeForMerchant($merchant, $raw);

        if ($mode === null) {
            return $this->fail('Invalid API Key', 401);
        }

        $merchantModeExpected = $mode === 'test';
        if ((bool) $merchant->test_mode !== $merchantModeExpected) {
            return $this->fail('Key does not match mode', 403);
        }

        $secretHeader = $this->readSecretHeader($request);
        if ($secretHeader !== null && $secretHeader !== '') {
            $expected = $mode === 'test' ? $merchant->test_secret_key : $merchant->live_secret_key;
            if ($expected === null || ! hash_equals((string) $expected, (string) $secretHeader)) {
                return $this->fail('Invalid Secret Key', 403);
            }
        }

        $apiKeyRow = ApiKey::where('merchant_id', $merchant->id)
            ->where('mode', $mode)
            ->where(function ($q) use ($raw) {
                $q->where('key', $raw)->orWhere('secret', $raw);
            })
            ->first();

        if ($apiKeyRow && $apiKeyRow->isValid()) {
            $apiKeyRow->markAsUsed();
        }

        Log::info('api_credential: ok', [
            'merchant_id' => $merchant->id,
            'mode' => $mode,
            'prefix' => $this->prefix($raw),
        ]);

        return [
            'ok' => true,
            'merchant' => $merchant,
            'mode' => $mode,
        ];
    }

    private function findMerchantByCredential(string $raw): ?Merchant
    {
        $m = Merchant::query()
            ->where('test_public_key', $raw)
            ->orWhere('live_public_key', $raw)
            ->first();

        if ($m) {
            return $m;
        }

        return Merchant::query()
            ->where('test_secret_key', $raw)
            ->orWhere('live_secret_key', $raw)
            ->first();
    }

    private function resolveModeForMerchant(Merchant $m, string $raw): ?string
    {
        if ($m->test_public_key === $raw || $m->test_secret_key === $raw) {
            return 'test';
        }
        if ($m->live_public_key === $raw || $m->live_secret_key === $raw) {
            return 'live';
        }

        return null;
    }

    private function readCredentialToken(Request $request): ?string
    {
        $v = $request->header('X-API-KEY')
            ?? $request->header('X-Api-Key')
            ?? $request->bearerToken();

        return $v !== null && $v !== '' ? trim($v) : null;
    }

    private function readSecretHeader(Request $request): ?string
    {
        $v = $request->header('X-SECRET-KEY') ?? $request->header('X-Secret-Key');

        return $v !== null && $v !== '' ? trim($v) : null;
    }

    private function prefix(string $value): string
    {
        return strlen($value) <= 12 ? $value : substr($value, 0, 12) . '…';
    }

    /**
     * @return array{ok: false, error: string, http_status: int}
     */
    private function fail(string $error, int $httpStatus): array
    {
        return [
            'ok' => false,
            'error' => $error,
            'http_status' => $httpStatus,
        ];
    }
}
