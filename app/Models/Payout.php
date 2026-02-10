<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'settlement_id',
        'payout_id',
        'amount',
        'currency',
        'bank_account_json',
        'status',
        'utr_number',
        'notes',
        'gateway_response',
        'processed_at',
    ];

    protected $casts = [
        'bank_account_json' => 'array',
        'gateway_response' => 'array',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the payout.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the settlement associated with this payout.
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    /**
     * Generate a unique payout ID.
     */
    public static function generatePayoutId(): string
    {
        return 'PYT_' . strtoupper(Str::random(16));
    }

    /**
     * Mark payout as completed.
     */
    public function markAsCompleted(string $utrNumber): void
    {
        $this->update([
            'status' => 'completed',
            'utr_number' => $utrNumber,
            'processed_at' => now(),
        ]);
    }
}

