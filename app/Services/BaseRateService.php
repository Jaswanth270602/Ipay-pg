<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\Merchant;
use App\Models\Bank;
use Illuminate\Support\Collection;

class BaseRateService
{
    /**
     * Get applicable rate for a transaction.
     * Priority: Merchant-specific > Bank-specific > Default
     */
    public function getApplicableRate(
        Merchant $merchant,
        ?Bank $bank,
        string $paymentMethod,
        string $serviceType = BaseRate::SERVICE_TYPE_PAYMENT,
        string $transactionType = BaseRate::TRANSACTION_TYPE_DOMESTIC
    ): ?BaseRate {
        // Priority 1: Merchant-specific rate
        $merchantRate = BaseRate::active()
            ->ofType(BaseRate::RATE_TYPE_MERCHANT)
            ->where('entity_id', $merchant->id)
            ->where('entity_type', 'merchant')
            ->forPaymentMethod($paymentMethod)
            ->forServiceType($serviceType)
            ->forTransactionType($transactionType)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($merchantRate) {
            return $merchantRate;
        }

        // Priority 2: Bank-specific rate
        if ($bank) {
            $bankRate = BaseRate::active()
                ->ofType(BaseRate::RATE_TYPE_BANK)
                ->where('entity_id', $bank->id)
                ->where('entity_type', 'bank')
                ->forPaymentMethod($paymentMethod)
                ->forServiceType($serviceType)
                ->forTransactionType($transactionType)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($bankRate) {
                return $bankRate;
            }
        }

        // Priority 3: Default rate (no entity_id)
        $defaultRate = BaseRate::active()
            ->ofType(BaseRate::RATE_TYPE_MERCHANT)
            ->whereNull('entity_id')
            ->forPaymentMethod($paymentMethod)
            ->forServiceType($serviceType)
            ->forTransactionType($transactionType)
            ->orderBy('created_at', 'desc')
            ->first();

        return $defaultRate;
    }

    /**
     * Calculate fee using base rates.
     * Returns array with fee_amount, gst_amount, and total_fee.
     */
    public function calculateFee(
        Merchant $merchant,
        float $amount,
        string $paymentMethod,
        ?Bank $bank = null,
        string $serviceType = BaseRate::SERVICE_TYPE_PAYMENT,
        string $transactionType = BaseRate::TRANSACTION_TYPE_DOMESTIC
    ): array {
        $rate = $this->getApplicableRate($merchant, $bank, $paymentMethod, $serviceType, $transactionType);

        // If no base rate found, fallback to merchant's default fee calculation
        if (!$rate) {
            $feeAmount = $merchant->calculateFee($amount);
            $gstPercentage = 18; // Default GST
            $gstAmount = round(($feeAmount * $gstPercentage) / 100, 2);
            
            return [
                'fee_amount' => $feeAmount,
                'gst_amount' => $gstAmount,
                'total_fee' => $feeAmount + $gstAmount,
                'rate_id' => null,
                'rate_type' => 'merchant_default',
            ];
        }

        // Calculate fee using base rate
        $feeAmount = $rate->calculateFee($amount);
        $gstAmount = $rate->calculateGST($feeAmount);
        $totalFee = $feeAmount + $gstAmount;

        return [
            'fee_amount' => $feeAmount,
            'gst_amount' => $gstAmount,
            'total_fee' => $totalFee,
            'rate_id' => $rate->id,
            'rate_type' => $rate->rate_type,
            'percentage_fee' => $rate->percentage_fee,
            'flat_fee' => $rate->flat_fee,
            'gst_percentage' => $rate->gst_percentage,
        ];
    }

    /**
     * Get all rates for a merchant.
     */
    public function getMerchantRates(Merchant $merchant): Collection
    {
        return BaseRate::where('rate_type', BaseRate::RATE_TYPE_MERCHANT)
            ->where('entity_id', $merchant->id)
            ->where('entity_type', 'merchant')
            ->orderBy('payment_method')
            ->orderBy('service_type')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all rates for a bank.
     */
    public function getBankRates(Bank $bank): Collection
    {
        return BaseRate::where('rate_type', BaseRate::RATE_TYPE_BANK)
            ->where('entity_id', $bank->id)
            ->where('entity_type', 'bank')
            ->orderBy('payment_method')
            ->orderBy('service_type')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get default rates (no entity_id).
     */
    public function getDefaultRates(): Collection
    {
        return BaseRate::whereNull('entity_id')
            ->orderBy('rate_type')
            ->orderBy('payment_method')
            ->orderBy('service_type')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create or update a rate.
     */
    public function createOrUpdateRate(array $data): BaseRate
    {
        // Check if rate already exists
        $query = BaseRate::where('rate_type', $data['rate_type'])
            ->where('payment_method', $data['payment_method'])
            ->where('service_type', $data['service_type'] ?? BaseRate::SERVICE_TYPE_PAYMENT)
            ->where('transaction_type', $data['transaction_type'] ?? BaseRate::TRANSACTION_TYPE_DOMESTIC);

        if (isset($data['entity_id']) && $data['entity_id']) {
            $query->where('entity_id', $data['entity_id'])
                  ->where('entity_type', $data['entity_type']);
        } else {
            $query->whereNull('entity_id');
        }

        $existingRate = $query->first();

        if ($existingRate) {
            $existingRate->update($data);
            return $existingRate->fresh();
        }

        return BaseRate::create($data);
    }
}

