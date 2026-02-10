<?php

namespace App\Services\Acquirers;

use App\Models\AcquirerAccount;
use App\Services\Payments\YapilyService;
use Illuminate\Support\Facades\Log;

/**
 * Yapily Acquirer Adapter (Sandbox / Dummy Bank)
 *
 * This adapter lets you treat Yapily as an acquirer in the orchestration layer
 * while keeping all existing Razorpay/Cashfree codepaths untouched.
 *
 * IMPORTANT:
 * - This is sandbox-only and currently simulates payment success.
 * - It uses the Yapily App ID and Secret stored in the same DB fields that
 *   Razorpay uses for keys (additional_key_1 / additional_key_2 / secret_key / salt),
 *   so you can configure it via the existing AcquirerAccount UI.
 */
class YapilyAcquirerAdapter implements AcquirerInterface
{
    protected ?AcquirerAccount $acquirerAccount = null;
    protected ?YapilyService $yapily = null;
    protected bool $isTestMode = true;

    /**
     * Initialize the adapter with an AcquirerAccount.
     *
     * Expected mapping of Yapily credentials into AcquirerAccount:
     * - App ID     -> additional_key_1 (preferred) or secret_key
     * - App Secret -> additional_key_2 (preferred) or salt
     */
    public function initialize(AcquirerAccount $acquirerAccount): self
    {
        $this->acquirerAccount = $acquirerAccount;
        $this->isTestMode = strtoupper($acquirerAccount->mode) === 'TEST';

        // Map DB fields to Yapily credentials (re-using Razorpay-style columns)
        $appId = $acquirerAccount->additional_key_1 ?? $acquirerAccount->secret_key;
        $appSecret = $acquirerAccount->additional_key_2 ?? $acquirerAccount->salt;

        Log::debug('Yapily credential extraction from AcquirerAccount', [
            'acquirer_account_id' => $acquirerAccount->id,
            'acquirer_name' => $acquirerAccount->acquirer_name,
            'additional_key_1' => $acquirerAccount->additional_key_1 ? 'SET' : 'EMPTY',
            'secret_key' => $acquirerAccount->secret_key ? 'SET' : 'EMPTY',
            'additional_key_2' => $acquirerAccount->additional_key_2 ? 'SET' : 'EMPTY',
            'salt' => $acquirerAccount->salt ? 'SET' : 'EMPTY',
            'appId_found' => !empty($appId),
            'appSecret_found' => !empty($appSecret),
        ]);

        if (!$appId || !$appSecret) {
            Log::error('Yapily credentials missing in AcquirerAccount', [
                'acquirer_account_id' => $acquirerAccount->id,
                'acquirer_name' => $acquirerAccount->acquirer_name,
            ]);

            throw new \RuntimeException('Yapily credentials not configured in AcquirerAccount. Please put App ID in Additional Key 1 (or Secret Key) and App Secret in Additional Key 2 (or Salt).');
        }

        // Optional per-acquirer base URL overrides (fall back to config)
        $baseUrl = $this->isTestMode
            ? ($acquirerAccount->test_request_url ?: null)
            : ($acquirerAccount->live_request_url ?: null);

        // Build Yapily service using per-acquirer credentials
        $this->yapily = new YapilyService(
            $baseUrl,
            $appId,
            $appSecret,
            null
        );

        // Lightweight health check (non-fatal) – validates credentials against /institutions
        $health = $this->yapily->getInstitutions();
        if (!$health['success']) {
            Log::warning('Yapily institutions health check failed during adapter init', [
                'acquirer_account_id' => $acquirerAccount->id,
                'status' => $health['status'] ?? null,
                'error' => $health['error'] ?? null,
            ]);
        } else {
            Log::info('Yapily institutions health check succeeded for acquirer', [
                'acquirer_account_id' => $acquirerAccount->id,
                'bank_count' => is_array($health['data'] ?? null) ? count($health['data']) : null,
            ]);
        }

        return $this;
    }

    /**
     * Create a Yapily-backed "order".
     *
     * Yapily is an open-banking aggregator and does not have the same concept
     * of card/UPI orders as Razorpay. For now, we simulate an order object
     * while still validating Yapily credentials at initialization time.
     */
    public function createOrder(array $orderData): array
    {
        $localOrderId = $orderData['order_id'] ?? ('yapily_' . uniqid());
        $gatewayOrderId = 'ypl_ord_' . strtoupper(substr(sha1($localOrderId . microtime(true)), 0, 14));

        return [
            'success' => true,
            'order_id' => $localOrderId,
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $orderData['amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'INR',
            'status' => 'pending',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
                'acquirer_account_id' => $this->acquirerAccount?->id,
            ],
        ];
    }

