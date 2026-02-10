<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_unique_id',
        'name',
        'email',
        'company_name',
        'status',
        'default_currency',
        'webhook_url',
        'webhook_secret',
        'test_mode',
        'fee_percentage',
        'fee_flat',
        'settlement_cycle_domestic',
        'settlement_cycle_international',
        'business_details',
        'settings',
        // KYC Fields
        'kyc_status',
        'kyc_document_type',
        'kyc_document_number',
        'kyc_document_file',
        // Business Details
        'business_type',
        'tax_id',
        'business_registration_number',
        'business_address',
        'business_city',
        'business_state',
        'business_country',
        'business_postal_code',
        'business_phone',
        'business_website',
        // Bank Account Details
        'bank_account_holder_name',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_name',
        'bank_branch',
        // Card Details (optional for onboarding)
        'card_holder_name',
        'card_number_encrypted',
        'card_expiry_month',
        'card_expiry_year',
        'card_cvv_encrypted',
        // Onboarding
        'onboarding_status',
        'onboarding_completed_at',
        'onboarding_steps',
        // Merchant Account Fields
        'is_partner_merchant',
        'partner_id',
        'partner_name',
        'team_id',
        'team_name',
        'legal_name',
        'phone',
        'merchant_category',
        'merchant_category_code',
        'ownership_type',
        'organization_name',
        'challan_urn',
        'address_line_1',
        'address_line_2',
        'merchant_pan_number',
        'name_on_pan_card',
        'gst_identification_no',
        'gstin_state',
        'tan_no',
        'contact_name',
        'contact_mobile',
        'contact_landline',
        'contact_email',
        'is_dummy_account',
        'account_type',
        'merchant_type',
        'approval_status',
        'registration_date',
        'acquirer_account_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'test_mode' => 'boolean',
        'fee_percentage' => 'decimal:2',
        'fee_flat' => 'decimal:2',
        'onboarding_steps' => 'array',
        'onboarding_completed_at' => 'datetime',
        'is_partner_merchant' => 'boolean',
        'is_dummy_account' => 'boolean',
        'registration_date' => 'date',
    ];

    /**
     * Get users associated with this merchant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get API keys for this merchant.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get orders for this merchant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get transactions for this merchant.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get refunds for this merchant.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get settlements for this merchant.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    /**
     * Get payment links for this merchant.
     */
    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    /**
     * Single assigned acquirer (many-to-one: one merchant -> one acquirer).
     */
    public function acquirerAccount(): BelongsTo
    {
        return $this->belongsTo(AcquirerAccount::class, 'acquirer_account_id');
    }

    /**
     * Legacy pivot: acquirer accounts associated via pivot (kept for sync when assigning from merchant form).
     */
    public function acquirerAccounts(): BelongsToMany
    {
        return $this->belongsToMany(AcquirerAccount::class, 'acquirer_account_merchant')
                    ->withTimestamps();
    }

    /**
     * Whether the merchant is approved to use acquirer (test or live).
     * Acquirer is only used when merchant is at least test_approved or approved.
     */
    public function isApprovedForAcquirer(): bool
    {
        return in_array($this->approval_status ?? '', ['test_approved', 'approved'], true);
    }

    /**
     * Whether this merchant has any active acquirer (any mode).
     * Returns true only if merchant is approved (test_approved or approved) and has an active acquirer.
     */
    public function hasAnyActiveAcquirer(): bool
    {
        if (!$this->isApprovedForAcquirer()) {
            return false;
        }
        if ($this->acquirer_account_id) {
            $acquirer = $this->acquirerAccount;
            return $acquirer && $acquirer->is_active;
        }
        return $this->acquirerAccounts()->where('is_active', true)->exists();
    }

    /**
     * Get active acquirer account for this merchant.
     * Uses single assigned acquirer (acquirer_account_id) when set; else fallback to pivot (legacy).
     * Returns null if merchant is not approved (test_approved or approved) so acquirer is not used until then.
     */
    public function getActiveAcquirerAccount(): ?AcquirerAccount
    {
        if (!$this->isApprovedForAcquirer()) {
            return null;
        }
        if ($this->acquirer_account_id) {
            $acquirer = $this->acquirerAccount;
            if ($acquirer && $acquirer->is_active) {
                return $acquirer;
            }
        }
        // Fallback: pivot-based (legacy)
        $mode = $this->test_mode ? 'TEST' : 'LIVE';
        $candidates = $this->acquirerAccounts()
            ->where('mode', $mode)
            ->where('is_active', true)
            ->get();
        if ($candidates->isEmpty() && !$this->test_mode) {
            $candidates = $this->acquirerAccounts()
                ->where('mode', 'TEST')
                ->where('is_active', true)
                ->get();
        }
        if ($candidates->isEmpty()) {
            return null;
        }
        $preferred = $candidates->first(function (AcquirerAccount $a) {
            return stripos($a->acquirer_name ?? '', 'razorpay') !== false;
        });
        return $preferred ?? $candidates->first();
    }

    /**
     * Get webhook events for this merchant.
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    /**
     * Get payouts for this merchant.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get subscriptions for this merchant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get base rates for this merchant.
     */
    public function baseRates(): HasMany
    {
        return $this->hasMany(BaseRate::class, 'entity_id')
            ->where('rate_type', BaseRate::RATE_TYPE_MERCHANT)
            ->where('entity_type', 'merchant');
    }

    /**
     * Calculate fee for a given amount.
     */
    public function calculateFee(float $amount): float
    {
        $percentageFee = ($amount * $this->fee_percentage) / 100;
        return round($percentageFee + $this->fee_flat, 2);
    }

    /**
     * Check if merchant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if merchant can use Live (merchant) mode.
     * Live mode as payment aggregator: allowed when merchant has an active acquirer
     * (e.g. Razorpay Test, Razorpay Live, Cashfree) — no bank account required.
     * Alternatively, full live credentials (API key + bank + production gateway) also allow live mode.
     */
    public function canUseLiveMode(): bool
    {
        if ($this->hasAnyActiveAcquirer()) {
            return true;
        }
        return $this->hasLiveCredentials();
    }

    /**
     * Check if merchant has full live credentials configured (API key, bank account, production gateway).
     * Used for flows that require payouts/settlements; not required for aggregator payments.
     */
    public function hasLiveCredentials(): bool
    {
        // Check if live API key exists
        $hasLiveApiKey = $this->apiKeys()
            ->where('mode', 'live')
            ->where('status', 'active')
            ->exists();

        // Check if bank account details are configured
        $hasBankDetails = !empty($this->bank_account_number)
            && !empty($this->bank_ifsc_code)
            && !empty($this->bank_account_holder_name);

        // Check if live payment provider credentials are configured in settings
        $hasLiveProviderSettings = isset($this->settings['production_api_key'])
            && isset($this->settings['production_api_secret'])
            && !empty($this->settings['production_api_key'])
            && !empty($this->settings['production_api_secret']);

        // All three conditions must be met for full live credentials
        return $hasLiveApiKey && $hasBankDetails && $hasLiveProviderSettings;
    }

    /**
     * Get the missing live credentials for helpful error messages.
     */
    public function getMissingLiveCredentials(): array
    {
        $missing = [];

        if (!$this->apiKeys()->where('mode', 'live')->where('status', 'active')->exists()) {
            $missing[] = 'Live API Key';
        }

        if (empty($this->bank_account_number) || empty($this->bank_ifsc_code) || empty($this->bank_account_holder_name)) {
            $missing[] = 'Bank Account Details';
        }

        if (!isset($this->settings['production_api_key']) || !isset($this->settings['production_api_secret']) 
            || empty($this->settings['production_api_key']) || empty($this->settings['production_api_secret'])) {
            $missing[] = 'Live Payment Gateway Credentials';
        }

        return $missing;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($merchant) {
            if (empty($merchant->merchant_unique_id)) {
                $merchant->merchant_unique_id = static::generateMerchantUniqueId();
            }
        });
    }

    /**
     * Generate the next unique merchant ID in format BC_MID_000001.
     *
     * @return string
     */
    public static function generateMerchantUniqueId(): string
    {
        // Get all existing merchant_unique_ids
        $existingIds = static::whereNotNull('merchant_unique_id')
            ->where('merchant_unique_id', 'like', 'BC_MID_%')
            ->pluck('merchant_unique_id')
            ->toArray();

        if (empty($existingIds)) {
            // First merchant
            $nextNumber = 1;
        } else {
            // Extract all numbers and find the maximum
            $numbers = [];
            foreach ($existingIds as $id) {
                $numberPart = substr($id, 7); // Remove 'BC_MID_' prefix (7 characters)
                if (is_numeric($numberPart)) {
                    $numbers[] = (int) $numberPart;
                }
            }
            
            if (empty($numbers)) {
                $nextNumber = 1;
            } else {
                $nextNumber = max($numbers) + 1;
            }
        }

        // Format as BC_MID_000001 (6 digits with leading zeros)
        return 'BC_MID_' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the route key for the model.
     * This allows using merchant_unique_id in route model binding.
     */
    public function getRouteKeyName()
    {
        return 'merchant_unique_id';
    }
}

