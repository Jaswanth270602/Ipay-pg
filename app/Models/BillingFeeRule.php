<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingFeeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_definition_id',
        'merchant_id',
        'partner_id',
        'event_type',
        'applies_to_status',
        'payment_method',
        'currency',
        'pricing_model',
        'percentage_rate',
        'fixed_amount',
        'minimum_amount',
        'maximum_amount',
        'hold_days',
        'rolling_reserve_cap',
        'bill_to',
        'referral_commission_percentage',
        'referral_commission_fixed',
        'effective_from',
        'effective_to',
        'priority',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'percentage_rate' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
        'minimum_amount' => 'decimal:4',
        'maximum_amount' => 'decimal:4',
        'rolling_reserve_cap' => 'decimal:4',
        'referral_commission_percentage' => 'decimal:4',
        'referral_commission_fixed' => 'decimal:4',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(BillingFeeDefinition::class, 'fee_definition_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FeeLedgerEntry::class, 'fee_rule_id');
    }
}

