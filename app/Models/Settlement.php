<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'settlement_id',
        'amount',
        'fee_amount',
        'refund_amount',
        'net_amount',
        'currency',
        'transaction_count',
        'refund_count',
        'period_start',
        'period_end',
        'status',
        'bank_details',
        'utr_number',
        'notes',
        'processed_at',
        // Extended fields
        'partner_id',
        'partner_name',
        'bank_reference',
        'account_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'bank_branch',
        'settlement_description',
        'payment_start_date',
        'payment_end_date',
        'settlement_date',
        'payout_amount',
        'settlement_status',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'processed_at' => 'datetime',
        'payment_start_date' => 'date',
        'payment_end_date' => 'date',
        'settlement_date' => 'date',
    ];

    /**
     * Get the merchant that owns the settlement.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get payouts for this settlement.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get transactions for this settlement.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Generate a unique settlement ID.
     */
    public static function generateSettlementId(): string
    {
        return 'STL_' . strtoupper(Str::random(16));
    }

    /**
     * Mark settlement as completed.
     */
    public function markAsCompleted(string $utrNumber = null): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'utr_number' => $utrNumber ?? $this->utr_number,
        ]);
    }
}

