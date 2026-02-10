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
            app.controller('AcquirerRatesController', ['$http', '$scope', '$timeout', '$compile', function($http, $scope, $timeout, $compile) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                
                vm.rates = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {
                    filter_id: '',
                    filter_payment_mode: 'all',
                    filter_bank_code: '',
                    filter_bank_description: '',
                    filter_acquirer_name: 'all',
                    filter_account_id: '',
                    filter_account_description: '',
                    filter_sector: 'all',
                    filter_settlement_time_frame: '',
                    filter_settlement_time_of_day: '',
                    filter_fixed_fee_mdr: '',
                    filter_percentage_mdr: '',
                    filter_service_tax_rates: '',
                    filter_min_amount: '',
                    filter_max_amount: '',
                    filter_min_transaction_charge: '',
                    filter_max_transaction_charge: '',
                    filter_part_paid_id: ''
                };
                vm.loading = false;
                vm.submitting = false;
                vm.selectedRate = null;
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                vm.modalTitle = 'Create new entry';
                vm.isEditMode = false;

                // Column visibility
                vm.visibleColumns = {
                    id: { visible: true, label: 'Id' },
                    payment_mode: { visible: true, label: 'Payment Mode' },
                    bank_code: { visible: true, label: 'Bank Code' },
                    bank_description: { visible: true, label: 'Bank Description' },
                    acquirer_name: { visible: true, label: 'Acquirer Name' },
                    account_id: { visible: true, label: 'Account Id' },
                    account_description: { visible: true, label: 'Account Description' },
                    sector: { visible: true, label: 'Sector' },
                    settlement_time_frame: { visible: true, label: 'Settlement Time Frame' },
                    settlement_time_of_day: { visible: true, label: 'Settlement Time of Day' },
                    fixed_fee_mdr: { visible: true, label: 'Fixed Fee Mdr' },
                    percentage_mdr: { visible: true, label: 'Percentage Mdr' },
                    service_tax_rates: { visible: true, label: 'Service Tax Rates' },
                    min_amount: { visible: true, label: 'Min Amount' },
                    max_amount: { visible: true, label: 'Max Amount' },
                    min_transaction_charge: { visible: true, label: 'Min Transaction Charge' },
                    max_transaction_charge: { visible: true, label: 'Max Transaction Charge' },
                    is_enabled: { visible: true, label: 'Enabled?' },
                    part_paid_id: { visible: true, label: 'Part Paid Id' }
                };

                // Rate form
                vm.rateForm = {
                    acquirer_account_id: '',
                    payment_mode: '',
                    bank_code: '',
                    bank_description: '',
                    sector: '',
                    settlement_time_frame: 't+1',
                    settlement_time_of_day: '',
                    fixed_fee_mdr: 0,
                    percentage_mdr: 0,
                    service_tax_rates: 0,
                    min_amount: 0,
                    max_amount: 0,
                    min_transaction_charge: 0,
                    max_transaction_charge: 0,
                    is_enabled: true,
                    part_paid_id: ''
                };

                vm.acquirerAccounts = [];
                vm.acquirerNames = [];
                vm.paymentModes = [];
                vm.banks = [];
                vm.sectors = ['B2B', 'Education', 'E-commerce', 'Travel & Hospitality', 'Insurance', 'Utilities', 'Telecom', 'Healthcare', 'Others'];

                // Load acquirer accounts
                vm.loadAcquirerAccounts = function() {
                    $http.get('/admin/acquirer-rates/acquirer-accounts', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.acquirerAccounts = response.data.data;
                        }
                    });
                };

                // Load acquirer names
                vm.loadAcquirerNames = function() {
                    $http.get('/admin/acquirer-rates/acquirer-names', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.acquirerNames = response.data.data;
                        }
                    });
                };

                // Load payment modes
                vm.loadPaymentModes = function() {
                    $http.get('/admin/acquirer-rates/payment-modes', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.paymentModes = response.data.data;
                        }
                    });
                };

                // Load banks
                vm.loadBanks = function() {
                    $http.get('/admin/acquirer-rates/banks', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.banks = response.data.data;
                        }
                    });
                };

                // On acquirer change
                vm.onAcquirerChange = function() {
                    var account = vm.acquirerAccounts.find(function(a) {
                        return a.id == vm.rateForm.acquirer_account_id;
                    });
                    if (account) {
                        vm.rateForm.account_id = account.account_id;
                        if (!vm.rateForm.sector && account.sector) {
                            vm.rateForm.sector = account.sector;
                        }
                    }
                };

                // Load rates
                vm.loadRates = function(page) {
                    if (page) vm.pagination.current_page = page;
                    vm.loading = true;
                    
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    // Add filters
                    if (vm.filters.filter_payment_mode && vm.filters.filter_payment_mode !== 'all') {
                        params.payment_mode = vm.filters.filter_payment_mode;
                    }
                    if (vm.filters.filter_acquirer_name && vm.filters.filter_acquirer_name !== 'all') {
                        params.acquirer_name = vm.filters.filter_acquirer_name;
                    }
                    if (vm.filters.filter_sector && vm.filters.filter_sector !== 'all') {
                        params.sector = vm.filters.filter_sector;
                    }
                    if (vm.filters.filter_bank_code) {
                        params.bank_code = vm.filters.filter_bank_code;
                    }

                    // Add search
                    var searchTerms = [];
                    if (vm.filters.filter_id) searchTerms.push(vm.filters.filter_id);
                    if (vm.filters.filter_account_id) searchTerms.push(vm.filters.filter_account_id);
                    if (vm.filters.filter_account_description) searchTerms.push(vm.filters.filter_account_description);
                    if (vm.filters.filter_bank_description) searchTerms.push(vm.filters.filter_bank_description);
                    if (searchTerms.length > 0) {
                        params.search = searchTerms.join(' ');
                    }

                    $http.get('/admin/acquirer-rates/data', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.rates = response.data.data;
                            vm.pagination = response.data.pagination;
                        }
                        vm.loading = false;
                    }).catch(function(error) {
                        console.error('Error loading rates:', error);
                        vm.loading = false;
                    });
                };

                // Select rate
                vm.selectRate = function(rate) {
                    vm.selectedRate = rate;
                };

                // Clear filters
                vm.clearFilters = function() {
                    vm.filters = {
                        filter_id: '',
                        filter_payment_mode: 'all',
                        filter_bank_code: '',
                        filter_bank_description: '',
                        filter_acquirer_name: 'all',
                        filter_account_id: '',
                        filter_account_description: '',
                        filter_sector: 'all',
                        filter_settlement_time_frame: '',
                        filter_settlement_time_of_day: '',
                        filter_fixed_fee_mdr: '',
                        filter_percentage_mdr: '',
                        filter_service_tax_rates: '',
                        filter_min_amount: '',
                        filter_max_amount: '',
                        filter_min_transaction_charge: '',
                        filter_max_transaction_charge: '',
                        filter_part_paid_id: ''
                    };
                    vm.loadRates();
                };

                // Apply filters
                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadRates();
                };

                // Toggle column visibility
                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns[key]) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                // Reset view
                vm.resetView = function() {
                    vm.clearFilters();
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.loadRates();
                };

                // Sort
                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadRates();
                };

                // Open new modal
                vm.openNewModal = function() {
                    vm.modalTitle = 'Create new entry';
                    vm.isEditMode = false;
                    vm.selectedRate = null;
                    vm.rateForm = {
                        acquirer_account_id: '',
                        account_id: '',
                        payment_mode: '',
                        bank_code: '',
                        bank_description: '',
                        sector: '',
                        settlement_time_frame: 't+1',
                        settlement_time_of_day: '',
                        fixed_fee_mdr: 0,
                        percentage_mdr: 0,
                        service_tax_rates: 0,
                        min_amount: 0,
                        max_amount: 0,
                        min_transaction_charge: 0,
                        max_transaction_charge: 0,
                        is_enabled: true,
                        part_paid_id: ''
                    };
                    
                    var modalElement = document.getElementById('acquirerRateModal');
                    var modal = new bootstrap.Modal(modalElement);
                    
                    modalElement.addEventListener('shown.bs.modal', function() {
                        $timeout(function() {
                            if (!$scope.$$phase && !$scope.$root.$$phase) {
                                $scope.$apply();
                            }
                        }, 100);
                    }, { once: true });
                    
                    modal.show();
                };

                // Open edit modal
                vm.openEditModal = function(rate) {
                    if (rate) vm.selectedRate = rate;
                    if (!vm.selectedRate) return;

                    vm.modalTitle = 'Edit Acquirer Rate';
                    vm.isEditMode = true;
                    vm.rateForm = {
                        acquirer_account_id: vm.selectedRate.acquirer_account_id,
                        account_id: vm.selectedRate.account_id,
                        payment_mode: vm.selectedRate.payment_mode,
                        bank_code: vm.selectedRate.bank_code || '',
                        bank_description: vm.selectedRate.bank_description || '',
                        sector: vm.selectedRate.sector || '',
                        settlement_time_frame: vm.selectedRate.settlement_time_frame || 't+1',
                        settlement_time_of_day: vm.selectedRate.settlement_time_of_day || '',
                        fixed_fee_mdr: vm.selectedRate.fixed_fee_mdr || 0,
                        percentage_mdr: vm.selectedRate.percentage_mdr || 0,
                        service_tax_rates: vm.selectedRate.service_tax_rates || 0,
                        min_amount: vm.selectedRate.min_amount || 0,
                        max_amount: vm.selectedRate.max_amount || 0,
                        min_transaction_charge: vm.selectedRate.min_transaction_charge || 0,
                        max_transaction_charge: vm.selectedRate.max_transaction_charge || 0,
                        is_enabled: vm.selectedRate.is_enabled !== undefined ? vm.selectedRate.is_enabled : true,
                        part_paid_id: vm.selectedRate.part_paid_id || ''
                    };

                    var modalElement = document.getElementById('acquirerRateModal');
                    var modal = new bootstrap.Modal(modalElement);
                    
                    modalElement.addEventListener('shown.bs.modal', function() {
                        $timeout(function() {
                            if (!$scope.$$phase && !$scope.$root.$$phase) {
                                $scope.$apply();
                            }
                        }, 100);
                    }, { once: true });
                    
                    modal.show();
                };

                // Submit rate
                vm.submitRate = function() {
                    vm.submitting = true;

                    var url = vm.isEditMode 
                        ? '/admin/acquirer-rates/' + vm.selectedRate.id
                        : '/admin/acquirer-rates';
                    var method = vm.isEditMode ? 'PUT' : 'POST';

                    var data = angular.copy(vm.rateForm);
                    data.is_enabled = data.is_enabled === true || data.is_enabled === 'true';

                    $http({
                        method: method,
                        url: url,
                        data: data,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            } else {
                                alert(response.data.message);
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById('acquirerRateModal'));
                            modal.hide();
                            vm.loadRates();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Failed to save rate');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                        vm.submitting = false;
                    }).catch(function(error) {
                        console.error('Error saving rate:', error);
                        var errorMsg = error.data && error.data.message 
                            ? error.data.message 
                            : 'Failed to save rate';
                        if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat().join(', ');
                            errorMsg += ': ' + errors;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                        vm.submitting = false;
                    });
                };

                // Delete rate
                vm.deleteRate = function() {
                    if (!vm.selectedRate) return;
                    if (!confirm('Are you sure you want to delete this acquirer rate?')) return;

                    $http.delete('/admin/acquirer-rates/' + vm.selectedRate.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            } else {
                                alert(response.data.message);
                            }
                            vm.selectedRate = null;
                            vm.loadRates();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Failed to delete rate');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }).catch(function(error) {
                        console.error('Error deleting rate:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to delete rate', 'error');
                        } else {
                            alert('Failed to delete rate');
                        }
                    });
                };

                // Duplicate rate
                vm.duplicateRate = function() {
                    if (!vm.selectedRate) return;

                    $http.post('/admin/acquirer-rates/' + vm.selectedRate.id + '/duplicate', {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            } else {
                                alert(response.data.message);
                            }
                            vm.loadRates();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Failed to duplicate rate');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }).catch(function(error) {
                        console.error('Error duplicating rate:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to duplicate rate', 'error');
                        } else {
                            alert('Failed to duplicate rate');
                        }
                    });
                };

                // Initialize
                vm.loadAcquirerAccounts();
                vm.loadAcquirerNames();
                vm.loadPaymentModes();
                vm.loadBanks();
                vm.loadRates();
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

