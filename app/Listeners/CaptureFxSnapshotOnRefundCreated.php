<?php

namespace App\Listeners;

use App\Events\RefundCreated;
use App\Services\FxSnapshotService;

class CaptureFxSnapshotOnRefundCreated
{
    public function __construct(
        private readonly FxSnapshotService $fxSnapshotService
    ) {}

    public function handle(RefundCreated $event): void
    {
        $refund = $event->refund->fresh(['transaction']);
        if (! $refund || $refund->status !== 'completed') {
            return;
        }

        $this->fxSnapshotService->captureRefundSnapshot($refund);
    }
}
