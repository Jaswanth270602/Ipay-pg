<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_unique_id',
        'name',
        'email',
        'phone',
        'company_name',
        'status',
        'commission_type',
        'commission_value',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'commission_value' => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Merchants assigned via merchants.reseller_id (single reseller per merchant).
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class, 'reseller_id');
    }

    /**
     * Legacy pivot relation (same merchants usually mirrored in reseller_merchant).
     */
    public function merchantsPivot(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'reseller_merchant')
            ->withPivot(['status', 'assigned_by'])
            ->withTimestamps();
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ResellerCommission::class);
    }

    public function merchantSplits(): HasMany
    {
        return $this->hasMany(MerchantResellerSplit::class);
    }
}

