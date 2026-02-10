<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispute_id',
        'merchant_id',
        'payment_id',
        'transaction_id',
        'order_id',
        'card_network',
        'reason',
        'status',
        'amount',
        'currency',
        'due_by',
        'evidence_submitted',
        'dispute_fee',
        'frozen_amount',
        'internal_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency' => 'string',
        'dispute_fee' => 'decimal:2',
        'frozen_amount' => 'decimal:2',
        'due_by' => 'datetime',
        'evidence_submitted' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispute) {
            if (empty($dispute->dispute_id)) {
                $dispute->dispute_id = 'dp_' . strtoupper(Str::random(14));
            }
        });

        static::created(function ($dispute) {
            // Create timeline entry
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event' => 'dispute_created',
                'notes' => 'Dispute raised by card network',
                'changed_by_type' => 'system',
                'created_at' => now(),
            ]);
        });

        static::updating(function ($dispute) {
            // Track status changes in timeline
            if ($dispute->isDirty('status')) {
                $oldStatus = $dispute->getOriginal('status');
                $newStatus = $dispute->status;
                
                DisputeTimeline::create([
                    'dispute_id' => $dispute->id,
                    'event' => 'status_changed',
                    'notes' => "Status changed from {$oldStatus} to {$newStatus}",
                    'changed_by_type' => auth()->check() ? (auth()->user()->isAdmin() ? 'admin' : 'merchant') : 'system',
                    'changed_by_id' => auth()->id(),
                    'metadata' => [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ],
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payment_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(DisputeTimeline::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if dispute is past due date
     */
    public function isPastDue(): bool
    {
        return $this->due_by && $this->due_by->isPast() && $this->status === 'action_required';
    }

    /**
     * Check if evidence can be uploaded
     */
    public function canUploadEvidence(): bool
    {
        return $this->status === 'action_required' && !$this->evidence_submitted;
    }

    /**
     * Check if dispute can be submitted
     */
    public function canSubmit(): bool
    {
        return $this->status === 'action_required' 
            && !$this->evidence_submitted 
            && $this->evidence()->count() > 0;
    }

    /**
     * Scope for action required disputes
     */
    public function scopeActionRequired($query)
    {
        return $query->where('status', 'action_required');
    }

    /**
     * Scope for due today
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_by', today());
    }

    /**
     * Scope for due tomorrow
     */
    public function scopeDueTomorrow($query)
    {
        return $query->whereDate('due_by', today()->addDay());
    }

    /**
     * Scope for insufficient evidence
     */
    public function scopeInsufficientEvidence($query)
    {
        return $query->where('status', 'insufficient_evidence');
    }

    /**
     * Scope for under review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope for closed disputes
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['won', 'lost', 'closed']);
    }
}
