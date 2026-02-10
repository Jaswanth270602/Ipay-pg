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
            
            // File model directive for file input
            app.directive('fileModel', ['$parse', function ($parse) {
                return {
                    restrict: 'A',
                    link: function(scope, element, attrs) {
                        var model = $parse(attrs.fileModel);
                        var modelSetter = model.assign;
                        
                        element.bind('change', function(){
                            scope.$apply(function(){
                                modelSetter(scope, element[0].files[0]);
                            });
                        });
                    }
                };
            }]);
            
            app.controller('AcquirerAccountUploadController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                
                vm.paymentModes = [];
                vm.allBanks = [];
                vm.filteredBanks = [];
                vm.jobs = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.loading = false;
                vm.uploading = false;
                vm.showBankDropdown = false;
                vm.bankSearch = '';
                vm.showBankError = false;
                
                vm.uploadForm = {
                    payment_mode: '',
                    bank_codes: [],
                    file: null
                };
                
                vm.filters = {
                    filter_job_id: '',
                    filter_job_name: '',
                    filter_status: 'all',
                    filter_started_from: '',
                    filter_started_to: '',
                    filter_finished_from: '',
                    filter_finished_to: ''
                };

                // Load payment modes
                vm.loadPaymentModes = function() {
                    $http.get('/admin/acquirer-account-upload/payment-modes', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.paymentModes = response.data.data;
                        }
                    });
                };

                // Load banks by payment mode
                vm.loadBanks = function(paymentMode, callback) {
                    var params = {};
                    if (paymentMode) {
                        params.payment_mode = paymentMode;
                    }
                    
                    $http.get('/admin/acquirer-account-upload/banks', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.allBanks = response.data.data;
                            vm.filteredBanks = response.data.data;
                            if (callback && typeof callback === 'function') {
                                callback();
                            }
                        }
                    });
                };

                // Payment mode change handler
                vm.onPaymentModeChange = function() {
                    if (vm.uploadForm.payment_mode) {
                        // Clear previous selections
                        vm.uploadForm.bank_codes = [];
                        // Load banks and auto-select all
                        vm.loadBanks(vm.uploadForm.payment_mode, function() {
                            $timeout(function() {
                                vm.selectAllBanks();
                            }, 100);
                        });
                    } else {
                        vm.allBanks = [];
                        vm.filteredBanks = [];
                        vm.uploadForm.bank_codes = [];
                    }
                };

                // Toggle bank dropdown
                vm.toggleBankDropdown = function() {
                    vm.showBankDropdown = !vm.showBankDropdown;
                };

                // Close bank dropdown when clicking outside
                $scope.$on('$locationChangeStart', function() {
                    vm.showBankDropdown = false;
                });

                // Filter banks by search
                $scope.$watch('aauc.bankSearch', function(newVal) {
                    if (!newVal) {
                        vm.filteredBanks = vm.allBanks;
                    } else {
                        var search = newVal.toLowerCase();
                        vm.filteredBanks = vm.allBanks.filter(function(bank) {
                            return bank.name.toLowerCase().indexOf(search) !== -1 || 
                                   bank.code.toLowerCase().indexOf(search) !== -1;
                        });
                    }
                });

                // Check if bank is selected
                vm.isBankSelected = function(bankCode) {
                    return vm.uploadForm.bank_codes.indexOf(bankCode) !== -1;
                };

                // Toggle bank code selection
                vm.toggleBankCode = function(bankCode) {
                    var index = vm.uploadForm.bank_codes.indexOf(bankCode);
                    if (index === -1) {
                        vm.uploadForm.bank_codes.push(bankCode);
                    } else {
                        vm.uploadForm.bank_codes.splice(index, 1);
                    }
                };

                // Remove bank code
                vm.removeBankCode = function(bankCode) {
                    var index = vm.uploadForm.bank_codes.indexOf(bankCode);
                    if (index !== -1) {
                        vm.uploadForm.bank_codes.splice(index, 1);
                    }
                };

                // Select all banks
                vm.selectAllBanks = function() {
                    var allCodes = vm.filteredBanks.map(function(bank) {
                        return bank.code;
                    });
                    // Add all filtered banks to selection (avoid duplicates)
                    allCodes.forEach(function(code) {
                        if (vm.uploadForm.bank_codes.indexOf(code) === -1) {
                            vm.uploadForm.bank_codes.push(code);
                        }
                    });
                    $scope.$apply();
                };

                // Deselect all banks
                vm.deselectAllBanks = function() {
                    // Remove only the filtered banks from selection
                    var filteredCodes = vm.filteredBanks.map(function(bank) {
                        return bank.code;
                    });
                    vm.uploadForm.bank_codes = vm.uploadForm.bank_codes.filter(function(code) {
                        return filteredCodes.indexOf(code) === -1;
                    });
                    $scope.$apply();
                };

                // Get bank name by code
                vm.getBankName = function(bankCode) {
                    var bank = vm.allBanks.find(function(b) {
                        return b.code === bankCode;
                    });
                    return bank ? bank.name : bankCode;
                };

                // Remove from array helper
                vm.removeFromArray = function(arr, item) {
                    var index = arr.indexOf(item);
                    if (index !== -1) {
                        arr.splice(index, 1);
                    }
                    return arr;
                };

                // Upload file
                vm.uploadFile = function() {
                    if (!vm.uploadForm.file) {
                        if (typeof showToast === 'function') {
                            showToast('Please select a file to upload', 'error');
                        } else {
                            alert('Please select a file to upload');
                        }
                        return;
                    }

                    vm.uploading = true;
                    vm.showBankError = false;

                    var formData = new FormData();
                    formData.append('file', vm.uploadForm.file);
                    formData.append('payment_mode', vm.uploadForm.payment_mode || '');
                    formData.append('bank_codes', JSON.stringify(vm.uploadForm.bank_codes));

                    $http({
                        method: 'POST',
                        url: '/admin/acquirer-account-upload/upload',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': undefined
                        },
                        transformRequest: angular.identity
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            } else {
                                alert(response.data.message);
                            }
                            // Reset form
                            vm.uploadForm = {
                                payment_mode: '',
                                bank_codes: [],
                                file: null
                            };
                            // Reset file input
                            var fileInput = document.querySelector('input[type="file"]');
                            if (fileInput) {
                                fileInput.value = '';
                            }
                            // Reload jobs
                            vm.loadJobs();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Failed to upload file');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                        vm.uploading = false;
                    }).catch(function(error) {
                        console.error('Upload error:', error);
                        var errorMsg = error.data && error.data.message 
                            ? error.data.message 
                            : 'Failed to upload file';
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                        vm.uploading = false;
                    });
                };

                // Load jobs
                vm.loadJobs = function(page) {
                    if (page) vm.pagination.current_page = page;
                    vm.loading = true;
                    
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    // Add filters
                    if (vm.filters.filter_job_id) {
                        params.filter_job_id = vm.filters.filter_job_id;
                    }
                    if (vm.filters.filter_job_name) {
                        params.filter_job_name = vm.filters.filter_job_name;
                    }
                    if (vm.filters.filter_status && vm.filters.filter_status !== 'all') {
                        params.filter_status = vm.filters.filter_status;
                    }
                    if (vm.filters.filter_started_from) {
                        params.filter_started_from = vm.filters.filter_started_from;
                    }
                    if (vm.filters.filter_started_to) {
                        params.filter_started_to = vm.filters.filter_started_to;
                    }
                    if (vm.filters.filter_finished_from) {
                        params.filter_finished_from = vm.filters.filter_finished_from;
                    }
                    if (vm.filters.filter_finished_to) {
                        params.filter_finished_to = vm.filters.filter_finished_to;
                    }

                    $http.get('/admin/acquirer-account-upload/jobs', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.jobs = response.data.data;
                            vm.pagination = response.data.pagination;
                        }
                        vm.loading = false;
                    }).catch(function(error) {
                        console.error('Error loading jobs:', error);
                        vm.loading = false;
                    });
                };

                // Apply filters
                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadJobs();
                };

                // Clear filters
                vm.clearFilters = function() {
                    vm.filters = {
                        filter_job_id: '',
                        filter_job_name: '',
                        filter_status: 'all',
                        filter_started_from: '',
                        filter_started_to: '',
                        filter_finished_from: '',
                        filter_finished_to: ''
                    };
                    vm.loadJobs();
                };

                // Download status file
                vm.downloadStatusFile = function(jobId) {
                    window.location.href = '/admin/acquirer-account-upload/download-status/' + jobId;
                };

                // Download template
                vm.downloadTemplate = function() {
                    window.location.href = '/admin/acquirer-account-upload/download-template';
                };

                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.position-relative')) {
                        vm.showBankDropdown = false;
                        $scope.$apply();
                    }
                });

                // Initialize
                vm.loadPaymentModes();
                vm.loadBanks();
                vm.loadJobs();

                // Auto-refresh jobs every 5 seconds if there are processing jobs
                setInterval(function() {
                    var hasProcessing = vm.jobs.some(function(job) {
                        return job.status === 'processing' || job.status === 'pending';
                    });
                    if (hasProcessing) {
                        vm.loadJobs();
                    }
                }, 5000);
            }]);
        } catch(e) {
            console.error('Error registering controller:', e);
            setTimeout(registerController, 100);
        }
    }
    registerController();
})();
</script>
@endpush

