<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'transaction_id',
        'alert_type', // suspicious_pattern, chargeback_risk, velocity_anomaly, amount_anomaly
        'severity', // low, medium, high, critical
        'status', // open, investigating, resolved, false_positive
        'description',
        'risk_score',
        'assigned_to',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

