@extends('layouts.app-sidebar')

@section('title', 'Onboarding - ' . config('app.name'))
@section('page-title', 'Complete Onboarding')

@section('content')
<div ng-app="badlicashApp" ng-controller="OnboardingController as oc">
    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="fw-bold">Complete Your Onboarding</h3>
            <p class="text-muted">Complete these steps to enable live mode and start accepting payments</p>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="stat-card mb-4">
        <div class="row g-3">
            @foreach($steps as $stepNum => $step)
            <div class="col-md-3">
                <div class="card border {{ $currentStep >= $stepNum ? 'border-primary' : '' }}">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            @if($currentStep > $stepNum)
                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            @elseif($currentStep == $stepNum)
                                <i class="bi {{ $step['icon'] }} text-primary fs-3"></i>
                            @else
                                <i class="bi {{ $step['icon'] }} text-muted fs-3"></i>
                            @endif
                        </div>
                        <h6 class="mb-1">{{ $step['title'] }}</h6>
                        <small class="text-muted">{{ $step['description'] }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Step Forms -->
    <div class="stat-card">
        <!-- Step 1: Business Details -->
        <div ng-show="oc.currentStep === 1">
            <h5 class="mb-3"><i class="bi bi-building me-2"></i>Business Details</h5>
            <form ng-submit="oc.submitStep(1)">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-control" ng-model="oc.form.business.company_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Type *</label>
                        <select class="form-select" ng-model="oc.form.business.business_type" required>
                            <option value="">Select Type</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership">Partnership</option>
                            <option value="private_limited">Private Limited</option>
                            <option value="llp">Limited Liability Partnership</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Phone *</label>
                        <input type="tel" class="form-control" ng-model="oc.form.business.business_phone" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Email</label>
                        <input type="email" class="form-control" ng-model="oc.form.business.business_email">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Business Address *</label>
                        <textarea class="form-control" rows="2" ng-model="oc.form.business.business_address" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City *</label>
                        <input type="text" class="form-control" ng-model="oc.form.business.business_city" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State *</label>
                        <input type="text" class="form-control" ng-model="oc.form.business.business_state" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code *</label>
                        <input type="text" class="form-control" ng-model="oc.form.business.business_postal_code" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" ng-model="oc.form.business.business_website" placeholder="https://">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" ng-disabled="oc.saving">
                        <span ng-if="oc.saving" class="spinner-border spinner-border-sm me-2"></span>
                        Next Step
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: Bank Details -->
        <div ng-show="oc.currentStep === 2">
            <h5 class="mb-3"><i class="bi bi-bank me-2"></i>Bank Account Details</h5>
            <form ng-submit="oc.submitStep(2)">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Account Holder Name *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_account_holder_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_account_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IFSC Code *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_ifsc_code" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Name *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_name" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_branch">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-outline-secondary" ng-click="oc.currentStep = 1">Previous</button>
                    <button type="submit" class="btn btn-primary ms-2" ng-disabled="oc.saving">
                        <span ng-if="oc.saving" class="spinner-border spinner-border-sm me-2"></span>
                        Next Step
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 3: KYC Documents -->
        <div ng-show="oc.currentStep === 3">
            <h5 class="mb-3"><i class="bi bi-file-earmark-check me-2"></i>KYC Documents</h5>
            <form ng-submit="oc.submitStep(3)" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Document Type *</label>
                        <select class="form-select" ng-model="oc.form.kyc.kyc_document_type" required>
                            <option value="">Select Document</option>
                            <option value="pan">PAN Card</option>
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="passport">Passport</option>
                            <option value="driving_license">Driving License</option>
                            <option value="business_license">Business License</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Document Number *</label>
                        <input type="text" class="form-control" ng-model="oc.form.kyc.kyc_document_number" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Upload Document *</label>
                        <input type="file" class="form-control" id="kycDocument" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">PDF, JPG, or PNG (Max 5MB)</small>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-outline-secondary" ng-click="oc.currentStep = 2">Previous</button>
                    <button type="submit" class="btn btn-primary ms-2" ng-disabled="oc.saving">
                        <span ng-if="oc.saving" class="spinner-border spinner-border-sm me-2"></span>
                        Next Step
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 4: Review -->
        <div ng-show="oc.currentStep === 4">
            <h5 class="mb-3"><i class="bi bi-check-circle me-2"></i>Review & Submit</h5>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Please review all your information before submitting. Once submitted, our team will review your application.
            </div>
            <div class="mt-4">
                <button type="button" class="btn btn-outline-secondary" ng-click="oc.currentStep = 3">Previous</button>
                <button type="button" class="btn btn-primary ms-2" ng-click="oc.submitStep(4)" ng-disabled="oc.saving">
                    <span ng-if="oc.saving" class="spinner-border spinner-border-sm me-2"></span>
                    Submit for Review
                </button>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div ng-show="oc.loading" class="loader-overlay position-relative" style="min-height: 200px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
            </div>
        </div>
    </div>
</div>

@include('merchant.onboarding.angular.main_controller')
@endsection

