<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'acquirer_account_id',
        'event_type',
        'provider_event_type',
        'raw_payload',
        'normalized_status',
        'provider_status',
        'payment_id',
        'order_id',
        'refund_id',
        'settlement_id',
        'dispute_id',
        'signature',
        'signature_verified',
        'ip_address',
        'error_message',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'signature_verified' => 'boolean',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the acquirer account that received this callback.
     */
    public function acquirerAccount(): BelongsTo
    {
        return $this->belongsTo(AcquirerAccount::class);
    }

    /**
     * Mark this response as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope to get unprocessed responses.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope to get responses by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get responses by event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
