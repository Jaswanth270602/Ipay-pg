<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantResellerSplit extends Model
{
    protected $fillable = [
        'base_rate_id',
        'merchant_id',
        'reseller_id',
        'admin_share_pct',
        'reseller_share_pct',
        'merchant_share_pct',
        'is_active',
    ];

    protected $casts = [
        'admin_share_pct' => 'decimal:4',
        'reseller_share_pct' => 'decimal:4',
        'merchant_share_pct' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function baseRate(): BelongsTo
    {
        return $this->belongsTo(BaseRate::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
