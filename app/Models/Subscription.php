<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'plan_id',
        'status', // active, past_due, canceled, expired
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'test_mode',
        'metadata',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'test_mode' => 'boolean',
        'metadata' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}


