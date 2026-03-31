<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Encrypted;
use App\Models\Rates\MerchantRateSnapshot;
use App\Models\Rates\MerchantVendorRateSnapshot;
use Illuminate\Support\Str;
use App\Traits\SanitizesCardData;

class Transaction extends Model
{
    use HasFactory, SanitizesCardData;

    protected $fillable = [
        'order_id',
        'merchant_id',
        'vendor_id',
        'admin_rate_snapshot_id',
        'admin_fee_percentage_snapshot',
        'merchant_vendor_rate_snapshot_id',
        'merchant_vendor_split_percentage_snapshot',
        'transaction_id',
        'txn_id',
        'payment_method',
        'amount',
        'fee_amount',
        'gst_amount',
        'other_fees',
        'net_amount',
        'currency',
        'status',
        'failure_reason',
        'settlement_id',
        'settlement_status',
        'settled_at',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'payment_details',
        'gateway_txn_id',
        'bank_id',
        'test_mode',
        'customer_email',
        'customer_phone',
        'idempotency_key',
        'ip_address',
        'user_agent',
        'processed_at',
        'authorized_at',
        'captured_at',
    ];

    protected $casts = [
        // PCI-DSS: Encrypt sensitive payment data at rest
        // Note: Using 'array' cast instead of Encrypted for now to avoid breaking existing data
        // TODO: Migrate existing data to encrypted format, then enable encryption
        'gateway_response' => 'array',
        'payment_details' => 'array',
        'test_mode' => 'boolean',
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'admin_fee_percentage_snapshot' => 'decimal:4',
        'merchant_vendor_split_percentage_snapshot' => 'decimal:4',
        'processed_at' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /**
     * Get payment details with automatic sanitization for API responses.
     * PCI-DSS: Ensures no card data is exposed in responses.
     *
     * @return array|null
     */
    public function getSanitizedPaymentDetails(): ?array
    {
        try {
            $details = $this->payment_details;
            if (!$details) {
                return null;
            }
            // Handle both array and JSON string
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            if (!is_array($details)) {
                return null;
            }
            return $this->sanitizePaymentDetails($details);
        } catch (\Exception $e) {
            // If sanitization fails, return empty array to prevent errors
            \Log::warning('Failed to get sanitized payment details', [
                'transaction_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get gateway response with automatic sanitization for API responses.
     * PCI-DSS: Ensures no card data is exposed in responses.
     *
     * @return array|null
     */
    public function getSanitizedGatewayResponse(): ?array
    {
        try {
            $response = $this->gateway_response;
            if (!$response) {
                return null;
            }
            // Handle both array and JSON string
            if (is_string($response)) {
                $response = json_decode($response, true);
            }
            if (!is_array($response)) {
                return null;
            }
            return $this->sanitizePaymentDetails($response);
        } catch (\Exception $e) {
            // If sanitization fails, return empty array to prevent errors
            \Log::warning('Failed to get sanitized gateway response', [
                'transaction_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get the order that owns the transaction.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the merchant that owns the transaction.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the bank for this transaction.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Settlement batch this transaction was included in (after daily settlement run).
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function adminRateSnapshot(): BelongsTo
    {
        return $this->belongsTo(MerchantRateSnapshot::class, 'admin_rate_snapshot_id');
    }

    public function merchantVendorRateSnapshot(): BelongsTo
    {
        return $this->belongsTo(MerchantVendorRateSnapshot::class, 'merchant_vendor_rate_snapshot_id');
    }

    /**
     * Get refunds for this transaction.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get split transactions for this transaction.
     */
    public function splitTransactions(): HasMany
    {
        return $this->hasMany(SplitTransaction::class);
    }

    /**
     * Generate a unique transaction ID.
     */
    public static function generateTxnId(): string
    {
        return 'TXN_' . strtoupper(Str::random(20));
    }

    /**
     * Calculate fee amount based on merchant settings.
     */
    public function calculateFee(): float
    {
        if ($this->merchant) {
            return $this->merchant->calculateFee($this->amount);
        }

        $percentageFee = ($this->amount * config('ipay.fee.percentage', 2.5)) / 100;
        return round($percentageFee + config('ipay.fee.flat', 0.30), 2);
    }

    /**
     * Calculate net amount after fee.
     */
    public function calculateNetAmount(): float
    {
        return round($this->amount - $this->fee_amount, 2);
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Get total refunded amount.
     */
    public function totalRefunded(): float
    {
        return (float) $this->refunds()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Amount tied up in refunds not yet completed (approval queue or bank processing).
     */
    public function reservedRefundAmount(): float
    {
        return (float) $this->refunds()
            ->whereIn('status', [
                'pending',
                'pending_approval',
                'pending_processing',
                'processing',
            ])
            ->sum('amount');
    }

    /**
     * Get refundable amount.
     */
    public function refundableAmount(): float
    {
        return max(0, $this->amount - $this->totalRefunded() - $this->reservedRefundAmount());
    }
}

