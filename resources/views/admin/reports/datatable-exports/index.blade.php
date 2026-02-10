@extends('layouts.app-sidebar')

@section('title', 'Datatable Export Files - Admin - ' . config('app.name'))
@section('page-title', 'Datatable Export Files')

@section('content')
<div ng-app="badlicashApp" ng-controller="DatatableExportController as de">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Datatable Export List']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0" style="color: #495057; font-size: 1.5rem; font-weight: 600;">Datatable Export Files</h2>
                <small class="text-muted">List of Datatable Export Files</small>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="de.pagination.per_page" ng-change="de.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="de.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="de.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.date_created" checked> Date Created</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.page_category" checked> Page Category</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.queue_status" checked> Queue Status</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.file_type" checked> File Type</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.downloadable_url" checked> Downloadable URL</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.time_for_expiry" checked> Time For Expiry</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="de.visibleColumns.file_name" checked> File Name</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="de.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="de.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="de.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th ng-show="de.visibleColumns.date_created">
                                <i class="bi bi-diamond"></i> Date Created
                            </th>
                            <th ng-show="de.visibleColumns.page_category">
                                <i class="bi bi-diamond"></i> Page Category
                            </th>
                            <th ng-show="de.visibleColumns.queue_status">
                                <i class="bi bi-diamond"></i> Queue Status
                            </th>
                            <th ng-show="de.visibleColumns.file_type">
                                <i class="bi bi-diamond"></i> File Type
                            </th>
                            <th ng-show="de.visibleColumns.downloadable_url">
                                <i class="bi bi-diamond"></i> Downloadable URL
                            </th>
                            <th ng-show="de.visibleColumns.time_for_expiry">
                                <i class="bi bi-diamond"></i> Time For Expiry
                            </th>
                            <th ng-show="de.visibleColumns.file_name">
                                <i class="bi bi-diamond"></i> File Name
                            </th>
                        </tr>
                        <tr>
                            <th ng-show="de.visibleColumns.date_created">
                                <input type="date" class="form-control form-control-sm" ng-model="de.tableFilters.date_created" ng-change="de.applyTableFilters()" placeholder="Date Created">
                            </th>
                            <th ng-show="de.visibleColumns.page_category">
                                <input type="text" class="form-control form-control-sm" ng-model="de.tableFilters.page_category" ng-change="de.applyTableFilters()" placeholder="Page Category">
                            </th>
                            <th ng-show="de.visibleColumns.queue_status">
                                <select class="form-select form-select-sm" ng-model="de.tableFilters.queue_status" ng-change="de.applyTableFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="status in de.queueStatuses" value="@{{ status }}">@{{ status | capitalize }}</option>
                                </select>
                            </th>
                            <th ng-show="de.visibleColumns.file_type">
                                <select class="form-select form-select-sm" ng-model="de.tableFilters.file_type" ng-change="de.applyTableFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="type in de.fileTypes" value="@{{ type }}">@{{ type | uppercase }}</option>
                                </select>
                            </th>
                            <th ng-show="de.visibleColumns.downloadable_url">
                                <input type="text" class="form-control form-control-sm" ng-model="de.tableFilters.downloadable_url" ng-change="de.applyTableFilters()" placeholder="Downloadable URL">
                            </th>
                            <th ng-show="de.visibleColumns.time_for_expiry">
                                <input type="date" class="form-control form-control-sm" ng-model="de.tableFilters.time_for_expiry" ng-change="de.applyTableFilters()" placeholder="Time For Expiry">
                            </th>
                            <th ng-show="de.visibleColumns.file_name">
                                <input type="text" class="form-control form-control-sm" ng-model="de.tableFilters.file_name" ng-change="de.applyTableFilters()" placeholder="File Name">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="de.data.length === 0 && !de.loading">
                            <td colspan="7" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="item in de.data track by item.id">
                            <td ng-show="de.visibleColumns.date_created">@{{ item.date_created || 'N/A' }}</td>
                            <td ng-show="de.visibleColumns.page_category">@{{ item.page_category || 'N/A' }}</td>
                            <td ng-show="de.visibleColumns.queue_status">
                                <span class="badge" 
                                      ng-class="{
                                          'bg-warning': item.queue_status === 'pending',
                                          'bg-info': item.queue_status === 'processing',
                                          'bg-success': item.queue_status === 'completed',
                                          'bg-danger': item.queue_status === 'failed'
                                      }">
                                    @{{ item.queue_status | capitalize }}
                                </span>
                            </td>
                            <td ng-show="de.visibleColumns.file_type">
                                <span class="badge bg-secondary">@{{ item.file_type | uppercase }}</span>
                            </td>
                            <td ng-show="de.visibleColumns.downloadable_url">
                                <a ng-if="item.downloadable_url && item.downloadable_url !== 'N/A'" 
                                   href="@{{ item.downloadable_url }}" 
                                   target="_blank" 
                                   class="text-primary">
                                    <i class="bi bi-download"></i> Download
                                </a>
                                <span ng-if="!item.downloadable_url || item.downloadable_url === 'N/A'">N/A</span>
                            </td>
                            <td ng-show="de.visibleColumns.time_for_expiry">@{{ item.time_for_expiry || 'N/A' }}</td>
                            <td ng-show="de.visibleColumns.file_name">@{{ item.file_name || 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (de.pagination.current_page - 1) * de.pagination.per_page + 1 }}
                    to @{{ Math.min(de.pagination.current_page * de.pagination.per_page, de.pagination.total) }}
                    of @{{ de.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="de.changePage(de.pagination.current_page - 1)"
                            ng-disabled="de.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ de.pagination.current_page }} of @{{ de.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="de.changePage(de.pagination.current_page + 1)"
                            ng-disabled="de.pagination.current_page === de.pagination.last_page">
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
            
            // Add capitalize filter if not exists
            app.filter('capitalize', function() {
                return function(input) {
                    if (input) {
                        return input.charAt(0).toUpperCase() + input.slice(1).toLowerCase();
                    }
                    return input;
                };
            });

            app.controller('DatatableExportController', ['$http', '$scope', function ($http, $scope) {
                var vm = this;

                vm.data = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.queueStatuses = [];
                vm.fileTypes = [];

                vm.visibleColumns = {
                    date_created: true,
                    page_category: true,
                    queue_status: true,
                    file_type: true,
                    downloadable_url: true,
                    time_for_expiry: true,
                    file_name: true
                };

                vm.tableFilters = {
                    date_created: '',
                    page_category: '',
                    queue_status: 'all',
                    file_type: 'all',
                    downloadable_url: '',
                    time_for_expiry: '',
                    file_name: ''
                };

                vm.loadQueueStatuses = function() {
                    $http.get("{{ route('admin.reports.datatable-exports.queue-statuses') }}")
                        .then(function (response) {
                            if (response.data && response.data.success) {
                                vm.queueStatuses = response.data.data || [];
                            }
                        });
                };

                vm.loadFileTypes = function() {
                    $http.get("{{ route('admin.reports.datatable-exports.file-types') }}")
                        .then(function (response) {
                            if (response.data && response.data.success) {
                                vm.fileTypes = response.data.data || [];
                            }
                        });
                };

                vm.loadData = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && 
                            vm.tableFilters[key] !== null && 
                            vm.tableFilters[key] !== '' && 
                            vm.tableFilters[key] !== 'all') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.reports.datatable-exports.data') }}", { params: params })
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
                        date_created: '',
                        page_category: '',
                        queue_status: 'all',
                        file_type: 'all',
                        downloadable_url: '',
                        time_for_expiry: '',
                        file_name: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.clearTableFilters();
                    vm.loadData();
                };

                // Initialize
                vm.loadQueueStatuses();
                vm.loadFileTypes();
                vm.loadData();
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