    /**
     * Initiate a payment.
     *
     * For now, we simulate a successful payment in sandbox mode. This keeps
     * the orchestration code happy without making real bank calls.
     */
    public function initiatePayment(array $paymentData): array
    {
        // In a future iteration, this is where a real Yapily payment/initiation
        // flow would be wired in.

        if (!$this->isTestMode) {
            return [
                'success' => false,
                'error_code' => 'YAPILY_LIVE_NOT_SUPPORTED',
                'message' => 'Yapily acquirer currently supports only TEST mode in this sandbox.',
            ];
        }

        $orderId = $paymentData['order_id'] ?? $paymentData['gateway_order_id'] ?? null;
        if (!$orderId) {
            return [
                'success' => false,
                'error_code' => 'MISSING_ORDER_ID',
                'message' => 'Order ID is required to initiate a Yapily payment.',
            ];
        }

        $simulatedPaymentId = 'ypl_pay_' . strtoupper(substr(uniqid(), 0, 14));

        Log::info('Simulating Yapily payment (sandbox acquirer)', [
            'payment_id' => $simulatedPaymentId,
            'order_id' => $orderId,
            'acquirer_account_id' => $this->acquirerAccount?->id,
        ]);

        return [
            'success' => true,
            'payment_id' => $simulatedPaymentId,
            'gateway_payment_id' => $simulatedPaymentId,
            'gateway_txn_id' => $simulatedPaymentId,
            'status' => 'captured',
            'order_id' => $orderId,
            'amount' => $paymentData['amount'] ?? null,
            'currency' => $paymentData['currency'] ?? 'INR',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
            ],
        ];
    }

    /**
     * For now, verification is a no-op for simulated Yapily payments.
     */
    public function verifyPayment(array $paymentData, string $signature): array
    {
        return [
            'verified' => true,
            'payment_id' => $paymentData['payment_id'] ?? null,
            'gateway_payment_id' => $paymentData['payment_id'] ?? null,
            'order_id' => $paymentData['order_id'] ?? null,
            'status' => 'success',
            'amount' => $paymentData['amount'] ?? null,
            'currency' => $paymentData['currency'] ?? 'INR',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
                'note' => 'Signature verification bypassed for sandbox.',
            ],
        ];
    }

    public function processRefund(string $paymentId, float $amount, array $options = []): array
    {
        // Refunds are not wired to Yapily yet; simulate success in TEST mode
        if (!$this->isTestMode) {
            return [
                'success' => false,
                'error_code' => 'YAPILY_REFUND_NOT_SUPPORTED',
                'message' => 'Yapily refunds are not implemented for live mode.',
            ];
        }

        $refundId = 'ypl_ref_' . strtoupper(substr(uniqid(), 0, 14));

        Log::info('Simulating Yapily refund (sandbox acquirer)', [
            'refund_id' => $refundId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'acquirer_account_id' => $this->acquirerAccount?->id,
        ]);

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_refund_id' => $refundId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'status' => 'success',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
            ],
        ];
    }

    public function getPaymentStatus(string $paymentId): array
    {
        // For simulated payments, just return success
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'gateway_payment_id' => $paymentId,
            'status' => 'success',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
            ],
        ];
    }

    public function getOrderStatus(string $orderId): array
    {
        return [
            'success' => true,
            'order_id' => $orderId,
            'gateway_order_id' => $orderId,
            'status' => 'completed',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
            ],
        ];
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // No Yapily webhooks are wired yet; always return true for sandbox
        Log::info('Yapily webhook signature verification is a no-op in sandbox.', [
            'acquirer_account_id' => $this->acquirerAccount?->id,
        ]);

        return true;
    }

    public function normalizeEventType(array $payload): string
    {
        // Placeholder mapping for future real Yapily webhooks
        return 'payment.success';
    }

    public function normalizeStatus(string $providerStatus): string
    {
        // Very simple mapping for now
        $status = strtolower($providerStatus);

        if (in_array($status, ['success', 'completed', 'captured'], true)) {
            return 'success';
        }

        if (in_array($status, ['failed', 'error'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    public function extractReferenceIds(array $payload): array
    {
        return [
            'payment_id' => $payload['payment_id'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'refund_id' => $payload['refund_id'] ?? null,
            'settlement_id' => null,
            'dispute_id' => null,
        ];
    }

    public function getProviderName(): string
    {
        return 'yapily';
    }

    public function createPaymentLink(array $linkData): array
    {
        // Not implemented for Yapily yet; simulate a link in TEST mode
        if (!$this->isTestMode) {
            return [
                'success' => false,
                'error_code' => 'YAPILY_LINK_NOT_SUPPORTED',
                'message' => 'Yapily payment links are not implemented for live mode.',
            ];
        }

        $linkId = 'ypl_link_' . strtoupper(substr(uniqid(), 0, 10));

        return [
            'success' => true,
            'link_id' => $linkId,
            'gateway_link_id' => $linkId,
            'short_url' => null,
            'status' => 'active',
            'amount' => $linkData['amount'] ?? null,
            'currency' => $linkData['currency'] ?? 'INR',
            'raw_response' => [
                'provider' => 'yapily',
                'simulated' => true,
            ],
        ];
    }

    public function getSettlements(array $filters = []): array
    {
        // No real settlements via Yapily in this sandbox adapter
        return [
            'success' => true,
            'settlements' => [],
            'count' => 0,
        ];
    }
}

