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
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
            'company_name' => preg_replace('/\s+/', ' ', trim((string) $this->input('company_name'))),
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:resellers,email', 'unique:users,email'],
            'phone' => ['required', 'digits:10'],
            'company_name' => ['required', 'string', 'min:2', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name can contain only alphabets and spaces.',
            'phone.digits' => 'Phone must be exactly 10 digits.',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
        ];
    }
}

