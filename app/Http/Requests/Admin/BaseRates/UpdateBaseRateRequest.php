<?php

namespace App\Http\Requests\Admin\BaseRates;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBaseRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_type' => 'required|in:bank,merchant,receiver,pricer',
            'entity_type' => 'nullable|string',
            'entity_id' => 'required|integer',

            'payment_method' => 'required|in:card,upi,netbanking,wallet',
            'payment_mode' => 'required|string|max:255',
            'service_type' => 'required|in:payment,refund,chargeback',
            'transaction_type' => 'required|in:domestic,international',
            'sector' => 'required|string|max:255',
            'currency' => 'required|string|max:10',

            'percentage_fee' => 'required|numeric|min:0|max:100',
            'flat_fee' => 'required|numeric|min:0',
            'gst_percentage' => 'required|numeric|min:0|max:100',

            'admin_share_pct' => 'required|numeric|min:0|max:100',
            'reseller_share_pct' => 'required|numeric|min:0|max:100',
            'merchant_share_pct' => 'required|numeric|min:0|max:100',

            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'min_share' => 'nullable|numeric|min:0|max:100',
            'max_share' => 'nullable|numeric|min:0|max:100',

            'team_id' => 'nullable|integer|min:0',
            'team_name' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:255',
            'bank_description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            'reseller_id' => 'nullable|exists:resellers,id',
            'split_is_active' => 'nullable|boolean',

            'is_active' => 'required|boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Validator $validator */
        $validator->after(function (Validator $v) {
            $minAmount = $this->input('min_amount');
            $maxAmount = $this->input('max_amount');
            if ($minAmount !== null && $maxAmount !== null && is_numeric($minAmount) && is_numeric($maxAmount)) {
                if ((float) $minAmount > (float) $maxAmount) {
                    $v->errors()->add('min_amount', 'Min Amount must be less than or equal to Max Amount.');
                }
            }

            $paymentMethod = (string) $this->input('payment_method');
            $paymentMode = (string) $this->input('payment_mode');
            if ($paymentMethod === 'card' && trim($paymentMode) === '') {
                $v->errors()->add('payment_mode', 'Payment Mode is required for Card payments.');
            }

            $admin = (float) $this->input('admin_share_pct', 0);
            $reseller = (float) $this->input('reseller_share_pct', 0);
            $merchant = (float) $this->input('merchant_share_pct', 0);
            if (abs(($admin + $reseller + $merchant) - 100.0) > 0.0001) {
                $v->errors()->add('admin_share_pct', 'Total share must equal 100%.');
                $v->errors()->add('reseller_share_pct', 'Total share must equal 100%.');
                $v->errors()->add('merchant_share_pct', 'Total share must equal 100%.');
            }

            $resellerId = $this->input('reseller_id');
            if (empty($resellerId) && $reseller > 0) {
                $v->errors()->add('reseller_id', 'Select a reseller or set Reseller Share to 0%.');
            }
        });
    }
}

