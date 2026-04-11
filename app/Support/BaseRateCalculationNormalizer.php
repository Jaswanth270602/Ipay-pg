<?php

namespace App\Support;

use App\Models\BaseRate;

class BaseRateCalculationNormalizer
{
    /**
     * Normalize validated base-rate payload for persistence (does not replace request validation).
     */
    public static function normalize(array $data): array
    {
        $type = $data['calculation_type'] ?? BaseRate::CALCULATION_TYPE_PERCENTAGE_FIXED;

        if ($type === BaseRate::CALCULATION_TYPE_PERCENTAGE_ONLY) {
            $data['flat_fee'] = 0;
            $data['tier_slabs'] = null;
            $data['tier_fee_unit'] = null;
        } elseif ($type === BaseRate::CALCULATION_TYPE_FIXED_ONLY) {
            $data['percentage_fee'] = 0;
            $data['tier_slabs'] = null;
            $data['tier_fee_unit'] = null;
        } elseif ($type === BaseRate::CALCULATION_TYPE_PERCENTAGE_FIXED) {
            $data['tier_slabs'] = null;
            $data['tier_fee_unit'] = null;
        } elseif ($type === BaseRate::CALCULATION_TYPE_TIERED) {
            $data['percentage_fee'] = 0;
            $data['flat_fee'] = 0;
            $data['tier_slabs'] = self::sortTierSlabs($data['tier_slabs'] ?? []);
        }

        return $data;
    }

    /**
     * @param  array<int, array{txn_from?: mixed, txn_to?: mixed, fee_value?: mixed}>  $slabs
     * @return list<array{txn_from: int, txn_to: int|null, fee_value: float}>
     */
    public static function sortTierSlabs(array $slabs): array
    {
        $out = [];
        foreach ($slabs as $row) {
            $out[] = [
                'txn_from' => (int) ($row['txn_from'] ?? 0),
                'txn_to' => array_key_exists('txn_to', $row) && $row['txn_to'] !== null && $row['txn_to'] !== ''
                    ? (int) $row['txn_to']
                    : null,
                'fee_value' => round((float) ($row['fee_value'] ?? 0), 4),
            ];
        }
        usort($out, fn ($a, $b) => $a['txn_from'] <=> $b['txn_from']);

        return $out;
    }
}
