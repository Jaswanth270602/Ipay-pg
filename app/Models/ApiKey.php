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
        $row = self::create([
            'merchant_id' => $merchantId,
            'key' => 'pk_' . $mode . '_' . Str::random(32),
            'secret' => 'sk_' . $mode . '_' . Str::random(32),
            'name' => $name,
            'mode' => $mode,
            'status' => 'active',
        ]);

        self::syncMerchantKeyColumns($merchantId, $mode, $row->key, $row->secret);

        return $row;
    }

    /**
     * Keep merchants.test_* / live_* columns in sync for ApiKeyAuthMiddleware.
     */
    public static function syncMerchantKeyColumns(int $merchantId, string $mode, string $publicKey, string $secretKey): void
    {
        $merchant = Merchant::query()->find($merchantId);
        if (!$merchant) {
            return;
        }

        if ($mode === 'test') {
            $merchant->forceFill([
                'test_public_key' => $publicKey,
                'test_secret_key' => $secretKey,
            ])->saveQuietly();
        } else {
            $merchant->forceFill([
                'live_public_key' => $publicKey,
                'live_secret_key' => $secretKey,
            ])->saveQuietly();
        }
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
        $this->refreshMerchantKeyColumnsIfNeeded();
    }

    protected function refreshMerchantKeyColumnsIfNeeded(): void
    {
        $merchant = $this->merchant;
        if (!$merchant) {
            return;
        }

        $latest = self::query()
            ->where('merchant_id', $this->merchant_id)
            ->where('mode', $this->mode)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if ($latest) {
            self::syncMerchantKeyColumns((int) $this->merchant_id, (string) $this->mode, $latest->key, $latest->secret);

            return;
        }

        if ($this->mode === 'test') {
            $merchant->forceFill(['test_public_key' => null, 'test_secret_key' => null])->saveQuietly();
        } else {
            $merchant->forceFill(['live_public_key' => null, 'live_secret_key' => null])->saveQuietly();
        }
    }
}

