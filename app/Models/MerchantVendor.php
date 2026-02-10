<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'vendor_code',
        'vendor_name',
        'vendor_email',
        'vendor_phone',
        'vendor_address',
        'vendor_pan_no',
        'vendor_login_id',
        'vendor_description_1',
        'vendor_description_2',
        'bank_account_number',
        'bank_account_ifsc',
        'bank_name',
        'bank_branch',
        'bank_account_holder_name',
        'account_type',
        'upi_id',
        'status',
        'note',
        'reference_id',
    ];

    /**
     * Parent merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}


