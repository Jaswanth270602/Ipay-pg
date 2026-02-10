<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'event_type',
        'payload',
        'webhook_url',
        'delivered',
        'attempt_count',
        'max_attempts',
        'last_error',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered' => 'boolean',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the webhook event.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Mark webhook as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'delivered' => true,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Increment attempt count and schedule next retry.
     */
    public function incrementAttempt(string $error = null): void
    {
        $newAttemptCount = $this->attempt_count + 1;
        
        $this->update([
            'attempt_count' => $newAttemptCount,
            'last_error' => $error,
            'next_retry_at' => $this->calculateNextRetry($newAttemptCount),
        ]);
    }

    /**
     * Calculate next retry time based on attempt count.
     */
    protected function calculateNextRetry(int $attemptCount): ?\Carbon\Carbon
    {
        if ($attemptCount >= $this->max_attempts) {
            return null;
        }

        // Exponential backoff: 1min, 5min, 15min, 30min, 1hour
        $delays = [60, 300, 900, 1800, 3600];
        $delaySeconds = $delays[min($attemptCount - 1, count($delays) - 1)];

        return now()->addSeconds($delaySeconds);
    }

    /**
     * Check if webhook should be retried.
     */
    public function shouldRetry(): bool
    {
        return !$this->delivered 
            && $this->attempt_count < $this->max_attempts
            && $this->next_retry_at
            && $this->next_retry_at <= now();
    }

    /**
     * Get status attribute for compatibility with views.
     */
    public function getStatusAttribute(): string
    {
        if ($this->delivered) {
            return 'success';
        }
        if ($this->attempt_count >= $this->max_attempts) {
            return 'failed';
        }
        return 'pending';
    }
}

