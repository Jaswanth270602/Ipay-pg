<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Services\BankProviders\BankProviderInterface;
use App\Services\Acquirers\AcquirerInterface;
use App\Services\Acquirers\AcquirerResolver;
use App\Events\PaymentCreated;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use App\Traits\SanitizesCardData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use SanitizesCardData;

    protected BankProviderInterface $bankProvider;

    public function __construct(BankProviderInterface $bankProvider)
    {
        $this->bankProvider = $bankProvider;
    }

    /**
     * Create a new payment order.
     */
    public function createOrder(Merchant $merchant, array $data): Order
    {
        // Check for idempotency
        if (isset($data['idempotency_key'])) {
            $existingOrder = Order::where('idempotency_key', $data['idempotency_key'])
                ->where('merchant_id', $merchant->id)
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }
        }

        $order = Order::create([
            'merchant_id' => $merchant->id,
            'order_id' => Order::generateOrderId(),
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $merchant->default_currency,
            'customer_details' => $data['customer_details'] ?? null,
            'status' => 'created',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'return_url' => $data['return_url'] ?? null,
            'cancel_url' => $data['cancel_url'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'test_mode' => $merchant->test_mode,
            'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : now()->addHours(24),
        ]);

        event(new PaymentCreated($order));

        return $order;
    }

    /**
     * Process a payment for an order.
     */
    public function processPayment(Order $order, array $paymentData): Transaction
    {
        return DB::transaction(function () use ($order, $paymentData) {
            // Try to get acquirer adapter first (new system)
            $acquirerAdapter = $this->getAcquirerAdapter($order->merchant);
            
            // Fallback to bank provider if no acquirer adapter (legacy system)
            $useAcquirer = $acquirerAdapter !== null;
            $provider = $useAcquirer ? $acquirerAdapter : $this->getBankProvider($order->merchant);
            
            // Check for idempotency
            if (isset($paymentData['idempotency_key'])) {
                $existingTransaction = Transaction::where('idempotency_key', $paymentData['idempotency_key'])
                    ->where('order_id', $order->id)
                    ->first();

                if ($existingTransaction) {
                    return $existingTransaction;
                }
            }

            // Calculate fee using BaseRateService
            $baseRateService = app(\App\Services\BaseRateService::class);
            $bank = $order->merchant->bank ?? null;
            $feeCalculation = $baseRateService->calculateFee(
                $order->merchant,
                $order->amount,
                $paymentData['payment_method'],
                $bank,
                \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
                \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC // TODO: Determine from payment details
            );

            // Create transaction record
            $transaction = Transaction::create([
                'order_id' => $order->id,
                'merchant_id' => $order->merchant_id,
                'txn_id' => Transaction::generateTxnId(),
                'payment_method' => $paymentData['payment_method'],
                'amount' => $order->amount,
                'fee_amount' => $feeCalculation['fee_amount'],
                'gst_amount' => $feeCalculation['gst_amount'] ?? 0,
                'net_amount' => $order->amount - $feeCalculation['total_fee'],
                'currency' => $order->currency,
                'status' => 'initiated',
                'payment_details' => $this->sanitizePaymentDetails($paymentData),
                'test_mode' => $order->test_mode,
                'idempotency_key' => $paymentData['idempotency_key'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Process payment through bank provider
            // PCI-DSS: Extract card data BEFORE sanitization for bank provider
            // Bank provider needs full card data, but we sanitize before storing
            $cardDataForProvider = [
                'card_number' => $paymentData['card_number'] ?? null,
                'cvv' => $paymentData['cvv'] ?? null,
                'card_holder' => $paymentData['card_holder'] ?? null,
                'expiry_month' => $paymentData['expiry_month'] ?? null,
                'expiry_year' => $paymentData['expiry_year'] ?? null,
            ];
            
            // Sanitize payment data for our records (PCI-DSS compliant)
            $sanitizedPaymentData = $this->sanitizePaymentDetails($paymentData);
            
            try {
                // Process payment through acquirer adapter or bank provider
                if ($useAcquirer) {
                    // First create order with acquirer adapter
                    $orderResult = $acquirerAdapter->createOrder([
                        'order_id' => $order->order_id,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'customer_details' => $order->customer_details,
                        'metadata' => $order->metadata,
                    ]);

                    if (!$orderResult['success']) {
                        throw new \Exception($orderResult['message'] ?? 'Order creation failed');
                    }

                    // Store gateway order ID
                    $gatewayOrderId = $orderResult['gateway_order_id'];
                    $transaction->update(['gateway_order_id' => $gatewayOrderId]);

                    // Initiate payment
                    $result = $acquirerAdapter->initiatePayment([
                        'order_id' => $gatewayOrderId,
                        'gateway_order_id' => $gatewayOrderId,
                        'payment_method' => $paymentData['payment_method'],
                        'card_number' => $paymentData['card_number'] ?? null,
                        'cvv' => $paymentData['cvv'] ?? null,
                        'expiry_month' => $paymentData['expiry_month'] ?? null,
                        'expiry_year' => $paymentData['expiry_year'] ?? null,
                        'card_holder' => $paymentData['card_holder'] ?? null,
                    ]);

                    // Normalize result format
                    if ($result['success']) {
                        $result['gateway_txn_id'] = $result['payment_id'] ?? $result['gateway_payment_id'] ?? null;
                    }
                } else {
                    // Legacy bank provider flow
                    $result = $provider->processPayment([
                        'merchant_id' => $order->merchant_id,
                        'order_id' => $order->order_id,
                        'transaction_id' => $transaction->txn_id,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'payment_method' => $paymentData['payment_method'],
                        'payment_details' => $paymentData, // Bank provider needs full data
                    ]);
                }

                if ($result['success']) {
                    // PCI-DSS: Sanitize gateway response before storing
                    $sanitizedGatewayResponse = $this->sanitizePaymentDetails($result);
                    $sanitizedResultPaymentDetails = isset($result['payment_details']) 
                        ? $this->sanitizePaymentDetails($result['payment_details']) 
                        : [];
                    
                    $transaction->update([
                        'status' => 'success',
                        'gateway_response' => $sanitizedGatewayResponse,
                        'gateway_txn_id' => $result['gateway_txn_id'] ?? null,
                        'payment_details' => array_merge(
                            $transaction->payment_details ?? [],
                            $sanitizedResultPaymentDetails
                        ),
                        'captured_at' => now(),
                    ]);

                    $order->update(['status' => 'completed']);
                    event(new PaymentSuccess($transaction));
                } else {
                    // Extract failure reason from gateway response
                    $failureReason = $result['message'] ?? 
                                    ($result['error_code'] ?? 'Payment failed') . 
                                    (isset($result['error']) ? ': ' . $result['error'] : '');
                    
                    // Add test mode context if applicable
                    if ($order->test_mode) {
                        $failureReason = '(Test Mode) ' . $failureReason;
                    }

                    // PCI-DSS: Sanitize gateway response before storing
                    $sanitizedGatewayResponse = $this->sanitizePaymentDetails($result);
                    
                    $transaction->update([
                        'status' => 'failed',
                        'gateway_response' => $sanitizedGatewayResponse,
                        'failure_reason' => $failureReason,
                    ]);

                    $order->update(['status' => 'failed']);
                    event(new PaymentFailed($transaction));
                }

            } catch (\Exception $e) {
                // PCI-DSS: Never log card data
                Log::error('Payment processing error', [
                    'transaction_id' => $transaction->txn_id,
                    'error' => $e->getMessage(),
                    // Note: payment_data intentionally excluded to prevent card data logging
                ]);

                $failureReason = 'Processing error: ' . $e->getMessage();
                if ($order->test_mode) {
                    $failureReason = '(Test Mode) ' . $failureReason;
                }

                // PCI-DSS: Sanitize error response (no card data in trace)
                $sanitizedErrorResponse = [
                    'error' => $e->getMessage(),
                    // Note: trace excluded to prevent potential card data exposure
                ];
                
                $transaction->update([
                    'status' => 'failed',
                    'gateway_response' => $sanitizedErrorResponse,
                    'failure_reason' => $failureReason,
                ]);

                $order->update(['status' => 'failed']);
                event(new PaymentFailed($transaction));
            }

            return $transaction;
        });
    }

    // SanitizePaymentDetails method moved to SanitizesCardData trait

    /**
     * Get acquirer adapter for the merchant (if available).
     * 
     * @param Merchant $merchant
     * @return AcquirerInterface|null
     */
    protected function getAcquirerAdapter(Merchant $merchant): ?AcquirerInterface
    {
        try {
            $acquirerAccount = $merchant->getActiveAcquirerAccount();

            if (!$acquirerAccount) {
                return null;
            }

            $resolver = app(AcquirerResolver::class);
            return $resolver->resolve($acquirerAccount);

        } catch (\Exception $e) {
            Log::warning('Failed to resolve acquirer adapter', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the appropriate bank provider for the merchant (legacy fallback).
     */
    protected function getBankProvider(Merchant $merchant): BankProviderInterface
    {
        if ($merchant->test_mode) {
            return new \App\Services\BankProviders\SandboxBankProvider();
        }

        // For production, try to get merchant-specific API credentials
        $apiKey = $merchant->settings['production_api_key'] ?? null;
        $apiSecret = $merchant->settings['production_api_secret'] ?? null;
        $bankName = $merchant->settings['production_bank_name'] ?? null;

        return new \App\Services\BankProviders\ProductionBankProvider($apiKey, $apiSecret, $bankName);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(Transaction $transaction): array
    {
        $bankProvider = $this->getBankProvider($transaction->merchant);
        $result = $bankProvider->verifyPayment($transaction->txn_id);

        if ($result['verified'] && $transaction->status !== 'success') {
            $transaction->update([
                'status' => 'success',
                'captured_at' => now(),
            ]);

            $transaction->order->update(['status' => 'completed']);
            event(new PaymentSuccess($transaction));
        }

        return $result;
    }
}

