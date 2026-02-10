@extends('layouts.app-sidebar')

@section('title', 'PG Refund Approvals - Admin - ' . config('app.name'))
@section('page-title', 'Approval Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="PgRefundApprovalController as pra">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Approvals']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0" style="color: #495057; font-size: 1.5rem; font-weight: 600;">Approval Management</h2>
                <small class="text-muted">List of Approval Details</small>
            </div>
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': pra.statusFilter === 'all', 'btn-outline-primary': pra.statusFilter !== 'all'}"
                    ng-click="pra.setStatusFilter('all')">
                <i class="bi bi-funnel"></i> All
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': pra.statusFilter === 'pending', 'btn-outline-primary': pra.statusFilter !== 'pending'}"
                    ng-click="pra.setStatusFilter('pending')">
                <i class="bi bi-funnel"></i> Pending
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': pra.statusFilter === 'approved', 'btn-outline-primary': pra.statusFilter !== 'approved'}"
                    ng-click="pra.setStatusFilter('approved')">
                <i class="bi bi-funnel"></i> Approved
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': pra.statusFilter === 'rejected', 'btn-outline-primary': pra.statusFilter !== 'rejected'}"
                    ng-click="pra.setStatusFilter('rejected')">
                <i class="bi bi-funnel"></i> Rejected
            </button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="pra.pagination.per_page" ng-change="pra.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="pra.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="pra.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="pra.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-paperclip"></i> Approve/Reject
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" ng-click="pra.bulkApprove()" ng-disabled="!pra.hasSelected()">Approve Selected</a></li>
                        <li><a class="dropdown-item" href="#" ng-click="pra.bulkReject()" ng-disabled="!pra.hasSelected()">Reject Selected</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="pra.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="pra.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="pra.selectAll" ng-change="pra.toggleSelectAll()">
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Approval Id
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Created By
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Merchant ID
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Merchant Name
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Model Id
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Model Name
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Operation
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Previous Changes
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Changes
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Is Approved
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Created At
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Approved Date
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                            </th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.approval_id" ng-change="pra.applyTableFilters()" placeholder="Approval ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.created_by" ng-change="pra.applyTableFilters()" placeholder="Created By">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.merchant_id" ng-change="pra.applyTableFilters()" placeholder="Merchant ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.merchant_name" ng-change="pra.applyTableFilters()" placeholder="Merchant Name">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.model_id" ng-change="pra.applyTableFilters()" placeholder="Model ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.model_name" ng-change="pra.applyTableFilters()" placeholder="Model Name">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.operation" ng-change="pra.applyTableFilters()" placeholder="Operation">
                            </th>
                            <th></th>
                            <th></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="pra.tableFilters.is_approved" ng-change="pra.applyTableFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.created_at" ng-change="pra.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="pra.tableFilters.approved_at" ng-change="pra.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="pra.data.length === 0 && !pra.loading">
                            <td colspan="14" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="item in pra.data track by item.id" ng-class="{'table-active': pra.selectedItems.indexOf(item.id) !== -1}">
                            <td>
                                <input type="checkbox" ng-model="item.selected" ng-click="$event.stopPropagation(); pra.toggleSelection(item)">
                            </td>
                            <td>@{{ item.approval_id || item.id || 'N/A' }}</td>
                            <td>@{{ item.created_by || 'N/A' }}</td>
                            <td>@{{ item.merchant_id || 'N/A' }}</td>
                            <td>@{{ item.merchant_name || 'N/A' }}</td>
                            <td>@{{ item.model_id || 'N/A' }}</td>
                            <td>@{{ item.model_name || 'N/A' }}</td>
                            <td>@{{ item.operation || 'N/A' }}</td>
                            <td>
                                <button ng-if="item.previous_changes && item.previous_changes !== 'N/A'" 
                                        class="btn btn-sm btn-link p-0" 
                                        ng-click="pra.viewPreviousChanges(item)" 
                                        title="View Previous Changes">
                                    View
                                </button>
                                <span ng-if="!item.previous_changes || item.previous_changes === 'N/A'">N/A</span>
                            </td>
                            <td>
                                <button ng-if="item.changes && item.changes !== 'N/A'" 
                                        class="btn btn-sm btn-link p-0" 
                                        ng-click="pra.viewChanges(item)" 
                                        title="View Changes">
                                    View
                                </button>
                                <span ng-if="!item.changes || item.changes === 'N/A'">N/A</span>
                            </td>
                            <td>
                                <span class="badge" 
                                      ng-class="{
                                          'bg-warning': item.is_approved === 'pending',
                                          'bg-success': item.is_approved === 'approved',
                                          'bg-danger': item.is_approved === 'rejected'
                                      }">
                                    @{{ item.is_approved | capitalize }}
                                </span>
                            </td>
                            <td>@{{ item.created_at || 'N/A' }}</td>
                            <td>@{{ item.approved_at || 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" ng-if="item.is_approved === 'pending'" ng-click="pra.approve(item)" title="Approve">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" ng-if="item.is_approved === 'pending'" ng-click="pra.reject(item)" title="Reject">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <span ng-if="item.is_approved !== 'pending'">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (pra.pagination.current_page - 1) * pra.pagination.per_page + 1 }}
                    to @{{ Math.min(pra.pagination.current_page * pra.pagination.per_page, pra.pagination.total) }}
                    of @{{ pra.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="pra.changePage(pra.pagination.current_page - 1)"
                            ng-disabled="pra.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ pra.pagination.current_page }} of @{{ pra.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="pra.changePage(pra.pagination.current_page + 1)"
                            ng-disabled="pra.pagination.current_page === pra.pagination.last_page">
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
            app.controller('PgRefundApprovalController', ['$http', '$scope', '$timeout', function ($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.data = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.statusFilter = 'all';
                vm.selectedItems = [];
                vm.selectAll = false;

                vm.tableFilters = {
                    approval_id: '',
                    created_by: '',
                    merchant_id: '',
                    merchant_name: '',
                    model_id: '',
                    model_name: '',
                    operation: '',
                    is_approved: 'all',
                    created_at: '',
                    approved_at: ''
                };

                vm.loadData = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.statusFilter
                    };

                    Object.keys(vm.tableFilters).forEach(function (key) {
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '' && vm.tableFilters[key] !== 'all') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.approvals.pg-refunds.data') }}", { params: params })
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

                vm.setStatusFilter = function (status) {
                    vm.statusFilter = status;
                    vm.pagination.current_page = 1;
                    vm.loadData();
                };

                vm.applyTableFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadData();
                };

                vm.clearTableFilters = function () {
                    vm.tableFilters = {
                        approval_id: '',
                        created_by: '',
                        merchant_id: '',
                        merchant_name: '',
                        model_id: '',
                        model_name: '',
                        operation: '',
                        is_approved: 'all',
                        created_at: '',
                        approved_at: ''
                    };
                    vm.applyTableFilters();
                };

                vm.resetView = function () {
                    vm.statusFilter = 'all';
                    vm.clearTableFilters();
                };

                vm.toggleSelectAll = function () {
                    vm.data.forEach(function (item) {
                        item.selected = vm.selectAll;
                    });
                    vm.updateSelectedItems();
                };

                vm.toggleSelection = function (item) {
                    vm.updateSelectedItems();
                };

                vm.updateSelectedItems = function () {
                    vm.selectedItems = vm.data.filter(function(item) { return item.selected; }).map(function(item) { return item.id; });
                    vm.selectAll = vm.data.length > 0 && vm.data.every(function (item) { return item.selected; });
                };

                vm.hasSelected = function () {
                    return vm.selectedItems.length > 0;
                };

                vm.approve = function (item) {
                    if (!confirm('Are you sure you want to approve this request?')) {
                        return;
                    }

                    $http.post("{{ url('admin/approvals/pg-refunds') }}/" + item.id + "/approve", {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = response.data.message || 'Failed to approve';
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to approve';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        }
                    });
                };

                vm.reject = function (item) {
                    if (!confirm('Are you sure you want to reject this request?')) {
                        return;
                    }

                    $http.post("{{ url('admin/approvals/pg-refunds') }}/" + item.id + "/reject", {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = response.data.message || 'Failed to reject';
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to reject';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        }
                    });
                };

                vm.bulkApprove = function () {
                    if (!vm.hasSelected()) {
                        if (typeof showToast === 'function') {
                            showToast('Please select at least one item', 'error');
                        }
                        return;
                    }

                    if (!confirm('Are you sure you want to approve selected items?')) {
                        return;
                    }

                    $http.post("{{ route('admin.approvals.pg-refunds.bulk-action') }}", {
                        ids: vm.selectedItems,
                        action: 'approve'
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = response.data.message || 'Failed to approve items';
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to approve items';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        }
                    });
                };

                vm.bulkReject = function () {
                    if (!vm.hasSelected()) {
                        if (typeof showToast === 'function') {
                            showToast('Please select at least one item', 'error');
                        }
                        return;
                    }

                    if (!confirm('Are you sure you want to reject selected items?')) {
                        return;
                    }

                    $http.post("{{ route('admin.approvals.pg-refunds.bulk-action') }}", {
                        ids: vm.selectedItems,
                        action: 'reject'
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message, 'success');
                            }
                            vm.loadData();
                        } else {
                            var errorMsg = response.data.message || 'Failed to reject items';
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            }
                        }
                    }, function (error) {
                        var errorMsg = 'Failed to reject items';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        }
                    });
                };

                vm.viewPreviousChanges = function (item) {
                    var content = item.previous_changes;
                    try {
                        var parsed = JSON.parse(content);
                        content = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        // Use as is if not valid JSON
                    }
                    alert('Previous Changes:\n\n' + content);
                };

                vm.viewChanges = function (item) {
                    var content = item.changes;
                    try {
                        var parsed = JSON.parse(content);
                        content = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        // Use as is if not valid JSON
                    }
                    alert('Changes:\n\n' + content);
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

