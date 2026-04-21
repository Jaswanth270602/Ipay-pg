<?php

namespace App\Http\Requests\Admin\Resellers;

use Illuminate\Foundation\Http\FormRequest;

class StoreResellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        if (is_string($status)) {
            $status = strtolower(trim($status));
            if (in_array($status, ['active', '1', 'true'], true)) {
                $status = true;
            } elseif (in_array($status, ['inactive', '0', 'false'], true)) {
                $status = false;
            }
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'company_name' => preg_replace('/\s+/', ' ', trim((string) $this->input('company_name'))),
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:resellers,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:7', 'max:16', 'regex:/^\+?[1-9][0-9]{6,14}$/'],
            'company_name' => ['required', 'string', 'min:3', 'max:256'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name can contain only alphabets and spaces.',
            'phone.regex' => 'Phone must be 7-15 digits with optional leading +.',
            'phone.min' => 'Phone must be at least 7 characters.',
            'phone.max' => 'Phone may not be greater than 16 characters.',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
        ];
    }
}

