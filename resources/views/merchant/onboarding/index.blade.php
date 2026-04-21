@extends('layouts.app-sidebar')

@section('title', 'Onboarding - ' . config('app.name'))
@section('page-title', 'Complete Onboarding')

@section('content')
<div ng-app="ipayApp" ng-controller="OnboardingController as oc">
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
                <div class="card border"
                     ng-class="{
                        'border-primary': oc.currentStep == {{ $stepNum }},
                        'border-success': oc.currentStep > {{ $stepNum }}
                     }">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="bi bi-check-circle-fill text-success fs-3" ng-if="oc.currentStep > {{ $stepNum }}"></i>
                            <i class="bi {{ $step['icon'] }} text-primary fs-3" ng-if="oc.currentStep == {{ $stepNum }}"></i>
                            <i class="bi {{ $step['icon'] }} text-muted fs-3" ng-if="oc.currentStep < {{ $stepNum }}"></i>
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
                        <input type="text" class="form-control" ng-model="oc.form.business.company_name" ng-change="oc.form.business.company_name=(oc.form.business.company_name||'').replace(/[^A-Za-z ]/g,'').slice(0,256); oc.validateBusinessField('company_name')" ng-blur="oc.validateBusinessField('company_name')" ng-class="{'is-invalid': oc.formErrors.company_name}" minlength="3" maxlength="256" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.company_name">@{{ oc.formErrors.company_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Type *</label>
                        <select class="form-select" ng-model="oc.form.business.business_type" ng-change="oc.validateBusinessField('business_type')" ng-blur="oc.validateBusinessField('business_type')" ng-class="{'is-invalid': oc.formErrors.business_type}" required>
                            <option value="">Select Type</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership">Partnership</option>
                            <option value="private_limited">Private Limited</option>
                            <option value="llp">Limited Liability Partnership</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_type">@{{ oc.formErrors.business_type }}</div>
                    </div>
                    <input type="hidden" ng-model="oc.form.business.business_country" ng-init="oc.form.business.business_country=oc.form.business.business_country || 'IN'">
                    <div class="col-md-6">
                        <label class="form-label">Business Phone *</label>
                        <input type="tel" class="form-control" ng-model="oc.form.business.business_phone" ng-change="oc.form.business.business_phone=(oc.form.business.business_phone||'').replace(/[^0-9+]/g,''); if((oc.form.business.business_phone||'').indexOf('+')>0){oc.form.business.business_phone='+'+(oc.form.business.business_phone||'').replace(/\+/g,'');} else if((oc.form.business.business_phone||'').indexOf('+')===0){oc.form.business.business_phone='+'+(oc.form.business.business_phone||'').substring(1).replace(/\+/g,'');} oc.form.business.business_phone=(oc.form.business.business_phone||'').slice(0,16); oc.validateBusinessField('business_phone')" ng-blur="oc.validateBusinessField('business_phone')" ng-class="{'is-invalid': oc.formErrors.business_phone}" pattern="\+?[0-9]{6,15}" minlength="6" maxlength="16" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_phone">@{{ oc.formErrors.business_phone }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Email</label>
                        <input type="email" class="form-control" ng-model="oc.form.business.business_email" ng-change="oc.validateBusinessField('business_email')" ng-blur="oc.validateBusinessField('business_email')" ng-class="{'is-invalid': oc.formErrors.business_email}">
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_email">@{{ oc.formErrors.business_email }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Business Address *</label>
                        <textarea class="form-control" rows="2" ng-model="oc.form.business.business_address" ng-change="oc.validateBusinessField('business_address')" ng-blur="oc.validateBusinessField('business_address')" ng-class="{'is-invalid': oc.formErrors.business_address}" minlength="3" maxlength="500" required></textarea>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_address">@{{ oc.formErrors.business_address }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City *</label>
                        <select class="form-select"
                                ng-model="oc.form.business.business_city"
                                ng-disabled="!oc.availableCities.length"
                                ng-change="oc.validateBusinessField('business_city')"
                                ng-blur="oc.validateBusinessField('business_city')"
                                ng-class="{'is-invalid': oc.formErrors.business_city}"
                                required>
                            <option value="">Select City</option>
                            <option ng-repeat="city in oc.availableCities track by city" ng-value="city">@{{ city }}</option>
                        </select>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_city">@{{ oc.formErrors.business_city }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State *</label>
                        <select class="form-select"
                                ng-model="oc.form.business.business_state"
                                ng-disabled="!oc.availableStates.length"
                                ng-change="oc.onStateChange()"
                                ng-blur="oc.validateBusinessField('business_state')"
                                ng-class="{'is-invalid': oc.formErrors.business_state}"
                                required>
                            <option value="">Select State</option>
                            <option ng-repeat="state in oc.availableStates track by state" ng-value="state">@{{ state }}</option>
                        </select>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_state">@{{ oc.formErrors.business_state }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code *</label>
                        <input type="text" class="form-control" ng-model="oc.form.business.business_postal_code" ng-change="oc.form.business.business_postal_code=(oc.form.business.business_postal_code||'').replace(/[^A-Za-z0-9]/g,'').slice(0,16); oc.validateBusinessField('business_postal_code')" ng-blur="oc.validateBusinessField('business_postal_code')" ng-class="{'is-invalid': oc.formErrors.business_postal_code}" minlength="4" maxlength="16" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_postal_code">@{{ oc.formErrors.business_postal_code }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" ng-model="oc.form.business.business_website" ng-change="oc.validateBusinessField('business_website')" ng-blur="oc.validateBusinessField('business_website')" ng-class="{'is-invalid': oc.formErrors.business_website}" placeholder="https://">
                        <div class="invalid-feedback" ng-if="oc.formErrors.business_website">@{{ oc.formErrors.business_website }}</div>
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
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_account_holder_name" ng-change="oc.form.bank.bank_account_holder_name=(oc.form.bank.bank_account_holder_name||'').replace(/[^A-Za-z ]/g,'').slice(0,256); oc.validateBankField('bank_account_holder_name')" ng-blur="oc.validateBankField('bank_account_holder_name')" ng-class="{'is-invalid': oc.formErrors.bank_account_holder_name}" minlength="3" maxlength="256" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.bank_account_holder_name">@{{ oc.formErrors.bank_account_holder_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_account_number" ng-change="oc.form.bank.bank_account_number=(oc.form.bank.bank_account_number||'').replace(/[^A-Za-z0-9]/g,'').slice(0,34); oc.validateBankField('bank_account_number')" ng-blur="oc.validateBankField('bank_account_number')" ng-class="{'is-invalid': oc.formErrors.bank_account_number}" minlength="8" maxlength="34" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.bank_account_number">@{{ oc.formErrors.bank_account_number }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IFSC Code *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_ifsc_code" ng-change="oc.form.bank.bank_ifsc_code=(oc.form.bank.bank_ifsc_code||'').replace(/[^A-Za-z0-9]/g,'').toUpperCase().slice(0,15); oc.validateBankField('bank_ifsc_code')" ng-blur="oc.validateBankField('bank_ifsc_code')" ng-class="{'is-invalid': oc.formErrors.bank_ifsc_code}" minlength="7" maxlength="15" pattern="[A-Za-z]{4}[A-Za-z0-9]{3,11}" title="Start with 4 letters, then letters/numbers (7-15 chars), e.g., ABCD0001234" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.bank_ifsc_code">@{{ oc.formErrors.bank_ifsc_code }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Name *</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_name" ng-change="oc.form.bank.bank_name=(oc.form.bank.bank_name||'').replace(/[^A-Za-z ]/g,'').slice(0,256); oc.validateBankField('bank_name')" ng-blur="oc.validateBankField('bank_name')" ng-class="{'is-invalid': oc.formErrors.bank_name}" minlength="3" maxlength="256" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.bank_name">@{{ oc.formErrors.bank_name }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" ng-model="oc.form.bank.bank_branch" ng-change="oc.form.bank.bank_branch=(oc.form.bank.bank_branch||'').replace(/[^A-Za-z0-9 ]/g,'').slice(0,255); oc.validateBankField('bank_branch')" ng-blur="oc.validateBankField('bank_branch')" ng-class="{'is-invalid': oc.formErrors.bank_branch}" maxlength="255">
                        <div class="invalid-feedback" ng-if="oc.formErrors.bank_branch">@{{ oc.formErrors.bank_branch }}</div>
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
                        <select class="form-select" ng-model="oc.form.kyc.kyc_document_type" ng-change="oc.validateKycField('kyc_document_type')" ng-blur="oc.validateKycField('kyc_document_type')" ng-class="{'is-invalid': oc.formErrors.kyc_document_type}" required>
                            <option value="">Select Document</option>
                            <option value="pan">PAN Card</option>
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="passport">Passport</option>
                            <option value="driving_license">Driving License</option>
                            <option value="business_license">Business License</option>
                        </select>
                        <div class="invalid-feedback" ng-if="oc.formErrors.kyc_document_type">@{{ oc.formErrors.kyc_document_type }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Document Number *</label>
                        <input type="text" class="form-control" ng-model="oc.form.kyc.kyc_document_number" ng-change="oc.form.kyc.kyc_document_number=(oc.form.kyc.kyc_document_number||'').replace(/[^A-Za-z0-9]/g,'').slice(0,50); oc.validateKycField('kyc_document_number')" ng-blur="oc.validateKycField('kyc_document_number')" ng-class="{'is-invalid': oc.formErrors.kyc_document_number}" minlength="4" maxlength="50" required>
                        <div class="invalid-feedback" ng-if="oc.formErrors.kyc_document_number">@{{ oc.formErrors.kyc_document_number }}</div>
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

