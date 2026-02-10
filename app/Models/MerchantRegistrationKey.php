<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantRegistrationKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'key_description',
        'registration_key',
        'status',
        'ip_address',
        'copy_merchant_params',
        'copy_velocity_checks',
        'copy_routing_randomize',
        'copy_account_whitelisting',
    ];

    protected $casts = [
        'copy_merchant_params' => 'boolean',
        'copy_velocity_checks' => 'boolean',
        'copy_routing_randomize' => 'boolean',
        'copy_account_whitelisting' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}


