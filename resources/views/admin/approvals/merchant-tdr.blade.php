@extends('layouts.app-sidebar')

@section('title', 'Merchant TDR Approvals - Admin - ' . config('app.name'))
@section('page-title', 'Approval Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantTdrApprovalController as mta">
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
                    ng-class="{'btn-primary': mta.statusFilter === 'all', 'btn-outline-primary': mta.statusFilter !== 'all'}"
                    ng-click="mta.setStatusFilter('all')">
                <i class="bi bi-funnel"></i> All
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': mta.statusFilter === 'pending', 'btn-outline-primary': mta.statusFilter !== 'pending'}"
                    ng-click="mta.setStatusFilter('pending')">
                <i class="bi bi-funnel"></i> Pending
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': mta.statusFilter === 'approved', 'btn-outline-primary': mta.statusFilter !== 'approved'}"
                    ng-click="mta.setStatusFilter('approved')">
                <i class="bi bi-funnel"></i> Approved
            </button>
            <button class="btn btn-sm" 
                    ng-class="{'btn-primary': mta.statusFilter === 'rejected', 'btn-outline-primary': mta.statusFilter !== 'rejected'}"
                    ng-click="mta.setStatusFilter('rejected')">
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
                        ng-model="mta.pagination.per_page" ng-change="mta.loadData()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mta.clearTableFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mta.loadData()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mta.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-paperclip"></i> Approve/Reject
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" ng-click="mta.bulkApprove()" ng-disabled="!mta.hasSelected()">Approve Selected</a></li>
                        <li><a class="dropdown-item" href="#" ng-click="mta.bulkReject()" ng-disabled="!mta.hasSelected()">Reject Selected</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="stat-card">
        <div ng-show="mta.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading data...</p>
            </div>
        </div>

        <div ng-hide="mta.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="mta.selectAll" ng-change="mta.toggleSelectAll()">
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
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Previous Changes
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Changes
                            </th>
                            <th>
                                <i class="bi bi-diamond"></i> Is Approved
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
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.approval_id" ng-change="mta.applyTableFilters()" placeholder="Approval ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.created_by" ng-change="mta.applyTableFilters()" placeholder="Created By">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.merchant_id" ng-change="mta.applyTableFilters()" placeholder="Merchant ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.merchant_name" ng-change="mta.applyTableFilters()" placeholder="Merchant Name">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.model_id" ng-change="mta.applyTableFilters()" placeholder="Model ID">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.model_name" ng-change="mta.applyTableFilters()" placeholder="Model Name">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.operation" ng-change="mta.applyTableFilters()" placeholder="Operation">
                            </th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.created_at" ng-change="mta.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm" ng-model="mta.tableFilters.approved_at" ng-change="mta.applyTableFilters()" placeholder="MM/DD/YYYY">
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mta.data.length === 0 && !mta.loading">
                            <td colspan="15" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="item in mta.data track by item.id" ng-class="{'table-active': mta.selectedItems.indexOf(item.id) !== -1}">
                            <td>
                                <input type="checkbox" ng-model="item.selected" ng-click="$event.stopPropagation(); mta.toggleSelection(item)">
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
                                        ng-click="mta.viewPreviousChanges(item)" 
                                        title="View Previous Changes">
                                    View
                                </button>
                                <span ng-if="!item.previous_changes || item.previous_changes === 'N/A'">N/A</span>
                            </td>
                            <td>
                                <button ng-if="item.changes && item.changes !== 'N/A'" 
                                        class="btn btn-sm btn-link p-0" 
                                        ng-click="mta.viewChanges(item)" 
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
                                <button class="btn btn-sm btn-success" ng-if="item.is_approved === 'pending'" ng-click="mta.approve(item)" title="Approve">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" ng-if="item.is_approved === 'pending'" ng-click="mta.reject(item)" title="Reject">
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
                    Showing @{{ (mta.pagination.current_page - 1) * mta.pagination.per_page + 1 }}
                    to @{{ Math.min(mta.pagination.current_page * mta.pagination.per_page, mta.pagination.total) }}
                    of @{{ mta.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mta.changePage(mta.pagination.current_page - 1)"
                            ng-disabled="mta.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ mta.pagination.current_page }} of @{{ mta.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mta.changePage(mta.pagination.current_page + 1)"
                            ng-disabled="mta.pagination.current_page === mta.pagination.last_page">
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
            app.controller('MerchantTdrApprovalController', ['$http', '$scope', '$timeout', function ($http, $scope, $timeout) {
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
                        if (vm.tableFilters[key] !== undefined && vm.tableFilters[key] !== null && vm.tableFilters[key] !== '') {
                            params[key] = vm.tableFilters[key];
                        }
                    });

                    $http.get("{{ route('admin.approvals.merchant-tdr.data') }}", { params: params })
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

                    $http.post("{{ route('admin.approvals.merchant-tdr.bulk-action') }}", {
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

                    $http.post("{{ route('admin.approvals.merchant-tdr.bulk-action') }}", {
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

