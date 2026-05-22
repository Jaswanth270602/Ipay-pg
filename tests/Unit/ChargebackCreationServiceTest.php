<?php

namespace Tests\Unit;

use App\Services\ChargebackCreationService;
use PHPUnit\Framework\TestCase;

class ChargebackCreationServiceTest extends TestCase
{
    public function test_normalize_status_maps_disputed_to_contested(): void
    {
        $service = new ChargebackCreationService;

        $this->assertSame('contested', $service->normalizeStatus('disputed'));
        $this->assertSame('pending', $service->normalizeStatus(''));
        $this->assertSame('won', $service->normalizeStatus('WON'));
    }

    public function test_generate_request_id_has_prefix(): void
    {
        $service = new ChargebackCreationService;
        $merchant = new \App\Models\Merchant;
        $merchant->id = 6;

        $id = $service->generateRequestId($merchant);

        $this->assertStringStartsWith('CB_6_', $id);
    }
}
