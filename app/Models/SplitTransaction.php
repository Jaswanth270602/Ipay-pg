<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'split_id',
        'order_id',
        'total_amount',
        'primary_amount',
        'secondary_amount',
        'primary_merchant_id',
        'secondary_merchant_id',
        'primary_percentage',
        'secondary_percentage',
        'status',
        'notes',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'split_type',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'primary_amount' => 'decimal:2',
        'secondary_amount' => 'decimal:2',
        'primary_percentage' => 'decimal:2',
        'secondary_percentage' => 'decimal:2',
    ];

    /**
     * Get the transaction that owns the split.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the merchant that owns the split.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Get the primary merchant.
     */
    public function primaryMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'primary_merchant_id');
    }

    /**
     * Get the secondary merchant.
     */
    public function secondaryMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'secondary_merchant_id');
    }
}

