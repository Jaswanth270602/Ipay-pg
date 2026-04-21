<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    public function locations()
    {
        try {
            $path = database_path('data/merchant_locations_dataset.json');
            $locations = [];

            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    $locations = $decoded;
                }
            }

            if ($locations === []) {
                $fallback = config('merchant_locations', []);
                $locations = is_array($fallback) ? $fallback : [];
            }

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locations',
                'data' => [],
            ], 500);
        }
    }

    public function index()
    {
        $merchant = auth()->user()->merchant;
        
        // Determine current step
        $steps = $this->getOnboardingSteps();
        $currentStep = $this->getCurrentStep($merchant);
        
        return view('merchant.onboarding.index', compact('merchant', 'steps', 'currentStep'));
    }

    public function updateStep(Request $request, $step)
    {
        $merchant = auth()->user()->merchant;
        $validated = $request->validate(
            $this->getStepValidationRules($step),
            $this->getStepValidationMessages($step)
        );

        try {
            switch ($step) {
                case 1: // Business Details
                    $merchant->update([
                        'company_name' => $validated['company_name'],
                        'business_type' => $validated['business_type'],
                        'business_phone' => $validated['business_phone'],
                        'business_email' => $validated['business_email'] ?? null,
                        'business_address' => $validated['business_address'],
                        'business_city' => $validated['business_city'],
                        'business_state' => $validated['business_state'],
                        'business_country' => $validated['business_country'] ?? 'IN',
                        'business_postal_code' => $validated['business_postal_code'],
                        'business_website' => $validated['business_website'] ?? null,
                    ]);
                    break;

                case 2: // Bank Details
                    $merchant->update([
                        'bank_account_holder_name' => $validated['bank_account_holder_name'],
                        'bank_account_number' => $validated['bank_account_number'],
                        'bank_ifsc_code' => $validated['bank_ifsc_code'],
                        'bank_name' => $validated['bank_name'],
                        'bank_branch' => $validated['bank_branch'] ?? null,
                    ]);
                    break;

                case 3: // KYC Documents
                    if ($request->hasFile('kyc_document')) {
                        $file = $request->file('kyc_document');
                        $path = $file->store('kyc_documents', 'public');
                        
                        $merchant->update([
                            'kyc_document_type' => $validated['kyc_document_type'],
                            'kyc_document_number' => $validated['kyc_document_number'],
                            'kyc_document_file' => $path,
                            'kyc_status' => 'under_review',
                        ]);
                    }
                    break;

                case 4: // Review & Submit
                    $merchant->update([
                        'onboarding_status' => 'completed',
                        'onboarding_completed_at' => now(),
                    ]);
                    break;
            }

            // Update onboarding steps progress
            $steps = $merchant->onboarding_steps ?? [];
            $steps['step_' . $step] = 'completed';
            $merchant->onboarding_steps = $steps;
            $merchant->onboarding_status = $step === 4 ? 'completed' : 'in_progress';
            $merchant->save();

            Log::info('Onboarding step completed', [
                'merchant_id' => $merchant->id,
                'step' => $step,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Step completed successfully',
                'next_step' => $step < 4 ? $step + 1 : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Onboarding step failed', [
                'merchant_id' => $merchant->id,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save step: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function getOnboardingSteps()
    {
        return [
            1 => [
                'title' => 'Business Details',
                'description' => 'Provide your business information',
                'icon' => 'bi-building',
            ],
            2 => [
                'title' => 'Bank Account',
                'description' => 'Add your bank account for settlements',
                'icon' => 'bi-bank',
            ],
            3 => [
                'title' => 'KYC Documents',
                'description' => 'Upload required verification documents',
                'icon' => 'bi-file-earmark-check',
            ],
            4 => [
                'title' => 'Review & Submit',
                'description' => 'Review your information and submit',
                'icon' => 'bi-check-circle',
            ],
        ];
    }

    protected function getCurrentStep(Merchant $merchant)
    {
        if ($merchant->onboarding_status === 'completed') {
            return 4;
        }

        $steps = $merchant->onboarding_steps ?? [];
        
        if (!isset($steps['step_1'])) return 1;
        if (!isset($steps['step_2'])) return 2;
        if (!isset($steps['step_3'])) return 3;
        return 4;
    }

    protected function getStepValidationRules($step)
    {
        switch ($step) {
            case 1:
                return [
                    'company_name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
                    'business_type' => 'required|string|in:sole_proprietorship,partnership,private_limited,llp,other',
                    'business_phone' => ['required', 'string', 'min:6', 'max:16', 'regex:/^\+?[0-9]{6,15}$/'],
                    'business_email' => 'nullable|email|max:255',
                    'business_address' => 'required|string|min:3|max:500',
                    'business_city' => ['required', 'string', 'max:100'],
                    'business_state' => ['required', 'string', 'max:100'],
                    'business_country' => 'required|string|max:2',
                    'business_postal_code' => ['required', 'string', 'min:4', 'max:16', 'regex:/^[A-Za-z0-9]+$/'],
                    'business_website' => 'nullable|url|max:255',
                ];

            case 2:
                return [
                    'bank_account_holder_name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
                    'bank_account_number' => ['required', 'string', 'min:8', 'max:34', 'regex:/^[A-Za-z0-9]+$/'],
                    'bank_ifsc_code' => ['required', 'string', 'min:7', 'max:15', 'regex:/^[A-Za-z]{4}[A-Za-z0-9]{3,11}$/'],
                    'bank_name' => ['required', 'string', 'min:3', 'max:256', 'regex:/^[A-Za-z ]+$/'],
                    'bank_branch' => ['nullable', 'string', 'min:3', 'max:255', 'regex:/^[A-Za-z0-9 ]+$/'],
                ];

            case 3:
                return [
                    'kyc_document_type' => 'required|in:pan,aadhaar,passport,driving_license,business_license',
                    'kyc_document_number' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[A-Za-z0-9]+$/'],
                    'kyc_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
                ];

            case 4:
                return [];

            default:
                return [];
        }
    }

    protected function getStepValidationMessages($step): array
    {
        if ((int) $step === 1) {
            return [
                'company_name.required' => 'Company name is required.',
                'company_name.min' => 'Company name must be at least 3 characters.',
                'company_name.max' => 'Company name may not be greater than 256 characters.',
                'company_name.regex' => 'Company name may contain only letters and spaces.',
                'business_type.required' => 'Business type is required.',
                'business_type.in' => 'Please select a valid business type.',
                'business_phone.required' => 'Business phone is required.',
                'business_phone.regex' => 'Business phone must contain only numbers and optional + (6-15 digits).',
                'business_phone.min' => 'Business phone must be at least 6 characters.',
                'business_phone.max' => 'Business phone may not be greater than 16 characters.',
                'business_email.email' => 'Business email must be a valid email address.',
                'business_address.required' => 'Business address is required.',
                'business_address.min' => 'Business address must be at least 3 characters.',
                'business_state.required' => 'State is required.',
                'business_city.required' => 'City is required.',
                'business_country.required' => 'Country is required.',
                'business_postal_code.required' => 'Postal code is required.',
                'business_postal_code.regex' => 'Postal code may contain only letters and numbers.',
                'business_postal_code.min' => 'Postal code must be at least 4 characters.',
                'business_postal_code.max' => 'Postal code may not be greater than 16 characters.',
                'business_website.url' => 'Website must be a valid URL.',
            ];
        }

        if ((int) $step === 2) {
            return [
                'bank_account_holder_name.required' => 'Account holder name is required.',
                'bank_account_holder_name.min' => 'Account holder name must be at least 3 characters.',
                'bank_account_holder_name.max' => 'Account holder name may not be greater than 256 characters.',
                'bank_account_holder_name.regex' => 'Account holder name may contain only letters and spaces.',
                'bank_account_number.required' => 'Account number is required.',
                'bank_account_number.min' => 'Account number must be at least 8 characters.',
                'bank_account_number.max' => 'Account number may not be greater than 34 characters.',
                'bank_account_number.regex' => 'Account number may contain only letters and numbers.',
                'bank_ifsc_code.required' => 'IFSC code is required.',
                'bank_ifsc_code.min' => 'IFSC code must be at least 7 characters.',
                'bank_ifsc_code.max' => 'IFSC code may not be greater than 15 characters.',
                'bank_ifsc_code.regex' => 'IFSC code must start with 4 letters followed by letters or numbers (e.g., ABCD0001234).',
                'bank_name.required' => 'Bank name is required.',
                'bank_name.min' => 'Bank name must be at least 3 characters.',
                'bank_name.max' => 'Bank name may not be greater than 256 characters.',
                'bank_name.regex' => 'Bank name may contain only letters and spaces.',
                'bank_branch.min' => 'Bank branch must be at least 3 characters.',
                'bank_branch.regex' => 'Bank branch may contain only letters, numbers, and spaces.',
            ];
        }

        if ((int) $step === 3) {
            return [
                'kyc_document_type.required' => 'Document type is required.',
                'kyc_document_number.required' => 'Document number is required.',
                'kyc_document_number.min' => 'Document number must be at least 4 characters.',
                'kyc_document_number.regex' => 'Document number may contain only letters and numbers.',
                'kyc_document.required' => 'Please upload a document file.',
                'kyc_document.mimes' => 'Document must be PDF, JPG, JPEG or PNG.',
                'kyc_document.max' => 'Document size must not exceed 5MB.',
            ];
        }

        return [];
    }
}

