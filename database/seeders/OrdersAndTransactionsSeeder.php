<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrdersAndTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = Merchant::all();
        $banks = Bank::all();
        
        $paymentMethods = ['card', 'netbanking', 'upi', 'wallet'];
        $orderStatuses = ['completed', 'completed', 'completed', 'failed', 'pending']; // Weighted toward completed
        $transactionStatuses = ['success', 'success', 'success', 'failed', 'pending']; // Weighted toward success

        $ordersCount = 0;
        $transactionsCount = 0;

        foreach ($merchants as $merchant) {
            // Create 50+ orders per merchant (total 150+ orders)
            $numOrders = rand(50, 70);

            for ($i = 0; $i < $numOrders; $i++) {
                $orderStatus = $orderStatuses[array_rand($orderStatuses)];
                $amount = rand(1000, 50000) / 100; // $10 to $500

                $order = Order::create([
                    'merchant_id' => $merchant->id,
                    'order_id' => Order::generateOrderId(),
                    'amount' => $amount,
                    'currency' => $merchant->default_currency,
                    'customer_details' => [
                        'name' => 'Customer ' . Str::random(8),
                        'email' => 'customer' . rand(1000, 9999) . '@example.com',
                        'phone' => '+1' . rand(1000000000, 9999999999),
                    ],
                    'status' => $orderStatus,
                    'description' => 'Test order #' . ($ordersCount + 1),
                    'metadata' => [
                        'source' => 'web',
                        'campaign' => 'summer_sale',
                    ],
                    'test_mode' => $merchant->test_mode,
                    'idempotency_key' => Str::uuid()->toString(),
                    'created_at' => now()->subDays(rand(1, 90)),
                    'updated_at' => now()->subDays(rand(0, 90)),
                ]);

                $ordersCount++;

                // Create 1-3 transactions per order (average 2.5, so total 150+ transactions for 50+ orders)
                $numTransactions = rand(1, 3);

                for ($j = 0; $j < $numTransactions; $j++) {
                    $txnStatus = $j === 0 && $orderStatus === 'completed' 
                        ? 'success' 
                        : $transactionStatuses[array_rand($transactionStatuses)];

                    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                    $bank = $banks->random();
                    
                    $feeAmount = $merchant->calculateFee($amount);
                    $netAmount = $amount - $feeAmount;

                    $transaction = Transaction::create([
                        'order_id' => $order->id,
                        'merchant_id' => $merchant->id,
                        'txn_id' => Transaction::generateTxnId(),
                        'payment_method' => $paymentMethod,
                        'amount' => $amount,
                        'fee_amount' => $feeAmount,
                        'net_amount' => $netAmount,
                        'currency' => $merchant->default_currency,
                        'status' => $txnStatus,
                        'gateway_response' => [
                            'code' => $txnStatus === 'success' ? '000' : 'ERR_' . rand(100, 999),
                            'message' => $txnStatus === 'success' ? 'Transaction successful' : 'Transaction failed',
                        ],
                        'payment_details' => $this->generatePaymentDetails($paymentMethod),
                        'gateway_txn_id' => 'GTW_' . strtoupper(Str::random(16)),
                        'bank_id' => $bank->id,
                        'test_mode' => $merchant->test_mode,
                        'idempotency_key' => Str::uuid()->toString(),
                        'ip_address' => $this->generateRandomIp(),
                        'user_agent' => $this->generateRandomUserAgent(),
                        'authorized_at' => $txnStatus === 'success' ? now()->subDays(rand(0, 90)) : null,
                        'captured_at' => $txnStatus === 'success' ? now()->subDays(rand(0, 90)) : null,
                        'created_at' => $order->created_at->addMinutes(rand(1, 10)),
                        'updated_at' => $order->created_at->addMinutes(rand(1, 15)),
                    ]);

                    $transactionsCount++;
                }
            }
        }

        $this->command->info("Seeded {$ordersCount} orders and {$transactionsCount} transactions successfully.");
    }

    /**
     * Generate payment details based on payment method.
     */
    private function generatePaymentDetails(string $paymentMethod): array
    {
        switch ($paymentMethod) {
            case 'card':
                return [
                    'card_type' => ['visa', 'mastercard', 'amex'][array_rand(['visa', 'mastercard', 'amex'])],
                    'last4' => str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'expiry_month' => str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT),
                    'expiry_year' => rand(2024, 2030),
                    'card_holder' => 'Test Customer',
                ];

            case 'upi':
                return [
                    'upi_id' => 'user' . rand(1000, 9999) . '@' . ['oksbi', 'okaxis', 'okicici'][array_rand(['oksbi', 'okaxis', 'okicici'])],
                    'provider' => 'UPI',
                ];

            case 'netbanking':
                return [
                    'bank_name' => ['HDFC Bank', 'ICICI Bank', 'SBI', 'Axis Bank'][array_rand(['HDFC Bank', 'ICICI Bank', 'SBI', 'Axis Bank'])],
                    'account_type' => 'savings',
                ];

            case 'wallet':
                return [
                    'wallet_name' => ['Paytm', 'PhonePe', 'GooglePay'][array_rand(['Paytm', 'PhonePe', 'GooglePay'])],
                    'wallet_id' => rand(1000000000, 9999999999),
                ];

            default:
                return [];
        }
    }

    /**
     * Generate random IP address.
     */
    private function generateRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }

    /**
     * Generate random user agent.
     */
    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}

