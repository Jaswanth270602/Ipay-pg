@extends('layouts.app-sidebar')

@section('title', 'Miscellaneous Reports List - Admin - ' . config('app.name'))
@section('page-title', 'Miscellaneous Reports List')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdhocReportController as ar">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Adhoc Report List']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0" style="color: #495057; font-size: 1.5rem; font-weight: 600;">Miscellaneous Reports List</h2>
                <small class="text-muted">Manage all your Adhoc Reports here</small>
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
                        ng-model="ar.pagination.per_page" ng-change="ar.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="ar.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="ar.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ar.visibleColumns.adhoc_report_id" checked> Adhoc Report ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ar.visibleColumns.adhoc_report_name" checked> Adhoc Report Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ar.visibleColumns.adhoc_report_description" checked> Adhoc Report Description</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ar.visibleColumns.adhoc_report_created_date" checked> Adhoc Report Created Date</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="ar.visibleColumns.action" checked> Action</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="ar.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="ar.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> New
                </button>
                <button class="btn btn-sm btn-info" ng-click="ar.editSelected()" ng-disabled="!ar.selectedReport">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" ng-click="ar.deleteSelected()" ng-disabled="!ar.selectedReport">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-sm btn-warning" ng-click="ar.duplicateSelected()" ng-disabled="!ar.selectedReport">
                    <i class="bi bi-files"></i> Duplicate
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="ar.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="ar.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="ar.selectAll" ng-change="ar.toggleSelectAll()">
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_id">
                                <i class="bi bi-diamond"></i> Adhoc Report ID
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_name">
                                <i class="bi bi-diamond"></i> Adhoc Report Name
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_description">
                                <i class="bi bi-diamond"></i> Adhoc Report Description
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_created_date">
                                <i class="bi bi-diamond"></i> Adhoc Report Created Date
                            </th>
                            <th ng-show="ar.visibleColumns.action">Action</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th ng-show="ar.visibleColumns.adhoc_report_id">
                                <input type="text" class="form-control form-control-sm" ng-model="ar.tableFilters.adhoc_report_id" ng-change="ar.applyTableFilters()" placeholder="Report ID">
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_name">
                                <input type="text" class="form-control form-control-sm" ng-model="ar.tableFilters.adhoc_report_name" ng-change="ar.applyTableFilters()" placeholder="Report Name">
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_description">
                                <input type="text" class="form-control form-control-sm" ng-model="ar.tableFilters.adhoc_report_description" ng-change="ar.applyTableFilters()" placeholder="Description">
                            </th>
                            <th ng-show="ar.visibleColumns.adhoc_report_created_date">
                                <input type="text" class="form-control form-control-sm" ng-model="ar.tableFilters.adhoc_report_created_date" ng-change="ar.applyTableFilters()" placeholder="MM/DD/YYYY-MM/DD/YYYY">
                            </th>
                            <th ng-show="ar.visibleColumns.action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="ar.data.length === 0 && !ar.loading">
                            <td colspan="6" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="item in ar.data track by item.id" ng-class="{'table-active': ar.selectedReport && ar.selectedReport.id === item.id}">
                            <td>
                                <input type="checkbox" ng-model="item.selected" ng-click="$event.stopPropagation(); ar.selectReport(item)">
                            </td>
                            <td ng-show="ar.visibleColumns.adhoc_report_id">@{{ item.adhoc_report_id || item.id || 'N/A' }}</td>
                            <td ng-show="ar.visibleColumns.adhoc_report_name">@{{ item.adhoc_report_name || 'N/A' }}</td>
                            <td ng-show="ar.visibleColumns.adhoc_report_description">@{{ item.adhoc_report_description || 'N/A' }}</td>
                            <td ng-show="ar.visibleColumns.adhoc_report_created_date">@{{ item.adhoc_report_created_date || 'N/A' }}</td>
                            <td ng-show="ar.visibleColumns.action">
                                <button class="btn btn-sm btn-info" ng-click="ar.editReport(item)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" ng-click="ar.deleteReport(item)" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" ng-click="ar.duplicateReport(item)" title="Duplicate">
                                    <i class="bi bi-files"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (ar.pagination.current_page - 1) * ar.pagination.per_page + 1 }}
                    to @{{ Math.min(ar.pagination.current_page * ar.pagination.per_page, ar.pagination.total) }}
                    of @{{ ar.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="ar.changePage(ar.pagination.current_page - 1)"
                            ng-disabled="ar.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ ar.pagination.current_page }} of @{{ ar.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="ar.changePage(ar.pagination.current_page + 1)"
                            ng-disabled="ar.pagination.current_page === ar.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="adhocReportModal" tabindex="-1" aria-labelledby="adhocReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6366f1; color: white;">
                <h5 class="modal-title" id="adhocReportModalLabel">
                    <i class="bi bi-file-earmark-text"></i> @{{ ar.isEditing ? 'Edit' : 'Create new entry' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="ar.form.adhoc_report_name" required placeholder="Enter report name">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="ar.form.adhoc_report_description" required placeholder="Enter report description">
                        </div>
                        <div class="col-12">
                            <label class="form-label">SQL <span class="text-danger">*</span></label>
                            <textarea class="form-control" ng-model="ar.form.sql_query" required rows="10" placeholder="Enter SQL query"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" ng-click="ar.saveReport()" ng-disabled="ar.saving">
                    <span ng-if="ar.saving" class="spinner-border spinner-border-sm me-1"></span>
                    <i class="bi bi-check-lg" ng-if="!ar.saving"></i>
                    @{{ ar.isEditing ? 'Update' : 'Create' }}
                </button>
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
            app.controller('AdhocReportController', ['$http', '$scope', '$timeout', function ($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.data = [];
                vm.loading = false;
                vm.saving = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.selectedReport = null;
                vm.selectAll = false;
                vm.isEditing = false;

                vm.visibleColumns = {
                    adhoc_report_id: true,
                    adhoc_report_name: true,
                    adhoc_report_description: true,
                    adhoc_report_created_date: true,
                    action: true
                };

                vm.tableFilters = {
                    adhoc_report_id: '',
                    adhoc_report_name: '',
                    adhoc_report_description: '',
                    adhoc_report_created_date: ''
                };

                vm.form = {
                    id: null,
                    adhoc_report_name: '',
                    adhoc_report_description: '',
                    sql_query: ''
                };

                vm.loadData = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.reports.miscellaneous.data') }}", { params: params })
                        .then(function (response) {
                            if (response.data && response.data.success) {
                                vm.data = response.data.data || [];
                                vm.pagination = {
                                    current_page: response.data.pagination.current_page,
                                    per_page: response.data.pagination.per_page,
                                    total: response.data.pagination.total,
                                    last_page: response.data.pagination.last_page
                                };
                                vm.data.forEach(function(item) {
                                    item.selected = false;
                                });
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
                        adhoc_report_id: '',
                        adhoc_report_name: '',
                        adhoc_report_description: '',
                        adhoc_report_created_date: ''
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
                    if (vm.selectAll && vm.data.length > 0) {
                        vm.selectedReport = vm.data[0];
                    } else {
                        vm.selectedReport = null;
                    }
                };

                vm.selectReport = function (report) {
                    vm.selectedReport = report.selected ? report : null;
                    vm.selectAll = vm.data.length > 0 && vm.data.every(function (item) { return item.selected; });
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        id: null,
                        adhoc_report_name: '',
                        adhoc_report_description: '',
                        sql_query: ''
                    };
                    $timeout(function() {
                        var modalElement = document.getElementById('adhocReportModal');
                        if (modalElement) {
                            var existingModal = bootstrap.Modal.getInstance(modalElement);
                            var modal = existingModal || new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    }, 100);
                };

                vm.editReport = function (report) {
                    vm.isEditing = true;
                    vm.selectedReport = report;
                    vm.form = {
                        id: report.id,
                        adhoc_report_name: report.adhoc_report_name,
                        adhoc_report_description: report.adhoc_report_description || '',
                        sql_query: report.sql_query || ''
                    };
                    $timeout(function() {
                        var modalElement = document.getElementById('adhocReportModal');
                        if (modalElement) {
                            var existingModal = bootstrap.Modal.getInstance(modalElement);
                            var modal = existingModal || new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    }, 100);
                };

                vm.editSelected = function () {
                    if (vm.selectedReport) {
                        vm.editReport(vm.selectedReport);
                    }
                };

                vm.saveReport = function () {
                    if (!vm.form.adhoc_report_name || !vm.form.adhoc_report_description || !vm.form.sql_query) {
                        if (typeof showToast === 'function') {
                            showToast('Please fill in all required fields', 'error');
                        } else {
                            alert('Please fill in all required fields');
                        }
                        return;
                    }

                    vm.saving = true;
                    var url = vm.isEditing ? "{{ url('admin/reports/miscellaneous') }}/" + vm.form.id : "{{ route('admin.reports.miscellaneous.store') }}";
                    var method = 'POST';

                    $http({
                        method: method,
                        url: url,
                        data: vm.form,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('adhocReportModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            var successMsg = vm.isEditing ? 'Adhoc report updated successfully' : 'Adhoc report created successfully';
                            if (typeof showToast === 'function') {
                                showToast(successMsg, 'success');
                            } else {
                                alert(successMsg);
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = response.data.message || 'Failed to save adhoc report';
                            if (response.data.errors) {
                                var errors = Object.values(response.data.errors).flat();
                                errorMsg = errors.join(', ');
                            }
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var errorMsg = 'Failed to save adhoc report';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            errorMsg = errors.join(', ');
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.deleteReport = function (report) {
                    if (!confirm('Are you sure you want to delete this adhoc report?')) {
                        return;
                    }

                    $http.delete("{{ url('admin/reports/miscellaneous') }}/" + report.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Adhoc report deleted successfully', 'success');
                            } else {
                                alert('Adhoc report deleted successfully');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = 'Failed to delete adhoc report: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to delete adhoc report';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.deleteSelected = function () {
                    if (vm.selectedReport) {
                        vm.deleteReport(vm.selectedReport);
                    }
                };

                vm.duplicateReport = function (report) {
                    if (!confirm('Are you sure you want to duplicate this adhoc report?')) {
                        return;
                    }

                    $http.post("{{ url('admin/reports/miscellaneous') }}/" + report.id + "/duplicate", {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Adhoc report duplicated successfully', 'success');
                            } else {
                                alert('Adhoc report duplicated successfully');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = 'Failed to duplicate adhoc report: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to duplicate adhoc report';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.duplicateSelected = function () {
                    if (vm.selectedReport) {
                        vm.duplicateReport(vm.selectedReport);
                    }
                };

                // Initialize
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

