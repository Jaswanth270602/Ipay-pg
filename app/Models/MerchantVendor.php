<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVendor extends Model
{
    protected $table = 'merchant_vendors';

    protected $fillable = [
        'merchant_id',
        'vendor_code',
        'vendor_name',
        'vendor_email',
        'vendor_phone',
        'status',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
