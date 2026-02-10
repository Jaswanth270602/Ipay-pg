@extends('layouts.app-sidebar')

@section('title', 'Partner Team Profit Report - Admin - ' . config('app.name'))
@section('page-title', 'Partner Team Profit Report')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPartnerTeamProfitController as ptp">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Canned Report']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0" style="color: #6c757d; font-size: 1.5rem;">PROFITABILITY</h2>
                <h3 class="mb-0" style="color: #adb5bd; font-size: 1rem; font-weight: normal;">Partner Team Profit</h3>
            </div>
            <div>
                <small class="text-muted">Home > Canned Report</small>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
    <div class="border-bottom mb-3" style="border-color: #6366f1 !important; border-width: 2px !important;"></div>

    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="mb-0" style="color: #495057; font-size: 1.25rem; font-weight: 600;">PROFITABILITY Partner Team Profit Report</h3>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="stat-card mb-3">
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" id="dateRange" placeholder="Select Date Range" ng-model="ptp.filters.date_range" style="width: 300px; cursor: pointer;" readonly>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="ptp.pagination.per_page" ng-change="ptp.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="ptp.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="ptp.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.partner_id" checked> Partner ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.partner_name" checked> Partner Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.merchant_id" checked> Merchant ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.merchant_name" checked> Merchant Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.transaction_sequence_id" checked> Transaction Sequence ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.transaction_id" checked> Transaction ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.order_id" checked> Order ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.payment_datetime" checked> Payment DateTime</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.payment_mode" checked> Payment Mode</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.payment_channel" checked> Payment Channel</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.merchant_tdr_percentage" checked> Merchant TDR %Age</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.merchant_tdr_fixed_fee" checked> Merchant TDR Fixedfee</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.merchant_tdr_amount" checked> Merchant TDR Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.partner_base_rate_percentage" checked> Partner Base Rate %age</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.partner_base_rate_fixed_fee" checked> Partner Base Rate Fixedfee</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.partner_tdr_amount" checked> Partner TDR Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ptp.visibleColumns.profit" checked> Profit</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="ptp.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="ptp.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="ptp.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th ng-show="ptp.visibleColumns.partner_id">
                                <i class="bi bi-diamond"></i> Partner ID
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_name">
                                <i class="bi bi-diamond"></i> Partner Name
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_id">
                                <i class="bi bi-diamond"></i> Merchant ID
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_name">
                                <i class="bi bi-diamond"></i> Merchant Name
                            </th>
                            <th ng-show="ptp.visibleColumns.transaction_sequence_id">
                                <i class="bi bi-diamond"></i> Transaction Sequence ID
                            </th>
                            <th ng-show="ptp.visibleColumns.transaction_id">
                                <i class="bi bi-diamond"></i> Transaction ID
                            </th>
                            <th ng-show="ptp.visibleColumns.order_id">
                                <i class="bi bi-diamond"></i> Order ID
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_datetime">
                                <i class="bi bi-diamond"></i> Payment DateTime
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_mode">
                                <i class="bi bi-diamond"></i> Payment Mode
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_channel">
                                <i class="bi bi-diamond"></i> Payment Channel
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_percentage">
                                <i class="bi bi-diamond"></i> Merchant TDR %Age
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_fixed_fee">
                                <i class="bi bi-diamond"></i> Merchant TDR Fixedfee
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_amount">
                                <i class="bi bi-diamond"></i> Merchant TDR Amount
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_base_rate_percentage">
                                <i class="bi bi-diamond"></i> Partner Base Rate %age
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_base_rate_fixed_fee">
                                <i class="bi bi-diamond"></i> Partner Base Rate Fixedfee
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_tdr_amount">
                                <i class="bi bi-diamond"></i> Partner TDR Amount
                            </th>
                            <th ng-show="ptp.visibleColumns.profit">
                                <i class="bi bi-diamond"></i> Profit
                            </th>
                        </tr>
                        <tr>
                            <th ng-show="ptp.visibleColumns.partner_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.partner_id" ng-change="ptp.applyTableFilters()" placeholder="Partner ID">
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_name">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.partner_name" ng-change="ptp.applyTableFilters()" placeholder="Partner Name">
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.merchant_id" ng-change="ptp.applyTableFilters()" placeholder="Merchant ID">
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_name">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.merchant_name" ng-change="ptp.applyTableFilters()" placeholder="Merchant Name">
                            </th>
                            <th ng-show="ptp.visibleColumns.transaction_sequence_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.transaction_sequence_id" ng-change="ptp.applyTableFilters()" placeholder="Transaction Sequence ID">
                            </th>
                            <th ng-show="ptp.visibleColumns.transaction_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.transaction_id" ng-change="ptp.applyTableFilters()" placeholder="Transaction ID">
                            </th>
                            <th ng-show="ptp.visibleColumns.order_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.order_id" ng-change="ptp.applyTableFilters()" placeholder="Order ID">
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_datetime">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.payment_datetime" ng-change="ptp.applyTableFilters()" placeholder="Payment DateTime">
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_mode">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.payment_mode" ng-change="ptp.applyTableFilters()" placeholder="Payment Mode">
                            </th>
                            <th ng-show="ptp.visibleColumns.payment_channel">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.payment_channel" ng-change="ptp.applyTableFilters()" placeholder="Payment Channel">
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_percentage">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.merchant_tdr_percentage" ng-change="ptp.applyTableFilters()" placeholder="Merchant TDR %Age">
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_fixed_fee">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.merchant_tdr_fixed_fee" ng-change="ptp.applyTableFilters()" placeholder="Merchant TDR Fixedfee">
                            </th>
                            <th ng-show="ptp.visibleColumns.merchant_tdr_amount">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.merchant_tdr_amount" ng-change="ptp.applyTableFilters()" placeholder="Merchant TDR Amount">
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_base_rate_percentage">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.partner_base_rate_percentage" ng-change="ptp.applyTableFilters()" placeholder="Partner Base Rate %age">
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_base_rate_fixed_fee">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.partner_base_rate_fixed_fee" ng-change="ptp.applyTableFilters()" placeholder="Partner Base Rate Fixedfee">
                            </th>
                            <th ng-show="ptp.visibleColumns.partner_tdr_amount">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.partner_tdr_amount" ng-change="ptp.applyTableFilters()" placeholder="Partner TDR Amount">
                            </th>
                            <th ng-show="ptp.visibleColumns.profit">
                                <input type="text" class="form-control form-control-sm" ng-model="ptp.tableFilters.profit" ng-change="ptp.applyTableFilters()" placeholder="Profit">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="ptp.data.length === 0 && !ptp.loading">
                            <td colspan="17" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="item in ptp.data track by item.id">
                            <td ng-show="ptp.visibleColumns.partner_id">@{{ item.partner_id || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.partner_name">@{{ item.partner_name || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.merchant_id">@{{ item.merchant_id || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.merchant_name">@{{ item.merchant_name || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.transaction_sequence_id">@{{ item.transaction_sequence_id || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.transaction_id">@{{ item.transaction_txn_id || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.order_id">@{{ item.order_order_id || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.payment_datetime">@{{ item.payment_datetime ? (item.payment_datetime | date:'dd/MM/yyyy HH:mm:ss') : 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.payment_mode">@{{ item.payment_mode || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.payment_channel">@{{ item.payment_channel || 'N/A' }}</td>
                            <td ng-show="ptp.visibleColumns.merchant_tdr_percentage">@{{ item.merchant_tdr_percentage || '0.0000' }}%</td>
                            <td ng-show="ptp.visibleColumns.merchant_tdr_fixed_fee">@{{ item.merchant_tdr_fixed_fee || '0.00' }}</td>
                            <td ng-show="ptp.visibleColumns.merchant_tdr_amount">@{{ item.merchant_tdr_amount || '0.00' }}</td>
                            <td ng-show="ptp.visibleColumns.partner_base_rate_percentage">@{{ item.partner_base_rate_percentage || '0.0000' }}%</td>
                            <td ng-show="ptp.visibleColumns.partner_base_rate_fixed_fee">@{{ item.partner_base_rate_fixed_fee || '0.00' }}</td>
                            <td ng-show="ptp.visibleColumns.partner_tdr_amount">@{{ item.partner_tdr_amount || '0.00' }}</td>
                            <td ng-show="ptp.visibleColumns.profit">@{{ item.profit || '0.00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (ptp.pagination.current_page - 1) * ptp.pagination.per_page + 1 }}
                    to @{{ Math.min(ptp.pagination.current_page * ptp.pagination.per_page, ptp.pagination.total) }}
                    of @{{ ptp.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="ptp.changePage(ptp.pagination.current_page - 1)"
                            ng-disabled="ptp.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ ptp.pagination.current_page }} of @{{ ptp.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="ptp.changePage(ptp.pagination.current_page + 1)"
                            ng-disabled="ptp.pagination.current_page === ptp.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
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
            app.controller('AdminPartnerTeamProfitController', ['$http', '$scope', function ($http, $scope) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.data = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    partner_id: true,
                    partner_name: true,
                    merchant_id: true,
                    merchant_name: true,
                    transaction_sequence_id: true,
                    transaction_id: true,
                    order_id: true,
                    payment_datetime: true,
                    payment_mode: true,
                    payment_channel: true,
                    merchant_tdr_percentage: true,
                    merchant_tdr_fixed_fee: true,
                    merchant_tdr_amount: true,
                    partner_base_rate_percentage: true,
                    partner_base_rate_fixed_fee: true,
                    partner_tdr_amount: true,
                    profit: true
                };

                vm.filters = {
                    date_range: ''
                };

                vm.tableFilters = {
                    partner_id: '',
                    partner_name: '',
                    merchant_id: '',
                    merchant_name: '',
                    transaction_sequence_id: '',
                    transaction_id: '',
                    order_id: '',
                    payment_datetime: '',
                    payment_mode: '',
                    payment_channel: '',
                    merchant_tdr_percentage: '',
                    merchant_tdr_fixed_fee: '',
                    merchant_tdr_amount: '',
                    partner_base_rate_percentage: '',
                    partner_base_rate_fixed_fee: '',
                    partner_tdr_amount: '',
                    profit: ''
                };

                // Initialize date range picker
                vm.initDateRangePicker = function() {
                    // Set default date range (last 15 days)
                    var end = moment();
                    var start = moment().subtract(15, 'days');
                    vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');

                    $('#dateRange').daterangepicker({
                        startDate: start,
                        endDate: end,
                        locale: {
                            format: 'MM/DD/YYYY'
                        },
                        opens: 'left',
                        autoUpdateInput: true,
                        showDropdowns: true
                    }, function(start, end) {
                        vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');
                        $scope.$apply();
                        vm.loadData();
                    });

                    // Auto-load data on page load
                    vm.loadData();
                };

                vm.loadData = function () {
                    if (!vm.filters.date_range) {
                        return;
                    }

                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.filters.date_range
                    };

                    // Apply table filters
                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.reports.profitability.partner-team-profit.data') }}", { params: params })
                        .then(function (response) {
                            if (response.data && response.data.success) {
                                vm.data = response.data.data || [];
                                vm.pagination = {
                                    current_page: response.data.pagination.current_page,
                                    per_page: response.data.pagination.per_page,
                                    total: response.data.pagination.total,
                                    last_page: response.data.pagination.last_page
                                };
                            } else {
                                vm.data = [];
                            }
                            vm.loading = false;
                        }, function (error) {
                            vm.loading = false;
                            vm.data = [];
                            var msg = 'Failed to load data';
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

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadData();
                    }
                };

                vm.applyTableFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadData();
                };

                vm.clearTableFilters = function () {
                    vm.tableFilters = {
                        partner_id: '',
                        partner_name: '',
                        merchant_id: '',
                        merchant_name: '',
                        transaction_sequence_id: '',
                        transaction_id: '',
                        order_id: '',
                        payment_datetime: '',
                        payment_mode: '',
                        payment_channel: '',
                        merchant_tdr_percentage: '',
                        merchant_tdr_fixed_fee: '',
                        merchant_tdr_amount: '',
                        partner_base_rate_percentage: '',
                        partner_base_rate_fixed_fee: '',
                        partner_tdr_amount: '',
                        profit: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.clearTableFilters();
                    vm.loadData();
                };

                // Initialize
                setTimeout(function() {
                    vm.initDateRangePicker();
                }, 500);
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

