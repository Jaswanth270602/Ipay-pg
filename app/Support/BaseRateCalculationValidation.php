<?php

namespace App\Support;

use App\Models\BaseRate;
use Illuminate\Validation\Validator;

class BaseRateCalculationValidation
{
    /**
     * Extra validation after base rules (tiers, calculation-type-specific fee fields).
     */
    public static function validate(Validator $v, array $input): void
    {
        $type = $input['calculation_type'] ?? null;

        if ($type === BaseRate::CALCULATION_TYPE_PERCENTAGE_ONLY) {
            $pct = $input['percentage_fee'] ?? null;
            if ($pct === null || $pct === '') {
                $v->errors()->add('percentage_fee', 'Percentage fee is required for this calculation type.');
            }
        } elseif ($type === BaseRate::CALCULATION_TYPE_FIXED_ONLY) {
            $flat = $input['flat_fee'] ?? null;
            if ($flat === null || $flat === '') {
                $v->errors()->add('flat_fee', 'Flat fee is required for this calculation type.');
            }
        } elseif ($type === BaseRate::CALCULATION_TYPE_PERCENTAGE_FIXED) {
            // Both required — covered by main rules
        } elseif ($type === BaseRate::CALCULATION_TYPE_TIERED) {
            $slabs = $input['tier_slabs'] ?? null;
            if (! is_array($slabs) || count($slabs) === 0) {
                $v->errors()->add('tier_slabs', 'At least one tier is required.');

                return;
            }
            self::validateTierSlabs($v, $slabs);
        }
    }

    /**
     * @param  array<int, mixed>  $slabs
     */
    public static function validateTierSlabs(Validator $v, array $slabs): void
    {
        $rows = [];
        foreach ($slabs as $i => $s) {
            if (! is_array($s)) {
                $v->errors()->add("tier_slabs.$i", 'Each tier must be an object with from/to/fee.');

                return;
            }
            $from = isset($s['txn_from']) && $s['txn_from'] !== '' ? (int) $s['txn_from'] : null;
            $to = array_key_exists('txn_to', $s) && $s['txn_to'] !== '' && $s['txn_to'] !== null
                ? (int) $s['txn_to']
                : null;
            if ($from === null || $from < 0) {
                $v->errors()->add("tier_slabs.$i.txn_from", 'From (transaction count) must be a non-negative integer.');

                return;
            }
            if ($to !== null && $to < $from) {
                $v->errors()->add("tier_slabs.$i.txn_to", 'To must be greater than or equal to From.');

                return;
            }
            $rows[] = ['from' => $from, 'to' => $to, 'i' => $i];
        }

        usort($rows, fn ($a, $b) => $a['from'] <=> $b['from']);

        $openEndedSeen = false;
        foreach ($rows as $idx => $row) {
            if ($row['to'] === null) {
                if ($openEndedSeen) {
                    $v->errors()->add('tier_slabs', 'Only one open-ended tier (empty To) is allowed, and it must be last.');

                    return;
                }
                $openEndedSeen = true;
                if ($idx !== count($rows) - 1) {
                    $v->errors()->add('tier_slabs', 'Open-ended tier must be the last slab.');

                    return;
                }
            }
        }

        for ($i = 0; $i < count($rows) - 1; $i++) {
            $a = $rows[$i];
            $b = $rows[$i + 1];
            if ($a['to'] === null) {
                break;
            }
            if ($b['from'] <= $a['to']) {
                $v->errors()->add('tier_slabs', 'Transaction count ranges must not overlap.');

                return;
            }
        }
    }
}
