<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Refund;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates comprehensive test data for both TEST and LIVE modes
     * to help developers test the payment gateway functionality.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating Test Data...');

        // Create Test Merchant
        $this->command->info('Creating Test Merchant...');
        $testUser = User::firstOrCreate(
            ['email' => 'test@merchant.com'],
            [
                'name' => 'Test Merchant User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $testMerchant = Merchant::firstOrCreate(
            ['business_name' => 'Test Merchant Business'],
            [
                'business_email' => 'test@merchant.com',
                'business_phone' => '+1234567890',
                'test_mode' => true, // TEST MODE
                'status' => 'active',
                'default_currency' => 'INR',
                'fee_percentage' => 2.5,
                'fee_flat' => 0,
                'settlement_schedule' => 'T+2',
                'settings' => [
                    'webhook_url' => 'https://test-merchant.com/webhook',
                    'return_url' => 'https://test-merchant.com/return',
                ],
            ]
        );

        // Associate user with merchant
        if (!$testMerchant->users()->where('user_id', $testUser->id)->exists()) {
            $testMerchant->users()->attach($testUser->id);
            $this->command->info('✅ Test Merchant Created: test@merchant.com');
        }

        // Create Test API Keys
        $this->command->info('Creating Test API Keys...');
        $testApiKey = ApiKey::firstOrCreate(
            ['merchant_id' => $testMerchant->id, 'mode' => 'test'],
            [
                'key' => 'pk_test_' . bin2hex(random_bytes(16)),
                'secret' => 'sk_test_' . bin2hex(random_bytes(16)),
                'name' => 'Test Mode API Key',
                'mode' => 'test',
                'status' => 'active',
            ]
        );
        $this->command->info("✅ Test API Key: {$testApiKey->key}");
        $this->command->info("   Secret: {$testApiKey->secret}");

        // Create Live API Keys (even though merchant is in test mode, for testing mode mismatch)
        $liveApiKey = ApiKey::firstOrCreate(
            ['merchant_id' => $testMerchant->id, 'mode' => 'live'],
            [
                'key' => 'pk_live_' . bin2hex(random_bytes(16)),
                'secret' => 'sk_live_' . bin2hex(random_bytes(16)),
                'name' => 'Live Mode API Key',
                'mode' => 'live',
                'status' => 'active',
            ]
        );
        $this->command->info("✅ Live API Key: {$liveApiKey->key}");
        $this->command->info("   Secret: {$liveApiKey->secret}");

        // Create Test Orders and Transactions
        $this->command->info('Creating Test Orders & Transactions...');
        $this->createTestOrders($testMerchant);

        // Create Test Payment Links
        $this->command->info('Creating Test Payment Links...');
        $this->createTestPaymentLinks($testMerchant);

        // Create Test Refunds
        $this->command->info('Creating Test Refunds...');
        $this->createTestRefunds($testMerchant);

        // Create Test Settlements
        $this->command->info('Creating Test Settlements...');
        $this->createTestSettlements($testMerchant);

        $this->command->info('');
        $this->command->info('🎉 Test Data Created Successfully!');
        $this->command->info('');
        $this->command->info('📋 Test Credentials:');
        $this->command->info('   Email: test@merchant.com');
        $this->command->info('   Password: password123');
        $this->command->info('   Test API Key: ' . $testApiKey->key);
        $this->command->info('   Test Secret: ' . $testApiKey->secret);
        $this->command->info('   Live API Key (for mode mismatch test): ' . $liveApiKey->key);
        $this->command->info('');
    }

    private function createTestOrders(Merchant $merchant): void
    {
        $statuses = ['created', 'pending', 'completed', 'failed', 'expired'];
        
        for ($i = 1; $i <= 10; $i++) {
            $status = $statuses[array_rand($statuses)];
            
            $order = Order::create([
                'merchant_id' => $merchant->id,
                'order_id' => 'ORD_TEST_' . strtoupper(bin2hex(random_bytes(8))),
                'amount' => rand(100, 10000),
                'currency' => 'INR',
                'status' => $status,
                'test_mode' => true,
                'customer_details' => [
                    'name' => 'Test Customer ' . $i,
                    'email' => "customer{$i}@test.com",
                    'phone' => '+91' . rand(6000000000, 9999999999),
                ],
                'description' => "Test Order #{$i}",
                'metadata' => ['test_order' => true, 'order_number' => $i],
                'return_url' => 'https://test-merchant.com/success',
                'cancel_url' => 'https://test-merchant.com/cancel',
                'expires_at' => now()->addHours(24),
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            // Create transactions for completed orders
            if (in_array($status, ['completed', 'pending'])) {
                $txnStatus = $status === 'completed' ? 'success' : 'initiated';
                
                Transaction::create([
                    'order_id' => $order->id,
                    'merchant_id' => $merchant->id,
                    'txn_id' => 'TXN_TEST_' . strtoupper(bin2hex(random_bytes(8))),
                    'payment_method' => ['upi', 'card', 'netbanking', 'wallet'][array_rand(['upi', 'card', 'netbanking', 'wallet'])],
                    'amount' => $order->amount,
                    'fee_amount' => $order->amount * 0.025,
                    'net_amount' => $order->amount * 0.975,
                    'currency' => $order->currency,
                    'status' => $txnStatus,
                    'test_mode' => true,
                    'payment_details' => [
                        'card_type' => 'visa',
                        'last4' => rand(1000, 9999),
                    ],
                    'gateway_response' => ['status' => 'success', 'gateway' => 'test'],
                    'gateway_txn_id' => 'GTW_' . bin2hex(random_bytes(8)),
                    'captured_at' => $txnStatus === 'success' ? now() : null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Test Agent',
                    'created_at' => $order->created_at,
                ]);
            }
        }
        
        $this->command->info('✅ Created 10 test orders with transactions');
    }

    private function createTestPaymentLinks(Merchant $merchant): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $active = $i <= 3; // First 3 are active
            
            PaymentLink::create([
                'merchant_id' => $merchant->id,
                'link_id' => 'LINK_TEST_' . strtoupper(bin2hex(random_bytes(8))),
                'title' => "Test Payment Link #{$i}",
                'description' => "Test payment link for testing purposes #{$i}",
                'amount' => $i % 2 === 0 ? rand(500, 5000) : null, // Some with fixed amount, some flexible
                'currency' => 'INR',
                'status' => $active ? 'active' : 'inactive',
                'test_mode' => true,
                'settings' => [
                    'custom_fields' => ['customer_id', 'reference'],
                    'send_email' => true,
                    'send_sms' => false,
                ],
                'metadata' => ['purpose' => 'testing', 'link_number' => $i],
                'expires_at' => $active ? now()->addDays(30) : now()->subDays(1),
                'created_at' => now()->subDays(rand(1, 15)),
            ]);
        }
        
        $this->command->info('✅ Created 5 test payment links');
    }

    private function createTestRefunds(Merchant $merchant): void
    {
        $successTransactions = Transaction::where('merchant_id', $merchant->id)
            ->where('status', 'success')
            ->where('test_mode', true)
            ->limit(3)
            ->get();

        foreach ($successTransactions as $index => $transaction) {
            $isPartial = $index === 0; // First refund is partial
            $refundAmount = $isPartial ? $transaction->amount * 0.5 : $transaction->amount;
            
            Refund::create([
                'transaction_id' => $transaction->id,
                'merchant_id' => $merchant->id,
                'refund_id' => 'REF_TEST_' . strtoupper(bin2hex(random_bytes(8))),
                'amount' => $refundAmount,
                'currency' => $transaction->currency,
                'reason' => $isPartial ? 'Partial refund - customer request' : 'Full refund - order cancelled',
                'status' => ['pending', 'processing', 'completed'][array_rand(['pending', 'processing', 'completed'])],
                'is_partial' => $isPartial,
                'initiated_by' => $merchant->users()->first()->id,
                'gateway_response' => ['status' => 'processing'],
                'created_at' => now()->subDays(rand(1, 10)),
            ]);
        }
        
        $this->command->info('✅ Created ' . $successTransactions->count() . ' test refunds');
    }

    private function createTestSettlements(Merchant $merchant): void
    {
        $settledTransactions = Transaction::where('merchant_id', $merchant->id)
            ->where('status', 'success')
            ->where('test_mode', true)
            ->where('settlement_status', 'pending')
            ->limit(5)
            ->get();

        if ($settledTransactions->isEmpty()) {
            $this->command->info('⚠️  No eligible transactions for settlements');
            return;
        }

        $totalAmount = $settledTransactions->sum('net_amount');
        
        $settlement = Settlement::create([
            'merchant_id' => $merchant->id,
            'settlement_id' => 'SETL_TEST_' . strtoupper(bin2hex(random_bytes(8))),
            'amount' => $totalAmount,
            'currency' => 'INR',
            'status' => 'pending',
            'transaction_count' => $settledTransactions->count(),
            'settlement_date' => now()->addDays(2),
            'created_at' => now(),
        ]);

        // Link transactions to settlement
        foreach ($settledTransactions as $transaction) {
            $transaction->update([
                'settlement_id' => $settlement->id,
                'settlement_status' => 'pending',
            ]);
        }
        
        $this->command->info('✅ Created 1 test settlement with ' . $settledTransactions->count() . ' transactions');
    }
}


