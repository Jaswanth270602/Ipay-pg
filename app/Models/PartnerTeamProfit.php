<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerTeamProfit extends Model
{
    use HasFactory;

    protected $table = 'partner_team_profit';

    protected $fillable = [
        'partner_id',
        'partner_name',
        'merchant_id',
        'merchant_name',
        'transaction_id',
        'transaction_txn_id',
        'transaction_sequence_id',
        'order_id',
        'order_order_id',
        'payment_datetime',
        'payment_mode',
        'payment_channel',
        'merchant_tdr_percentage',
        'merchant_tdr_fixed_fee',
        'merchant_tdr_amount',
        'partner_base_rate_percentage',
        'partner_base_rate_fixed_fee',
        'partner_tdr_amount',
        'profit',
        'transaction_amount',
    ];

    protected $casts = [
        'merchant_tdr_percentage' => 'decimal:4',
        'merchant_tdr_fixed_fee' => 'decimal:2',
        'merchant_tdr_amount' => 'decimal:2',
        'partner_base_rate_percentage' => 'decimal:4',
        'partner_base_rate_fixed_fee' => 'decimal:2',
        'partner_tdr_amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'transaction_amount' => 'decimal:2',
        'payment_datetime' => 'datetime',
    ];

    /**
     * Get the partner.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Get the merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Get the transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
