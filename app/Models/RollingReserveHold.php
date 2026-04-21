<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RollingReserveHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'transaction_id',
        'fee_rule_id',
        'ledger_entry_id',
        'hold_amount',
        'currency',
        'held_at',
        'release_due_at',
        'released_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'hold_amount' => 'decimal:4',
        'held_at' => 'datetime',
        'release_due_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BillingFeeRule::class, 'fee_rule_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(FeeLedgerEntry::class, 'ledger_entry_id');
    }
}

