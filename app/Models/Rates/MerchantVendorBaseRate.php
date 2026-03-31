<?php

namespace App\Models\Rates;

use App\Models\Merchant;
use App\Models\MerchantVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVendorBaseRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'vendor_id',
        'payment_method',
        'service_type',
        'currency',
        'percentage_share',
        'flat_share',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'percentage_share' => 'decimal:4',
        'flat_share' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MerchantVendor::class, 'vendor_id');
    }
}

