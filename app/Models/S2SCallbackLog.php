<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S2SCallbackLog extends Model
{
    use HasFactory;

    protected $table = 's2s_callback_logs';

    protected $fillable = [
        'merchant_id',
        'merchant_name',
        'order_id',
        'tran_id',
        'transaction_id',
        'callback_url',
        'payment_datetime',
        'http_status_code',
        'initiated_by',
        'callback_datetime',
        'request_log',
        'response_log',
    ];

    protected $casts = [
        'payment_datetime' => 'datetime',
        'callback_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
