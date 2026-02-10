<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // velocity, amount_limit, geo_block, merchant_block, ip_block
        'rule_config', // JSON config
        'action', // block, alert, review
        'status', // active, inactive
        'priority',
    ];

    protected $casts = [
        'rule_config' => 'array',
        'priority' => 'integer',
    ];

    public function events()
    {
        return $this->hasMany(RiskEvent::class, 'rule_id');
    }
}

