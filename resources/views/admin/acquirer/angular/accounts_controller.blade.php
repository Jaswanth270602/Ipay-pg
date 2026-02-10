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
            app.controller('AdminAcquirerAccountsController', ['$http', '$scope', '$timeout', '$compile', function($http, $scope, $timeout, $compile) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                
                // Full list of acquirer names
                var defaultAcquirerNames = [
                    'A2Pay', 'ABCMoney', 'AbhiJack', 'AblePay', 'Accosis', 'AccureUpi', 'AfrimoneyDrc', 
                    'Aggrepay', 'AggrePayDirect', 'AirPay', 'AirtelRwanda', 'AirtelRwandaV2', 'AirtelUpi', 
                    'AKGPay', 'ALLAHABAD', 'AmazonPay', 'ApexPayUni', 'ATOM', 'AtomV2', 'AXIS', 'AxisPG', 
                    'AXISUPI', 'AxisUPICollect', 'AxisUPIV2', 'BankOfBaroda', 'BANKONE', 'BBPSAxis', 'BenePay', 
                    'BennuPay', 'BHARTIPAY', 'Billdesk', 'BilldeskCards', 'BillDeskUPI', 'BillDeskV2', 'BringePays', 
                    'CamsPay', 'CASHe', 'CashFree', 'CashFreev2', 'CCAvenue', 'CCAvenuev2', 'ChalPayUpi', 
                    'CorequestUpi', 'CryptoPool', 'CyberSource', 'Dizibizpay', 'Easebuzz', 'EazyPay', 'EazyPaymentz', 
                    'EBS', 'Edviron', 'Enkash', 'EquitasBharatQR', 'EquitasUPI', 'Ewire', 'Federal', 'FederalUpi', 
                    'Feipay', 'FinoUpi', 'FirstData', 'Flakpay', 'FreeCharge', 'FreechargeUPI', 'FreeChargev2', 
                    'FSSDC', 'Gizmope', 'GrezPay', 'HDFC', 'HDFCBANK', 'HDFCBQR', 'HdfcNonSeamless', 'HDFCUPI', 
                    'Heksa', 'Hitachi', 'Hyalpha', 'ICICI', 'ICICIBharatQR', 'ICICIUPI', 'IDFC', 'IDFCUpi', 
                    'IndianBank', 'Indiaonlinepay', 'Indiconnect', 'Indusspay', 'IndusUpi', 'InnopayUPI', 'IPaisa', 
                    'IppoPayUPI', 'ISERVEU', 'ISGPAY', 'ISGPAYV2', 'iSmartPay', 'JCPays', 'Jeetoabhi', 'JigsPayP2P', 
                    'JioPay', 'JodetxUpi', 'JssMoney', 'JusPayUpi', 'Kopay', 'KotakAllPay', 'KotakCard', 'KotakDCEMI', 
                    'KotakUpi', 'LazyPay', 'LazyPayEmi', 'LetsPe', 'LevinPay', 'LightspeedPay', 'Paytm', 'Switch', 
                    'SBI', 'Razorpay', 'razorpay', 'razorpay_test', 'razorpay_live', 'PayU',
                    // Yapily sandbox acquirer entries
                    'Yapily', 'YapilyTest', 'YapilyLive'
                ];
                
                vm.accounts = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {
                    filter_id: '',
                    filter_account_id: '',
                    filter_acquirer_name: 'all',
                    filter_team: '',
                    filter_description: '',
                    filter_whitelist_url: '',
                    filter_mode: 'all',
                    filter_sector: 'all',
                    filter_hdfc_me_code: '',
                    filter_settlement_account_name: '',
                    filter_email_ids: '',
                    filter_live_request_url: '',
                    filter_live_query_url: '',
                    filter_live_refund_url: '',
                    filter_test_request_url: '',
                    filter_test_query_url: '',
                    filter_test_refund_url: '',
                    filter_merchants: ''
                };
                vm.loading = false;
                vm.submitting = false;
                vm.selectedAccount = null;
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                vm.modalTitle = 'Create new entry';
                vm.isEditMode = false;

                // Column visibility
                vm.visibleColumns = {
                    id: { visible: true, label: 'Id' },
                    account_id: { visible: true, label: 'Account Id' },
                    acquirer_name: { visible: true, label: 'Acquirer Name' },
                    team: { visible: true, label: 'Team' },
                    description: { visible: true, label: 'Description' },
                    whitelist_url: { visible: true, label: 'Whitelist Url' },
                    mode: { visible: true, label: 'Mode' },
                    sector: { visible: true, label: 'Sector' },
                    hdfc_me_code: { visible: true, label: 'Hdfc Me Code' },
                    settlement_account_name: { visible: true, label: 'Settlement Account Name' },
                    refund_allowed: { visible: true, label: 'Refund Allowed' },
                    settlements_to_be_created: { visible: true, label: 'Settlements to be created for this TID ?' },
                    mask_pii: { visible: true, label: 'Mask Pii' },
                    email_ids: { visible: true, label: 'Email Ids' },
                    live_request_url: { visible: false, label: 'Live Request URL' },
                    live_query_url: { visible: false, label: 'Live Query URL' },
                    live_refund_url: { visible: false, label: 'Live Refund URL' },
                    test_request_url: { visible: false, label: 'Test Request URL' },
                    test_query_url: { visible: false, label: 'Test Query URL' },
                    test_refund_url: { visible: false, label: 'Test Refund URL' },
                    merchants: { visible: true, label: 'Merchants' }
                };

                // Account form
                vm.accountForm = {
                    account_id: '',
                    acquirer_name: '',
                    team: '',
                    description: '',
                    whitelist_url: '',
                    mode: 'TEST',
                    sector: '',
                    hdfc_me_code: '',
                    settlement_account_name: '',
                    refund_allowed: true,
                    settlements_to_be_created: true,
                    mask_pii: false,
                    email_ids: '',
                    secret_key: '',
                    salt: '',
                    additional_key_1: '',
                    additional_key_2: '',
                    additional_key_3: '',
                    additional_key_data: '',
                    live_request_url: '',
                    live_query_url: '',
                    live_refund_url: '',
                    test_request_url: '',
                    test_query_url: '',
                    test_refund_url: '',
                    nodal_account: '',
                    merchant_ids: []
                };

                // Initialize with all acquirer names
                vm.acquirerNames = defaultAcquirerNames.slice(); // Use copy of default list
                vm.sectors = ['B2B', 'Education', 'E-commerce', 'Travel & Hospitality', 'Insurance', 'Utilities', 'Telecom', 'Healthcare', 'Others'];
                vm.merchants = [];
                
                // Button text getter
                vm.getSaveButtonText = function() {
                    return vm.submitting ? 'Saving...' : 'Save';
                };

                // Load acquirer names
                vm.loadAcquirerNames = function() {
                    // Always start with default names to ensure Razorpay is included
                    vm.acquirerNames = defaultAcquirerNames.slice();
                    console.log('Initial acquirer names count:', vm.acquirerNames.length);
                    console.log('Razorpay in list?', vm.acquirerNames.indexOf('Razorpay') !== -1 || vm.acquirerNames.indexOf('razorpay') !== -1);
                    
                    $http.get('/admin/acquirer-accounts/acquirer-names', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        console.log('API response received:', response.data);
                        if (response.data && response.data.success && response.data.data && response.data.data.length > 0) {
                            // Merge API response with defaults (API may have additional names from database)
                            var apiNames = response.data.data;
                            var merged = defaultAcquirerNames.slice();
                            // Add any new names from API that aren't in defaults
                            apiNames.forEach(function(name) {
                                if (merged.indexOf(name) === -1) {
                                    merged.push(name);
                                }
                            });
                            // Sort and set
                            vm.acquirerNames = merged.sort();
                            console.log('After merge, acquirer names count:', vm.acquirerNames.length);
                            console.log('Razorpay variants:', vm.acquirerNames.filter(function(n) { return n.toLowerCase().indexOf('razorpay') !== -1; }));
                        } else {
                            // If API returns empty, keep defaults
                            vm.acquirerNames = defaultAcquirerNames.slice();
                            console.log('API returned empty, using defaults');
                        }
                        // Force Angular update
                        $timeout(function() {
                            try {
                                $scope.$apply();
                            } catch(e) {
                                // If apply fails (e.g., already in digest), Angular will update on next digest
                            }
                        }, 0);
                    }).catch(function(error) {
                        console.error('Error loading acquirer names:', error);
                        console.log('Using default names due to error');
                        // Keep default names on error
                        vm.acquirerNames = defaultAcquirerNames.slice();
                        $timeout(function() {
                            try {
                                $scope.$apply();
                            } catch(e) {
                                // If apply fails (e.g., already in digest), Angular will update on next digest
                            }
                        }, 0);
                    });
                };

                // Load merchants
                vm.loadMerchants = function() {
                    $http.get('/admin/acquirer-accounts/merchants', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data && response.data.success) {
                            vm.merchants = response.data.data || [];
                        } else {
                            vm.merchants = [];
                        }
                    }).catch(function(error) {
                        console.error('Error loading merchants:', error);
                        vm.merchants = [];
                    });
                };

                // Load accounts
                vm.loadAccounts = function(page) {
                    if (page) vm.pagination.current_page = page;
                    vm.loading = true;
                    
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    // Add filters
                    if (vm.filters.filter_acquirer_name && vm.filters.filter_acquirer_name !== 'all') {
                        params.acquirer_name = vm.filters.filter_acquirer_name;
                    }
                    if (vm.filters.filter_mode && vm.filters.filter_mode !== 'all') {
                        params.mode = vm.filters.filter_mode;
                    }
                    if (vm.filters.filter_sector && vm.filters.filter_sector !== 'all') {
                        params.sector = vm.filters.filter_sector;
                    }
                    if (vm.filters.filter_team) {
                        params.team = vm.filters.filter_team;
                    }

                    // Add search
                    var searchTerms = [];
                    if (vm.filters.filter_id) searchTerms.push(vm.filters.filter_id);
                    if (vm.filters.filter_account_id) searchTerms.push(vm.filters.filter_account_id);
                    if (vm.filters.filter_description) searchTerms.push(vm.filters.filter_description);
                    if (vm.filters.filter_hdfc_me_code) searchTerms.push(vm.filters.filter_hdfc_me_code);
                    if (searchTerms.length > 0) {
                        params.search = searchTerms.join(' ');
                    }

                    $http.get('/admin/acquirer-accounts/data', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.accounts = response.data.data;
                            vm.pagination = response.data.pagination;
                            
                            // Debug: Check if keys are in the response
                            if (vm.accounts && vm.accounts.length > 0) {
                                var razorpayAccount = vm.accounts.find(function(acc) {
                                    return acc.acquirer_name && acc.acquirer_name.toLowerCase().indexOf('razorpay') !== -1;
                                });
                                if (razorpayAccount) {
                                    console.log('Razorpay account in response:', {
                                        id: razorpayAccount.id,
                                        additional_key_1: razorpayAccount.additional_key_1 ? 'SET (' + (razorpayAccount.additional_key_1.length || 0) + ' chars)' : 'EMPTY',
                                        secret_key: razorpayAccount.secret_key ? 'SET (' + (razorpayAccount.secret_key.length || 0) + ' chars)' : 'EMPTY'
                                    });
                                }
                            }
                        }
                        vm.loading = false;
                    }).catch(function(error) {
                        console.error('Error loading accounts:', error);
                        vm.loading = false;
                    });
                };

                // Select account
                vm.selectAccount = function(account) {
                    vm.selectedAccount = account;
                };

                // Clear filters
                vm.clearFilters = function() {
                    vm.filters = {
                        filter_id: '',
                        filter_account_id: '',
                        filter_acquirer_name: 'all',
                        filter_team: '',
                        filter_description: '',
                        filter_whitelist_url: '',
                        filter_mode: 'all',
                        filter_sector: 'all',
                        filter_hdfc_me_code: '',
                        filter_settlement_account_name: '',
                        filter_email_ids: '',
                        filter_live_request_url: '',
                        filter_live_query_url: '',
                        filter_live_refund_url: '',
                        filter_test_request_url: '',
                        filter_test_query_url: '',
                        filter_test_refund_url: '',
                        filter_merchants: ''
                    };
                    vm.loadAccounts();
                };

                // Apply filters
                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadAccounts();
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
                    // Reset column visibility to defaults
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.loadAccounts();
                };

                // Sort
                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadAccounts();
                };

                // Open new modal
                vm.openNewModal = function() {
                    // CRITICAL: Reset submitting to false FIRST - before anything else
                    vm.submitting = false;
                    vm.modalTitle = 'Create new entry';
                    vm.isEditMode = false;
                    vm.accountForm = {
                        account_id: '',
                        acquirer_name: '',
                        team: '',
                        description: '',
                        whitelist_url: '',
                        mode: 'TEST',
                        sector: '',
                        hdfc_me_code: '',
                        settlement_account_name: '',
                        refund_allowed: true,
                        settlements_to_be_created: true,
                        mask_pii: false,
                        is_active: true,
                        email_ids: '',
                        secret_key: '',
                        salt: '',
                        additional_key_1: '',
                        additional_key_2: '',
                        additional_key_3: '',
                        additional_key_data: '',
                        live_request_url: '',
                        live_query_url: '',
                        live_refund_url: '',
                        test_request_url: '',
                        test_query_url: '',
                        test_refund_url: '',
                        nodal_account: '',
                        merchant_ids: []
                    };
                    
                    // Always ensure acquirer names are set to defaults BEFORE opening modal
                    vm.acquirerNames = defaultAcquirerNames.slice();
                    console.log('Opening modal - Acquirer names available:', vm.acquirerNames.length);
                    
                    // Load acquirer names from API (will merge with defaults)
                    vm.loadAcquirerNames();
                    
                    // Reload merchants
                    vm.loadMerchants();
                    
                    var modalElement = document.getElementById('acquirerAccountModal');
                    var modal = new bootstrap.Modal(modalElement);
                    
                    // Ensure Angular compiles the modal when shown
                    modalElement.addEventListener('shown.bs.modal', function() {
                        // Force ensure acquirer names are set BEFORE timeout
                        if (!vm.acquirerNames || vm.acquirerNames.length === 0) {
                            vm.acquirerNames = defaultAcquirerNames.slice();
                        }
                        
                        // Compile the modal content with Angular
                        $compile(modalElement)($scope);
                        
                        $timeout(function() {
                            // Force reset submitting flag immediately
                            vm.submitting = false;
                            // Force ensure acquirer names are set (defensive)
                            if (!vm.acquirerNames || vm.acquirerNames.length === 0) {
                                vm.acquirerNames = defaultAcquirerNames.slice();
                            }
                            // Force Angular to update the view
                            try {
                                $scope.$apply();
                            } catch(e) {
                                // Already in digest
                            }
                            console.log('Modal shown - Acquirer names:', vm.acquirerNames.length, 'Submitting:', vm.submitting, 'isEditMode:', vm.isEditMode);
                            console.log('Razorpay variants in dropdown:', vm.acquirerNames.filter(function(n) { return n.toLowerCase().indexOf('razorpay') !== -1; }));
                            console.log('First 5 acquirer names:', vm.acquirerNames.slice(0, 5));
                        }, 100);
                    }, { once: true });
                    
                    modal.show();
                };

                // Open edit modal
                vm.openEditModal = function(account) {
                    if (account) vm.selectedAccount = account;
                    if (!vm.selectedAccount) return;

                    // Debug: Log the selected account data
                    console.log('Selected Account Data:', {
                        id: vm.selectedAccount.id,
                        additional_key_1: vm.selectedAccount.additional_key_1 ? 'SET (' + vm.selectedAccount.additional_key_1.length + ' chars)' : 'EMPTY',
                        secret_key: vm.selectedAccount.secret_key ? 'SET (' + vm.selectedAccount.secret_key.length + ' chars)' : 'EMPTY',
                        full_data: vm.selectedAccount
                    });

                    // Reset submitting flag first
                    vm.submitting = false;
                    vm.modalTitle = 'Edit Acquirer Account';
                    vm.isEditMode = true;
                    vm.accountForm = {
                        account_id: vm.selectedAccount.account_id,
                        acquirer_name: vm.selectedAccount.acquirer_name,
                        team: vm.selectedAccount.team || '',
                        description: vm.selectedAccount.description || '',
                        whitelist_url: vm.selectedAccount.whitelist_url || '',
                        mode: vm.selectedAccount.mode || 'TEST',
                        sector: vm.selectedAccount.sector || '',
                        hdfc_me_code: vm.selectedAccount.hdfc_me_code || '',
                        settlement_account_name: vm.selectedAccount.settlement_account_name || '',
                        refund_allowed: vm.selectedAccount.refund_allowed,
                        settlements_to_be_created: vm.selectedAccount.settlements_to_be_created,
                        mask_pii: vm.selectedAccount.mask_pii,
                        is_active: vm.selectedAccount.is_active !== undefined ? vm.selectedAccount.is_active : true,
                        email_ids: vm.selectedAccount.email_ids || '',
                        secret_key: vm.selectedAccount.secret_key || '',
                        salt: vm.selectedAccount.salt || '',
                        additional_key_1: vm.selectedAccount.additional_key_1 || '',
                        additional_key_2: vm.selectedAccount.additional_key_2 || '',
                        additional_key_3: vm.selectedAccount.additional_key_3 || '',
                        additional_key_data: vm.selectedAccount.additional_key_data || '',
                        live_request_url: vm.selectedAccount.live_request_url || '',
                        live_query_url: vm.selectedAccount.live_query_url || '',
                        live_refund_url: vm.selectedAccount.live_refund_url || '',
                        test_request_url: vm.selectedAccount.test_request_url || '',
                        test_query_url: vm.selectedAccount.test_query_url || '',
                        test_refund_url: vm.selectedAccount.test_refund_url || '',
                        nodal_account: '',
                        merchant_ids: vm.selectedAccount.merchant_ids || []
                    };

                    // Ensure acquirer names are loaded
                    if (!vm.acquirerNames || vm.acquirerNames.length === 0) {
                        vm.acquirerNames = defaultAcquirerNames.slice();
                    }
                    
                    // Reload merchants (don't reload acquirer names to avoid overwriting with empty array from API)
                    vm.loadMerchants();
                    // Only load acquirer names if array is empty
                    if (!vm.acquirerNames || vm.acquirerNames.length === 0) {
                        vm.loadAcquirerNames();
                    }

                    // Ensure merchants are loaded
                    if (!vm.merchants || vm.merchants.length === 0) {
                        vm.loadMerchants();
                    }

                    var modalElement = document.getElementById('acquirerAccountModal');
                    var modal = new bootstrap.Modal(modalElement);
                    
                    // Compile modal to ensure Angular bindings work
                    $timeout(function() {
                        $compile(modalElement)($scope);
                    }, 0);
                    
                    // Ensure Angular compiles the modal when shown
                    modalElement.addEventListener('shown.bs.modal', function() {
                        // Reset submitting flag explicitly to false (boolean)
                        $timeout(function() {
                            vm.submitting = false;
                            // Ensure data is loaded
                            if (!vm.acquirerNames || vm.acquirerNames.length === 0) {
                                vm.acquirerNames = defaultAcquirerNames.slice();
                            }
                            console.log('Edit modal shown - Submitting state:', vm.submitting, 'Type:', typeof vm.submitting);
                        }, 0);
                    }, { once: true });
                    
                    modal.show();
                };

                // Submit account
                vm.submitAccount = function($event) {
                    if ($event) {
                        $event.preventDefault();
                        $event.stopPropagation();
                    }
                    
                    // Prevent double submission
                    if (vm.submitting) {
                        return false;
                    }
                    
                    vm.submitting = true;

                    var url = vm.isEditMode 
                        ? '/admin/acquirer-accounts/' + vm.selectedAccount.id
                        : '/admin/acquirer-accounts';
                    var method = vm.isEditMode ? 'PUT' : 'POST';

                    var data = angular.copy(vm.accountForm);
                    
                    // Convert string booleans to actual booleans
                    if (data.refund_allowed === 'true' || data.refund_allowed === true) {
                        data.refund_allowed = true;
                    } else if (data.refund_allowed === 'false' || data.refund_allowed === false) {
                        data.refund_allowed = false;
                    } else {
                        data.refund_allowed = false;
                    }
                    
                    if (data.settlements_to_be_created === 'true' || data.settlements_to_be_created === true) {
                        data.settlements_to_be_created = true;
                    } else if (data.settlements_to_be_created === 'false' || data.settlements_to_be_created === false) {
                        data.settlements_to_be_created = false;
                    } else {
                        data.settlements_to_be_created = false;
                    }
                    
                    if (data.mask_pii === 'true' || data.mask_pii === true) {
                        data.mask_pii = true;
                    } else if (data.mask_pii === 'false' || data.mask_pii === false) {
                        data.mask_pii = false;
                    } else {
                        data.mask_pii = false;
                    }
                    
                    if (data.is_active === 'true' || data.is_active === true) {
                        data.is_active = true;
                    } else if (data.is_active === 'false' || data.is_active === false) {
                        data.is_active = false;
                    } else {
                        data.is_active = true; // Default to true if not set
                    }
                    
                    // Remove copy_rates_from if empty (not needed for save)
                    if (!data.copy_rates_from || data.copy_rates_from === '') {
                        delete data.copy_rates_from;
                    }
                    
                    // Debug: Log key fields being submitted
                    console.log('Submitting account data:', {
                        account_id: data.account_id,
                        acquirer_name: data.acquirer_name,
                        additional_key_1: data.additional_key_1 ? 'SET (' + data.additional_key_1.length + ' chars)' : 'EMPTY',
                        secret_key: data.secret_key ? 'SET (' + data.secret_key.length + ' chars)' : 'EMPTY',
                        is_edit: vm.isEditMode
                    });
                    
                    console.log('Full data being submitted:', data);

                    $http({
                        method: method,
                        url: url,
                        data: data,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        console.log('Account save response:', response.data);
                        vm.submitting = false;
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Account saved successfully', 'success');
                            } else {
                                alert(response.data.message || 'Account saved successfully');
                            }
                            var modalElement = document.getElementById('acquirerAccountModal');
                            var modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                            vm.loadAccounts();
                        } else {
                            vm.submitting = false;
                            var errorMsg = 'Error: ' + (response.data && response.data.message || 'Failed to save account');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }).catch(function(error) {
                        vm.submitting = false;
                        console.error('Error saving account:', error);
                        var errorMsg = 'Failed to save account';
                        
                        if (error.data) {
                            if (error.data.message) {
                                errorMsg = error.data.message;
                            }
                            if (error.data.errors) {
                                var errors = Object.values(error.data.errors).flat().join(', ');
                                errorMsg += ': ' + errors;
                            }
                        } else if (error.status === 422) {
                            errorMsg = 'Validation failed. Please check your input.';
                        } else if (error.status === 500) {
                            errorMsg = 'Server error. Please try again.';
                        }
                        
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                    
                    return false;
                };

                // Delete account
                vm.deleteAccount = function() {
                    if (!vm.selectedAccount) return;
                    if (!confirm('Are you sure you want to delete this acquirer account?')) return;

                    $http.delete('/admin/acquirer-accounts/' + vm.selectedAccount.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            } else {
                                alert(response.data.message);
                            }
                            vm.selectedAccount = null;
                            vm.loadAccounts();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Failed to delete account');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }).catch(function(error) {
                        console.error('Error deleting account:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to delete account', 'error');
                        } else {
                            alert('Failed to delete account');
                        }
                    });
                };

                // Initialize
                // Don't call loadAcquirerNames on init - use defaults to avoid overwriting with empty array from API
                // vm.loadAcquirerNames(); // Commented out to preserve default names
                vm.loadMerchants();
                vm.loadAccounts();
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

