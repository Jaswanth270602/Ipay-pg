@extends('layouts.app-sidebar')

@section('title', 'Partner TDR Management - Admin - ' . config('app.name'))
@section('page-title', 'Partner TDR Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPartnerTDRController as tdr">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Pg Partners','url'=>route('admin.partners.index')],
        ['label'=>'Pg Partner TDR']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Partner TDR Management</h2>
            <p class="text-muted">List of Partner TDR Details</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="tdr.pagination.per_page" ng-change="tdr.loadTDRData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="tdr.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="tdr.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="tdr.loadTDRData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.partner_id" checked> Partner ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.partner_name" checked> Partner Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.merchant_id" checked> Merchant ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.merchant_name" checked> Merchant Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.category" checked> Category</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.payment_mode" checked> Payment Mode</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.payment_channel" checked> Payment Channel</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="tdr.visibleColumns.bank_code" checked> Bank Code</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-primary" ng-click="tdr.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> Create
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="tdr.duplicateWithCategory()" ng-disabled="!tdr.selectedTDR">
                    <i class="bi bi-files"></i> Duplicatewithcategory
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="tdr.editTDR()" ng-disabled="!tdr.selectedTDR">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" ng-click="tdr.deleteTDR()" ng-disabled="!tdr.selectedTDR">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-sm btn-outline-info" ng-click="tdr.addVariableTDR()">
                    <i class="bi bi-plus-circle"></i> Add Variable TDR
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="tdr.duplicateTDR()" ng-disabled="!tdr.selectedTDR">
                    <i class="bi bi-copy"></i> Duplicate
                </button>
            </div>
        </div>
    </div>

    <!-- TDR Table -->
    <div class="stat-card">
        <div ng-show="tdr.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading TDR data...</p>
            </div>
        </div>

        <div ng-hide="tdr.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="tdr.selectAll" ng-change="tdr.toggleSelectAll()"> Partner Tdr ID
                            </th>
                            <th>Partner ID</th>
                            <th>Partner Name</th>
                            <th>Merchant ID</th>
                            <th>Merchant Name</th>
                            <th>Category</th>
                            <th>Payment Mode</th>
                            <th>Payment Channel</th>
                            <th>Bank Code</th>
                            <th>Partner TDR Fixed Fee</th>
                            <th>Partner TDR Percentage</th>
                            <th>Partner TDR Min Amount</th>
                            <th>Partner TDR Max Amount</th>
                            <th>Partner Min Transaction Charge</th>
                            <th>Partner Max Transaction Charge</th>
                            <th>Overall Profit Share %Age</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.partner_tdr_id" ng-change="tdr.applyFilters()" placeholder="ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.partner_id" ng-change="tdr.applyFilters()" placeholder="Partner ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.partner_name" ng-change="tdr.applyFilters()" placeholder="Partner Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.merchant_id" ng-change="tdr.applyFilters()" placeholder="Merchant ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.merchant_name" ng-change="tdr.applyFilters()" placeholder="Merchant Name"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="tdr.filters.category" ng-change="tdr.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="cat in tdr.categories" value="@{{ cat }}">@{{ cat }}</option>
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="tdr.filters.payment_mode" ng-change="tdr.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="mode in tdr.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="tdr.filters.payment_channel" ng-change="tdr.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.bank_code" ng-change="tdr.applyFilters()" placeholder="Bank Code"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.tdr_fixed_fee" ng-change="tdr.applyFilters()" placeholder="Fixed Fee"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.tdr_percentage" ng-change="tdr.applyFilters()" placeholder="Percentage"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.tdr_min_amount" ng-change="tdr.applyFilters()" placeholder="Min Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.tdr_max_amount" ng-change="tdr.applyFilters()" placeholder="Max Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.min_transaction_charge" ng-change="tdr.applyFilters()" placeholder="Min Charge"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.max_transaction_charge" ng-change="tdr.applyFilters()" placeholder="Max Charge"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="tdr.filters.overall_profit_share_percentage" ng-change="tdr.applyFilters()" placeholder="Profit Share %"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="tdr.tdrData.length === 0">
                            <td colspan="17" class="text-center text-muted py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="item in tdr.tdrData track by item.id"
                            ng-class="{'table-active': tdr.selectedTDR && tdr.selectedTDR.id === item.id}"
                            ng-click="tdr.selectTDR(item)">
                            <td>
                                <input type="checkbox" ng-model="item.selected" ng-click="$event.stopPropagation(); tdr.updateSelectionState()">
                                @{{ item.id }}
                            </td>
                            <td>@{{ item.partner_id || '-' }}</td>
                            <td>@{{ item.partner_name || '-' }}</td>
                            <td>@{{ item.merchant_id || '-' }}</td>
                            <td>@{{ item.merchant_name || '-' }}</td>
                            <td>@{{ item.category || '-' }}</td>
                            <td>@{{ item.payment_mode || '-' }}</td>
                            <td>@{{ item.payment_channel || '-' }}</td>
                            <td>@{{ item.bank_code || '-' }}</td>
                            <td>@{{ item.tdr_fixed_fee || '0.00' }}</td>
                            <td>@{{ item.tdr_percentage || '0.0000' }}</td>
                            <td>@{{ item.tdr_min_amount || '0.00' }}</td>
                            <td>@{{ item.tdr_max_amount || '0.00' }}</td>
                            <td>@{{ item.min_transaction_charge || '0.00' }}</td>
                            <td>@{{ item.max_transaction_charge || '0.00' }}</td>
                            <td>@{{ item.overall_profit_share_percentage || '0.0000' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" ng-click="tdr.viewTDR(item); $event.stopPropagation();" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" ng-click="tdr.editTDRItem(item); $event.stopPropagation();" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" ng-click="tdr.deleteTDRItem(item); $event.stopPropagation();" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (tdr.pagination.current_page - 1) * tdr.pagination.per_page + 1 }}
                    to @{{ Math.min(tdr.pagination.current_page * tdr.pagination.per_page, tdr.pagination.total) }}
                    of @{{ tdr.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="tdr.changePage(tdr.pagination.current_page - 1)"
                            ng-disabled="tdr.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ tdr.pagination.current_page }} of @{{ tdr.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="tdr.changePage(tdr.pagination.current_page + 1)"
                            ng-disabled="tdr.pagination.current_page === tdr.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="tdrModal" tabindex="-1" aria-labelledby="tdrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tdrModalLabel">
                        <i class="bi bi-percent"></i> @{{ tdr.isEditing ? 'Edit Partner TDR' : 'Create new entry' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form novalidate>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Partner Id <span class="text-danger">*</span></label>
                                <select class="form-select" ng-model="tdr.form.partner_id" required ng-change="tdr.onPartnerChange()">
                                    <option value="">Select Partner</option>
                                    <option ng-repeat="p in tdr.partners" value="@{{ p.id }}">@{{ p.name }} - @{{ p.id }}</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Search Merchant <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="tdr.merchantSearch" 
                                       ng-keyup="tdr.searchMerchants()" 
                                       placeholder="Search Merchant by Id, Name, Phone or Email">
                                <div ng-if="tdr.merchantSearchResults.length > 0" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">
                                    <a href="#" class="list-group-item list-group-item-action" 
                                       ng-repeat="m in tdr.merchantSearchResults" 
                                       ng-click="tdr.selectMerchant(m); $event.preventDefault();">
                                        @{{ m.name }} (@{{ m.id }}) - @{{ m.email }}
                                    </a>
                                </div>
                                <div ng-if="tdr.form.merchant_id" class="mt-2">
                                    <span class="badge bg-primary">Selected: @{{ tdr.form.merchant_name || 'Merchant ID: ' + tdr.form.merchant_id }}</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Merchant Category</label>
                                <select class="form-select" ng-model="tdr.form.category">
                                    <option value="">Select Category</option>
                                    <option ng-repeat="cat in tdr.categories" value="@{{ cat }}">@{{ cat }}</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Payment Mode</label>
                                <select class="form-select" ng-model="tdr.form.payment_mode" ng-change="tdr.onPaymentModeChange()">
                                    <option value="">Select Payment Mode</option>
                                    <option ng-repeat="mode in tdr.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="ATM Card">ATM Card</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Netbanking">Netbanking</option>
                                    <option value="Wallet">Wallet</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Bank</label>
                                <select class="form-select" ng-model="tdr.form.bank_code">
                                    <option value="">Select Bank</option>
                                    <option ng-repeat="bank in tdr.banks" value="@{{ bank.code }}">@{{ bank.name }} (@{{ bank.code }})</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TDR Fixed Fee</label>
                                <input type="number" class="form-control" ng-model="tdr.form.tdr_fixed_fee" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TDR Percentage</label>
                                <input type="number" class="form-control" ng-model="tdr.form.tdr_percentage" step="0.0001" min="0" max="100" placeholder="0.0000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TDR Min Amount</label>
                                <input type="number" class="form-control" ng-model="tdr.form.tdr_min_amount" step="0.01" min="0" placeholder="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TDR Max Amount</label>
                                <input type="number" class="form-control" ng-model="tdr.form.tdr_max_amount" step="0.01" min="0" placeholder="99999999.99">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Transaction Amount</label>
                                <input type="number" class="form-control" ng-model="tdr.form.min_transaction_amount" step="0.01" min="0" placeholder="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Transaction Amount</label>
                                <input type="number" class="form-control" ng-model="tdr.form.max_transaction_amount" step="0.01" min="0" placeholder="99999999.99">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Partner Min Transaction Charge</label>
                                <input type="number" class="form-control" ng-model="tdr.form.min_transaction_charge" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Partner Max Transaction Charge</label>
                                <input type="number" class="form-control" ng-model="tdr.form.max_transaction_charge" step="0.01" min="0" placeholder="99999999.99">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Overall Profit Share %Age</label>
                                <input type="number" class="form-control" ng-model="tdr.form.overall_profit_share_percentage" step="0.0001" min="0" max="100" placeholder="0.0000">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Payment Channel</label>
                                <select class="form-select" ng-model="tdr.form.payment_channel">
                                    <option value="">Select Payment Channel</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Bank Description</label>
                                <input type="text" class="form-control" ng-model="tdr.form.bank_description" placeholder="Bank Description">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" rows="3" ng-model="tdr.form.notes" placeholder="Enter notes"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="tdr.saveTDR()" ng-disabled="tdr.saving">
                        <span ng-if="tdr.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!tdr.saving"></i> @{{ tdr.isEditing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }

        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminPartnerTDRController', ['$http', '$timeout', function ($http, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.tdrData = [];
                vm.partners = [];
                vm.categories = [];
                vm.paymentModes = [];
                vm.banks = [];
                vm.loading = false;
                vm.saving = false;
                vm.isEditing = false;
                vm.selectedTDR = null;
                vm.selectAll = false;
                vm.merchantSearch = '';
                vm.merchantSearchResults = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    partner_id: true,
                    partner_name: true,
                    merchant_id: true,
                    merchant_name: true,
                    category: true,
                    payment_mode: true,
                    payment_channel: true,
                    bank_code: true
                };

                vm.filters = {
                    partner_tdr_id: '',
                    partner_id: '',
                    partner_name: '',
                    merchant_id: '',
                    merchant_name: '',
                    category: 'all',
                    payment_mode: 'all',
                    payment_channel: 'all',
                    bank_code: '',
                    tdr_fixed_fee: '',
                    tdr_percentage: '',
                    tdr_min_amount: '',
                    tdr_max_amount: '',
                    min_transaction_charge: '',
                    max_transaction_charge: '',
                    overall_profit_share_percentage: ''
                };

                vm.form = {};

                // Load initial data
                vm.loadPartners = function () {
                    $http.get("{{ route('admin.partners.tdr.partners') }}").then(function (response) {
                        vm.partners = response.data.data || [];
                    });
                };

                vm.loadCategories = function () {
                    $http.get("{{ route('admin.partners.tdr.categories') }}").then(function (response) {
                        vm.categories = response.data.data || [];
                    });
                };

                vm.loadPaymentModes = function () {
                    $http.get("{{ route('admin.partners.tdr.payment-modes') }}").then(function (response) {
                        vm.paymentModes = response.data.data || [];
                    });
                };

                vm.loadBanks = function (paymentMode) {
                    var params = paymentMode ? { payment_mode: paymentMode } : {};
                    $http.get("{{ route('admin.partners.tdr.banks') }}", { params: params }).then(function (response) {
                        vm.banks = response.data.data || [];
                    });
                };

                vm.searchMerchants = function () {
                    if (!vm.merchantSearch || vm.merchantSearch.length < 2) {
                        vm.merchantSearchResults = [];
                        return;
                    }
                    $http.get("{{ route('admin.partners.tdr.merchants.search') }}", {
                        params: { search: vm.merchantSearch }
                    }).then(function (response) {
                        vm.merchantSearchResults = response.data.data || [];
                    });
                };

                vm.selectMerchant = function (merchant) {
                    vm.form.merchant_id = merchant.id;
                    vm.form.merchant_name = merchant.name;
                    vm.merchantSearch = merchant.name;
                    vm.merchantSearchResults = [];
                };

                vm.onPartnerChange = function () {
                    var partner = vm.partners.find(function(p) { return p.id == vm.form.partner_id; });
                    if (partner) {
                        vm.form.partner_name = partner.name;
                    }
                };

                vm.onPaymentModeChange = function () {
                    vm.loadBanks(vm.form.payment_mode);
                };

                vm.loadTDRData = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function (key) {
                        if (vm.filters[key] !== undefined && vm.filters[key] !== null && vm.filters[key] !== '' && vm.filters[key] !== 'all') {
                            params[key] = vm.filters[key];
                        }
                    });

                    $http.get("{{ route('admin.partners.tdr.data') }}", { params: params })
                        .then(function (response) {
                            vm.tdrData = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                            vm.selectAll = false;
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load TDR data', 'error');
                            } else {
                                alert('Failed to load TDR data');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadTDRData();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadTDRData();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        partner_tdr_id: '',
                        partner_id: '',
                        partner_name: '',
                        merchant_id: '',
                        merchant_name: '',
                        category: 'all',
                        payment_mode: 'all',
                        payment_channel: 'all',
                        bank_code: '',
                        tdr_fixed_fee: '',
                        tdr_percentage: '',
                        tdr_min_amount: '',
                        tdr_max_amount: '',
                        min_transaction_charge: '',
                        max_transaction_charge: '',
                        overall_profit_share_percentage: ''
                    };
                    vm.applyFilters();
                };

                vm.resetView = function () {
                    vm.clearFilters();
                    vm.pagination.current_page = 1;
                    vm.pagination.per_page = 5;
                };

                vm.toggleSelectAll = function () {
                    vm.tdrData.forEach(function (item) {
                        item.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.tdrData.length > 0 && vm.tdrData.every(function (item) { return item.selected; });
                };

                vm.selectTDR = function (tdr) {
                    vm.selectedTDR = tdr;
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        partner_id: '',
                        partner_name: '',
                        merchant_id: '',
                        merchant_name: '',
                        category: '',
                        payment_mode: '',
                        payment_channel: '',
                        bank_code: '',
                        bank_description: '',
                        tdr_fixed_fee: 0,
                        tdr_percentage: 0,
                        tdr_min_amount: 0,
                        tdr_max_amount: 99999999.99,
                        min_transaction_amount: 0,
                        max_transaction_amount: 99999999.99,
                        min_transaction_charge: 0,
                        max_transaction_charge: 99999999.99,
                        overall_profit_share_percentage: 0,
                        notes: ''
                    };
                    vm.merchantSearch = '';
                    vm.merchantSearchResults = [];
                    vm.loadPartners();
                    vm.loadCategories();
                    vm.loadPaymentModes();
                    vm.loadBanks();
                    var modal = new bootstrap.Modal(document.getElementById('tdrModal'));
                    modal.show();
                };

                vm.editTDRItem = function (item) {
                    vm.isEditing = true;
                    vm.form = angular.copy(item);
                    vm.merchantSearch = item.merchant_name || '';
                    vm.merchantSearchResults = [];
                    vm.loadPartners();
                    vm.loadCategories();
                    vm.loadPaymentModes();
                    vm.loadBanks(vm.form.payment_mode);
                    var modal = new bootstrap.Modal(document.getElementById('tdrModal'));
                    modal.show();
                };

                vm.editTDR = function () {
                    if (vm.selectedTDR) {
                        vm.editTDRItem(vm.selectedTDR);
                    }
                };

                vm.viewTDR = function (item) {
                    // TODO: Implement view functionality
                    alert('View TDR: ' + item.id);
                };

                vm.saveTDR = function () {
                    if (!vm.form.partner_id || !vm.form.merchant_id) {
                        if (typeof showToast === 'function') {
                            showToast('Please select Partner and Merchant', 'error');
                        } else {
                            alert('Please select Partner and Merchant');
                        }
                        return;
                    }

                    vm.saving = true;
                    var url, method;
                    if (vm.isEditing) {
                        url = "{{ url('admin/partners/tdr') }}/" + vm.form.id;
                        method = 'POST';
                    } else {
                        url = "{{ route('admin.partners.tdr.store') }}";
                        method = 'POST';
                    }

                    $http({
                        method: method,
                        url: url,
                        data: vm.form,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('tdrModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'TDR saved successfully', 'success');
                            } else {
                                alert(response.data.message || 'TDR saved successfully');
                            }
                            vm.loadTDRData();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to save TDR';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var msg = 'Failed to save TDR';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            msg = errors.join(', ');
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.deleteTDRItem = function (item) {
                    if (!confirm('Are you sure you want to delete this TDR?')) return;

                    $http.delete("{{ url('admin/partners/tdr') }}/" + item.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'TDR deleted successfully', 'success');
                            } else {
                                alert(response.data.message || 'TDR deleted successfully');
                            }
                            vm.loadTDRData();
                        }
                    }, function (error) {
                        var msg = 'Failed to delete TDR';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.deleteTDR = function () {
                    if (vm.selectedTDR) {
                        vm.deleteTDRItem(vm.selectedTDR);
                    }
                };

                vm.duplicateTDR = function () {
                    if (!vm.selectedTDR) return;
                    if (!confirm('Are you sure you want to duplicate this TDR?')) return;

                    $http.post("{{ url('admin/partners/tdr') }}/" + vm.selectedTDR.id + "/duplicate", {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'TDR duplicated successfully', 'success');
                            } else {
                                alert(response.data.message || 'TDR duplicated successfully');
                            }
                            vm.loadTDRData();
                        }
                    });
                };

                vm.duplicateWithCategory = function () {
                    if (!vm.selectedTDR) return;
                    // TODO: Implement duplicate with category functionality
                    alert('Duplicate with category functionality - Coming soon');
                };

                vm.addVariableTDR = function () {
                    // TODO: Implement add variable TDR functionality
                    alert('Add Variable TDR functionality - Coming soon');
                };

                // Initialize
                vm.loadPartners();
                vm.loadCategories();
                vm.loadPaymentModes();
                vm.loadBanks();
                vm.loadTDRData();
            }]);
        } catch (e) {
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

