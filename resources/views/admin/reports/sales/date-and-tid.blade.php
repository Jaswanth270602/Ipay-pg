@extends('layouts.app-sidebar')

@section('title', 'Sales Date and TID Report - Admin - ' . config('app.name'))
@section('page-title', 'Sales Date and TID Report')

@section('content')
<div ng-app="badlicashApp" ng-controller="SalesDateAndTidController as sr">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Canned Report']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0" style="color: #6c757d; font-size: 1.5rem;">SALES</h2>
                <h3 class="mb-0" style="color: #adb5bd; font-size: 1rem; font-weight: normal;">Date and TID</h3>
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
            <h3 class="mb-0" style="color: #495057; font-size: 1.25rem; font-weight: 600;">SALES Date and TID Report</h3>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="stat-card mb-3">
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" id="dateRange" placeholder="Select Date Range" ng-model="sr.filters.date_range" style="width: 300px; cursor: pointer;" readonly>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="sr.pagination.per_page" ng-change="sr.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="sr.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="sr.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="sr.visibleColumns.transaction_provider" checked> Transaction Provider</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="sr.visibleColumns.mid_name" checked> Mid Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="sr.visibleColumns.transaction_date" checked> Transaction Date</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="sr.visibleColumns.transaction_count" checked> Transaction Count</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="sr.visibleColumns.transaction_total_amount" checked> Transaction Total Amount</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="sr.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="sr.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="sr.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th ng-show="sr.visibleColumns.transaction_provider">
                                <i class="bi bi-diamond"></i> Transaction Provider
                            </th>
                            <th ng-show="sr.visibleColumns.mid_name">
                                <i class="bi bi-diamond"></i> Mid Name
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_date">
                                <i class="bi bi-diamond"></i> Transaction Date
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_count">
                                <i class="bi bi-diamond"></i> Transaction Count
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_total_amount">
                                <i class="bi bi-diamond"></i> Transaction Total Amount
                            </th>
                        </tr>
                        <tr>
                            <th ng-show="sr.visibleColumns.transaction_provider">
                                <input type="text" class="form-control form-control-sm" ng-model="sr.tableFilters.transaction_provider" ng-change="sr.applyTableFilters()" placeholder="Transaction Provider">
                            </th>
                            <th ng-show="sr.visibleColumns.mid_name">
                                <input type="text" class="form-control form-control-sm" ng-model="sr.tableFilters.mid_name" ng-change="sr.applyTableFilters()" placeholder="Mid Name">
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_date">
                                <input type="text" class="form-control form-control-sm" ng-model="sr.tableFilters.transaction_date" ng-change="sr.applyTableFilters()" placeholder="Transaction Date">
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_count">
                                <input type="text" class="form-control form-control-sm" ng-model="sr.tableFilters.transaction_count" ng-change="sr.applyTableFilters()" placeholder="Transaction Count">
                            </th>
                            <th ng-show="sr.visibleColumns.transaction_total_amount">
                                <input type="text" class="form-control form-control-sm" ng-model="sr.tableFilters.transaction_total_amount" ng-change="sr.applyTableFilters()" placeholder="Transaction Total Amount">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="sr.data.length === 0 && !sr.loading">
                            <td colspan="5" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="item in sr.data track by $index">
                            <td ng-show="sr.visibleColumns.transaction_provider">@{{ item.transaction_provider || 'N/A' }}</td>
                            <td ng-show="sr.visibleColumns.mid_name">@{{ item.mid_name || 'N/A' }}</td>
                            <td ng-show="sr.visibleColumns.transaction_date">@{{ item.transaction_date || 'N/A' }}</td>
                            <td ng-show="sr.visibleColumns.transaction_count">@{{ item.transaction_count || 0 }}</td>
                            <td ng-show="sr.visibleColumns.transaction_total_amount">@{{ item.transaction_total_amount || '0.00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (sr.pagination.current_page - 1) * sr.pagination.per_page + 1 }}
                    to @{{ Math.min(sr.pagination.current_page * sr.pagination.per_page, sr.pagination.total) }}
                    of @{{ sr.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="sr.changePage(sr.pagination.current_page - 1)"
                            ng-disabled="sr.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ sr.pagination.current_page }} of @{{ sr.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="sr.changePage(sr.pagination.current_page + 1)"
                            ng-disabled="sr.pagination.current_page === sr.pagination.last_page">
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
            app.controller('SalesDateAndTidController', ['$http', '$scope', function ($http, $scope) {
                var vm = this;

                vm.data = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    transaction_provider: true,
                    mid_name: true,
                    transaction_date: true,
                    transaction_count: true,
                    transaction_total_amount: true
                };

                vm.filters = {
                    date_range: ''
                };

                vm.tableFilters = {
                    transaction_provider: '',
                    mid_name: '',
                    transaction_date: '',
                    transaction_count: '',
                    transaction_total_amount: ''
                };

                vm.initDateRangePicker = function() {
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
                        autoUpdateInput: true
                    }, function(start, end) {
                        vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');
                        $scope.$apply();
                        vm.loadData();
                    });

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

                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.reports.sales.date-and-tid.data') }}", { params: params })
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
                        transaction_provider: '',
                        mid_name: '',
                        transaction_date: '',
                        transaction_count: '',
                        transaction_total_amount: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.clearTableFilters();
                    vm.loadData();
                };

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

