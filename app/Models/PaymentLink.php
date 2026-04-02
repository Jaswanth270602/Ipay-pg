<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'link_token',
        'title',
        'description',
        'amount',
        'allow_partial_payment',
        'amount_paid',
        'currency',
        'customer_details',
        'status',
        'usage_count',
        'max_usage',
        'test_mode',
        'metadata',
        'payment_methods',
        'success_url',
        'cancel_url',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'metadata' => 'array',
        'payment_methods' => 'array',
        'test_mode' => 'boolean',
        'allow_partial_payment' => 'boolean',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the payment link.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Generate a unique link token.
     */
    public static function generateLinkToken(): string
    {
        return Str::random(32);
    }

    /**
     * Get the full payment URL.
     */
    public function getPaymentUrl(): string
    {
        return url('/pay/' . $this->link_token);
    }

    /**
     * Check if link is active and valid.
     * Note: This method does NOT update the database - it only checks the current state.
     */
    public function isActive(): bool
    {
        // Check status first - allow 'active' status even if partially paid
        if ($this->status !== 'active') {
            return false;
        }

        // If fully paid, link is no longer active
        if ($this->isFullyPaid()) {
            return false;
        }

        // Check expiry - only if expires_at is set
        if ($this->expires_at) {
            // Use Carbon's isPast() for accurate comparison
            if ($this->expires_at->isPast()) {
                return false;
            }
        }

        // Check usage limit - only if max_usage is set
        if ($this->max_usage && $this->max_usage > 0) {
            if ($this->usage_count >= $this->max_usage) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark link as expired if it has passed expiry or usage limit.
     * This should be called separately when you want to update the database.
     */
    public function checkAndMarkExpired(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $shouldExpire = false;

        // Check expiry
        if ($this->expires_at && $this->expires_at->isPast()) {
            $shouldExpire = true;
        }

        // Check usage limit
        if ($this->max_usage && $this->max_usage > 0 && $this->usage_count >= $this->max_usage) {
            $shouldExpire = true;
        }

        if ($shouldExpire) {
            $this->update(['status' => 'expired']);
            return true;
        }

        return false;
    }

    /**
     * Get the remaining balance to be paid.
     */
    public function getRemainingBalance(): float
    {
        return max(0, $this->amount - ($this->amount_paid ?? 0));
    }

    /**
     * Check if the payment link is fully paid.
     */
    public function isFullyPaid(): bool
    {
        $amountPaid = $this->amount_paid ?? 0;
        return $amountPaid >= $this->amount;
    }

    /**
     * Check if the payment link is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        $amountPaid = $this->amount_paid ?? 0;
        return $amountPaid > 0 && $amountPaid < $this->amount;
    }

    /**
     * Add a partial payment amount.
     * Returns true if the link is now fully paid.
     */
    public function addPartialPayment(float $amount): bool
    {
        $newAmountPaid = ($this->amount_paid ?? 0) + $amount;
        
        // Ensure we don't exceed the total amount
        $newAmountPaid = min($newAmountPaid, $this->amount);
        
        $this->amount_paid = $newAmountPaid;
        
        // If fully paid, mark as paid
        if ($this->isFullyPaid()) {
            $this->status = 'paid';
            $this->paid_at = now();
        }
        
        $this->usage_count = $this->usage_count + 1;
        $this->save();
        
        return $this->isFullyPaid();
    }

    /**
     * Mark link as paid (for full payment or legacy support).
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'amount_paid' => $this->amount,
            'paid_at' => now(),
            'usage_count' => $this->usage_count + 1,
        ]);
    }
}

