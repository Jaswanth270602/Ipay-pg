@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('ipayApp');
            app.controller('OnboardingController', ['$http', function($http) {
        var vm = this;
        vm.currentStep = {{ $currentStep }};
        vm.loading = false;
        vm.saving = false;
        vm.formErrors = {};
        vm.form = {
            business: {},
            bank: {},
            kyc: {}
        };

        vm.validateBusinessField = function(field) {
            if (!vm.formErrors) vm.formErrors = {};
            delete vm.formErrors[field];
            var f = vm.form.business || {};
            var value = String(f[field] == null ? '' : f[field]).trim();

            switch (field) {
                case 'company_name':
                    if (!value) vm.formErrors.company_name = 'Company name is required.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.company_name = 'Company name may contain only letters and spaces.';
                    break;
                case 'business_phone':
                    if (!value) vm.formErrors.business_phone = 'Business phone is required.';
                    else if (!/^[+0-9]+$/.test(value)) vm.formErrors.business_phone = 'Business phone may contain only digits and +.';
                    break;
                case 'business_email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) vm.formErrors.business_email = 'Business email must be a valid email address.';
                    break;
                case 'business_city':
                    if (!value) vm.formErrors.business_city = 'City is required.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.business_city = 'City may contain only letters and spaces.';
                    break;
                case 'business_state':
                    if (!value) vm.formErrors.business_state = 'State is required.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.business_state = 'State may contain only letters and spaces.';
                    break;
                case 'business_postal_code':
                    if (!value) vm.formErrors.business_postal_code = 'Postal code is required.';
                    else if (!/^[0-9]+$/.test(value)) vm.formErrors.business_postal_code = 'Postal code may contain only numbers.';
                    break;
                case 'business_website':
                    if (value) {
                        try {
                            new URL(value);
                        } catch (e) {
                            vm.formErrors.business_website = 'Website must be a valid URL.';
                        }
                    }
                    break;
                default:
                    break;
            }
        };

        vm.validateBusinessStep = function() {
            vm.formErrors = {};
            [
                'company_name',
                'business_phone',
                'business_email',
                'business_city',
                'business_state',
                'business_postal_code',
                'business_website'
            ].forEach(vm.validateBusinessField);
            return Object.keys(vm.formErrors).length === 0;
        };

        vm.validateBankField = function(field) {
            if (!vm.formErrors) vm.formErrors = {};
            delete vm.formErrors[field];
            var f = vm.form.bank || {};
            var value = String(f[field] == null ? '' : f[field]).trim();

            switch (field) {
                case 'bank_account_holder_name':
                    if (!value) vm.formErrors.bank_account_holder_name = 'Account holder name is required.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.bank_account_holder_name = 'Account holder name may contain only letters and spaces.';
                    break;
                case 'bank_account_number':
                    if (!value) vm.formErrors.bank_account_number = 'Account number is required.';
                    else if (!/^[A-Za-z0-9]+$/.test(value)) vm.formErrors.bank_account_number = 'Account number may contain only letters and numbers.';
                    break;
                case 'bank_ifsc_code':
                    if (!value) vm.formErrors.bank_ifsc_code = 'IFSC code is required.';
                    else if (!/^[A-Za-z0-9]+$/.test(value)) vm.formErrors.bank_ifsc_code = 'IFSC code may contain only letters and numbers.';
                    break;
                case 'bank_name':
                    if (!value) vm.formErrors.bank_name = 'Bank name is required.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.bank_name = 'Bank name may contain only letters and spaces.';
                    break;
                case 'bank_branch':
                    if (value && !/^[A-Za-z0-9 ]+$/.test(value)) vm.formErrors.bank_branch = 'Branch may contain only letters, numbers, and spaces.';
                    break;
                default:
                    break;
            }
        };

        vm.validateBankStep = function() {
            vm.formErrors = {};
            [
                'bank_account_holder_name',
                'bank_account_number',
                'bank_ifsc_code',
                'bank_name',
                'bank_branch'
            ].forEach(vm.validateBankField);
            return Object.keys(vm.formErrors).length === 0;
        };

        vm.validateKycField = function(field) {
            if (!vm.formErrors) vm.formErrors = {};
            delete vm.formErrors[field];
            var f = vm.form.kyc || {};
            var value = String(f[field] == null ? '' : f[field]).trim();

            switch (field) {
                case 'kyc_document_type':
                    if (!value) vm.formErrors.kyc_document_type = 'Document type is required.';
                    break;
                case 'kyc_document_number':
                    if (!value) vm.formErrors.kyc_document_number = 'Document number is required.';
                    else if (!/^[A-Za-z0-9]+$/.test(value)) vm.formErrors.kyc_document_number = 'Document number may contain only letters and numbers.';
                    break;
                default:
                    break;
            }
        };

        vm.validateKycStep = function() {
            vm.formErrors = {};
            ['kyc_document_type', 'kyc_document_number'].forEach(vm.validateKycField);
            return Object.keys(vm.formErrors).length === 0;
        };

        vm.submitStep = function(step) {
            if (step === 1 && !vm.validateBusinessStep()) {
                return;
            }
            if (step === 2 && !vm.validateBankStep()) {
                return;
            }
            if (step === 3 && !vm.validateKycStep()) {
                return;
            }
            vm.saving = true;
            var formData = new FormData();
            
            if (step === 1) {
                Object.keys(vm.form.business).forEach(function(key) {
                    if (vm.form.business[key]) {
                        formData.append(key, vm.form.business[key]);
                    }
                });
            } else if (step === 2) {
                Object.keys(vm.form.bank).forEach(function(key) {
                    if (vm.form.bank[key]) {
                        formData.append(key, vm.form.bank[key]);
                    }
                });
            } else if (step === 3) {
                Object.keys(vm.form.kyc).forEach(function(key) {
                    if (vm.form.kyc[key]) {
                        formData.append(key, vm.form.kyc[key]);
                    }
                });
                var fileInput = document.getElementById('kycDocument');
                if (fileInput && fileInput.files[0]) {
                    formData.append('kyc_document', fileInput.files[0]);
                }
            }

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/onboarding/step/' + step, formData, {
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': undefined
                },
                transformRequest: angular.identity
            }).then(function(response) {
                vm.saving = false;
                if (response.data.success) {
                    vm.formErrors = {};
                    if (response.data.next_step) {
                        vm.currentStep = response.data.next_step;
                        alert('Step completed successfully!');
                    } else {
                        alert('Onboarding submitted successfully! Our team will review your application.');
                        window.location.href = '/dashboard';
                    }
                }
            }, function(error) {
                vm.saving = false;
                vm.formErrors = {};
                if (error.data && error.data.errors) {
                    Object.keys(error.data.errors).forEach(function(key) {
                        vm.formErrors[key] = (error.data.errors[key] || [])[0] || 'Invalid value';
                    });
                }
                alert(error.data?.message || 'Failed to save step');
            });
        };
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', registerController);
        } else {
            registerController();
        }
    }
})();
</script>
@endpush

