<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'payment_link_id',
        'order_id',
        'gateway_order_id',
        'amount',
        'currency',
        'payment_method',
        'payment_details',
        'customer_details',
        'status',
        'description',
        'metadata',
        'return_url',
        'cancel_url',
        'idempotency_key',
        'test_mode',
        'expires_at',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'payment_details' => 'array',
        'metadata' => 'array',
        'test_mode' => 'boolean',
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the order.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the payment link that created this order.
     */
    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    /**
     * Get transactions for this order.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Generate a unique order ID.
     */
    public static function generateOrderId(): string
    {
        return 'ORD_' . strtoupper(Str::random(16));
    }

    /**
     * Check if order is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get successful transaction for this order.
     */
    public function successfulTransaction()
    {
        return $this->transactions()
            ->where('status', 'success')
            ->first();
    }
}

