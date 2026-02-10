@extends('layouts.app-sidebar')

@section('title', 'Bank Code Success Rate - Admin - ' . config('app.name'))
@section('page-title', 'Bank Code Success Rate')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminBankCodeSuccessRateController as bcsr">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'BankCode-wise']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Bank Code Success Rate</h2>
            <p class="text-muted">Daywise Bank Code Success Rate</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="stat-card mb-3">
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Select Date Range:</label>
                <input type="text" class="form-control" id="dateRange" placeholder="Select Date Range" ng-model="bcsr.filters.date_range">
            </div>
            <div class="col-md-5">
                <label class="form-label">Select Merchants:</label>
                <div class="position-relative">
                    <input type="text" class="form-control" id="merchantSelect" placeholder="Select an merchant" ng-model="bcsr.selectedMerchantName" ng-focus="bcsr.showMerchantDropdown = true" ng-blur="bcsr.hideMerchantDropdown()">
                    <div class="dropdown-menu position-absolute w-100" id="merchantDropdown" ng-show="bcsr.showMerchantDropdown" style="max-height: 300px; overflow-y: auto; z-index: 1000; display: block;">
                        <div class="px-2 py-1">
                            <input type="text" class="form-control form-control-sm mb-2" placeholder="Search merchants..." ng-model="bcsr.merchantSearch" ng-change="bcsr.filterMerchants()">
                        </div>
                        <div ng-repeat="merchant in bcsr.filteredMerchants" class="dropdown-item" style="cursor: pointer;" ng-click="bcsr.selectMerchant(merchant)">
                            @{{ merchant.name }}
                        </div>
                        <div ng-if="bcsr.filteredMerchants.length === 0" class="dropdown-item text-muted">
                            No merchants found
                        </div>
                    </div>
                </div>
                <div ng-if="bcsr.filters.merchant_ids.length > 0" class="mt-2">
                    <span ng-repeat="merchantId in bcsr.filters.merchant_ids" class="badge bg-primary me-1 mb-1">
                        @{{ bcsr.getMerchantName(merchantId) }}
                        <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" ng-click="bcsr.removeMerchant(merchantId)" aria-label="Remove"></button>
                    </span>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" ng-click="bcsr.submitFilters()">
                    <i class="bi bi-check-lg"></i> Submit
                </button>
            </div>
        </div>
        <div ng-if="!bcsr.dataLoaded || (bcsr.dataLoaded && bcsr.data.length === 0 && !bcsr.loading)" class="text-center py-3">
            <p class="text-muted mb-0">No data for selected date range and merchants</p>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="bcsr.pagination.per_page" ng-change="bcsr.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="bcsr.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="bcsr.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="bcsr.visibleColumns.bank_code" checked> Bank Code</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="bcsr.visibleColumns.success_count" checked> Success Count</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="bcsr.visibleColumns.failure_count" checked> Failure Count</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="bcsr.visibleColumns.dropped_count" checked> Dropped Count</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="bcsr.visibleColumns.success_rate" checked> Success Rate (%)</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="bcsr.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>

        <!-- Data Table -->
        <div ng-show="bcsr.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="bcsr.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="bcsr.selectAll" ng-change="bcsr.toggleSelectAll()"> Bank Code
                            </th>
                            <th>Success Count</th>
                            <th>Failure Count</th>
                            <th>Dropped Count</th>
                            <th>Success Rate (%)</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="bcsr.tableFilters.bank_code" ng-change="bcsr.applyTableFilters()" placeholder="Bank Code"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="bcsr.tableFilters.success_count" ng-change="bcsr.applyTableFilters()" placeholder="Success Count"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="bcsr.tableFilters.failure_count" ng-change="bcsr.applyTableFilters()" placeholder="Failure Count"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="bcsr.tableFilters.dropped_count" ng-change="bcsr.applyTableFilters()" placeholder="Dropped Count"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="bcsr.tableFilters.success_rate" ng-change="bcsr.applyTableFilters()" placeholder="Success Rate"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="bcsr.data.length === 0 && !bcsr.loading">
                            <td colspan="5" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="item in bcsr.data track by item.bank_code"
                            ng-class="{'table-active': bcsr.selectedItem && bcsr.selectedItem.bank_code === item.bank_code}">
                            <td>
                                <input type="checkbox" ng-model="item.selected" ng-click="$event.stopPropagation(); bcsr.updateSelectionState()">
                                @{{ item.bank_code || 'N/A' }}
                            </td>
                            <td>@{{ item.success_count || 0 }}</td>
                            <td>@{{ item.failure_count || 0 }}</td>
                            <td>@{{ item.dropped_count || 0 }}</td>
                            <td>@{{ item.success_rate || '0.00' }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (bcsr.pagination.current_page - 1) * bcsr.pagination.per_page + 1 }}
                    to @{{ Math.min(bcsr.pagination.current_page * bcsr.pagination.per_page, bcsr.pagination.total) }}
                    of @{{ bcsr.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="bcsr.changePage(bcsr.pagination.current_page - 1)"
                            ng-disabled="bcsr.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ bcsr.pagination.current_page }} of @{{ bcsr.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="bcsr.changePage(bcsr.pagination.current_page + 1)"
                            ng-disabled="bcsr.pagination.current_page === bcsr.pagination.last_page">
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
            app.controller('AdminBankCodeSuccessRateController', ['$http', '$scope', function ($http, $scope) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.data = [];
                vm.merchants = [];
                vm.loading = false;
                vm.dataLoaded = false;
                vm.selectedItem = null;
                vm.selectAll = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    bank_code: true,
                    success_count: true,
                    failure_count: true,
                    dropped_count: true,
                    success_rate: true
                };

                vm.filters = {
                    date_range: '',
                    merchant_ids: []
                };

                vm.tableFilters = {
                    bank_code: '',
                    success_count: '',
                    failure_count: '',
                    dropped_count: '',
                    success_rate: ''
                };

                vm.selectedMerchantName = '';
                vm.merchantSearch = '';
                vm.filteredMerchants = [];
                vm.showMerchantDropdown = false;

                // Initialize date range picker
                vm.initDateRangePicker = function() {
                    // Set default date range (last 15 days as shown in screenshot)
                    var end = moment();
                    var start = moment().subtract(15, 'days');
                    vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');

                    $('#dateRange').daterangepicker({
                        startDate: start,
                        endDate: end,
                        locale: {
                            format: 'MM/DD/YYYY'
                        },
                        opens: 'left'
                    }, function(start, end) {
                        vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');
                        $scope.$apply();
                    });
                };

                vm.filterMerchants = function() {
                    if (!vm.merchantSearch || vm.merchantSearch === '') {
                        vm.filteredMerchants = vm.merchants;
                    } else {
                        var search = vm.merchantSearch.toLowerCase();
                        vm.filteredMerchants = vm.merchants.filter(function(m) {
                            return m.name.toLowerCase().indexOf(search) !== -1;
                        });
                    }
                };

                vm.hideMerchantDropdown = function() {
                    setTimeout(function() {
                        vm.showMerchantDropdown = false;
                        $scope.$apply();
                    }, 200);
                };

                vm.selectMerchant = function(merchant) {
                    if (vm.filters.merchant_ids.indexOf(merchant.id) === -1) {
                        vm.filters.merchant_ids.push(merchant.id);
                    }
                    vm.selectedMerchantName = '';
                    vm.showMerchantDropdown = false;
                    $scope.$apply();
                };

                vm.removeMerchant = function(merchantId) {
                    var index = vm.filters.merchant_ids.indexOf(merchantId);
                    if (index > -1) {
                        vm.filters.merchant_ids.splice(index, 1);
                    }
                };

                vm.getMerchantName = function(merchantId) {
                    var merchant = vm.merchants.find(function(m) { return m.id === merchantId; });
                    return merchant ? merchant.name : 'Unknown';
                };

                vm.loadMerchants = function () {
                    $http.get("{{ route('admin.reports.success-rate.bankcode-wise.merchants') }}").then(function (response) {
                        vm.merchants = response.data.data || [];
                        vm.filteredMerchants = vm.merchants;
                    });
                };

                vm.submitFilters = function () {
                    if (!vm.filters.date_range) {
                        alert('Please select a date range');
                        return;
                    }
                    vm.dataLoaded = true;
                    vm.pagination.current_page = 1;
                    vm.loadData();
                };

                vm.loadData = function () {
                    if (!vm.filters.date_range || !vm.dataLoaded) {
                        return;
                    }

                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.filters.date_range
                    };

                    if (vm.filters.merchant_ids && Array.isArray(vm.filters.merchant_ids) && vm.filters.merchant_ids.length > 0) {
                        params.merchant_ids = vm.filters.merchant_ids;
                    }

                    // Apply table filters
                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.reports.success-rate.bankcode-wise.data') }}", { params: params })
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
                            vm.selectAll = false;
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
                        bank_code: '',
                        success_count: '',
                        failure_count: '',
                        dropped_count: '',
                        success_rate: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.clearTableFilters();
                    vm.loadData();
                };

                vm.toggleSelectAll = function () {
                    vm.data.forEach(function (item) {
                        item.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.data.length > 0 && vm.data.every(function (item) { return item.selected; });
                };

                // Initialize
                vm.loadMerchants();
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

