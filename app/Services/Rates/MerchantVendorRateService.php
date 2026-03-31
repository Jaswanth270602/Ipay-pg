<?php

namespace App\Services\Rates;

use App\Models\Rates\MerchantVendorBaseRate;
use App\Models\Rates\MerchantVendorRateSnapshot;

class MerchantVendorRateService
{
    public function getActiveVendorPaymentRate(
        int $merchantId,
        int $vendorId,
        ?string $paymentMethod = null
    ): ?MerchantVendorBaseRate {
        $q = MerchantVendorBaseRate::query()
            ->where('merchant_id', $merchantId)
            ->where('vendor_id', $vendorId)
            ->where('service_type', 'payment')
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN payment_method IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('id');

        if ($paymentMethod) {
            $q->where(function ($sub) use ($paymentMethod) {
                $sub->where('payment_method', $paymentMethod)
                    ->orWhereNull('payment_method');
            });
        }

        return $q->first();
    }

    /**
     * Return split percentage for vendor share from post-admin-fee net amount.
     */
    public function resolveVendorSharePercentage(
        int $merchantId,
        int $vendorId,
        string $paymentMethod = 'card'
    ): float {
        $rate = $this->getActiveVendorPaymentRate($merchantId, $vendorId, $paymentMethod);
        if (! $rate) {
            return 100.0;
        }
        $pct = (float) ($rate->percentage_share ?? 0);
        if ($pct < 0) {
            return 0.0;
        }
        if ($pct > 100) {
            return 100.0;
        }
        return round($pct, 4);
    }

    public function snapshotVendorRate(
        int $merchantId,
        int $vendorId,
        string $paymentMethod,
        float $effectiveSplitPercentage
    ): MerchantVendorRateSnapshot {
        $rate = $this->getActiveVendorPaymentRate($merchantId, $vendorId, $paymentMethod);

        return MerchantVendorRateSnapshot::create([
            'merchant_id' => $merchantId,
            'vendor_id' => $vendorId,
            'merchant_vendor_base_rate_id' => $rate?->id,
            'payment_method' => $paymentMethod,
            'service_type' => 'payment',
            'percentage_share' => (float) ($rate?->percentage_share ?? $effectiveSplitPercentage),
            'flat_share' => (float) ($rate?->flat_share ?? 0),
            'effective_split_percentage' => round($effectiveSplitPercentage, 4),
        ]);
    }
}

