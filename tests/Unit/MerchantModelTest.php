<?php

namespace Tests\Unit;

use App\Models\Merchant;
use Tests\TestCase;

class MerchantModelTest extends TestCase
{
    public function test_calculate_fee(): void
    {
        $merchant = new Merchant([
            'fee_percentage' => 2.5,
            'fee_flat' => 0.30,
        ]);

        $fee = $merchant->calculateFee(100.00);

        // (100 * 2.5%) + 0.30 = 2.50 + 0.30 = 2.80
        $this->assertEquals(2.80, $fee);
    }

    public function test_is_active(): void
    {
        $activeMerchant = new Merchant(['status' => 'active']);
        $inactiveMerchant = new Merchant(['status' => 'inactive']);

        $this->assertTrue($activeMerchant->isActive());
        $this->assertFalse($inactiveMerchant->isActive());
    }
}

