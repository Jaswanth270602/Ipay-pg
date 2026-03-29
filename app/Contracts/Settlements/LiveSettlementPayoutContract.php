<?php

namespace App\Contracts\Settlements;

use App\Models\Settlement;

/**
 * When a bank/NEFT/IMPS integration is available, implement this to initiate payouts
 * after ops approval or automated rules. The core app completes settlement records only.
 */
interface LiveSettlementPayoutContract
{
    /**
     * Whether this driver can initiate payouts for the given settlement (e.g. credentials present).
     */
    public function canInitiate(Settlement $settlement): bool;

    /**
     * Initiate payout; return reference id / status for bank_reference field.
     */
    public function initiate(Settlement $settlement): LiveSettlementPayoutResult;
}
