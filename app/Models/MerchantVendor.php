<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Rates\MerchantVendorBaseRate;

class MerchantVendor extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'merchant_id',
        'vendor_code',
        'vendor_name',
        'vendor_email',
        'vendor_phone',
        'vendor_address',
        'vendor_pan_no',
        'vendor_login_id',
        'password',
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
        'kyc_verified',
        'kyc_verified_at',
        'note',
        'reference_id',
    ];

    protected $casts = [
        'kyc_verified' => 'boolean',
        'kyc_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Parent merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function baseRates(): HasMany
    {
        return $this->hasMany(MerchantVendorBaseRate::class, 'vendor_id');
    }
}


