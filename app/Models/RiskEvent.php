<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'merchant_id',
        'transaction_id',
        'event_type', // rule_triggered, manual_review, auto_blocked
        'severity', // low, medium, high, critical
        'details',
        'ip_address',
        'user_agent',
        'resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'details' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(RiskRule::class, 'rule_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

