<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
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
        $validated = $request->validate($this->getStepValidationRules($step));

        try {
            switch ($step) {
                case 1: // Business Details
                    $merchant->update([
                        'company_name' => $validated['company_name'],
                        'business_type' => $validated['business_type'],
                        'business_phone' => $validated['business_phone'],
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
                    'company_name' => 'required|string|max:255',
                    'business_type' => 'required|string|max:100',
                    'business_phone' => 'required|string|max:20',
                    'business_address' => 'required|string|max:500',
                    'business_city' => 'required|string|max:100',
                    'business_state' => 'required|string|max:100',
                    'business_country' => 'nullable|string|max:2',
                    'business_postal_code' => 'required|string|max:20',
                    'business_website' => 'nullable|url|max:255',
                ];

            case 2:
                return [
                    'bank_account_holder_name' => 'required|string|max:255',
                    'bank_account_number' => 'required|string|max:50',
                    'bank_ifsc_code' => 'required|string|max:20',
                    'bank_name' => 'required|string|max:255',
                    'bank_branch' => 'nullable|string|max:255',
                ];

            case 3:
                return [
                    'kyc_document_type' => 'required|in:pan,aadhaar,passport,driving_license,business_license',
                    'kyc_document_number' => 'required|string|max:50',
                    'kyc_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
                ];

            case 4:
                return [];

            default:
                return [];
        }
    }
}

