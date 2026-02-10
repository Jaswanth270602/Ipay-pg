<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerTDR extends Model
{
    use HasFactory;

    protected $table = 'partner_tdrs';

    protected $fillable = [
        'partner_id',
        'partner_name',
        'merchant_id',
        'merchant_name',
        'category',
        'payment_mode',
        'payment_channel',
        'bank_code',
        'bank_description',
        'tdr_fixed_fee',
        'tdr_percentage',
        'tdr_min_amount',
        'tdr_max_amount',
        'min_transaction_amount',
        'max_transaction_amount',
        'min_transaction_charge',
        'max_transaction_charge',
        'overall_profit_share_percentage',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'tdr_fixed_fee' => 'decimal:2',
        'tdr_percentage' => 'decimal:4',
        'tdr_min_amount' => 'decimal:2',
        'tdr_max_amount' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'min_transaction_charge' => 'decimal:2',
        'max_transaction_charge' => 'decimal:2',
        'overall_profit_share_percentage' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the partner that owns the TDR.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Get the merchant that owns the TDR.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
