<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DisputeTimeline extends Model
{
    use HasFactory;

    protected $table = 'dispute_timeline';

    public $timestamps = false;

    protected $fillable = [
        'dispute_id',
        'event',
        'notes',
        'changed_by_type',
        'changed_by_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Get the entity that made the change
     */
    public function changedBy(): MorphTo
    {
        return $this->morphTo('changed_by');
    }
}

