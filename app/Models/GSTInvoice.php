<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GSTInvoice extends Model
{
    use HasFactory;

    protected $table = 'gst_invoices';

    protected $fillable = [
        'invoice_number',
        'month',
        'year',
        'merchant_id',
        'gst_provided_by',
        'gst_payer_name',
        'payer_gstin',
        'payer_gstin_state',
        'non_taxable_tdr',
        'taxable_tdr',
        'sgst',
        'cgst',
        'igst',
        'utgst',
        'invoice_value',
        'invoice_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'non_taxable_tdr' => 'decimal:2',
        'taxable_tdr' => 'decimal:2',
        'sgst' => 'decimal:2',
        'cgst' => 'decimal:2',
        'igst' => 'decimal:2',
        'utgst' => 'decimal:2',
        'invoice_value' => 'decimal:2',
        'invoice_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the merchant.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber($month = null, $year = null): string
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        
        do {
            $number = 'GST' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * Get month name.
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[$this->month] ?? '';
    }
}
