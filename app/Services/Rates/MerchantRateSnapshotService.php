<?php

namespace App\Services\Rates;

use App\Models\BaseRate;
use App\Models\Merchant;
use App\Models\Rates\MerchantRateSnapshot;

class MerchantRateSnapshotService
{
    public function createPaymentSnapshot(
        Merchant $merchant,
        string $paymentMethod,
        float $amount,
        float $feeAmount,
        ?float $percentageFee = null,
        ?float $flatFee = null,
        ?float $gstPercentage = null,
        ?int $baseRateId = null
    ): MerchantRateSnapshot {
        $effectivePct = $amount > 0 ? round(($feeAmount / $amount) * 100, 4) : 0.0;

        if ($baseRateId === null || $percentageFee === null || $flatFee === null || $gstPercentage === null) {
            $baseRate = BaseRate::query()
                ->where('id', $baseRateId)
                ->first();

            if (! $baseRate) {
                $baseRate = app(\App\Services\BaseRateService::class)->getApplicableRate(
                    $merchant,
                    $merchant->bank ?? null,
                    $paymentMethod,
                    BaseRate::SERVICE_TYPE_PAYMENT,
                    BaseRate::TRANSACTION_TYPE_DOMESTIC
                );
            }

            $baseRateId = $baseRate?->id;
            $percentageFee = $percentageFee ?? (float) ($baseRate?->percentage_fee ?? 0);
            $flatFee = $flatFee ?? (float) ($baseRate?->flat_fee ?? 0);
            $gstPercentage = $gstPercentage ?? (float) ($baseRate?->gst_percentage ?? 18);
        }

        return MerchantRateSnapshot::create([
            'merchant_id' => $merchant->id,
            'base_rate_id' => $baseRateId,
            'payment_method' => $paymentMethod,
            'service_type' => BaseRate::SERVICE_TYPE_PAYMENT,
            'transaction_type' => BaseRate::TRANSACTION_TYPE_DOMESTIC,
            'percentage_fee' => $percentageFee,
            'flat_fee' => $flatFee,
            'gst_percentage' => $gstPercentage,
            'effective_fee_percentage' => $effectivePct,
        ]);
    }
}

