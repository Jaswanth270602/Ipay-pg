<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSettlementDetail extends Model
{
    use HasFactory;

    protected $table = 'partner_settlement_details';

    protected $fillable = [
        'partner_settlement_id',
        'partner_id',
        'partner_name',
        'merchant_id',
        'merchant_name',
        'merchant_category',
        'transaction_id',
        'transaction_txn_id',
        'settlement_record_id',
        'transaction_amount',
        'partner_tdr_percentage',
        'partner_tdr_fixed_fee',
        'partner_tdr_amount',
        'merchant_tdr_percentage',
        'merchant_tdr_fixed_fee',
        'merchant_tdr_amount',
        'tdr_amount',
        'partner_revenue',
        'bank_code',
        'payment_mode',
        'payment_channel',
        'payment_datetime',
        'organization_name',
        'notes',
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'partner_tdr_percentage' => 'decimal:4',
        'partner_tdr_fixed_fee' => 'decimal:2',
        'partner_tdr_amount' => 'decimal:2',
        'merchant_tdr_percentage' => 'decimal:4',
        'merchant_tdr_fixed_fee' => 'decimal:2',
        'merchant_tdr_amount' => 'decimal:2',
        'tdr_amount' => 'decimal:2',
        'partner_revenue' => 'decimal:2',
        'payment_datetime' => 'datetime',
    ];

    /**
     * Get the partner settlement that owns this detail.
     */
    public function partnerSettlement(): BelongsTo
    {
        return $this->belongsTo(PartnerSettlement::class, 'partner_settlement_id');
    }

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
}
