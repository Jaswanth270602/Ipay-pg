<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\PaymentLink;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use App\Traits\SanitizesCardData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentSimulationService
{
    use SanitizesCardData;

    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * Process payment (simulation for test mode or real for live mode).
     */
    public function processPayment(array $paymentData): array
    {
        return DB::transaction(function () use ($paymentData) {
            try {
                if (! empty($paymentData['idempotency_key'])) {
                    $existing = Transaction::where('idempotency_key', $paymentData['idempotency_key'])
                        ->where('merchant_id', $paymentData['merchant_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {
                        return $this->responseForExistingTransaction($existing, $paymentData);
                    }
                }

                // Create or find order
                $order = $this->createOrder($paymentData);

            // PCI-DSS: Sanitize payment data before storing
            $sanitizedPaymentData = $this->sanitizePaymentDetails($paymentData);
            
            // Create transaction with sanitized data
            if (! empty($paymentData['idempotency_key'])) {
                $sanitizedPaymentData['idempotency_key'] = $paymentData['idempotency_key'];
            }
            $transaction = $this->createTransaction($order, $sanitizedPaymentData);

            // Simulate payment processing (needs full card data for simulation)
            $paymentResult = $this->simulatePaymentGateway($paymentData);

                if (! empty($paymentResult['pending'])) {
                    $transaction->update([
                        'status' => 'pending',
                        'gateway_response' => $this->sanitizePaymentDetails($paymentResult),
                        'gateway_txn_id' => $paymentResult['gateway_txn_id'] ?? null,
                    ]);
                    $order->update(['status' => 'pending']);

                    Log::info('Wallet test payment pending', [
                        'transaction_id' => $transaction->txn_id,
                        'payment_method' => $transaction->payment_method,
                    ]);

                    return [
                        'success' => true,
                        'pending' => true,
                        'message' => $paymentResult['message'] ?? 'Wallet payment pending',
                        'order_id' => $order->order_id,
                        'transaction_id' => $transaction->txn_id,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'wallet_response' => $paymentResult['wallet_response'] ?? null,
                    ];
                }

                // Update transaction and order based on result
                if ($paymentResult['success']) {
                    $transaction->update([
                        'status' => 'success',
                        'gateway_response' => $paymentResult,
                        'gateway_txn_id' => $paymentResult['gateway_txn_id'] ?? null,
                        'captured_at' => now(),
                    ]);

                    $order->update(['status' => 'completed']);

                    // Update payment link if used
                    if (isset($paymentData['payment_link_id'])) {
                        $paymentLink = PaymentLink::find($paymentData['payment_link_id']);
                        if ($paymentLink) {
                            // Handle partial payments
                            if ($paymentLink->allow_partial_payment) {
                                // Add partial payment amount
                                $paymentAmount = $transaction->amount;
                                $isFullyPaid = $paymentLink->addPartialPayment($paymentAmount);
                                
                                Log::info('Partial payment added to payment link', [
                                    'payment_link_id' => $paymentLink->id,
                                    'payment_amount' => $paymentAmount,
                                    'amount_paid' => $paymentLink->amount_paid,
                                    'total_amount' => $paymentLink->amount,
                                    'is_fully_paid' => $isFullyPaid,
                                ]);
                            } else {
                                // Full payment - mark as paid
                                $paymentLink->markAsPaid();
                            }
                        }
                    }

                    event(new PaymentSuccess($transaction));

                    return [
                        'success' => true,
                        'message' => 'Payment successful',
                        'order_id' => $order->order_id,
                        'transaction_id' => $transaction->txn_id,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'redirect_url' => $this->getSuccessUrl($paymentData),
                    ];
                } else {
                    // PCI-DSS: Sanitize gateway response before storing
                    $sanitizedGatewayResponse = $this->sanitizePaymentDetails($paymentResult);
                    
                    $transaction->update([
                        'status' => 'failed',
                        'gateway_response' => $sanitizedGatewayResponse,
                    ]);

                    $order->update(['status' => 'failed']);

                    event(new PaymentFailed($transaction));

                    return [
                        'success' => false,
                        'message' => $paymentResult['message'] ?? 'Payment failed',
                        'order_id' => $order->order_id,
                        'transaction_id' => $transaction->txn_id,
                        'error_code' => $paymentResult['error_code'] ?? 'PAYMENT_FAILED',
                        'redirect_url' => $this->getFailureUrl($paymentData),
                    ];
                }
            } catch (\Exception $e) {
                // PCI-DSS: Never log card data - exclude trace to prevent potential exposure
                Log::error('Payment simulation error', [
                    'error' => $e->getMessage(),
                    // trace excluded to prevent potential card data exposure
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create order from payment data.
     */
    protected function createOrder(array $paymentData): Order
    {
        return Order::create([
            'merchant_id' => $paymentData['merchant_id'],
            'order_id' => Order::generateOrderId(),
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'customer_details' => $paymentData['customer_details'] ?? null,
            'status' => 'created',
            'description' => $paymentData['description'] ?? null,
            'test_mode' => $paymentData['test_mode'] ?? false,
            'payment_link_id' => $paymentData['payment_link_id'] ?? null,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Create transaction from order and payment data.
     */
    protected function createTransaction(Order $order, array $paymentData): Transaction
    {
        // Calculate fee using BaseRateService
        $baseRateService = app(\App\Services\BaseRateService::class);
        $merchant = $order->merchant;
        $bank = $merchant->bank ?? null;
        $feeCalculation = $baseRateService->calculateFee(
            $merchant,
            $order->amount,
            $paymentData['payment_method'],
            $bank,
            \App\Models\BaseRate::SERVICE_TYPE_PAYMENT,
            \App\Models\BaseRate::TRANSACTION_TYPE_DOMESTIC // TODO: Determine from payment details
        );

        $feeAmount = $feeCalculation['fee_amount'];
        $gstAmount = $feeCalculation['gst_amount'] ?? 0;
        $otherFees = 0; // Default 0, can be configured per merchant
        $totalDeductions = $feeAmount + $gstAmount + $otherFees;
        $netAmount = $order->amount - $totalDeductions;

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'merchant_id' => $order->merchant_id,
            'txn_id' => Transaction::generateTxnId(),
            'payment_method' => $paymentData['payment_method'],
            'amount' => $order->amount,
            'fee_amount' => $feeAmount,
            'gst_amount' => $gstAmount,
            'other_fees' => $otherFees,
            'net_amount' => $netAmount,
            'currency' => $order->currency,
            'status' => 'initiated',
            'settlement_status' => 'pending',
            'payment_details' => $this->sanitizePaymentDetails($paymentData['payment_details'] ?? []),
            'test_mode' => $order->test_mode,
            'idempotency_key' => $paymentData['idempotency_key'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        try {
            $snapshot = app(\App\Services\Rates\MerchantRateSnapshotService::class)->createPaymentSnapshot(
                merchant: $merchant,
                paymentMethod: (string) $paymentData['payment_method'],
                amount: (float) $order->amount,
                feeAmount: (float) ($feeCalculation['fee_amount'] ?? 0),
                percentageFee: (float) ($feeCalculation['percentage_fee'] ?? 0),
                flatFee: (float) ($feeCalculation['flat_fee'] ?? 0),
                gstPercentage: (float) ($feeCalculation['gst_percentage'] ?? 18),
                baseRateId: $feeCalculation['rate_id'] ?? null
            );
            $transaction->update([
                'admin_rate_snapshot_id' => $snapshot->id,
                'admin_fee_percentage_snapshot' => $snapshot->effective_fee_percentage,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to snapshot rate in PaymentSimulationService', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $transaction;
    }

    /**
     * Simulate payment gateway processing.
     * In test mode, uses test card numbers to determine success/failure.
     * In live mode, would integrate with real payment gateway.
     */
    protected function simulatePaymentGateway(array $paymentData): array
    {
        $paymentMethod = $paymentData['payment_method'];
        $paymentDetails = $paymentData['payment_details'] ?? []; // Handle missing payment_details
        $testMode = $paymentData['test_mode'] ?? false;

        // In test mode, simulate based on test data
        if ($testMode) {
            return $this->simulateTestPayment($paymentData);
        }

        // In live mode, you would integrate with real payment gateway here
        // For now, we'll treat it as test mode
        return $this->simulateTestPayment($paymentData);
    }

    /**
     * Simulate test payment based on test card numbers, UPI IDs, and wallet rules.
     */
    protected function simulateTestPayment(array $paymentData): array
    {
        $paymentMethod = (string) ($paymentData['payment_method'] ?? '');
        $paymentDetails = $paymentData['payment_details'] ?? [];
        $amount = (float) ($paymentData['amount'] ?? 0);

        if ($paymentMethod === 'wallet') {
            return $this->simulateWalletTestPayment($paymentData, $paymentDetails, $amount);
        }

        // Check for explicit simulation result (from test mode buttons)
        if (isset($paymentDetails['simulate']) && isset($paymentDetails['simulate_result'])) {
            if ($paymentDetails['simulate_result'] === 'pending') {
                return [
                    'pending' => true,
                    'success' => false,
                    'gateway_txn_id' => strtoupper($paymentMethod).'_'.strtoupper(uniqid()),
                    'message' => 'Payment pending (simulated)',
                    'payment_method' => $paymentMethod,
                ];
            }
            if ($paymentDetails['simulate_result'] === 'success') {
                return [
                    'success' => true,
                    'gateway_txn_id' => 'TEST_' . strtoupper(uniqid()),
                    'message' => 'Payment successful (simulated)',
                    'payment_method' => $paymentMethod,
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment failed (simulated)',
                'error_code' => 'PAYMENT_FAILED',
            ];
        }

        if ($paymentMethod === 'card') {
            // Handle case where payment_details might be empty (test mode simulation)
            $cardNumber = str_replace(' ', '', $paymentDetails['card_number'] ?? '');

            // If no card number provided (test mode without card details), default to success
            if (empty($cardNumber)) {
                return [
                    'success' => true,
                    'gateway_txn_id' => 'TEST_' . strtoupper(uniqid()),
                    'message' => 'Payment successful (test mode)',
                    'payment_method' => 'card',
                    'card_last4' => '0000',
                ];
            }

            // Test card numbers
            $successCards = ['4242424242424242', '4111111111111111', '5555555555554444'];
            $failureCards = ['4000000000000002', '4000000000009995'];

            if (in_array($cardNumber, $successCards)) {
                return [
                    'success' => true,
                    'gateway_txn_id' => 'TEST_' . strtoupper(uniqid()),
                    'message' => 'Payment successful',
                    'payment_method' => 'card',
                    'card_last4' => substr($cardNumber, -4),
                ];
            }

            if (in_array($cardNumber, $failureCards)) {
                $message = $cardNumber === '4000000000009995' 
                    ? '(Test Mode) Test card used for failed payments - Insufficient funds' 
                    : '(Test Mode) Test card used for failed payments - Payment declined';
                return [
                    'success' => false,
                    'message' => $message,
                    'error_code' => $cardNumber === '4000000000009995' 
                        ? 'INSUFFICIENT_FUNDS' 
                        : 'PAYMENT_DECLINED',
                ];
            }

            // Last-4 shortcuts (same convention as orchestration test simulator): ...1111 = approve, ...0000 = decline
            if (strlen($cardNumber) >= 16) {
                $last4 = substr($cardNumber, -4);
                if ($last4 === '1111') {
                    return [
                        'success' => true,
                        'gateway_txn_id' => 'TEST_' . strtoupper(uniqid()),
                        'message' => 'Payment successful (test card ending 1111)',
                        'payment_method' => 'card',
                        'card_last4' => '1111',
                    ];
                }
                if ($last4 === '0000') {
                    return [
                        'success' => false,
                        'message' => '(Test Mode) Card ending 0000 — simulated decline',
                        'error_code' => 'PAYMENT_DECLINED',
                    ];
                }
            }

            // Unknown card - default to success in test mode
            return [
                'success' => true,
                'gateway_txn_id' => 'TEST_' . strtoupper(uniqid()),
                'message' => 'Payment successful',
                'payment_method' => 'card',
                'card_last4' => substr($cardNumber, -4),
            ];
        }

        if ($paymentMethod === 'upi') {
            $upiId = strtolower(trim((string) ($paymentDetails['upi_id'] ?? '')));

            // Strict test UPI behavior:
            // only these two IDs are accepted for deterministic QA.
            if ($upiId === 'testsuccess@gocash') {
                return [
                    'success' => true,
                    'gateway_txn_id' => 'UPI_' . strtoupper(uniqid()),
                    'message' => 'Payment successful',
                    'payment_method' => 'upi',
                    'upi_id' => $upiId,
                ];
            }

            if ($upiId === 'testfailure@gocash') {
                return [
                    'success' => false,
                    'message' => 'UPI payment failed',
                    'error_code' => 'UPI_FAILED',
                ];
            }

            // Any other UPI ID is invalid for test-mode simulation
            return [
                'success' => false,
                'message' => 'Invalid test UPI ID. Use testsuccess@gocash or testfailure@gocash.',
                'error_code' => 'UPI_TEST_ID_INVALID',
            ];
        }

        // For netbanking, default to success
        return [
            'success' => true,
            'gateway_txn_id' => strtoupper($paymentMethod) . '_' . strtoupper(uniqid()),
            'message' => 'Payment successful',
            'payment_method' => $paymentMethod,
        ];
    }

    /**
     * Wallet-only test simulation (amount 101/102/103, explicit simulate, or default success).
     */
    protected function simulateWalletTestPayment(array $paymentData, array $paymentDetails, float $amount): array
    {
        $provider = strtolower(trim((string) ($paymentDetails['wallet_provider'] ?? '')));
        $walletLabel = $this->walletService->labelFor($provider !== '' ? $provider : 'wallet');
        $txnId = $this->walletService->generateTestTxnId();

        $resolveOutcome = function (string $outcome) use ($walletLabel, $txnId, $amount, $provider): array {
            $payload = $this->walletService->buildGatewayPayload($outcome, $walletLabel, $txnId, $amount);

            if ($outcome === 'pending') {
                return [
                    'pending' => true,
                    'success' => false,
                    'gateway_txn_id' => $txnId,
                    'message' => 'Wallet payment pending (test mode)',
                    'payment_method' => 'wallet',
                    'wallet_provider' => $provider,
                    'wallet_response' => $payload,
                    'wallet_mode' => 'test',
                ];
            }

            if ($outcome === 'failed') {
                return [
                    'success' => false,
                    'message' => 'Wallet payment failed (test mode)',
                    'error_code' => 'WALLET_PAYMENT_FAILED',
                    'gateway_txn_id' => $txnId,
                    'payment_method' => 'wallet',
                    'wallet_provider' => $provider,
                    'wallet_response' => $payload,
                    'wallet_mode' => 'test',
                ];
            }

            return [
                'success' => true,
                'gateway_txn_id' => $txnId,
                'message' => 'Wallet payment successful (test mode)',
                'payment_method' => 'wallet',
                'wallet_provider' => $provider,
                'wallet_response' => $payload,
                'wallet_mode' => 'test',
            ];
        };

        if (isset($paymentDetails['simulate'], $paymentDetails['simulate_result'])) {
            $sim = (string) $paymentDetails['simulate_result'];
            if ($sim === 'pending') {
                return $resolveOutcome('pending');
            }
            if ($sim === 'success') {
                return $resolveOutcome('success');
            }

            return $resolveOutcome('failed');
        }

        $amountOutcome = $this->walletService->resolveAmountTestOutcome($amount);
        if ($amountOutcome !== null) {
            return $resolveOutcome($amountOutcome);
        }

        return $resolveOutcome('success');
    }

    /**
     * Idempotent replay for duplicate wallet/test callbacks.
     */
    protected function responseForExistingTransaction(Transaction $transaction, array $paymentData): array
    {
        Log::info('Wallet payment idempotency: existing transaction returned', [
            'transaction_id' => $transaction->txn_id,
            'status' => $transaction->status,
        ]);

        $base = [
            'order_id' => $transaction->order?->order_id,
            'transaction_id' => $transaction->txn_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'already_processed' => true,
        ];

        if ($transaction->status === 'success') {
            return array_merge($base, [
                'success' => true,
                'message' => 'Payment already processed',
                'redirect_url' => $this->getSuccessUrl($paymentData),
            ]);
        }

        if ($transaction->status === 'pending') {
            return array_merge($base, [
                'success' => true,
                'pending' => true,
                'message' => 'Payment already pending',
            ]);
        }

        return array_merge($base, [
            'success' => false,
            'message' => 'Payment already processed',
            'redirect_url' => $this->getFailureUrl($paymentData),
        ]);
    }

    /**
     * Calculate fee for transaction.
     */
    protected function calculateFee(float $amount): float
    {
        // Default 2.5% fee
        return round($amount * 0.025, 2);
    }

    /**
     * Calculate GST on commission (18%).
     */
    protected function calculateGST(float $feeAmount): float
    {
        // GST is 18% of the commission
        return round($feeAmount * 0.18, 2);
    }

    // SanitizePaymentDetails method moved to SanitizesCardData trait

    /**
     * Get success redirect URL.
     */
    protected function getSuccessUrl(array $paymentData): string
    {
        if (isset($paymentData['payment_link_id'])) {
            $paymentLink = PaymentLink::find($paymentData['payment_link_id']);
            if ($paymentLink) {
                return route('payment.success', ['token' => $paymentLink->link_token]);
            }
        }

        return $paymentData['return_url'] ?? route('dashboard');
    }

    /**
     * Get failure redirect URL.
     */
    protected function getFailureUrl(array $paymentData): string
    {
        if (isset($paymentData['payment_link_id'])) {
            $paymentLink = PaymentLink::find($paymentData['payment_link_id']);
            if ($paymentLink) {
                return route('payment.failed', ['token' => $paymentLink->link_token]);
            }
        }

        return $paymentData['cancel_url'] ?? route('dashboard');
    }
}
