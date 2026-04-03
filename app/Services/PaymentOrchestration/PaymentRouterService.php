<?php

namespace App\Services\PaymentOrchestration;

use App\Models\Merchant;
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
        protected AcquirerResolver $acquirerResolver
    ) {}

    /**
     * Resolve live gateway adapter for merchant (null if no acquirer configured).
     */
    public function resolveLiveAdapter(Merchant $merchant): ?AcquirerInterface
    {
        $account = $merchant->getActiveAcquirerAccount();
        if (!$account) {
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

        $adapter = $this->resolveLiveAdapter($merchant);
        if ($adapter === null) {
            Log::warning('Live payment attempted without acquirer', ['merchant_id' => $merchant->id]);

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

            return PaymentResponseNormalizer::fromTransaction($transaction, $message, $gateway);
        } catch (\Throwable $e) {
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timeout')
                || $e instanceof \Illuminate\Http\Client\ConnectionException;

            Log::error('Live orchestration payment failed', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
                'timeout' => $isTimeout,
            ]);

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
