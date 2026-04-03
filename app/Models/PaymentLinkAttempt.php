<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLinkAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'mode',
        'status',
        'reason',
        'request_payload',
        'response_payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}

