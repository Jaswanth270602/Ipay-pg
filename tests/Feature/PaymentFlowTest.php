<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Merchant $merchant;
    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::create(['name' => 'merchant']);

        // Create test merchant
        $this->merchant = Merchant::create([
            'name' => 'Test Merchant',
            'email' => 'test@merchant.com',
            'status' => 'active',
            'test_mode' => true,
            'default_currency' => 'USD',
            'fee_percentage' => 2.5,
            'fee_flat' => 0.30,
        ]);

        // Create API key
        $this->apiKey = ApiKey::generate($this->merchant->id, 'test', 'Test Key');
    }

    public function test_create_payment_with_valid_api_key(): void
    {
        $response = $this->postJson('/api/v1/payment', [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'card',
            'customer_details' => [
                'name' => 'Test Customer',
                'email' => 'customer@test.com',
            ],
            'description' => 'Test payment',
        ], [
            'X-API-Key' => $this->apiKey->key,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'transaction_id',
                    'amount',
                    'currency',
                    'status',
                    'payment_method',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'merchant_id' => $this->merchant->id,
            'amount' => 100.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'merchant_id' => $this->merchant->id,
            'amount' => 100.00,
            'payment_method' => 'card',
        ]);
    }

    public function test_create_payment_without_api_key(): void
    {
        $response = $this->postJson('/api/v1/payment', [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'card',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'API key is required',
            ]);
    }

    public function test_create_payment_with_invalid_api_key(): void
    {
        $response = $this->postJson('/api/v1/payment', [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'card',
        ], [
            'X-API-Key' => 'invalid_key',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid or expired API key',
            ]);
    }

    public function test_idempotency_prevents_duplicate_payments(): void
    {
        $idempotencyKey = 'test-idempotent-key-' . time();

        // First request
        $response1 = $this->postJson('/api/v1/payment', [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'card',
            'idempotency_key' => $idempotencyKey,
        ], [
            'X-API-Key' => $this->apiKey->key,
        ]);

        $response1->assertStatus(201);
        $orderId1 = $response1->json('data.order_id');

        // Second request with same idempotency key
        $response2 = $this->postJson('/api/v1/payment', [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'card',
            'idempotency_key' => $idempotencyKey,
        ], [
            'X-API-Key' => $this->apiKey->key,
        ]);

        $response2->assertStatus(201);
        $orderId2 = $response2->json('data.order_id');

        // Should return same order
        $this->assertEquals($orderId1, $orderId2);

        // Should only have one order in database
        $this->assertDatabaseCount('orders', 1);
    }
}

