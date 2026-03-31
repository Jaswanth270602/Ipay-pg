<?php

namespace App\Models\Rates;

use App\Models\Merchant;
use App\Models\MerchantVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVendorRateSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'vendor_id',
        'merchant_vendor_base_rate_id',
        'payment_method',
        'service_type',
        'percentage_share',
        'flat_share',
        'effective_split_percentage',
    ];

    protected $casts = [
        'percentage_share' => 'decimal:4',
        'flat_share' => 'decimal:4',
        'effective_split_percentage' => 'decimal:4',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MerchantVendor::class, 'vendor_id');
    }

    public function baseRate(): BelongsTo
    {
        return $this->belongsTo(MerchantVendorBaseRate::class, 'merchant_vendor_base_rate_id');
    }
}

