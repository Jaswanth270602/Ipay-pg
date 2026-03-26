<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraud_transaction_id',
        'rule_name',
        'triggered',
        'score',
        'metadata',
    ];

    protected $casts = [
        'triggered' => 'boolean',
        'score' => 'integer',
        'metadata' => 'array',
    ];

    public function fraudTransaction(): BelongsTo
    {
        return $this->belongsTo(FraudTransaction::class);
    }
}
