<?php

namespace App\Services\Settlements;

use App\Contracts\Settlements\LiveSettlementPayoutContract;
use App\Contracts\Settlements\LiveSettlementPayoutResult;
use App\Models\Settlement;

/**
 * Default until a bank/acquirer payout module is wired (same API shape as future drivers).
 */
class NullLiveSettlementPayout implements LiveSettlementPayoutContract
{
    public function canInitiate(Settlement $settlement): bool
    {
        return false;
    }

    public function initiate(Settlement $settlement): LiveSettlementPayoutResult
    {
        return new LiveSettlementPayoutResult(
            success: false,
            bankReference: null,
            message: 'Live payout driver not configured.',
        );
    }
}
