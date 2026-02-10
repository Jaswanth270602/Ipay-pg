<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcquirerAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'acquirer_name',
        'team',
        'description',
        'whitelist_url',
        'mode',
        'sector',
        'hdfc_me_code',
        'settlement_account_name',
        'refund_allowed',
        'settlements_to_be_created',
        'mask_pii',
        'email_ids',
        'secret_key',
        'salt',
        'additional_key_1',
        'additional_key_2',
        'additional_key_3',
        'additional_key_data',
        'live_request_url',
        'live_query_url',
        'live_refund_url',
        'test_request_url',
        'test_query_url',
        'test_refund_url',
        'nodal_account',
        'is_active',
    ];

    protected $casts = [
        'refund_allowed' => 'boolean',
        'settlements_to_be_created' => 'boolean',
        'mask_pii' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the merchants associated with this acquirer account.
     */
    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'acquirer_account_merchant')
                    ->withTimestamps();
    }

    /**
     * Get comma-separated merchant names.
     */
    public function getMerchantsListAttribute(): string
    {
        return $this->merchants->pluck('name')->implode(', ');
    }

    /**
     * Get the rates for this acquirer account.
     */
    public function rates(): HasMany
    {
        return $this->hasMany(AcquirerRate::class, 'acquirer_account_id');
    }
}
