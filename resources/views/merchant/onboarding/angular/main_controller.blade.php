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
            var app = angular.module('badlicashApp');
            app.controller('OnboardingController', ['$http', function($http) {
        var vm = this;
        vm.currentStep = {{ $currentStep }};
        vm.loading = false;
        vm.saving = false;
        vm.form = {
            business: {},
            bank: {},
            kyc: {}
        };

        vm.submitStep = function(step) {
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

