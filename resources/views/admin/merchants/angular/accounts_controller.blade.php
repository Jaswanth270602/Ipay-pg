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
            app.controller('AdminMerchantAccountsController', ['$http', '$scope', function($http, $scope) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {
                    approval_status: 'all',
                    merchant_type: 'all',
                    filter_id: '',
                    filter_name: '',
                    filter_email: '',
                    filter_phone: '',
                    filter_status: 'all',
                    filter_partner: '',
                    filter_organization: '',
                    filter_category: 'all',
                    filter_registration_date: '',
                    filter_challan_urn: ''
                };
                vm.loading = false;
                vm.submitting = false;
                vm.selectedMerchant = null;
                vm.selectAll = false;
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                // Column visibility
                vm.visibleColumns = {
                    id: { visible: true, label: 'Merchant ID.' },
                    name: { visible: true, label: 'Merchant Name' },
                    email: { visible: true, label: 'Merchant Email' },
                    phone: { visible: true, label: 'Merchant Phone' },
                    status: { visible: true, label: 'Merchant Status' },
                    partner: { visible: true, label: 'Partner Names' },
                    organization: { visible: true, label: 'Organization Name' },
                    category: { visible: true, label: 'Merchant Category' },
                    acquirer: { visible: true, label: 'Acquirer' },
                    registration_date: { visible: true, label: 'Registration Date' },
                    challan_urn: { visible: true, label: 'Challan URN' },
                    merchant_unique_id: { visible: true, label: 'Merchant Unique ID' }
                };

                vm.acquirers = [];
                vm.editingMerchantId = null;

                // Merchant form
                vm.merchantForm = {
                    acquirer_account_id: '',
                    is_partner_merchant: false,
                    partner_id: '',
                    partner_name: '',
                    team_id: '',
                    team_name: '',
                    name: '',
                    legal_name: '',
                    email: '',
                    phone: '',
                    merchant_category: '',
                    merchant_category_code: '',
                    ownership_type: '',
                    website_link: '',
                    organization_name: '',
                    address_line_1: '',
                    address_line_2: '',
                    business_country: 'India',
                    business_state: '',
                    business_city: '',
                    business_postal_code: '',
                    merchant_pan_number: '',
                    name_on_pan_card: '',
                    gst_identification_no: '',
                    gstin_state: '',
                    tan_no: '',
                    contact_name: '',
                    contact_mobile: '',
                    contact_landline: '',
                    contact_email: '',
                    is_dummy_account: false,
                    bank_account_holder_name: '',
                    bank_account_number: '',
                    bank_name: '',
                    account_type: 'Savings Account',
                    bank_branch: '',
                    bank_ifsc_code: '',
                    create_user_login: false,
                    login_name: '',
                    password: '',
                    retype_password: '',
                    merchant_type: 'merchant',
                    settlement_cycle_domestic: 1,
                    settlement_cycle_international: 7
                };

                // States and cities (sample data - should be loaded from API)
                vm.states = ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];
                vm.cities = ['Bombooflat', 'Port Blair', 'Hyderabad', 'Visakhapatnam', 'Vijayawada', 'Mumbai', 'Pune', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata'];
                vm.teams = [];

                vm.loadMerchants = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        approval_status: vm.filters.approval_status === 'all' ? '' : vm.filters.approval_status,
                        merchant_type: vm.filters.merchant_type === 'all' ? '' : vm.filters.merchant_type,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    // Add column filters
                    Object.keys(vm.filters).forEach(function(key) {
                        if (key.startsWith('filter_') && vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/admin/merchant-accounts/data', { params: params }).then(function(response) {
                        vm.merchants = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading merchants:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to load merchants', 'error');
                        } else {
                            alert('Failed to load merchants');
                        }
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadMerchants();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadMerchants();
                };

                vm.clearFilters = function() {
                    vm.filters = {
                        approval_status: 'all',
                        merchant_type: 'all',
                        filter_id: '',
                        filter_name: '',
                        filter_email: '',
                        filter_phone: '',
                        filter_status: 'all',
                        filter_partner: '',
                        filter_organization: '',
                        filter_category: 'all',
                        filter_registration_date: '',
                        filter_challan_urn: ''
                    };
                    vm.applyFilters();
                };

                vm.setApprovalStatus = function(status) {
                    vm.filters.approval_status = status;
                    vm.applyFilters();
                };

                vm.setMerchantType = function(type) {
                    vm.filters.merchant_type = type;
                    vm.applyFilters();
                };

                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadMerchants();
                };

                vm.selectMerchant = function(merchant) {
                    vm.selectedMerchant = merchant;
                };

                vm.toggleSelectAll = function() {
                    vm.merchants.forEach(function(merchant) {
                        merchant.selected = vm.selectAll;
                    });
                };

                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns.hasOwnProperty(key)) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                vm.resetView = function() {
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.clearFilters();
                };

                vm.loadAcquirers = function() {
                    $http.get('/admin/merchant-accounts/acquirers').then(function(res) {
                        if (res.data.success && res.data.data) {
                            vm.acquirers = res.data.data;
                        }
                    });
                };

                vm.openNewModal = function() {
                    vm.editingMerchantId = null;
                    vm.merchantForm = {
                        acquirer_account_id: '',
                        is_partner_merchant: false,
                        partner_id: '',
                        partner_name: '',
                        team_id: '',
                        team_name: '',
                        name: '',
                        legal_name: '',
                        email: '',
                        phone: '',
                        merchant_category: '',
                        merchant_category_code: '',
                        ownership_type: '',
                        website_link: '',
                        organization_name: '',
                        address_line_1: '',
                        address_line_2: '',
                        business_country: 'India',
                        business_state: '',
                        business_city: '',
                        business_postal_code: '',
                        merchant_pan_number: '',
                        name_on_pan_card: '',
                        gst_identification_no: '',
                        gstin_state: '',
                        tan_no: '',
                        contact_name: '',
                        contact_mobile: '',
                        contact_landline: '',
                        contact_email: '',
                        is_dummy_account: false,
                        bank_account_holder_name: '',
                        bank_account_number: '',
                        bank_name: '',
                        account_type: 'Savings Account',
                        bank_branch: '',
                        bank_ifsc_code: '',
                        create_user_login: false,
                        login_name: '',
                        password: '',
                        retype_password: '',
                        merchant_type: (vm.filters.merchant_type && vm.filters.merchant_type !== 'all') ? vm.filters.merchant_type : 'merchant',
                        settlement_cycle_domestic: 1,
                        settlement_cycle_international: 7
                    };
                    vm.loadAcquirers();
                    var modal = new bootstrap.Modal(document.getElementById('newMerchantModal'));
                    modal.show();
                };

                vm.openEditModal = function(merchant) {
                    vm.editingMerchantId = merchant.id;
                    vm.loadAcquirers();
                    $http.get('/admin/merchant-accounts/' + merchant.id).then(function(res) {
                        if (!res.data.success || !res.data.data) return;
                        var m = res.data.data;
                        vm.merchantForm = {
                            acquirer_account_id: m.acquirer_account_id ? String(m.acquirer_account_id) : '',
                            is_partner_merchant: !!m.is_partner_merchant,
                            partner_id: m.partner_id || '',
                            partner_name: m.partner_name || '',
                            team_id: m.team_id || '',
                            team_name: m.team_name || '',
                            name: m.name || '',
                            legal_name: m.legal_name || '',
                            email: m.email || '',
                            phone: m.phone || '',
                            merchant_category: m.merchant_category || '',
                            merchant_category_code: m.merchant_category_code || '',
                            ownership_type: m.ownership_type || '',
                            website_link: m.business_website || '',
                            organization_name: m.organization_name || '',
                            address_line_1: m.address_line_1 || '',
                            address_line_2: m.address_line_2 || '',
                            business_country: m.business_country || 'India',
                            business_state: m.business_state || '',
                            business_city: m.business_city || '',
                            business_postal_code: m.business_postal_code || '',
                            merchant_pan_number: m.merchant_pan_number || '',
                            name_on_pan_card: m.name_on_pan_card || '',
                            gst_identification_no: m.gst_identification_no || '',
                            gstin_state: m.gstin_state || '',
                            tan_no: m.tan_no || '',
                            contact_name: m.contact_name || '',
                            contact_mobile: m.contact_mobile || '',
                            contact_landline: m.contact_landline || '',
                            contact_email: m.contact_email || '',
                            is_dummy_account: !!m.is_dummy_account,
                            bank_account_holder_name: m.bank_account_holder_name || '',
                            bank_account_number: m.bank_account_number || '',
                            bank_name: m.bank_name || '',
                            account_type: m.account_type || 'Savings Account',
                            bank_branch: m.bank_branch || '',
                            bank_ifsc_code: m.bank_ifsc_code || '',
                            create_user_login: false,
                            login_name: '',
                            password: '',
                            retype_password: '',
                            merchant_type: (m.merchant_type === 'vendor_merchant') ? 'vendor_merchant' : 'merchant',
                            settlement_cycle_domestic: m.settlement_cycle_domestic != null ? m.settlement_cycle_domestic : 1,
                            settlement_cycle_international: m.settlement_cycle_international != null ? m.settlement_cycle_international : 7
                        };
                        var modal = new bootstrap.Modal(document.getElementById('newMerchantModal'));
                        modal.show();
                    }, function() {
                        alert('Failed to load merchant');
                    });
                };

                vm.loadPartnerTeams = function() {
                    // Load teams for selected partner
                    // This should be an API call
                    vm.teams = [
                        { id: 1, name: 'Team 1' },
                        { id: 2, name: 'Team 2' }
                    ];
                };

                vm.submitMerchant = function() {
                    if (!vm.merchantForm.name || !vm.merchantForm.legal_name || !vm.merchantForm.email || !vm.merchantForm.phone) {
                        alert('Please fill in all required fields');
                        return;
                    }

                    if (!vm.editingMerchantId && vm.merchantForm.create_user_login) {
                        if (!vm.merchantForm.login_name || !vm.merchantForm.password || !vm.merchantForm.retype_password) {
                            alert('Please fill in all user login fields');
                            return;
                        }
                        if (vm.merchantForm.password !== vm.merchantForm.retype_password) {
                            alert('Passwords do not match');
                            return;
                        }
                        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/;
                        if (!passwordRegex.test(vm.merchantForm.password)) {
                            alert('Password must have minimum 12 characters and should include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character.');
                            return;
                        }
                    }

                    if (!vm.merchantForm.merchant_type || !['merchant', 'vendor_merchant'].includes(vm.merchantForm.merchant_type)) {
                        vm.merchantForm.merchant_type = 'merchant';
                    }

                    vm.submitting = true;
                    var isEdit = !!vm.editingMerchantId;
                    var url = isEdit ? ('/admin/merchant-accounts/' + vm.editingMerchantId) : '/admin/merchant-accounts';
                    var method = isEdit ? 'put' : 'post';
                    $http[method](url, vm.merchantForm, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.submitting = false;
                        if (response.data.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('newMerchantModal'));
                            modal.hide();
                            vm.editingMerchantId = null;
                            alert(isEdit ? 'Merchant updated successfully' : 'Merchant account created successfully');
                            vm.loadMerchants();
                        } else {
                            var errorMsg = response.data.message || (isEdit ? 'Failed to update merchant' : 'Failed to create merchant account');
                            if (response.data.errors) {
                                var errors = Object.values(response.data.errors).flat();
                                errorMsg = errors.join(', ');
                            }
                            alert(errorMsg);
                        }
                    }, function(error) {
                        vm.submitting = false;
                        var errorMsg = isEdit ? 'Failed to update merchant' : 'Failed to create merchant account';
                        if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            if (errors.length > 0) errorMsg = errors.join(', ');
                        } else if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        alert(errorMsg);
                    });
                };

                vm.duplicateSelected = function() {
                    if (!vm.selectedMerchant) {
                        alert('Please select a merchant to duplicate');
                        return;
                    }
                    if (!confirm('Are you sure you want to duplicate this merchant?')) {
                        return;
                    }

                    $http.post('/admin/merchant-accounts/' + vm.selectedMerchant.id + '/duplicate', {}, {
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Merchant duplicated successfully');
                            vm.loadMerchants();
                        } else {
                            alert('Failed to duplicate merchant: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        alert('Failed to duplicate merchant');
                        console.error('Error:', error);
                    });
                };

                vm.viewMerchant = function(merchant) {
                    vm.selectedMerchant = merchant;
                    var modal = new bootstrap.Modal(document.getElementById('viewMerchantModal'));
                    modal.show();
                };

                // Update Approval Status
                vm.updateApprovalStatus = function(merchant) {
                    if (!merchant || !merchant.id) {
                        return;
                    }

                    $http.post('/admin/merchant-accounts/' + merchant.id + '/update-approval-status', {
                        approval_status: merchant.approval_status
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Approval status updated successfully to: ' + merchant.approval_status.replace(/_/g, ' ').toUpperCase());
                        } else {
                            alert('Failed to update approval status: ' + (response.data.message || 'Unknown error'));
                            vm.loadMerchants(); // Reload to reset dropdown
                        }
                    }, function(error) {
                        alert('Failed to update approval status');
                        console.error('Error:', error);
                        vm.loadMerchants(); // Reload to reset dropdown
                    });
                };

                // Update Account Status (Active/Inactive)
                vm.updateAccountStatus = function(merchant) {
                    if (!merchant || !merchant.id) {
                        return;
                    }

                    $http.post('/admin/merchant-accounts/' + merchant.id + '/update-status', {
                        status: merchant.status
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Account status updated successfully to: ' + merchant.status.toUpperCase());
                        } else {
                            alert('Failed to update account status: ' + (response.data.message || 'Unknown error'));
                            vm.loadMerchants(); // Reload to reset dropdown
                        }
                    }, function(error) {
                        alert('Failed to update account status');
                        console.error('Error:', error);
                        vm.loadMerchants(); // Reload to reset dropdown
                    });
                };

                // Format status helper
                vm.formatStatus = function(status) {
                    if (!status) return 'NOT APPROVED';
                    return status.replace(/_/g, ' ').toUpperCase();
                };

                // Settlement Settings
                vm.settlementSettingsMerchant = null;
                vm.settlementSettings = {
                    settlement_cycle_domestic: 1,
                    settlement_cycle_international: 7,
                    fee_percentage: 0,
                    fee_flat: 0
                };
                vm.savingSettlementSettings = false;

                vm.openSettlementSettingsModal = function(merchant) {
                    if (!merchant) return;
                    vm.settlementSettingsMerchant = merchant;
                    vm.settlementSettings = {
                        settlement_cycle_domestic: merchant.settlement_cycle_domestic || 1,
                        settlement_cycle_international: merchant.settlement_cycle_international || 7,
                        fee_percentage: merchant.fee_percentage || 0,
                        fee_flat: merchant.fee_flat || 0
                    };
                    var modal = new bootstrap.Modal(document.getElementById('settlementSettingsModal'));
                    modal.show();
                };

                vm.saveSettlementSettings = function() {
                    if (!vm.settlementSettingsMerchant || !vm.settlementSettingsMerchant.id) {
                        alert('Invalid merchant');
                        return;
                    }

                    vm.savingSettlementSettings = true;
                    $http.post('/admin/merchant-accounts/' + vm.settlementSettingsMerchant.id + '/update-settings', vm.settlementSettings, {
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function(response) {
                        vm.savingSettlementSettings = false;
                        if (response.data.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('settlementSettingsModal'));
                            modal.hide();
                            alert('Settlement settings updated successfully');
                            vm.loadMerchants(); // Reload to get updated data
                        } else {
                            var errorMsg = response.data.message || 'Failed to update settings';
                            if (response.data.errors) {
                                var errors = Object.values(response.data.errors).flat();
                                errorMsg = errors.join(', ');
                            }
                            alert(errorMsg);
                        }
                    }, function(error) {
                        vm.savingSettlementSettings = false;
                        var errorMsg = 'Failed to update settlement settings';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            errorMsg = errors.join(', ');
                        }
                        alert(errorMsg);
                    });
                };

                // Initialize
                vm.loadMerchants();
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



