<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sandbox_endpoint',
        'production_endpoint',
        'credentials',
        'is_active',
        'supported_methods',
    ];

    protected $casts = [
        'credentials' => 'array',
        'supported_methods' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get transactions for this bank.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get base rates for this bank.
     */
    public function baseRates(): HasMany
    {
        return $this->hasMany(BaseRate::class, 'entity_id')
            ->where('rate_type', BaseRate::RATE_TYPE_BANK)
            ->where('entity_type', 'bank');
    }
}

