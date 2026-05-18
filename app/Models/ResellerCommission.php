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
    public static function netTotalsForReseller(?int $resellerId, ?bool $testMode = null): array
    {
        if (! $resellerId) {
            return [
                'total_earnings' => 0.0,
                'pending_earnings' => 0.0,
                'paid_earnings' => 0.0,
            ];
        }

        $testMode ??= \App\Support\PaymentViewMode::isTestMode();
        $net = 'SUM(GREATEST(0, reseller_commissions.commission_amount - reseller_commissions.reversed_amount))';

        $base = DB::table('reseller_commissions')
            ->join('transactions', 'reseller_commissions.transaction_id', '=', 'transactions.id')
            ->where('reseller_commissions.reseller_id', $resellerId)
            ->where('transactions.test_mode', $testMode);

        $total = (float) ((clone $base)->selectRaw("{$net} as n")->value('n') ?? 0);

        $pending = (float) ((clone $base)
            ->whereIn('reseller_commissions.status', ['pending', 'partially_reversed'])
            ->selectRaw("{$net} as n")
            ->value('n') ?? 0);

        $paid = (float) ((clone $base)
            ->where('reseller_commissions.status', 'paid')
            ->selectRaw("{$net} as n")
            ->value('n') ?? 0);

        return [
            'total_earnings' => round($total, 2),
            'pending_earnings' => round($pending, 2),
            'paid_earnings' => round($paid, 2),
        ];
    }
}
