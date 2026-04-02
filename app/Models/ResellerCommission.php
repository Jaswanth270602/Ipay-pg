<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ResellerCommission extends Model
{
    protected $fillable = [
        'reseller_id',
        'merchant_id',
        'transaction_id',
        'commission_amount',
        'reversed_amount',
        'status',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'reversed_amount' => 'decimal:2',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function netAmount(): string
    {
        $net = (float) $this->commission_amount - (float) $this->reversed_amount;

        return number_format(max(0, $net), 2, '.', '');
    }

    /**
     * @return array{total_earnings: float, pending_earnings: float, paid_earnings: float}
     */
    public static function netTotalsForReseller(?int $resellerId): array
    {
        if (! $resellerId) {
            return [
                'total_earnings' => 0.0,
                'pending_earnings' => 0.0,
                'paid_earnings' => 0.0,
            ];
        }

        $net = 'SUM(GREATEST(0, commission_amount - reversed_amount))';

        $total = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->selectRaw("{$net} as n")
            ->value('n') ?? 0);

        $pending = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->whereIn('status', ['pending', 'partially_reversed'])
            ->selectRaw("{$net} as n")
            ->value('n') ?? 0);

        $paid = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->where('status', 'paid')
            ->selectRaw("{$net} as n")
            ->value('n') ?? 0);

        return [
            'total_earnings' => round($total, 2),
            'pending_earnings' => round($pending, 2),
            'paid_earnings' => round($paid, 2),
        ];
    }
}
