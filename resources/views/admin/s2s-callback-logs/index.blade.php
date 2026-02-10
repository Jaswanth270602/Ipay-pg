@extends('layouts.app-sidebar')

@section('title', 'Server To Server Call Back Logs - Admin - ' . config('app.name'))
@section('page-title', 'Server To Server Call Back Logs')

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="S2SCallbackLogController as s2s">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Server To Server Call Back Logs']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-0" style="color: #495057; font-size: 1.5rem; font-weight: 600;">Server To Server Call Back Logs</h2>
            <small class="text-muted">List of Server To Server Call Back Log Details</small>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" id="dateRange" class="form-control" ng-model="s2s.filters.date_range" readonly>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="s2s.pagination.per_page" ng-change="s2s.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="s2s.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="s2s.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.id" checked> Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.merchant_id" checked> Merchant Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.merchant_name" checked> Merchant Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.order_id" checked> Order Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.tran_id" checked> Tran Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.transaction_id" checked> Transaction Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.callback_url" checked> CallBack URL</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.payment_datetime" checked> Payment Datetime</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.http_status_code" checked> Http Status Code</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.initiated_by" checked> Initiated By</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.callback_datetime" checked> Callback Datetime</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.request_log" checked> Request Log</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="s2s.visibleColumns.response_log" checked> Response Log</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="s2s.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="s2s.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="s2s.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th ng-show="s2s.visibleColumns.id">
                                <i class="bi bi-diamond"></i> Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                            </th>
                            <th ng-show="s2s.visibleColumns.merchant_id">
                                <i class="bi bi-diamond"></i> Merchant Id
                            </th>
                            <th ng-show="s2s.visibleColumns.merchant_name">
                                <i class="bi bi-diamond"></i> Merchant Name
                            </th>
                            <th ng-show="s2s.visibleColumns.order_id">
                                <i class="bi bi-diamond"></i> Order Id
                            </th>
                            <th ng-show="s2s.visibleColumns.tran_id">
                                <i class="bi bi-diamond"></i> Tran Id
                            </th>
                            <th ng-show="s2s.visibleColumns.transaction_id">
                                <i class="bi bi-diamond"></i> Transaction Id
                            </th>
                            <th ng-show="s2s.visibleColumns.callback_url">
                                <i class="bi bi-diamond"></i> CallBack URL
                            </th>
                            <th ng-show="s2s.visibleColumns.payment_datetime">
                                <i class="bi bi-diamond"></i> Payment Datetime
                            </th>
                            <th ng-show="s2s.visibleColumns.http_status_code">
                                <i class="bi bi-diamond"></i> Http Status Code
                            </th>
                            <th ng-show="s2s.visibleColumns.initiated_by">
                                <i class="bi bi-diamond"></i> Initiated By
                            </th>
                            <th ng-show="s2s.visibleColumns.callback_datetime">
                                <i class="bi bi-diamond"></i> Callback Datetime
                            </th>
                            <th ng-show="s2s.visibleColumns.request_log">
                                <i class="bi bi-diamond"></i> Request Log
                            </th>
                            <th ng-show="s2s.visibleColumns.response_log">
                                <i class="bi bi-diamond"></i> Response Log
                            </th>
                        </tr>
                        <tr>
                            <th ng-show="s2s.visibleColumns.id">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.id" ng-change="s2s.applyTableFilters()" placeholder="Id">
                            </th>
                            <th ng-show="s2s.visibleColumns.merchant_id">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.merchant_id" ng-change="s2s.applyTableFilters()" placeholder="Merchant Id">
                            </th>
                            <th ng-show="s2s.visibleColumns.merchant_name">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.merchant_name" ng-change="s2s.applyTableFilters()" placeholder="Merchant Name">
                            </th>
                            <th ng-show="s2s.visibleColumns.order_id">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.order_id" ng-change="s2s.applyTableFilters()" placeholder="Order Id">
                            </th>
                            <th ng-show="s2s.visibleColumns.tran_id">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.tran_id" ng-change="s2s.applyTableFilters()" placeholder="Tran Id">
                            </th>
                            <th ng-show="s2s.visibleColumns.transaction_id">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.transaction_id" ng-change="s2s.applyTableFilters()" placeholder="Transaction Id">
                            </th>
                            <th ng-show="s2s.visibleColumns.callback_url">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.callback_url" ng-change="s2s.applyTableFilters()" placeholder="CallBack URL">
                            </th>
                            <th ng-show="s2s.visibleColumns.payment_datetime">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.payment_datetime" ng-change="s2s.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th ng-show="s2s.visibleColumns.http_status_code">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.http_status_code" ng-change="s2s.applyTableFilters()" placeholder="Http Status Code">
                            </th>
                            <th ng-show="s2s.visibleColumns.initiated_by">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.initiated_by" ng-change="s2s.applyTableFilters()" placeholder="Initiated By">
                            </th>
                            <th ng-show="s2s.visibleColumns.callback_datetime">
                                <input type="text" class="form-control form-control-sm" ng-model="s2s.tableFilters.callback_datetime" ng-change="s2s.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th ng-show="s2s.visibleColumns.request_log"></th>
                            <th ng-show="s2s.visibleColumns.response_log"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="s2s.data.length === 0 && !s2s.loading">
                            <td colspan="13" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="item in s2s.data track by item.id">
                            <td ng-show="s2s.visibleColumns.id">@{{ item.id || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.merchant_id">@{{ item.merchant_id || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.merchant_name">@{{ item.merchant_name || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.order_id">@{{ item.order_id || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.tran_id">@{{ item.tran_id || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.transaction_id">@{{ item.transaction_id || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.callback_url">
                                <span style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="@{{ item.callback_url }}">
                                    @{{ item.callback_url || 'N/A' }}
                                </span>
                            </td>
                            <td ng-show="s2s.visibleColumns.payment_datetime">@{{ item.payment_datetime || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.http_status_code">
                                <span class="badge" 
                                      ng-class="{
                                          'bg-success': item.http_status_code >= 200 && item.http_status_code < 300,
                                          'bg-warning': item.http_status_code >= 300 && item.http_status_code < 400,
                                          'bg-danger': item.http_status_code >= 400
                                      }">
                                    @{{ item.http_status_code || 'N/A' }}
                                </span>
                            </td>
                            <td ng-show="s2s.visibleColumns.initiated_by">@{{ item.initiated_by || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.callback_datetime">@{{ item.callback_datetime || 'N/A' }}</td>
                            <td ng-show="s2s.visibleColumns.request_log">
                                <button ng-if="item.request_log && item.request_log !== 'N/A'" 
                                        class="btn btn-sm btn-link p-0" 
                                        ng-click="s2s.viewRequestLog(item)" 
                                        title="View Request Log">
                                    View
                                </button>
                                <span ng-if="!item.request_log || item.request_log === 'N/A'">N/A</span>
                            </td>
                            <td ng-show="s2s.visibleColumns.response_log">
                                <button ng-if="item.response_log && item.response_log !== 'N/A'" 
                                        class="btn btn-sm btn-link p-0" 
                                        ng-click="s2s.viewResponseLog(item)" 
                                        title="View Response Log">
                                    View
                                </button>
                                <span ng-if="!item.response_log || item.response_log === 'N/A'">N/A</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (s2s.pagination.current_page - 1) * s2s.pagination.per_page + 1 }}
                    to @{{ Math.min(s2s.pagination.current_page * s2s.pagination.per_page, s2s.pagination.total) }}
                    of @{{ s2s.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="s2s.changePage(s2s.pagination.current_page - 1)"
                            ng-disabled="s2s.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ s2s.pagination.current_page }} of @{{ s2s.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="s2s.changePage(s2s.pagination.current_page + 1)"
                            ng-disabled="s2s.pagination.current_page === s2s.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
(function () {
    'use strict';

    function registerController() {
        try {
            var app = angular.module('badlicashApp');
            app.controller('S2SCallbackLogController', ['$http', '$scope', function ($http, $scope) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.data = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    id: true,
                    merchant_id: true,
                    merchant_name: true,
                    order_id: true,
                    tran_id: true,
                    transaction_id: true,
                    callback_url: true,
                    payment_datetime: true,
                    http_status_code: true,
                    initiated_by: true,
                    callback_datetime: true,
                    request_log: true,
                    response_log: true
                };

                vm.filters = {
                    date_range: ''
                };

                vm.tableFilters = {
                    id: '',
                    merchant_id: '',
                    merchant_name: '',
                    order_id: '',
                    tran_id: '',
                    transaction_id: '',
                    callback_url: '',
                    payment_datetime: '',
                    http_status_code: '',
                    initiated_by: '',
                    callback_datetime: ''
                };

                // Initialize date range picker
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
                        autoUpdateInput: true,
                        showDropdowns: true
                    }, function(start, end) {
                        vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');
                        $scope.$apply();
                        vm.loadData();
                    });

                    vm.loadData();
                };

                vm.loadData = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    if (vm.filters.date_range) {
                        params.date_range = vm.filters.date_range;
                    }

                    Object.keys(vm.tableFilters).forEach(function(key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '' && vm.tableFilters[key] !== 'all') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.s2s-callback-logs.data') }}", { params: params })
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
                                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
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
                        id: '',
                        merchant_id: '',
                        merchant_name: '',
                        order_id: '',
                        tran_id: '',
                        transaction_id: '',
                        callback_url: '',
                        payment_datetime: '',
                        http_status_code: '',
                        initiated_by: '',
                        callback_datetime: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.filters.date_range = '';
                    vm.clearTableFilters();
                    var end = moment();
                    var start = moment().subtract(15, 'days');
                    vm.filters.date_range = start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY');
                    $('#dateRange').data('daterangepicker').setStartDate(start);
                    $('#dateRange').data('daterangepicker').setEndDate(end);
                    vm.loadData();
                };

                vm.viewRequestLog = function (item) {
                    var content = item.request_log;
                    try {
                        var parsed = JSON.parse(content);
                        content = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        // Use as is if not valid JSON
                    }
                    alert('Request Log:\n\n' + content);
                };

                vm.viewResponseLog = function (item) {
                    var content = item.response_log;
                    try {
                        var parsed = JSON.parse(content);
                        content = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        // Use as is if not valid JSON
                    }
                    alert('Response Log:\n\n' + content);
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

