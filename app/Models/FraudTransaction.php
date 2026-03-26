<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FraudTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'user_id',
        'risk_score',
        'decision',
        'reasons',
        'context',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'reasons' => 'array',
        'context' => 'array',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(FraudEvent::class);
    }
}
