<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'name',
        'description',
        'category',
        'enabled',
        'payload_structure',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'payload_structure' => 'array',
    ];

    /**
     * Get all enabled event types.
     */
    public static function getEnabled(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('enabled', true)->orderBy('sort_order')->get();
    }

    /**
     * Check if an event type is enabled.
     */
    public static function isEnabled(string $eventKey): bool
    {
        return static::where('event_key', $eventKey)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Get event type by key.
     */
    public static function getByKey(string $eventKey): ?self
    {
        return static::where('event_key', $eventKey)->first();
    }
}

