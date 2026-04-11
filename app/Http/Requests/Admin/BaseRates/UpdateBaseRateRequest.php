<?php

namespace App\Http\Requests\Admin\BaseRates;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBaseRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamIdRule = ['required', 'integer'];
        if (Schema::hasTable('teams')) {
            $teamIdRule[] = Rule::exists('teams', 'id');
        }

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

            'percentage_fee' => 'required|numeric|between:0,100',
            'flat_fee' => 'required|numeric|min:0',
            'gst_percentage' => 'required|numeric|min:0|max:100',

            'admin_share_pct' => 'required|numeric|min:0|max:100',
            'reseller_share_pct' => 'required|numeric|min:0|max:100',
            'merchant_share_pct' => 'required|numeric|min:0|max:100',

            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gte:min_amount',
            'min_share' => 'required|numeric|between:0,100',
            'max_share' => 'required|numeric|gte:min_share|lte:100',

            'team_id' => $teamIdRule,
            'team_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z ]+$/'],
            'bank_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'bank_description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            'reseller_id' => 'nullable|exists:resellers,id',
            'split_is_active' => 'nullable|boolean',

            'status' => 'required_without:is_active|in:active,inactive',
            'is_active' => 'required_without:status|boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('status') && ! $this->has('is_active')) {
            $this->merge([
                'is_active' => $this->input('status') === 'active',
            ]);
        }
        if ($this->has('is_active') && ! $this->filled('status')) {
            $this->merge([
                'status' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN) ? 'active' : 'inactive',
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'team_id.required' => 'Team ID is required.',
            'team_id.integer' => 'Team ID must be an integer.',
            'team_id.exists' => 'Selected team does not exist.',
            'team_name.required' => 'Team name is required.',
            'team_name.max' => 'Team name may not exceed 255 characters.',
            'team_name.regex' => 'Team name may contain only letters and spaces.',
            'bank_code.required' => 'Bank code is required.',
            'bank_code.max' => 'Bank code may not exceed 20 characters.',
            'bank_code.regex' => 'Bank code may contain only letters and numbers.',
            'bank_description.max' => 'Bank description may not exceed 255 characters.',
            'percentage_fee.required' => 'Percentage fee is required.',
            'percentage_fee.numeric' => 'Percentage fee must be a number.',
            'percentage_fee.between' => 'Percentage fee must be between 0 and 100.',
            'flat_fee.required' => 'Flat fee is required.',
            'flat_fee.numeric' => 'Flat fee must be a number.',
            'flat_fee.min' => 'Flat fee must be at least 0.',
            'min_amount.required' => 'Minimum amount is required.',
            'min_amount.numeric' => 'Minimum amount must be a number.',
            'min_amount.min' => 'Minimum amount must be at least 0.',
            'max_amount.required' => 'Maximum amount is required.',
            'max_amount.numeric' => 'Maximum amount must be a number.',
            'max_amount.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'min_share.required' => 'Minimum share is required.',
            'min_share.numeric' => 'Minimum share must be a number.',
            'min_share.between' => 'Minimum share must be between 0 and 100.',
            'max_share.required' => 'Maximum share is required.',
            'max_share.numeric' => 'Maximum share must be a number.',
            'max_share.gte' => 'Maximum share must be greater than or equal to minimum share.',
            'max_share.lte' => 'Maximum share must not exceed 100.',
            'status.required_without' => 'Status is required.',
            'status.in' => 'Status must be either active or inactive.',
            'is_active.required_without' => 'Status is required.',
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

