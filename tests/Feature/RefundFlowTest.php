<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Merchant $merchant;
    protected ApiKey $apiKey;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $merchantRole = Role::create(['name' => 'merchant']);
        
        $this->merchant = Merchant::create([
            'name' => 'Test Merchant',
            'email' => 'test@merchant.com',
            'status' => 'active',
            'test_mode' => true,
            'default_currency' => 'USD',
            'fee_percentage' => 2.5,
            'fee_flat' => 0.30,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role_id' => $merchantRole->id,
            'merchant_id' => $this->merchant->id,
            'status' => 'active',
        ]);

        $this->apiKey = ApiKey::generate($this->merchant->id, 'test');
    }

    public function test_create_refund_for_successful_transaction(): void
    {
        // Create a successful transaction
        $order = Order::create([
            'merchant_id' => $this->merchant->id,
            'order_id' => Order::generateOrderId(),
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'completed',
            'test_mode' => true,
        ]);

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'merchant_id' => $this->merchant->id,
            'txn_id' => Transaction::generateTxnId(),
            'payment_method' => 'card',
            'amount' => 100.00,
            'fee_amount' => 2.80,
            'net_amount' => 97.20,
            'currency' => 'USD',
            'status' => 'success',
            'test_mode' => true,
        ]);

        $response = $this->postJson('/api/v1/refunds', [
            'transaction_id' => $transaction->txn_id,
            'amount' => 50.00,
            'reason' => 'Customer request',
        ], [
            'X-API-Key' => $this->apiKey->key,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'refund_id',
                    'transaction_id',
                    'amount',
                    'status',
                    'is_partial',
                ],
            ]);

        $this->assertDatabaseHas('refunds', [
            'transaction_id' => $transaction->id,
            'amount' => 50.00,
            'is_partial' => true,
        ]);
    }

    public function test_cannot_refund_failed_transaction(): void
    {
        $order = Order::create([
            'merchant_id' => $this->merchant->id,
            'order_id' => Order::generateOrderId(),
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'failed',
            'test_mode' => true,
        ]);

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'merchant_id' => $this->merchant->id,
            'txn_id' => Transaction::generateTxnId(),
            'payment_method' => 'card',
            'amount' => 100.00,
            'fee_amount' => 2.80,
            'net_amount' => 97.20,
            'currency' => 'USD',
            'status' => 'failed',
            'test_mode' => true,
        ]);

        $response = $this->postJson('/api/v1/refunds', [
            'transaction_id' => $transaction->txn_id,
            'amount' => 50.00,
        ], [
            'X-API-Key' => $this->apiKey->key,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot refund unsuccessful transaction',
            ]);
    }
}

