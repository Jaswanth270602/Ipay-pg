<?php

namespace App\Models\Rates;

use App\Models\BaseRate;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantRateSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'base_rate_id',
        'payment_method',
        'service_type',
        'transaction_type',
        'percentage_fee',
        'flat_fee',
        'gst_percentage',
        'effective_fee_percentage',
    ];

    protected $casts = [
        'percentage_fee' => 'decimal:4',
        'flat_fee' => 'decimal:4',
        'gst_percentage' => 'decimal:4',
        'effective_fee_percentage' => 'decimal:4',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function baseRate(): BelongsTo
    {
        return $this->belongsTo(BaseRate::class);
    }
}

