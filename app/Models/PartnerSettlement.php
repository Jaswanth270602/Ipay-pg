<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerSettlement extends Model
{
    use HasFactory;

    protected $table = 'partner_settlements';

    protected $fillable = [
        'settlement_id',
        'partner_id',
        'partner_name',
        'organization_name',
        'settlement_amount',
        'net_settlement_amount',
        'tds_percentage',
        'tds_amount',
        'gst_amount',
        'settlement_status',
        'settlement_date',
        'settlement_start_time',
        'settlement_end_time',
        'bank_reference_id',
        'account_holder_name',
        'account_number',
        'bank_name',
        'bank_ifsc',
        'transfer_method',
        'transfer_status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'settlement_amount' => 'decimal:2',
        'net_settlement_amount' => 'decimal:2',
        'tds_percentage' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'settlement_date' => 'date',
        'settlement_start_time' => 'datetime',
        'settlement_end_time' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the partner that owns the settlement.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Get the settlement details for this settlement.
     */
    public function details(): HasMany
    {
        return $this->hasMany(PartnerSettlementDetail::class, 'partner_settlement_id');
    }

    /**
     * Generate a unique settlement ID.
     */
    public static function generateSettlementId(): string
    {
        do {
            $id = 'PS' . date('Ymd') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('settlement_id', $id)->exists());

        return $id;
    }
}
