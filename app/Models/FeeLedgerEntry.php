<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'partner_id',
        'reseller_id',
        'fee_definition_id',
        'fee_rule_id',
        'source_type',
        'source_id',
        'event_type',
        'fee_code',
        'fee_name',
        'bill_to',
        'entry_direction',
        'currency',
        'basis_amount',
        'percentage_rate',
        'fixed_amount',
        'amount',
        'referral_commission_amount',
        'status',
        'metadata',
    ];

    protected $casts = [
        'basis_amount' => 'decimal:4',
        'percentage_rate' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'referral_commission_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BillingFeeRule::class, 'fee_rule_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(BillingFeeDefinition::class, 'fee_definition_id');
    }
}

