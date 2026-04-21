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
        vm.initialForm = {
            business: {
                company_name: @json($merchant->company_name ?? ''),
                business_type: @json($merchant->business_type ?? ''),
                business_phone: @json($merchant->business_phone ?? ''),
                business_email: @json($merchant->business_email ?? ''),
                business_address: @json($merchant->business_address ?? ''),
                business_city: @json($merchant->business_city ?? ''),
                business_state: @json($merchant->business_state ?? ''),
                business_country: @json($merchant->business_country ?? 'IN'),
                business_postal_code: @json($merchant->business_postal_code ?? ''),
                business_website: @json($merchant->business_website ?? '')
            },
            bank: {
                bank_account_holder_name: @json($merchant->bank_account_holder_name ?? ''),
                bank_account_number: @json($merchant->bank_account_number ?? ''),
                bank_ifsc_code: @json($merchant->bank_ifsc_code ?? ''),
                bank_name: @json($merchant->bank_name ?? ''),
                bank_branch: @json($merchant->bank_branch ?? '')
            },
            kyc: {
                kyc_document_type: @json($merchant->kyc_document_type ?? ''),
                kyc_document_number: @json($merchant->kyc_document_number ?? '')
            }
        };
        vm.form = {
            business: angular.copy(vm.initialForm.business),
            bank: angular.copy(vm.initialForm.bank),
            kyc: angular.copy(vm.initialForm.kyc)
        };
        vm.locationMap = {};
        vm.availableStates = [];
        vm.availableCities = [];

        vm.validateBusinessField = function(field) {
            if (!vm.formErrors) vm.formErrors = {};
            delete vm.formErrors[field];
            var f = vm.form.business || {};
            var value = String(f[field] == null ? '' : f[field]).trim();

            switch (field) {
                case 'company_name':
                    if (!value) vm.formErrors.company_name = 'Company name is required.';
                    else if (value.length < 3) vm.formErrors.company_name = 'Company name must be at least 3 characters.';
                    else if (value.length > 256) vm.formErrors.company_name = 'Company name may not be greater than 256 characters.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.company_name = 'Company name may contain only letters and spaces.';
                    break;
                case 'business_type':
                    if (!value) vm.formErrors.business_type = 'Business type is required.';
                    break;
                case 'business_phone':
                    if (!value) vm.formErrors.business_phone = 'Business phone is required.';
                    else if (!/^\+?[0-9]{6,15}$/.test(value)) vm.formErrors.business_phone = 'Business phone must contain only numbers and optional + (6-15 digits).';
                    break;
                case 'business_email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) vm.formErrors.business_email = 'Business email must be a valid email address.';
                    break;
                case 'business_address':
                    if (!value) vm.formErrors.business_address = 'Business address is required.';
                    else if (value.length < 3) vm.formErrors.business_address = 'Business address must be at least 3 characters.';
                    else if (value.length > 500) vm.formErrors.business_address = 'Business address may not be greater than 500 characters.';
                    break;
                case 'business_city':
                    if (!value) vm.formErrors.business_city = 'City is required.';
                    else if (vm.availableCities.length && vm.availableCities.indexOf(value) === -1) vm.formErrors.business_city = 'Please select a valid city.';
                    break;
                case 'business_state':
                    if (!value) vm.formErrors.business_state = 'State is required.';
                    else if (vm.availableStates.length && vm.availableStates.indexOf(value) === -1) vm.formErrors.business_state = 'Please select a valid state.';
                    break;
                case 'business_postal_code':
                    if (!value) vm.formErrors.business_postal_code = 'Postal code is required.';
                    else if (!/^[A-Za-z0-9]+$/.test(value)) vm.formErrors.business_postal_code = 'Postal code may contain only letters and numbers.';
                    else if (value.length < 4 || value.length > 16) vm.formErrors.business_postal_code = 'Postal code must be 4 to 16 characters.';
                    break;
                case 'business_website':
                    if (value) {
                        try {
                            var url = new URL(value);
                            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                                vm.formErrors.business_website = 'Website must be a valid URL.';
                            }
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
                'business_type',
                'business_phone',
                'business_email',
                'business_address',
                'business_city',
                'business_state',
                'business_postal_code',
                'business_website'
            ].forEach(vm.validateBusinessField);
            return Object.keys(vm.formErrors).length === 0;
        };

        vm.onStateChange = function() {
            var state = (vm.form.business && vm.form.business.business_state) || '';
            vm.availableCities = state && vm.locationMap[state] ? vm.locationMap[state] : [];
            if (vm.availableCities.indexOf(vm.form.business.business_city) === -1) {
                vm.form.business.business_city = '';
            }
            vm.validateBusinessField('business_state');
            vm.validateBusinessField('business_city');
        };

        function isStateCityMap(obj) {
            if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return false;
            var keys = Object.keys(obj);
            if (!keys.length) return false;
            var sample = obj[keys[0]];
            return Array.isArray(sample);
        }

        vm.loadLocations = function() {
            function applyLocationPayload(res) {
                var data = (res.data && res.data.success && res.data.data) ? res.data.data : {};
                // Support both shapes:
                // 1) { India: { State: [City] } }
                // 2) { State: [City] }
                var country = null;
                if (isStateCityMap(data)) {
                    country = data;
                } else {
                    country = data.India || data['India'] || data.india || data['INDIA'] || null;
                    if (!country) {
                        var keys = Object.keys(data || {});
                        country = keys.length ? data[keys[0]] : {};
                    }
                }
                vm.locationMap = country || {};
                vm.availableStates = Object.keys(vm.locationMap);
                if (!vm.form.business.business_state) vm.form.business.business_state = '';
                vm.onStateChange();
            }

            return $http.get('/merchant/onboarding/locations').then(applyLocationPayload, function() {
                return $http.get('/signup/locations').then(applyLocationPayload, function() {
                    vm.locationMap = {};
                    vm.availableStates = [];
                    vm.availableCities = [];
                });
            });
        };

        vm.validateBankField = function(field) {
            if (!vm.formErrors) vm.formErrors = {};
            delete vm.formErrors[field];
            var f = vm.form.bank || {};
            var value = String(f[field] == null ? '' : f[field]).trim();

            switch (field) {
                case 'bank_account_holder_name':
                    if (!value) vm.formErrors.bank_account_holder_name = 'Account holder name is required.';
                    else if (value.length < 3) vm.formErrors.bank_account_holder_name = 'Account holder name must be at least 3 characters.';
                    else if (value.length > 256) vm.formErrors.bank_account_holder_name = 'Account holder name may not be greater than 256 characters.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.bank_account_holder_name = 'Account holder name may contain only letters and spaces.';
                    break;
                case 'bank_account_number':
                    if (!value) vm.formErrors.bank_account_number = 'Account number is required.';
                    else if (value.length < 8 || value.length > 34) vm.formErrors.bank_account_number = 'Account number must be 8 to 34 characters.';
                    else if (!/^[A-Za-z0-9]+$/.test(value)) vm.formErrors.bank_account_number = 'Account number may contain only letters and numbers.';
                    break;
                case 'bank_ifsc_code':
                    if (!value) vm.formErrors.bank_ifsc_code = 'IFSC code is required.';
                    else if (value.length < 7 || value.length > 15) vm.formErrors.bank_ifsc_code = 'IFSC code must be 7 to 15 characters.';
                    else if (!/^[A-Za-z]{4}[A-Za-z0-9]{3,11}$/.test(value)) vm.formErrors.bank_ifsc_code = 'IFSC code must start with 4 letters followed by letters or numbers (e.g., ABCD0001234).';
                    break;
                case 'bank_name':
                    if (!value) vm.formErrors.bank_name = 'Bank name is required.';
                    else if (value.length < 3) vm.formErrors.bank_name = 'Bank name must be at least 3 characters.';
                    else if (value.length > 256) vm.formErrors.bank_name = 'Bank name may not be greater than 256 characters.';
                    else if (!/^[A-Za-z ]+$/.test(value)) vm.formErrors.bank_name = 'Bank name may contain only letters and spaces.';
                    break;
                case 'bank_branch':
                    if (value && value.length < 3) vm.formErrors.bank_branch = 'Branch must be at least 3 characters.';
                    else if (value && !/^[A-Za-z0-9 ]+$/.test(value)) vm.formErrors.bank_branch = 'Branch may contain only letters, numbers, and spaces.';
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
                    else if (value.length < 4 || value.length > 50) vm.formErrors.kyc_document_number = 'Document number must be 4 to 50 characters.';
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
                var business = vm.form.business || {};
                // Append all expected fields explicitly so backend validation messages are accurate.
                formData.append('company_name', (business.company_name || '').toString().trim());
                formData.append('business_type', (business.business_type || '').toString().trim());
                formData.append('business_phone', (business.business_phone || '').toString().trim());
                formData.append('business_email', (business.business_email || '').toString().trim());
                formData.append('business_address', (business.business_address || '').toString().trim());
                formData.append('business_state', (business.business_state || '').toString().trim());
                formData.append('business_city', (business.business_city || '').toString().trim());
                formData.append('business_postal_code', (business.business_postal_code || '').toString().trim());
                formData.append('business_website', (business.business_website || '').toString().trim());
                formData.append('business_country', (business.business_country || 'IN').toString().trim());
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
        vm.loadLocations();
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

