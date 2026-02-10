<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PgRefundApproval extends Model
{
    use HasFactory;

    protected $table = 'pg_refund_approvals';

    protected $fillable = [
        'created_by',
        'merchant_id',
        'merchant_name',
        'model_id',
        'model_name',
        'operation',
        'previous_changes',
        'changes',
        'is_approved',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'previous_changes' => 'array',
        'changes' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created the approval request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved/rejected the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
