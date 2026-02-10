@extends('layouts.app-sidebar')

@section('title', 'Partner Settlement Details - Admin - ' . config('app.name'))
@section('page-title', 'Partner Settlement Details')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPartnerSettlementsDetailsController as psd">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Partner Settlement Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Partner Settlement Details</h2>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="psd.pagination.per_page" ng-change="psd.loadSettlements()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="psd.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="psd.loadSettlements()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.settlement_detail_id" checked> Settlement Detail ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.partner_name" checked> Partner Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.merchant_id" checked> Merchant ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.merchant_name" checked> Merchant Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.transaction_id" checked> Transaction ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.settlement_record_id" checked> Settlement Record ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.transaction_amount" checked> Transaction Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.partner_tdr_percentage" checked> Partner TDR Percentage</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.partner_tdr_fixed_fee" checked> Partner TDR Fixed Fee</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.partner_tdr_amount" checked> Partner TDR Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.merchant_tdr_percentage" checked> Merchant TDR Percentage</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.merchant_tdr_fixed_fee" checked> Merchant TDR Fixed Fee</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.tdr_amount" checked> TDR Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.partner_revenue" checked> Partner Revenue</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.bank_code" checked> Bank Code</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.payment_datetime" checked> Payment Datetime</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.merchant_category" checked> Merchant Category</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.payment_mode" checked> Mode Of Payment</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="psd.visibleColumns.organization_name" checked> Organization Name</label></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlements Table -->
    <div class="stat-card">
        <div ng-show="psd.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlement details...</p>
            </div>
        </div>

        <div ng-hide="psd.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="psd.selectAll" ng-change="psd.toggleSelectAll()"> Settlement Detail ID
                            </th>
                            <th>Partner Name</th>
                            <th>Merchant ID</th>
                            <th>Merchant Name</th>
                            <th>Transaction ID</th>
                            <th>Settlement Record ID</th>
                            <th>Transaction Amount</th>
                            <th>Partner TDR Percentage</th>
                            <th>Partner TDR Fixed Fee</th>
                            <th>Partner TDR Amount</th>
                            <th>Merchant TDR Percentage</th>
                            <th>Merchant TDR Fixed Fee</th>
                            <th>TDR Amount</th>
                            <th>Partner Revenue</th>
                            <th>Bank Code</th>
                            <th>Payment Datetime</th>
                            <th>Merchant Category</th>
                            <th>Mode Of Payment</th>
                            <th>Organization Name</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.settlement_detail_id" ng-change="psd.applyFilters()" placeholder="Detail ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.partner_name" ng-change="psd.applyFilters()" placeholder="Partner Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.merchant_id" ng-change="psd.applyFilters()" placeholder="Merchant ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.merchant_name" ng-change="psd.applyFilters()" placeholder="Merchant Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.transaction_id" ng-change="psd.applyFilters()" placeholder="Transaction ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.settlement_record_id" ng-change="psd.applyFilters()" placeholder="Settlement Record ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.transaction_amount" ng-change="psd.applyFilters()" placeholder="Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.partner_tdr_percentage" ng-change="psd.applyFilters()" placeholder="TDR %"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.partner_tdr_fixed_fee" ng-change="psd.applyFilters()" placeholder="Fixed Fee"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.partner_tdr_amount" ng-change="psd.applyFilters()" placeholder="TDR Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.merchant_tdr_percentage" ng-change="psd.applyFilters()" placeholder="Merchant TDR %"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.merchant_tdr_fixed_fee" ng-change="psd.applyFilters()" placeholder="Merchant Fixed Fee"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.tdr_amount" ng-change="psd.applyFilters()" placeholder="Total TDR"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.partner_revenue" ng-change="psd.applyFilters()" placeholder="Revenue"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.bank_code" ng-change="psd.applyFilters()" placeholder="Bank Code"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="psd.filters.payment_datetime" placeholder="MM/DD/YYYY-MM/DD/" ng-change="psd.applyFilters()"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="psd.filters.merchant_category" ng-change="psd.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="cat in psd.categories" value="@{{ cat }}">@{{ cat }}</option>
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="psd.filters.payment_mode" ng-change="psd.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="mode in psd.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="psd.filters.organization_name" ng-change="psd.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="org in psd.organizations" value="@{{ org }}">@{{ org }}</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="psd.settlements.length === 0">
                            <td colspan="20" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="detail in psd.settlements track by detail.id"
                            ng-class="{'table-active': psd.selectedSettlement && psd.selectedSettlement.id === detail.id}"
                            ng-click="psd.selectSettlement(detail)">
                            <td>
                                <input type="checkbox" ng-model="detail.selected" ng-click="$event.stopPropagation(); psd.updateSelectionState()">
                                @{{ detail.id }}
                            </td>
                            <td>@{{ detail.partner_name || '-' }}</td>
                            <td>@{{ detail.merchant_id || '-' }}</td>
                            <td>@{{ detail.merchant_name || '-' }}</td>
                            <td>@{{ detail.transaction_txn_id || '-' }}</td>
                            <td>@{{ detail.settlement_record_id || '-' }}</td>
                            <td>@{{ detail.transaction_amount || '0.00' }}</td>
                            <td>@{{ detail.partner_tdr_percentage || '0.0000' }}</td>
                            <td>@{{ detail.partner_tdr_fixed_fee || '0.00' }}</td>
                            <td>@{{ detail.partner_tdr_amount || '0.00' }}</td>
                            <td>@{{ detail.merchant_tdr_percentage || '0.0000' }}</td>
                            <td>@{{ detail.merchant_tdr_fixed_fee || '0.00' }}</td>
                            <td>@{{ detail.tdr_amount || '0.00' }}</td>
                            <td>@{{ detail.partner_revenue || '0.00' }}</td>
                            <td>@{{ detail.bank_code || '-' }}</td>
                            <td>@{{ detail.payment_datetime || '-' }}</td>
                            <td>@{{ detail.merchant_category || '-' }}</td>
                            <td>@{{ detail.payment_mode || '-' }}</td>
                            <td>@{{ detail.organization_name || '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" ng-click="psd.viewSettlement(detail); $event.stopPropagation();" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" ng-click="psd.editSettlement(detail); $event.stopPropagation();" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (psd.pagination.current_page - 1) * psd.pagination.per_page + 1 }}
                    to @{{ Math.min(psd.pagination.current_page * psd.pagination.per_page, psd.pagination.total) }}
                    of @{{ psd.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="psd.changePage(psd.pagination.current_page - 1)"
                            ng-disabled="psd.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ psd.pagination.current_page }} of @{{ psd.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="psd.changePage(psd.pagination.current_page + 1)"
                            ng-disabled="psd.pagination.current_page === psd.pagination.last_page">
                        Next
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
            app.controller('AdminPartnerSettlementsDetailsController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.settlements = [];
                vm.categories = [];
                vm.paymentModes = [];
                vm.organizations = [];
                vm.loading = false;
                vm.selectedSettlement = null;
                vm.selectAll = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    settlement_detail_id: true,
                    partner_name: true,
                    merchant_id: true,
                    merchant_name: true,
                    transaction_id: true,
                    settlement_record_id: true,
                    transaction_amount: true,
                    partner_tdr_percentage: true,
                    partner_tdr_fixed_fee: true,
                    partner_tdr_amount: true,
                    merchant_tdr_percentage: true,
                    merchant_tdr_fixed_fee: true,
                    tdr_amount: true,
                    partner_revenue: true,
                    bank_code: true,
                    payment_datetime: true,
                    merchant_category: true,
                    payment_mode: true,
                    organization_name: true
                };

                vm.filters = {
                    settlement_detail_id: '',
                    partner_name: '',
                    partner_id: '',
                    merchant_id: '',
                    merchant_name: '',
                    transaction_id: '',
                    settlement_record_id: '',
                    transaction_amount: '',
                    partner_tdr_percentage: '',
                    partner_tdr_fixed_fee: '',
                    partner_tdr_amount: '',
                    merchant_tdr_percentage: '',
                    merchant_tdr_fixed_fee: '',
                    tdr_amount: '',
                    partner_revenue: '',
                    bank_code: '',
                    payment_datetime: '',
                    merchant_category: 'all',
                    payment_mode: 'all',
                    organization_name: 'all'
                };

                vm.loadCategories = function () {
                    $http.get("{{ route('admin.partner-settlements.merchant-categories') }}").then(function (response) {
                        vm.categories = response.data.data || [];
                    });
                };

                vm.loadPaymentModes = function () {
                    $http.get("{{ route('admin.partner-settlements.payment-modes') }}").then(function (response) {
                        vm.paymentModes = response.data.data || [];
                    });
                };

                vm.loadOrganizations = function () {
                    $http.get("{{ route('admin.partner-settlements.organizations') }}").then(function (response) {
                        vm.organizations = response.data.data || [];
                    });
                };

                vm.loadSettlements = function () {
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

                    $http.get("{{ route('admin.partner-settlements.details.data') }}", { params: params })
                        .then(function (response) {
                            vm.settlements = response.data.data || [];
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
                                showToast('Failed to load settlement details', 'error');
                            } else {
                                alert('Failed to load settlement details');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadSettlements();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadSettlements();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        settlement_detail_id: '',
                        partner_name: '',
                        partner_id: '',
                        merchant_id: '',
                        merchant_name: '',
                        transaction_id: '',
                        settlement_record_id: '',
                        transaction_amount: '',
                        partner_tdr_percentage: '',
                        partner_tdr_fixed_fee: '',
                        partner_tdr_amount: '',
                        merchant_tdr_percentage: '',
                        merchant_tdr_fixed_fee: '',
                        tdr_amount: '',
                        partner_revenue: '',
                        bank_code: '',
                        payment_datetime: '',
                        merchant_category: 'all',
                        payment_mode: 'all',
                        organization_name: 'all'
                    };
                    vm.applyFilters();
                };

                vm.toggleSelectAll = function () {
                    vm.settlements.forEach(function (s) {
                        s.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.settlements.length > 0 && vm.settlements.every(function (s) { return s.selected; });
                };

                vm.selectSettlement = function (settlement) {
                    vm.selectedSettlement = settlement;
                };

                vm.viewSettlement = function (detail) {
                    // TODO: Implement view functionality
                    alert('View Settlement Detail: ' + detail.id);
                };

                vm.editSettlement = function (detail) {
                    // TODO: Implement edit functionality
                    alert('Edit Settlement Detail: ' + detail.id);
                };

                // Initialize
                vm.loadCategories();
                vm.loadPaymentModes();
                vm.loadOrganizations();
                vm.loadSettlements();
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
