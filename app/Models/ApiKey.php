<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'key',
        'secret',
        'name',
        'scopes',
        'status',
        'mode',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the API key.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Generate a new API key pair.
     */
    public static function generate(int $merchantId, string $mode = 'test', ?string $name = null): self
    {
        return self::create([
            'merchant_id' => $merchantId,
            'key' => 'pk_' . $mode . '_' . Str::random(32),
            'secret' => 'sk_' . $mode . '_' . Str::random(32),
            'name' => $name,
            'mode' => $mode,
            'status' => 'active',
        ]);
    }

    /**
     * Check if API key is valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Mark API key as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }
}

