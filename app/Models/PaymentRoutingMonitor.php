<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRoutingMonitor extends Model
{
    protected $fillable = [
        'merchant_id',
        'txn_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'payment_method',
        'final_acquirer_account_id',
        'final_acquirer_name',
        'status',
        'flow_trace',
        'error_message',
        'test_mode',
        'source',
    ];

    protected $casts = [
        'flow_trace' => 'array',
        'test_mode' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function finalAcquirerAccount(): BelongsTo
    {
        return $this->belongsTo(AcquirerAccount::class, 'final_acquirer_account_id');
    }
}
