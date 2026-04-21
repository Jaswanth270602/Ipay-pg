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
            app.controller('AdminMerchantAccountsController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1, from: 0, to: 0 };
                vm.filters = {
                    approval_status: 'all',
                    merchant_type: 'all',
                    filter_id: '',
                    filter_merchant_unique_id: '',
                    filter_name: '',
                    filter_email: '',
                    filter_phone: '',
                    filter_status: 'all',
                    filter_partner: '',
                    filter_organization: '',
                    filter_category: 'all',
                    filter_acquirer: '',
                    filter_registration_date: '',
                    filter_challan_urn: '',
                    filter_reseller_id: ''
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
                    merchant_unique_id: { visible: true, label: 'Merchant Unique ID' },
                    reseller: { visible: true, label: 'Reseller' }
                };

                vm.acquirers = [];
                vm.resellers = [];
                vm.partners = [];
                vm.merchantTab = 'details';
                vm.editingMerchantId = null;
                vm.formErrors = {};

                // Generic max-length enforcer for text fields
                vm.enforceMaxLength = function(field, max) {
                    if (!vm.merchantForm || !field || !max) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    if (current.length > max) {
                        vm.merchantForm[field] = current.substring(0, max);
                    }
                };

                vm.enforceDigitsOnly = function(field, max) {
                    if (!vm.merchantForm || !field) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    current = current.replace(/\D+/g, '');
                    if (max && current.length > max) {
                        current = current.substring(0, max);
                    }
                    vm.merchantForm[field] = current;
                };

                vm.enforceAlphaNumericOnly = function(field, max) {
                    if (!vm.merchantForm || !field) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    current = current.replace(/[^A-Za-z0-9]+/g, '');
                    if (max && current.length > max) {
                        current = current.substring(0, max);
                    }
                    vm.merchantForm[field] = current;
                };

                vm.enforceLettersAndSpacesOnly = function(field, max) {
                    if (!vm.merchantForm || !field) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    current = current.replace(/[^A-Za-z ]+/g, '');
                    if (max && current.length > max) {
                        current = current.substring(0, max);
                    }
                    vm.merchantForm[field] = current;
                };

                vm.enforceAlphaNumericAndSpacesOnly = function(field, max) {
                    if (!vm.merchantForm || !field) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    current = current.replace(/[^A-Za-z0-9 ]+/g, '');
                    if (max && current.length > max) {
                        current = current.substring(0, max);
                    }
                    vm.merchantForm[field] = current;
                };

                vm.enforcePlusAndDigitsOnly = function(field, max) {
                    if (!vm.merchantForm || !field) return;
                    var current = vm.merchantForm[field];
                    if (typeof current !== 'string') {
                        current = current == null ? '' : String(current);
                    }
                    current = current.replace(/[^0-9+]+/g, '');
                    if (current.indexOf('+') > 0) {
                        current = '+' + current.replace(/\+/g, '');
                    } else if (current.indexOf('+') === 0) {
                        current = '+' + current.substring(1).replace(/\+/g, '');
                    }
                    if (max && current.length > max) {
                        current = current.substring(0, max);
                    }
                    vm.merchantForm[field] = current;
                };

                // Per-field validation helpers (used on blur)
                vm.validateMerchantName = function () {
                    vm.formErrors.name = [];
                    var value = (vm.merchantForm.name || '').toString();
                    var merchantNameRegex = /^[A-Za-z ]+$/;
                    if (!value.trim()) {
                        vm.formErrors.name.push('Merchant name is required.');
                    } else if (value.length >= 250) {
                        vm.formErrors.name.push('Merchant name cannot exceed 250 characters.');
                    } else if (!merchantNameRegex.test(value.trim())) {
                        vm.formErrors.name.push('Merchant name may contain only letters and spaces.');
                    }
                    if (vm.formErrors.name.length === 0) {
                        delete vm.formErrors.name;
                    }
                };

                vm.validateLegalName = function () {
                    vm.formErrors.legal_name = [];
                    var value = (vm.merchantForm.legal_name || '').toString();
                    var merchantNameRegex = /^[A-Za-z ]+$/;
                    if (!value.trim()) {
                        vm.formErrors.legal_name.push('Merchant legal name is required.');
                    } else if (value.length >= 250) {
                        vm.formErrors.legal_name.push('Merchant legal name cannot exceed 250 characters.');
                    } else if (!merchantNameRegex.test(value.trim())) {
                        vm.formErrors.legal_name.push('Merchant legal name may contain only letters and spaces.');
                    }
                    if (vm.formErrors.legal_name.length === 0) {
                        delete vm.formErrors.legal_name;
                    }
                };

                vm.validateAddress1 = function () {
                    vm.formErrors.address_line_1 = [];
                    var value = (vm.merchantForm.address_line_1 || '').toString();
                    if (!value.trim()) {
                        vm.formErrors.address_line_1.push('Address Line 1 is required.');
                    } else if (value.length >= 250) {
                        vm.formErrors.address_line_1.push('Address Line 1 cannot exceed 250 characters.');
                    }
                    if (vm.formErrors.address_line_1.length === 0) {
                        delete vm.formErrors.address_line_1;
                    }
                };

                vm.validateMerchantEmail = function () {
                    vm.formErrors.email = [];
                    var value = (vm.merchantForm.email || '').trim();
                    if (!value) {
                        vm.formErrors.email.push('Merchant email is required.');
                    } else {
                        if (value.length > 120) {
                            vm.formErrors.email.push('Merchant email cannot exceed 120 characters.');
                        }
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            vm.formErrors.email.push('Please enter a valid email ID.');
                        } else {
                            // basic duplicate check against already loaded merchants list
                            var lower = value.toLowerCase();
                            var exists = vm.merchants.some(function(m) {
                                return m && m.email && m.email.toLowerCase() === lower;
                            });
                            if (exists) {
                                vm.formErrors.email.push('Email already exists.');
                            }
                        }
                    }
                    if (vm.formErrors.email.length === 0) {
                        delete vm.formErrors.email;
                    }
                };

                vm.validateMerchantPhone = function () {
                    vm.formErrors.phone = [];
                    var value = (vm.merchantForm.phone || '').trim();
                    if (!value) {
                        vm.formErrors.phone.push('Merchant phone is required.');
                    } else if (!/^\+?[0-9]+$/.test(value) || value.length > 20) {
                        vm.formErrors.phone.push('Merchant phone may contain only digits and the + symbol (max 20 characters).');
                    }
                    if (vm.formErrors.phone.length === 0) {
                        delete vm.formErrors.phone;
                    }
                };

                vm.validateContactEmail = function () {
                    vm.formErrors.contact_email = [];
                    var value = (vm.merchantForm.contact_email || '').trim();
                    if (!value) {
                        vm.formErrors.contact_email.push('Contact email is required.');
                    } else {
                        if (value.length > 120) {
                            vm.formErrors.contact_email.push('Contact email cannot exceed 120 characters.');
                        }
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            vm.formErrors.contact_email.push('Please enter a valid email ID.');
                        }
                    }
                    if (vm.formErrors.contact_email.length === 0) {
                        delete vm.formErrors.contact_email;
                    }
                };

                vm.validateContactMobile = function () {
                    vm.formErrors.contact_mobile = [];
                    var value = (vm.merchantForm.contact_mobile || '').trim();
                    if (!value) {
                        vm.formErrors.contact_mobile.push('Contact mobile is required.');
                    } else if (!/^\+?[0-9]+$/.test(value) || value.length > 20) {
                        vm.formErrors.contact_mobile.push('Contact mobile may contain only digits and the + symbol (max 20 characters).');
                    }
                    if (vm.formErrors.contact_mobile.length === 0) {
                        delete vm.formErrors.contact_mobile;
                    }
                };

                vm.validateZipCode = function () {
                    vm.formErrors.business_postal_code = [];
                    var value = (vm.merchantForm.business_postal_code || '').toString().trim();
                    if (!value) {
                        vm.formErrors.business_postal_code.push('Zip code is required.');
                    } else if (!/^\d+$/.test(value)) {
                        vm.formErrors.business_postal_code.push('Zip code must contain only numbers.');
                    } else if (value.length > 15) {
                        vm.formErrors.business_postal_code.push('Zip code may not exceed 15 digits.');
                    }
                    if (vm.formErrors.business_postal_code.length === 0) delete vm.formErrors.business_postal_code;
                };

                vm.validateMerchantPan = function () {
                    vm.formErrors.merchant_pan_number = [];
                    var value = (vm.merchantForm.merchant_pan_number || '').toString().trim();
                    if (!value) {
                        vm.formErrors.merchant_pan_number.push('Merchant PAN number is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                        vm.formErrors.merchant_pan_number.push('Merchant PAN number may contain only letters and numbers.');
                    }
                    if (vm.formErrors.merchant_pan_number.length === 0) delete vm.formErrors.merchant_pan_number;
                };

                vm.validateNameOnPan = function () {
                    vm.formErrors.name_on_pan_card = [];
                    var value = (vm.merchantForm.name_on_pan_card || '').toString().trim();
                    if (!value) {
                        vm.formErrors.name_on_pan_card.push('Name on PAN card is required.');
                    } else if (!/^[A-Za-z ]+$/.test(value)) {
                        vm.formErrors.name_on_pan_card.push('Name on PAN card may contain only letters and spaces.');
                    }
                    if (vm.formErrors.name_on_pan_card.length === 0) delete vm.formErrors.name_on_pan_card;
                };

                vm.validateGstin = function () {
                    vm.formErrors.gst_identification_no = [];
                    var value = (vm.merchantForm.gst_identification_no || '').toString().trim();
                    if (value && !/^[A-Za-z0-9]+$/.test(value)) {
                        vm.formErrors.gst_identification_no.push('VAT identification number may contain only letters and numbers.');
                    }
                    if (vm.formErrors.gst_identification_no.length === 0) delete vm.formErrors.gst_identification_no;
                };

                vm.validateGstinState = function () {
                    vm.formErrors.gstin_state = [];
                    var value = (vm.merchantForm.gstin_state || '').toString().trim();
                    if (value && !/^[A-Za-z ]+$/.test(value)) {
                        vm.formErrors.gstin_state.push('VATIN state may contain only letters and spaces.');
                    }
                    if (vm.formErrors.gstin_state.length === 0) delete vm.formErrors.gstin_state;
                };

                vm.validateTanNo = function () {
                    vm.formErrors.tan_no = [];
                    var value = (vm.merchantForm.tan_no || '').toString().trim();
                    if (value && !/^[A-Za-z0-9 ]+$/.test(value)) {
                        vm.formErrors.tan_no.push('TAN number may contain only letters, numbers, and spaces.');
                    }
                    if (vm.formErrors.tan_no.length === 0) delete vm.formErrors.tan_no;
                };

                vm.validateContactName = function () {
                    vm.formErrors.contact_name = [];
                    var value = (vm.merchantForm.contact_name || '').toString().trim();
                    if (!value) {
                        vm.formErrors.contact_name.push('Contact name is required.');
                    } else if (!/^[A-Za-z ]+$/.test(value)) {
                        vm.formErrors.contact_name.push('Contact name may contain only letters and spaces.');
                    }
                    if (vm.formErrors.contact_name.length === 0) delete vm.formErrors.contact_name;
                };

                vm.validateContactLandline = function () {
                    vm.formErrors.contact_landline = [];
                    var value = (vm.merchantForm.contact_landline || '').toString().trim();
                    if (value && (!/^\+?[0-9]+$/.test(value) || value.length > 20)) {
                        vm.formErrors.contact_landline.push('Contact landline may contain only digits and the + symbol (max 20 characters).');
                    }
                    if (vm.formErrors.contact_landline.length === 0) delete vm.formErrors.contact_landline;
                };

                vm.validateAccountHolderName = function () {
                    vm.formErrors.bank_account_holder_name = [];
                    var value = (vm.merchantForm.bank_account_holder_name || '').toString().trim();
                    if (!value) {
                        vm.formErrors.bank_account_holder_name.push('Account holder name is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                        vm.formErrors.bank_account_holder_name.push('Account holder name may contain only letters and numbers.');
                    }
                    if (vm.formErrors.bank_account_holder_name.length === 0) delete vm.formErrors.bank_account_holder_name;
                };

                vm.validateBankAccountNumber = function () {
                    vm.formErrors.bank_account_number = [];
                    var value = (vm.merchantForm.bank_account_number || '').toString().trim();
                    if (!value) {
                        vm.formErrors.bank_account_number.push('Bank account number is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                        vm.formErrors.bank_account_number.push('Bank account number may contain only letters and numbers.');
                    }
                    if (vm.formErrors.bank_account_number.length === 0) delete vm.formErrors.bank_account_number;
                };

                vm.validateBankName = function () {
                    vm.formErrors.bank_name = [];
                    var value = (vm.merchantForm.bank_name || '').toString().trim();
                    if (!value) {
                        vm.formErrors.bank_name.push('Bank name is required.');
                    } else if (!/^[A-Za-z ]+$/.test(value)) {
                        vm.formErrors.bank_name.push('Bank name may contain only letters and spaces.');
                    }
                    if (vm.formErrors.bank_name.length === 0) delete vm.formErrors.bank_name;
                };

                vm.validateBankBranch = function () {
                    vm.formErrors.bank_branch = [];
                    var value = (vm.merchantForm.bank_branch || '').toString().trim();
                    if (!value) {
                        vm.formErrors.bank_branch.push('Bank branch is required.');
                    } else if (!/^[A-Za-z0-9 ]+$/.test(value)) {
                        vm.formErrors.bank_branch.push('Bank branch may contain only letters, numbers, and spaces.');
                    }
                    if (vm.formErrors.bank_branch.length === 0) delete vm.formErrors.bank_branch;
                };

                vm.validateIfscCode = function () {
                    vm.formErrors.bank_ifsc_code = [];
                    var value = (vm.merchantForm.bank_ifsc_code || '').toString().trim();
                    if (!value) {
                        vm.formErrors.bank_ifsc_code.push('IFSC code is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                        vm.formErrors.bank_ifsc_code.push('IFSC code may contain only letters and numbers.');
                    }
                    if (vm.formErrors.bank_ifsc_code.length === 0) delete vm.formErrors.bank_ifsc_code;
                };

                var MERCHANT_TAB_FIELDS = {
                    details: ['name', 'legal_name', 'email', 'phone', 'partner_id', 'team_name', 'reseller_id', 'merchant_category', 'address_line_1', 'business_country', 'business_state', 'business_city', 'business_postal_code'],
                    tax: ['merchant_pan_number', 'name_on_pan_card', 'gst_identification_no', 'gstin_state', 'tan_no'],
                    contact: ['contact_name', 'contact_mobile', 'contact_landline', 'contact_email'],
                    bank: ['bank_account_holder_name', 'bank_account_number', 'bank_name', 'account_type', 'bank_branch', 'bank_ifsc_code'],
                    login: ['password', 'retype_password', 'login_name']
                };

                vm.validateMerchantTab = function (tab) {
                    var fields = MERCHANT_TAB_FIELDS[tab];
                    if (!fields) {
                        return true;
                    }
                    fields.forEach(function (field) {
                        if (vm.formErrors[field]) {
                            delete vm.formErrors[field];
                        }
                    });

                    function add(field, msg) {
                        if (!vm.formErrors[field]) {
                            vm.formErrors[field] = [];
                        }
                        vm.formErrors[field].push(msg);
                    }

                    var f = vm.merchantForm;

                    switch (tab) {
                    case 'details':
                        vm.validateMerchantName();
                        vm.validateLegalName();
                        vm.validateMerchantEmail();
                        vm.validateMerchantPhone();
                        if (!f.merchant_category) {
                            add('merchant_category', 'Merchant category is required.');
                        }
                        if (!f.address_line_1 || !String(f.address_line_1).trim()) {
                            add('address_line_1', 'Address Line 1 is required.');
                        } else if (String(f.address_line_1).length > 250) {
                            add('address_line_1', 'Address Line 1 cannot exceed 250 characters.');
                        }
                        if (!f.business_country) {
                            add('business_country', 'Country is required.');
                        }
                        if (!f.business_state) {
                            add('business_state', 'State is required.');
                        }
                        if (!f.business_city) {
                            add('business_city', 'City is required.');
                        }
                        if (!f.business_postal_code) {
                            add('business_postal_code', 'Zip code is required.');
                        } else if (!/^\d+$/.test(String(f.business_postal_code))) {
                            add('business_postal_code', 'Zip code must contain only numbers.');
                        } else if (String(f.business_postal_code).length > 15) {
                            add('business_postal_code', 'Zip code may not exceed 15 digits.');
                        }
                        if (f.is_reseller_merchant && !f.reseller_id) {
                            add('reseller_id', 'Please select a reseller.');
                        }
                        if (f.is_partner_merchant) {
                            if (!f.partner_id) {
                                add('partner_id', 'Please select a partner.');
                            }
                            if (vm.teams && vm.teams.length > 0) {
                                var tn = (f.team_name || '').toString().trim();
                                if (!tn) {
                                    add('team_name', 'Please select a team.');
                                }
                            }
                        }
                        break;
                    case 'tax':
                        if (!f.merchant_pan_number || !String(f.merchant_pan_number).trim()) {
                            add('merchant_pan_number', 'Merchant PAN number is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(String(f.merchant_pan_number))) {
                            add('merchant_pan_number', 'Merchant PAN number may contain only letters and numbers.');
                        }
                        if (!f.name_on_pan_card || !String(f.name_on_pan_card).trim()) {
                            add('name_on_pan_card', 'Name on PAN card is required.');
                        } else if (!/^[A-Za-z ]+$/.test(String(f.name_on_pan_card).trim())) {
                            add('name_on_pan_card', 'Name on PAN card may contain only letters and spaces.');
                        }
                        if (f.gst_identification_no && String(f.gst_identification_no).trim() && !/^[A-Za-z0-9]+$/.test(String(f.gst_identification_no).trim())) {
                            add('gst_identification_no', 'VAT identification number may contain only letters and numbers.');
                        }
                        if (f.gstin_state && String(f.gstin_state).trim() && !/^[A-Za-z ]+$/.test(String(f.gstin_state).trim())) {
                            add('gstin_state', 'VATIN state may contain only letters and spaces.');
                        }
                        if (f.tan_no && String(f.tan_no).trim() && !/^[A-Za-z0-9 ]+$/.test(String(f.tan_no).trim())) {
                            add('tan_no', 'TAN number may contain only letters, numbers, and spaces.');
                        }
                        break;
                    case 'contact':
                        if (!f.contact_name || !String(f.contact_name).trim()) {
                            add('contact_name', 'Contact name is required.');
                        } else if (!/^[A-Za-z ]+$/.test(String(f.contact_name).trim())) {
                            add('contact_name', 'Contact name may contain only letters and spaces.');
                        }
                        vm.validateContactMobile();
                        vm.validateContactEmail();
                        if (f.contact_landline && String(f.contact_landline).trim()) {
                            if (!/^\+?[0-9]+$/.test(String(f.contact_landline).trim()) || String(f.contact_landline).trim().length > 20) {
                                add('contact_landline', 'Contact landline may contain only digits and the + symbol (max 20 characters).');
                            }
                        }
                        break;
                    case 'bank':
                        if (!f.bank_account_holder_name || !String(f.bank_account_holder_name).trim()) {
                            add('bank_account_holder_name', 'Account holder name is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(String(f.bank_account_holder_name == null ? '' : f.bank_account_holder_name).trim())) {
                            add('bank_account_holder_name', 'Account holder name may contain only letters and numbers.');
                        }
                        if (!f.bank_account_number || !String(f.bank_account_number).trim()) {
                            add('bank_account_number', 'Bank account number is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(String(f.bank_account_number).trim())) {
                            add('bank_account_number', 'Bank account number may contain only letters and numbers.');
                        }
                        if (!f.bank_name || !String(f.bank_name).trim()) {
                            add('bank_name', 'Bank name is required.');
                        } else if (!/^[A-Za-z ]+$/.test(String(f.bank_name).trim())) {
                            add('bank_name', 'Bank name may contain only letters and spaces.');
                        }
                        if (!f.account_type) {
                            add('account_type', 'Account type is required.');
                        }
                        if (!f.bank_branch || !String(f.bank_branch).trim()) {
                            add('bank_branch', 'Bank branch is required.');
                        } else if (!/^[A-Za-z0-9 ]+$/.test(String(f.bank_branch).trim())) {
                            add('bank_branch', 'Bank branch may contain only letters, numbers, and spaces.');
                        }
                        if (!f.bank_ifsc_code || !String(f.bank_ifsc_code).trim()) {
                            add('bank_ifsc_code', 'IFSC code is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(String(f.bank_ifsc_code).trim())) {
                            add('bank_ifsc_code', 'IFSC code may contain only letters and numbers.');
                        }
                        break;
                    case 'login':
                        if (!vm.editingMerchantId) {
                            var pwd = f.password || '';
                            var rp = f.retype_password || '';
                            if (pwd && pwd !== rp) {
                                add('retype_password', 'Passwords do not match.');
                            }
                        }
                        break;
                    default:
                        break;
                    }

                    var hasErr = false;
                    fields.forEach(function (field) {
                        if (vm.formErrors[field] && vm.formErrors[field].length) {
                            hasErr = true;
                        }
                    });
                    return !hasErr;
                };

                vm.validateMerchantFormFull = function () {
                    var tabs = ['details', 'tax', 'contact', 'bank'];
                    if (!vm.editingMerchantId) {
                        tabs.push('login');
                    }
                    var i;
                    for (i = 0; i < tabs.length; i++) {
                        if (!vm.validateMerchantTab(tabs[i])) {
                            vm.merchantTab = tabs[i];
                            return false;
                        }
                    }
                    return true;
                };

                vm.goMerchantTab = function (target) {
                    var order = ['details', 'tax', 'contact', 'bank', 'login'];
                    var cur = vm.merchantTab || 'details';
                    var ci = order.indexOf(cur);
                    var ti = order.indexOf(target);
                    if (ti < 0) {
                        return;
                    }
                    if (ti > ci) {
                        if (!vm.validateMerchantTab(cur)) {
                            if (typeof showToast === 'function') {
                                showToast('Please fix the errors in this section before continuing.', 'error');
                            }
                            return;
                        }
                    }
                    vm.merchantTab = target;
                };

                vm.loadPartners = function () {
                    return $http.get('/admin/merchant-accounts/partners').then(function (res) {
                        if (res.data && res.data.success && res.data.data) {
                            vm.partners = res.data.data;
                        } else {
                            vm.partners = [];
                        }
                    }, function () {
                        vm.partners = [];
                    });
                };

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
                    settlement_cycle_international: 7,
                    is_reseller_merchant: false,
                    reseller_id: ''
                };

                vm.locationMap = {};
                vm.availableCountries = [];
                vm.availableStates = [];
                vm.availableCities = [];
                vm.teams = [];

                vm.loadLocations = function() {
                    return $http.get('/admin/merchant-accounts/locations').then(function(res) {
                        if (res.data && res.data.success && res.data.data) {
                            vm.locationMap = res.data.data;
                            vm.availableCountries = Object.keys(vm.locationMap);
                        } else {
                            vm.locationMap = {};
                            vm.availableCountries = [];
                        }
                    }, function() {
                        vm.locationMap = {};
                        vm.availableCountries = [];
                    });
                };

                vm.onCountryChange = function () {
                    var country = vm.merchantForm.business_country;
                    if (country && vm.locationMap[country]) {
                        vm.availableStates = Object.keys(vm.locationMap[country]);
                    } else {
                        vm.availableStates = [];
                    }
                    vm.merchantForm.business_state = '';
                    vm.availableCities = [];
                    vm.merchantForm.business_city = '';
                };

                vm.onStateChange = function () {
                    var country = vm.merchantForm.business_country;
                    var state = vm.merchantForm.business_state;
                    if (country && state && vm.locationMap[country] && vm.locationMap[country][state]) {
                        vm.availableCities = vm.locationMap[country][state];
                    } else {
                        vm.availableCities = [];
                    }
                    vm.merchantForm.business_city = '';
                };

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
                            per_page: response.data.pagination.per_page,
                            from: response.data.pagination.from,
                            to: response.data.pagination.to
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
                        filter_merchant_unique_id: '',
                        filter_name: '',
                        filter_email: '',
                        filter_phone: '',
                        filter_status: 'all',
                        filter_partner: '',
                        filter_organization: '',
                        filter_category: 'all',
                        filter_acquirer: '',
                        filter_registration_date: '',
                        filter_challan_urn: '',
                        filter_reseller_id: ''
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

                vm.loadResellers = function() {
                    $http.get('/admin/merchant-accounts/resellers').then(function(res) {
                        if (res.data.success && res.data.data) {
                            vm.resellers = res.data.data;
                        } else {
                            vm.resellers = [];
                        }
                    }, function() {
                        vm.resellers = [];
                    });
                };

                vm.openNewModal = function() {
                    vm.editingMerchantId = null;
                    vm.merchantTab = 'details';
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
                        settlement_cycle_international: 7,
                        is_reseller_merchant: false,
                        reseller_id: ''
                    };
                    vm.loadAcquirers();
                    vm.loadResellers();
                    vm.loadPartners();
                    vm.teams = [];
                    if (!vm.availableCountries.length) {
                        vm.loadLocations();
                    }
                    vm.onCountryChange();
                    var modal = new bootstrap.Modal(document.getElementById('newMerchantModal'));
                    modal.show();
                };

                vm.openEditModal = function(merchant) {
                    vm.editingMerchantId = merchant.id;
                    if (!vm.availableCountries.length) {
                        vm.loadLocations();
                    }
                    vm.loadAcquirers();
                    vm.loadResellers();
                    $http.get('/admin/merchant-accounts/' + merchant.id).then(function(res) {
                        if (!res.data.success || !res.data.data) return;
                        var m = res.data.data;
                        var assignedResellerId = m.reseller_id
                            ? String(m.reseller_id)
                            : ((m.resellers && m.resellers.length > 0) ? String(m.resellers[0].id) : '');
                        vm.merchantForm = {
                            acquirer_account_id: m.acquirer_account_id ? String(m.acquirer_account_id) : '',
                            is_partner_merchant: !!m.is_partner_merchant,
                            partner_id: m.partner_id != null && m.partner_id !== '' ? String(m.partner_id) : '',
                            partner_name: m.partner_name || '',
                            team_id: m.team_id || '',
                            team_name: m.team_name || m.team_id || '',
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
                            merchant_type: 'merchant',
                            settlement_cycle_domestic: m.settlement_cycle_domestic != null ? m.settlement_cycle_domestic : 1,
                            settlement_cycle_international: m.settlement_cycle_international != null ? m.settlement_cycle_international : 7,
                            is_reseller_merchant: !!assignedResellerId,
                            reseller_id: assignedResellerId
                        };
                        // Prefill dependent dropdowns based on existing country/state.
                        var existingCountry = vm.merchantForm.business_country;
                        var existingState = vm.merchantForm.business_state;
                        var existingCity = vm.merchantForm.business_city;
                        if (existingCountry && vm.locationMap[existingCountry]) {
                            vm.availableStates = Object.keys(vm.locationMap[existingCountry]);
                        } else if (existingState) {
                            vm.availableStates = [existingState];
                        } else {
                            vm.availableStates = [];
                        }
                        if (existingCountry && existingState && vm.locationMap[existingCountry] && vm.locationMap[existingCountry][existingState]) {
                            vm.availableCities = vm.locationMap[existingCountry][existingState];
                        } else if (existingCity) {
                            vm.availableCities = [existingCity];
                        } else {
                            vm.availableCities = [];
                        }
                        vm.merchantForm.business_state = existingState;
                        vm.merchantForm.business_city = existingCity;
                        vm.merchantTab = 'details';
                        vm.loadPartners().then(function () {
                            vm.loadPartnerTeams();
                        });
                        var modal = new bootstrap.Modal(document.getElementById('newMerchantModal'));
                        modal.show();
                    }, function() {
                        alert('Failed to load merchant');
                    });
                };

                vm.onPartnerMerchantPartnerChange = function () {
                    if (!vm.merchantForm) {
                        return;
                    }
                    vm.merchantForm.team_name = '';
                    vm.loadPartnerTeams();
                };

                vm.loadPartnerTeams = function () {
                    vm.teams = [];
                    if (!vm.merchantForm) {
                        return;
                    }
                    if (!vm.merchantForm.partner_id) {
                        vm.merchantForm.partner_name = '';
                        vm.merchantForm.team_name = '';
                        return;
                    }
                    var pid = String(vm.merchantForm.partner_id);
                    var p = (vm.partners || []).filter(function (x) {
                        return String(x.id) === pid;
                    })[0];
                    if (p) {
                        vm.merchantForm.partner_name = p.name;
                        vm.teams = Array.isArray(p.teams) ? p.teams.slice() : [];
                    } else {
                        vm.merchantForm.partner_name = '';
                    }
                };

                vm.submitMerchant = function() {
                    vm.formErrors = {};
                    if (vm.merchantForm && vm.merchantForm.bank_account_holder_name != null && vm.merchantForm.bank_account_holder_name !== '') {
                        vm.merchantForm.bank_account_holder_name = String(vm.merchantForm.bank_account_holder_name);
                    }
                    if (!vm.validateMerchantFormFull()) {
                        if (typeof showToast === 'function') {
                            showToast('Please correct the highlighted errors in the form.', 'error');
                        }
                        return;
                    }

                    if (!vm.merchantForm.merchant_type || !['merchant'].includes(vm.merchantForm.merchant_type)) {
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
                            if (typeof showToast === 'function') {
                                showToast(isEdit ? 'Merchant updated successfully.' : 'Merchant account created successfully.', 'success');
                            }
                            vm.loadMerchants();
                        } else {
                            vm.formErrors = response.data.errors || {};

                            var msgText = (response.data.message || '').toString().toLowerCase();
                            var hasDuplicate =
                                msgText.indexOf('already exists') !== -1 ||
                                msgText.indexOf('has already been taken') !== -1;

                            // Normalize duplicate email error message (merchant email or login_name)
                            ['email', 'login_name'].forEach(function(field) {
                                if (vm.formErrors[field] && vm.formErrors[field].length) {
                                    vm.formErrors[field] = vm.formErrors[field].map(function(msg) {
                                        var lower = msg && msg.toString().toLowerCase();
                                        if (lower && (lower.indexOf('has already been taken') !== -1 || lower.indexOf('already exists') !== -1)) {
                                            hasDuplicate = true;
                                            return 'Email already exists.';
                                        }
                                        return msg;
                                    });
                                }
                            });

                            if (hasDuplicate && (!vm.formErrors.email || !vm.formErrors.email.length)) {
                                vm.formErrors.email = ['Email already exists.'];
                            }

                            // Build a user-friendly first validation error if available
                            var firstError = null;
                            if (vm.formErrors && Object.keys(vm.formErrors).length) {
                                Object.keys(vm.formErrors).some(function(key) {
                                    var arr = vm.formErrors[key];
                                    if (Array.isArray(arr) && arr.length) {
                                        firstError = arr[0];
                                        return true;
                                    }
                                    return false;
                                });
                            }

                            var errorMsg;
                            if (hasDuplicate) {
                                errorMsg = 'Email already exists.';
                            } else if (firstError) {
                                errorMsg = firstError;
                            } else {
                                errorMsg = response.data.message || (isEdit ? 'Failed to update merchant' : 'Failed to create merchant account');
                            }

                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            }
                        }
                    }, function(error) {
                        vm.submitting = false;
                        vm.formErrors = (error.data && error.data.errors) || {};

                        var msgText = (error.data && error.data.message ? error.data.message : '').toString().toLowerCase();
                        var hasDuplicate =
                            msgText.indexOf('already exists') !== -1 ||
                            msgText.indexOf('has already been taken') !== -1;

                        // Normalize duplicate email error message (merchant email or login_name)
                        ['email', 'login_name'].forEach(function(field) {
                            if (vm.formErrors[field] && vm.formErrors[field].length) {
                                vm.formErrors[field] = vm.formErrors[field].map(function(msg) {
                                    var lower = msg && msg.toString().toLowerCase();
                                    if (lower && (lower.indexOf('has already been taken') !== -1 || lower.indexOf('already exists') !== -1)) {
                                        hasDuplicate = true;
                                        return 'Email already exists.';
                                    }
                                    return msg;
                                });
                            }
                        });

                        if (hasDuplicate && (!vm.formErrors.email || !vm.formErrors.email.length)) {
                            vm.formErrors.email = ['Email already exists.'];
                        }

                        // Build a user-friendly first validation error if available
                        var firstError = null;
                        if (vm.formErrors && Object.keys(vm.formErrors).length) {
                            Object.keys(vm.formErrors).some(function(key) {
                                var arr = vm.formErrors[key];
                                if (Array.isArray(arr) && arr.length) {
                                    firstError = arr[0];
                                    return true;
                                }
                                return false;
                            });
                        }

                        var errorMsg;
                        if (hasDuplicate) {
                            errorMsg = 'Email already exists.';
                        } else if (firstError) {
                            errorMsg = firstError;
                        } else {
                            errorMsg = isEdit ? 'Failed to update merchant' : 'Failed to create merchant account';
                            if (error.data && error.data.message) {
                                errorMsg = error.data.message;
                            }
                        }

                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        }
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
                        if (response.data && response.data.success) {
                            var label = merchant.approval_status
                                ? merchant.approval_status.replace(/_/g, ' ').toUpperCase()
                                : 'UPDATED';
                            var msg = 'Approval status updated to ' + label + '.';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'success');
                            } else {
                                console.log(msg);
                            }
                        } else {
                            var err = 'Failed to update approval status: ' + ((response.data && response.data.message) || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(err, 'error');
                            } else {
                                console.error(err);
                            }
                            vm.loadMerchants(); // Reload to reset dropdown
                        }
                    }, function(error) {
                        var err = 'Failed to update approval status';
                        if (error && error.data && error.data.message) {
                            err = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(err, 'error');
                        } else {
                            console.error(err);
                        }
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
                            if (typeof showToast === 'function') {
                                showToast('Account status updated to ' + merchant.status.toUpperCase() + '.', 'success');
                            }
                        } else {
                            var msg = 'Failed to update account status: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                console.error(msg);
                            }
                            vm.loadMerchants(); // Reload to reset dropdown
                        }
                    }, function(error) {
                        var msg = 'Failed to update account status';
                        if (error && error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            console.error(msg);
                        }
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
                vm.loadLocations();
                vm.loadResellers();
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



