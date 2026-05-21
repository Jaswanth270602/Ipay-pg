<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Merchant IDs this reseller may view (merchants.reseller_id + reseller_merchant pivot).
     */
    public function assignedMerchantIds(): Collection
    {
        $viaColumn = $this->merchants()->pluck('id');

        if (! Schema::hasTable('reseller_merchant')) {
            return $viaColumn->unique()->values();
        }

        return $viaColumn
            ->merge($this->merchantsPivot()->pluck('merchants.id'))
            ->unique()
            ->values();
    }

    /**
     * Query assigned merchants ordered by name.
     */
    public function assignedMerchants()
    {
        return Merchant::query()
            ->whereIn('id', $this->assignedMerchantIds())
            ->orderBy('name');
    }
}

