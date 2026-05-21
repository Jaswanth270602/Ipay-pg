<?php

namespace Tests\Unit;

use App\Services\PaymentSimulationService;
use App\Services\WalletService;
use Tests\TestCase;

class WalletSimulationTest extends TestCase
{
    public function test_wallet_service_amount_outcomes(): void
    {
        $service = new WalletService();
        $this->assertSame('success', $service->resolveAmountTestOutcome(101));
        $this->assertSame('success', $service->resolveAmountTestOutcome(101.0));
        $this->assertSame('failed', $service->resolveAmountTestOutcome(102));
        $this->assertSame('pending', $service->resolveAmountTestOutcome(103));
        $this->assertNull($service->resolveAmountTestOutcome(99.5));
    }

    public function test_wallet_service_validates_providers(): void
    {
        $service = new WalletService();
        $this->assertTrue($service->isValidProvider('paytm'));
        $this->assertTrue($service->isValidProvider('phonepe'));
        $this->assertFalse($service->isValidProvider('not-a-wallet'));
    }

    public function test_wallet_gateway_payload_shape(): void
    {
        $service = new WalletService();
        $payload = $service->buildGatewayPayload('success', 'Paytm Wallet', 'TESTWALLET123', 101.0);
        $this->assertSame('SUCCESS', $payload['status']);
        $this->assertSame('Paytm Wallet', $payload['wallet']);
        $this->assertSame('TESTWALLET123', $payload['txn']);
        $this->assertSame('101.00', $payload['amount']);
    }

    public function test_simulate_wallet_test_payment_via_reflection(): void
    {
        $sim = new PaymentSimulationService(new WalletService());
        $method = new \ReflectionMethod(PaymentSimulationService::class, 'simulateWalletTestPayment');
        $method->setAccessible(true);

        $success = $method->invoke($sim, [
            'payment_method' => 'wallet',
            'amount' => 101,
        ], ['wallet_provider' => 'paytm'], 101.0);

        $this->assertTrue($success['success']);
        $this->assertStringStartsWith('TESTWALLET', $success['gateway_txn_id']);
        $this->assertSame('SUCCESS', $success['wallet_response']['status']);

        $failed = $method->invoke($sim, [
            'payment_method' => 'wallet',
            'amount' => 102,
        ], ['wallet_provider' => 'phonepe'], 102.0);

        $this->assertFalse($failed['success']);
        $this->assertSame('FAILED', $failed['wallet_response']['status']);

        $pending = $method->invoke($sim, [
            'payment_method' => 'wallet',
            'amount' => 103,
        ], ['wallet_provider' => 'mobikwik'], 103.0);

        $this->assertTrue($pending['pending']);
        $this->assertSame('PENDING', $pending['wallet_response']['status']);
    }
}
