<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Events\RefundCreated;
use App\Services\DashboardMetricsCacheService;

class InvalidateDashboardMetricsCache
{
    public function __construct(
        private readonly DashboardMetricsCacheService $dashboardCache
    ) {}

    public function handlePaymentSuccess(PaymentSuccess $event): void
    {
        $this->dashboardCache->invalidateAll();
    }

    public function handleRefundCreated(RefundCreated $event): void
    {
        if ($event->refund->status !== 'completed') {
            return;
        }

        $this->dashboardCache->invalidateAll();
    }
}
