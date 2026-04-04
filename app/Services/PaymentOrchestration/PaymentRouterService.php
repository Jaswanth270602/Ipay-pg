<?php

namespace App\Services\PaymentOrchestration;

use App\Models\AcquirerAccount;
use App\Models\Merchant;
use App\Models\PaymentRoutingMonitor;
use App\Models\Transaction;
use App\Services\Acquirers\AcquirerInterface;
use App\Services\Acquirers\AcquirerResolver;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * Routes payments to test simulation or live acquirer adapters (Razorpay, Cashfree, etc.).
 */
class PaymentRouterService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected AcquirerResolver $acquirerResolver,
        protected AcquirerRoutingService $acquirerRoutingService
    ) {}

    /**
     * Resolve live gateway adapter for merchant (null if no acquirer configured).
     */
    public function resolveLiveAdapter(Merchant $merchant): ?AcquirerInterface
    {
        $account = $this->acquirerRoutingService->resolve($merchant)['account'];
        if (! $account) {
            return null;
        }

        try {
            return $this->acquirerResolver->resolve($account);
        } catch (\Throwable $e) {
            Log::warning('PaymentRouterService: could not resolve acquirer', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Human-readable gateway name for responses (razorpay, cashfree, ...).
     */
    public function gatewayLabel(?AcquirerInterface $adapter): string
    {
        if ($adapter === null) {
            return 'none';
        }

        return strtolower($adapter->getProviderName());
    }

    /**
     * Initiate payment: test mode uses simulator (no external gateway); live uses PaymentService + acquirer.
     *
     * @param  array<string, mixed>  $orderData  Passed to PaymentService::createOrder
     * @param  array<string, mixed>  $paymentData  Card / UPI / method details
     * @return array<string, mixed> Normalized orchestration response
     */
    public function initiate(Merchant $merchant, array $orderData, array $paymentData): array
    {
        $requestSnapshot = $this->sanitizedRequestSnapshot($orderData, $paymentData);

        if ($merchant->test_mode) {
            $order = $this->paymentService->createOrder($merchant, $orderData);
            $transaction = $this->paymentService->processTestSimulation($order, $paymentData, $requestSnapshot);

            $msg = match ($transaction->status) {
                'success' => 'Payment successful (test mode)',
                'failed' => 'Payment failed (test mode)',
                default => 'Payment pending (test mode)',
            };

            return PaymentResponseNormalizer::fromTransaction(
                $transaction,
                $msg,
                'test_simulator'
            );
        }

        $routing = $this->acquirerRoutingService->resolve($merchant);
        $account = $routing['account'];
        $flowTrace = $routing['flow_trace'];

        $adapter = null;
        if ($account) {
            try {
                $adapter = $this->acquirerResolver->resolve($account);
            } catch (\Throwable $e) {
                Log::warning('PaymentRouterService: could not resolve acquirer adapter', [
                    'merchant_id' => $merchant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($adapter === null) {
            Log::warning('Live payment attempted without acquirer', ['merchant_id' => $merchant->id]);
            $this->persistRoutingMonitor(
                $merchant,
                $orderData,
                $paymentData,
                $flowTrace,
                null,
                null,
                'failed',
                'Acquirer not configured'
            );

            return array_merge(PaymentResponseNormalizer::error('Acquirer not configured'), [
                'transaction_id' => null,
                'amount' => null,
                'gateway' => 'none',
            ]);
        }

        try {
            $order = $this->paymentService->createOrder($merchant, $orderData);
            $transaction = $this->paymentService->processPayment($order, array_merge($paymentData, [
                '_orchestration_request_payload' => $requestSnapshot,
                '_acquirer_order_only' => true,
            ]));

            $gateway = $this->gatewayLabel($adapter);
            $message = match ($transaction->status) {
                'success' => 'Payment successful',
                'failed' => $this->classifyLiveFailure($transaction->failure_reason ?? 'Payment failed'),
                default => 'Payment pending. Complete payment on gateway checkout.',
            };

            $monitorStatus = match ($transaction->status) {
                'success' => 'success',
                'failed' => 'failed',
                default => 'pending',
            };
            $this->persistRoutingMonitor(
                $merchant,
                $orderData,
                $paymentData,
                $flowTrace,
                $account,
                $transaction,
                $monitorStatus,
                $transaction->status === 'failed' ? ($transaction->failure_reason ?? 'Payment failed') : null
            );

            return PaymentResponseNormalizer::fromTransaction($transaction, $message, $gateway);
        } catch (\Throwable $e) {
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timeout')
                || $e instanceof \Illuminate\Http\Client\ConnectionException;

            Log::error('Live orchestration payment failed', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
                'timeout' => $isTimeout,
            ]);

            $this->persistRoutingMonitor(
                $merchant,
                $orderData,
                $paymentData,
                $flowTrace,
                $account,
                null,
                'failed',
                $e->getMessage()
            );

            return array_merge(
                PaymentResponseNormalizer::error($isTimeout ? 'No response from acquirer' : $this->classifyLiveFailure($e->getMessage())),
                [
                    'transaction_id' => null,
                    'amount' => isset($orderData['amount']) ? (string) $orderData['amount'] : null,
                    'gateway' => $this->gatewayLabel($adapter),
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $flowTrace
     */
    protected function persistRoutingMonitor(
        Merchant $merchant,
        array $orderData,
        array $paymentData,
        array $flowTrace,
        ?AcquirerAccount $account,
        ?Transaction $transaction,
        string $status,
        ?string $errorMessage = null
    ): void {
        try {
            $cd = $orderData['customer_details'] ?? [];
            if (is_string($cd)) {
                $decoded = json_decode($cd, true);
                $cd = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($cd)) {
                $cd = [];
            }

            PaymentRoutingMonitor::create([
                'merchant_id' => $merchant->id,
                'txn_id' => $transaction?->txn_id,
                'customer_name' => $cd['name'] ?? ($cd['customer_name'] ?? null),
                'customer_email' => $cd['email'] ?? ($cd['customer_email'] ?? null),
                'customer_phone' => isset($cd['phone']) ? (string) $cd['phone'] : (isset($cd['contact']) ? (string) $cd['contact'] : null),
                'payment_method' => $paymentData['payment_method'] ?? null,
                'final_acquirer_account_id' => $account?->id,
                'final_acquirer_name' => $account?->acquirer_name,
                'status' => $status,
                'flow_trace' => $flowTrace,
                'error_message' => $errorMessage,
                'test_mode' => (bool) $merchant->test_mode,
                'source' => 'orchestration_api',
            ]);
        } catch (\Throwable $e) {
            Log::warning('payment_routing_monitor persist failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    protected function sanitizedRequestSnapshot(array $orderData, array $paymentData): array
    {
        $copy = array_merge($orderData, $paymentData);
        foreach (['card_number', 'cvv', 'card_cvv'] as $k) {
            if (!empty($copy[$k])) {
                $copy[$k] = '***';
            }
        }

        return $copy;
    }

    protected function classifyLiveFailure(string $message): string
    {
        $m = strtolower($message);

        if (str_contains($m, 'unauthorized') || str_contains($m, 'authentication') || str_contains($m, 'invalid key') || str_contains($m, 'forbidden') || str_contains($m, '401') || str_contains($m, '403')) {
            return 'Invalid API credentials from gateway';
        }
        if (str_contains($m, 'timeout') || str_contains($m, 'timed out') || str_contains($m, 'connection') || str_contains($m, 'could not resolve host') || str_contains($m, 'curl error 28')) {
            return 'No response from acquirer';
        }
        if (str_contains($m, 'acquirer') && str_contains($m, 'not configured')) {
            return 'Acquirer not configured';
        }

        return 'Payment failed';
    }
}
