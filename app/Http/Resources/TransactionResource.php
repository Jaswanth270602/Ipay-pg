<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\SanitizesCardData;

/**
 * PCI-DSS Compliant Transaction Resource
 * Ensures no card data is exposed in API responses
 */
class TransactionResource extends JsonResource
{
    use SanitizesCardData;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->txn_id,
            'order_id' => $this->order_id,
            'merchant_id' => $this->merchant_id,
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'fee_amount' => (float) $this->fee_amount,
            'net_amount' => (float) $this->net_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'gateway_txn_id' => $this->gateway_txn_id,
            // PCI-DSS: Use sanitized payment details (no card data)
            'payment_details' => $this->getSanitizedPaymentDetails(),
            // PCI-DSS: Use sanitized gateway response (no card data)
            'gateway_response' => $this->getSanitizedGatewayResponse(),
            'test_mode' => $this->test_mode,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'captured_at' => $this->captured_at?->toIso8601String(),
        ];
    }
}

