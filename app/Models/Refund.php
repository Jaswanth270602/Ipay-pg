<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'refund_id',
        'amount',
        'currency',
        'status',
        'reason',
        'notes',
        'initiated_by',
        'gateway_response',
        'gateway_refund_id',
        'is_partial',
        'processed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'is_partial' => 'boolean',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the transaction that owns the refund.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the merchant that owns the refund.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the user who initiated the refund.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Generate a unique refund ID.
     */
    public static function generateRefundId(): string
    {
        return 'RFD_' . strtoupper(Str::random(18));
    }

    /**
     * Mark refund as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }
}

