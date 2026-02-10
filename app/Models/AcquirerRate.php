<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquirerRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquirer_account_id',
        'payment_mode',
        'bank_code',
        'bank_description',
        'acquirer_name',
        'account_id',
        'account_description',
        'sector',
        'settlement_time_frame',
        'settlement_time_of_day',
        'fixed_fee_mdr',
        'percentage_mdr',
        'service_tax_rates',
        'min_amount',
        'max_amount',
        'min_transaction_charge',
        'max_transaction_charge',
        'is_enabled',
        'part_paid_id',
    ];

    protected $casts = [
        'fixed_fee_mdr' => 'decimal:4',
        'percentage_mdr' => 'decimal:4',
        'service_tax_rates' => 'decimal:4',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'min_transaction_charge' => 'decimal:2',
        'max_transaction_charge' => 'decimal:2',
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the acquirer account that owns this rate.
     */
    public function acquirerAccount(): BelongsTo
    {
        return $this->belongsTo(AcquirerAccount::class, 'acquirer_account_id');
    }
}
