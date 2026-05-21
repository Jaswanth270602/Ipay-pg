<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Models\Role;
use App\Models\Transaction;
use App\Services\PaymentSimulationService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'merchant']);
        $this->merchant = Merchant::create([
            'name' => 'Wallet Test Merchant',
            'email' => 'wallet-merchant@test.com',
            'status' => 'active',
            'test_mode' => true,
            'default_currency' => 'INR',
            'fee_percentage' => 2.5,
            'fee_flat' => 0,
        ]);
    }

    protected function createTestPaymentLink(float $amount = 100.00): PaymentLink
    {
        return PaymentLink::create([
            'merchant_id' => $this->merchant->id,
            'link_token' => PaymentLink::generateLinkToken(),
            'title' => 'Wallet test link',
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'active',
            'test_mode' => true,
            'payment_methods' => ['card', 'upi', 'netbanking', 'wallet'],
            'expires_at' => now()->addDay(),
        ]);
    }

    protected function walletPayload(PaymentLink $link, float $amount, array $extraDetails = []): array
    {
        return [
            'payment_method' => 'wallet',
            'amount' => $amount,
            'customer_details' => [
                'name' => 'Test User',
                'email' => 'wallet@test.com',
                'phone' => '9876543210',
            ],
            'payment_details' => array_merge([
                'wallet_provider' => 'paytm',
            ], $extraDetails),
        ];
    }

    public function test_wallet_providers_endpoint(): void
    {
        $link = $this->createTestPaymentLink();

        $response = $this->getJson(route('payment.wallet-providers', ['token' => $link->link_token]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['providers' => [['code', 'label']]]);
    }

    public function test_wallet_amount_101_succeeds_with_test_txn_id(): void
    {
        $link = $this->createTestPaymentLink(101);

        $response = $this->postJson(
            route('payment.process', ['token' => $link->link_token]),
            $this->walletPayload($link, 101)
        );

        $response->assertOk()->assertJsonPath('success', true);

        $txnId = $response->json('transaction_id');
        $this->assertNotEmpty($txnId);

        $transaction = Transaction::where('txn_id', $txnId)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('wallet', $transaction->payment_method);
        $this->assertSame('success', $transaction->status);
        $this->assertStringStartsWith('TESTWALLET', (string) $transaction->gateway_txn_id);
        $this->assertSame('SUCCESS', data_get($transaction->gateway_response, 'wallet_response.status'));
    }

    public function test_wallet_amount_102_fails(): void
    {
        $link = $this->createTestPaymentLink(102);

        $response = $this->postJson(
            route('payment.process', ['token' => $link->link_token]),
            $this->walletPayload($link, 102)
        );

        $response->assertStatus(402)->assertJsonPath('success', false);

        $transaction = Transaction::where('txn_id', $response->json('transaction_id'))->first();
        $this->assertNotNull($transaction);
        $this->assertSame('failed', $transaction->status);
    }

    public function test_wallet_amount_103_pending(): void
    {
        $link = $this->createTestPaymentLink(103);

        $response = $this->postJson(
            route('payment.process', ['token' => $link->link_token]),
            $this->walletPayload($link, 103)
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pending', true);

        $transaction = Transaction::where('txn_id', $response->json('transaction_id'))->first();
        $this->assertNotNull($transaction);
        $this->assertSame('pending', $transaction->status);
        $link->refresh();
        $this->assertNotSame('paid', $link->status);
    }

    public function test_wallet_invalid_provider_rejected(): void
    {
        $link = $this->createTestPaymentLink();

        $payload = $this->walletPayload($link, 100);
        $payload['payment_details']['wallet_provider'] = 'invalid-wallet';

        $this->postJson(route('payment.process', ['token' => $link->link_token]), $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_wallet_idempotency_prevents_duplicate_success(): void
    {
        $link = $this->createTestPaymentLink(101);
        $payload = $this->walletPayload($link, 101);
        $payload['idempotency_key'] = 'wallet-idem-'.uniqid();

        $first = $this->postJson(route('payment.process', ['token' => $link->link_token]), $payload);
        $first->assertOk();

        $second = $this->postJson(route('payment.process', ['token' => $link->link_token]), $payload);
        $second->assertOk()->assertJsonPath('already_processed', true);

        $count = Transaction::where('idempotency_key', $payload['idempotency_key'])->count();
        $this->assertSame(1, $count);
    }

    public function test_wallet_service_unit_amount_mapping(): void
    {
        $service = app(WalletService::class);
        $this->assertSame('success', $service->resolveAmountTestOutcome(101));
        $this->assertSame('failed', $service->resolveAmountTestOutcome(102));
        $this->assertSame('pending', $service->resolveAmountTestOutcome(103));
        $this->assertNull($service->resolveAmountTestOutcome(50));
    }
}
