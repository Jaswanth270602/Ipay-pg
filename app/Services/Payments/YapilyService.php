<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Yapily Sandbox Service (Dummy Bank API).
 *
 * Isolated service for open-banking style sandbox testing only.
 * Handles authentication and raw API calls; no business logic.
 * All credentials loaded from config (.env).
 */
class YapilyService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appSecret;
    protected int $timeout;

    /**
     * Optionally accept per-acquirer overrides.
     *
     * If no overrides are provided, falls back to config('yapily.*') which
     * is driven from .env and the global sandbox credentials.
     */
    public function __construct(
        ?string $baseUrl = null,
        ?string $appId = null,
        ?string $appSecret = null,
        ?int $timeout = null
    )
    {
        $this->baseUrl = rtrim($baseUrl ?? config('yapily.base_url', 'https://api.yapily.com'), '/');
        $this->appId = $appId ?? config('yapily.app_id', '');
        $this->appSecret = $appSecret ?? config('yapily.app_secret', '');
        $this->timeout = $timeout ?? (int) config('yapily.timeout', 15);
    }

    /**
     * Build Basic auth header: base64(app_id:secret).
     */
    protected function basicAuthHeader(): string
    {
        $credentials = $this->appId . ':' . $this->appSecret;

        return 'Basic ' . base64_encode($credentials);
    }

    /**
     * GET /institutions – list dummy banks (health check / verify credentials).
     * Returns raw JSON-decoded response; no side effects.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function getInstitutions(): array
    {
        $url = $this->baseUrl . '/institutions';

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->basicAuthHeader(),
            ])
                ->timeout($this->timeout)
                ->get($url);

            $body = $response->json();
            $status = $response->status();

            if (!$response->successful()) {
                Log::warning('Yapily institutions request failed', [
                    'status' => $status,
                    'body' => $body,
                ]);

                return [
                    'success' => false,
                    'error' => $body['message'] ?? $response->body() ?? 'Request failed',
                    'status' => $status,
                    'data' => $body,
                ];
            }

            return [
                'success' => true,
                'data' => $body,
                'status' => $status,
            ];
        } catch (\Throwable $e) {
            Log::error('Yapily institutions request exception', [
                'message' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 0,
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Future scope – structure only, do not implement yet.
    // -------------------------------------------------------------------------

    /**
     * Consent creation (future).
     *
     * @return array{success: bool, error?: string, data?: array}
     */
    public function createConsent(array $params = []): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }

    /**
     * Account fetch (future).
     *
     * @return array{success: bool, error?: string, data?: array}
     */
    public function getAccounts(string $consentId = ''): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }

    /**
     * Balance fetch (future).
     *
     * @return array{success: bool, error?: string, data?: array}
     */
    public function getBalances(string $accountId = ''): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }

    /**
     * Transaction fetch (future).
     *
     * @return array{success: bool, error?: string, data?: array}
     */
    public function getTransactions(string $accountId = ''): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}
