@extends('layouts.app-sidebar')

@section('title', 'Users - Admin - ' . config('app.name'))
@section('page-title', 'User Settings')

@push('styles')
<style>
    .action-buttons-container {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        min-width: 300px;
    }
    
    .action-btn {
        min-width: 40px;
        height: 32px;
        border-radius: 6px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
        color: white;
    }
    
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .action-btn:active {
        transform: translateY(0);
    }
    
    .action-btn-edit {
        background-color: #198754;
    }
    .action-btn-edit:hover {
        background-color: #157347;
    }
    
    .action-btn-email {
        background-color: #6f42c1;
    }
    .action-btn-email:hover {
        background-color: #5a32a3;
    }
    
    .action-btn-roles {
        background-color: #0dcaf0;
        min-width: 50px;
        padding: 0 8px;
        gap: 4px;
    }
    .action-btn-roles:hover {
        background-color: #0aa2c0;
    }
    
    .action-btn-permissions {
        background-color: #ffc107;
        min-width: 50px;
        padding: 0 8px;
        gap: 4px;
    }
    .action-btn-permissions:hover {
        background-color: #ffb300;
    }
    
    .action-btn-2fa {
        background-color: #6c757d;
        min-width: 50px;
        padding: 0 8px;
        gap: 4px;
    }
    .action-btn-2fa:hover {
        background-color: #5c636a;
    }
    
    .action-btn-text {
        font-size: 12px;
        font-weight: 600;
        margin-left: 2px;
    }
</style>
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminUsersController as auc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'User Settings'],
        ['label'=>'Users']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>List of Users</h2>
        </div>
    </div>

    <!-- Action Buttons and Controls -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="auc.pagination.per_page" ng-change="auc.loadUsers()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="auc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="auc.loadUsers()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in auc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="auc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="auc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="auc.openNewModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="auc.editSelected()" ng-disabled="!auc.selectedUser">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" ng-click="auc.deleteSelected()" ng-disabled="!auc.selectedUser">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-sm btn-outline-info" ng-click="auc.duplicateSelected()" ng-disabled="!auc.selectedUser">
                    <i class="bi bi-files"></i> Duplicate
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="auc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading users...</p>
            </div>
        </div>

        <div ng-hide="auc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="auc.selectAll" ng-change="auc.toggleSelectAll()">
                            </th>
                            <th ng-show="auc.visibleColumns.id.visible">
                                <i class="bi bi-diamond"></i> Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_id" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.name.visible">
                                <i class="bi bi-diamond"></i> Name
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_name" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.email.visible">
                                <i class="bi bi-diamond"></i> Email
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_email" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.active.visible">
                                <i class="bi bi-diamond"></i> Active
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <select class="form-select form-select-sm mt-1" ng-model="auc.filters.filter_active" ng-change="auc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </th>
                            <th ng-show="auc.visibleColumns.email_verified.visible">
                                <i class="bi bi-diamond"></i> Email Verified
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <select class="form-select form-select-sm mt-1" ng-model="auc.filters.filter_email_verified" ng-change="auc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </th>
                            <th ng-show="auc.visibleColumns.roles.visible">
                                <i class="bi bi-diamond"></i> Roles
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_roles" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.time_zone.visible">
                                <i class="bi bi-diamond"></i> Time Zone
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <select class="form-select form-select-sm mt-1" ng-model="auc.filters.filter_time_zone" ng-change="auc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Asia/Kolkata">Asia/Kolkata</option>
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">America/New_York</option>
                                    <option value="Europe/London">Europe/London</option>
                                </select>
                            </th>
                            <th ng-show="auc.visibleColumns.two_factor_auth.visible">
                                <i class="bi bi-diamond"></i> 2FactorAuth
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <select class="form-select form-select-sm mt-1" ng-model="auc.filters.filter_2factor_auth" ng-change="auc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </th>
                            <th ng-show="auc.visibleColumns.organization_name.visible">
                                <i class="bi bi-diamond"></i> Organization Name
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_organization_name" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.vendor_codes.visible">
                                <i class="bi bi-diamond"></i> Vendor Codes
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="auc.filters.filter_vendor_codes" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.last_login_at.visible">
                                <i class="bi bi-diamond"></i> Last Login At
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY-MM/DD" ng-model="auc.filters.filter_last_login_at" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.created_at.visible">
                                <i class="bi bi-diamond"></i> Created At
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY-MM/DD" ng-model="auc.filters.filter_created_at" ng-change="auc.applyFilters()">
                            </th>
                            <th ng-show="auc.visibleColumns.updated_at.visible">
                                <i class="bi bi-diamond"></i> Updated At
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY-MM/DD" ng-model="auc.filters.filter_updated_at" ng-change="auc.applyFilters()">
                            </th>
                            <th style="min-width: 320px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="auc.users.length === 0">
                            <td colspan="15" class="text-center text-muted py-4">No users found</td>
                        </tr>
                        <tr ng-repeat="user in auc.users track by $index" 
                            ng-class="{'table-primary': auc.selectedUser && user.id === auc.selectedUser.id}"
                            ng-click="auc.selectUser(user)">
                            <td>
                                <input type="checkbox" ng-model="user.selected" ng-click="auc.toggleUserSelection(user, $event)">
                            </td>
                            <td ng-show="auc.visibleColumns.id.visible">@{{ user.id }}</td>
                            <td ng-show="auc.visibleColumns.name.visible">@{{ user.name }}</td>
                            <td ng-show="auc.visibleColumns.email.visible">@{{ user.email }}</td>
                            <td ng-show="auc.visibleColumns.active.visible">
                                <input type="checkbox" ng-checked="user.active" disabled>
                            </td>
                            <td ng-show="auc.visibleColumns.email_verified.visible">
                                <input type="checkbox" ng-checked="user.email_verified" disabled>
                            </td>
                            <td ng-show="auc.visibleColumns.roles.visible">@{{ user.roles }}</td>
                            <td ng-show="auc.visibleColumns.time_zone.visible">@{{ user.time_zone }}</td>
                            <td ng-show="auc.visibleColumns.two_factor_auth.visible">@{{ user.two_factor_auth }}</td>
                            <td ng-show="auc.visibleColumns.organization_name.visible">@{{ user.organization_name }}</td>
                            <td ng-show="auc.visibleColumns.vendor_codes.visible">@{{ user.vendor_codes }}</td>
                            <td ng-show="auc.visibleColumns.last_login_at.visible">@{{ user.last_login_at }}</td>
                            <td ng-show="auc.visibleColumns.created_at.visible">@{{ user.created_at }}</td>
                            <td ng-show="auc.visibleColumns.updated_at.visible">@{{ user.updated_at }}</td>
                            <td style="text-align: center; vertical-align: middle;">
                                <div class="action-buttons-container">
                                    <button class="action-btn action-btn-edit" 
                                            ng-click="auc.editUser(user, $event)" 
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="action-btn action-btn-email" 
                                            ng-click="auc.toggleEmailVerification(user, $event)" 
                                            title="Toggle Email Verification">
                                        <i class="bi" ng-class="user.email_verified ? 'bi-envelope-check' : 'bi-envelope-x'"></i>
                                    </button>
                                    <button class="action-btn action-btn-roles" 
                                            ng-click="auc.manageRoles(user, $event)" 
                                            title="Roles">
                                        <i class="bi bi-tablet-landscape"></i>
                                        <span class="action-btn-text">R</span>
                                    </button>
                                    <button class="action-btn action-btn-permissions" 
                                            ng-click="auc.managePermissions(user, $event)" 
                                            title="Permissions">
                                        <i class="bi bi-shield-check"></i>
                                        <span class="action-btn-text">P</span>
                                    </button>
                                    <button class="action-btn action-btn-2fa" 
                                            ng-click="auc.toggle2FA(user, $event)" 
                                            title="2FA">
                                        <i class="bi bi-shield-lock"></i>
                                        <span class="action-btn-text">2F</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (auc.pagination.current_page - 1) * auc.pagination.per_page + 1 }} to 
                    @{{ Math.min(auc.pagination.current_page * auc.pagination.per_page, auc.pagination.total) }} of 
                    @{{ auc.pagination.total }} entries
                    <span ng-if="auc.selectedUser">| @{{ auc.getSelectedCount() }} row selected</span>
                </div>
                <div>
                    <ul class="pagination mb-0">
                        <li class="page-item" ng-class="{'disabled': auc.pagination.current_page === 1}">
                            <a class="page-link" href="#" ng-click="auc.changePage(auc.pagination.current_page - 1)">Previous</a>
                        </li>
                        <li class="page-item" ng-repeat="page in auc.getPageNumbers() track by $index" 
                            ng-class="{'active': page === auc.pagination.current_page}">
                            <a class="page-link" href="#" ng-click="auc.changePage(page)">@{{ page }}</a>
                        </li>
                        <li class="page-item" ng-class="{'disabled': auc.pagination.current_page === auc.pagination.last_page}">
                            <a class="page-link" href="#" ng-click="auc.changePage(auc.pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
                <h5 class="modal-title" id="editUserModalLabel">Edit entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" ng-click="auc.cancelEdit()"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="auc.editForm.name" required>
                            <small class="text-muted" ng-if="auc.editForm.merchant_name" style="display: block; margin-top: 5px; color: #6366f1; font-weight: 500;">
                                <i class="bi bi-building"></i> <strong>Merchant:</strong> <span ng-bind="auc.editForm.merchant_name"></span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" ng-model="auc.editForm.email" required>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editActive" ng-model="auc.editForm.active">
                                <label class="form-check-label" for="editActive">Active:</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editEmailVerified" ng-model="auc.editForm.email_verified">
                                <label class="form-check-label" for="editEmailVerified">Email Verified:</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time Zone <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="auc.editForm.timezone" required>
                                <option value="Asia/Kolkata">Asia/Kolkata</option>
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">America/New_York</option>
                                <option value="Europe/London">Europe/London</option>
                                <option value="America/Los_Angeles">America/Los_Angeles</option>
                                <option value="Europe/Paris">Europe/Paris</option>
                                <option value="Asia/Dubai">Asia/Dubai</option>
                                <option value="Asia/Singapore">Asia/Singapore</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Code <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="auc.editForm.currency_code" required>
                                <option value="INR">INR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="AED">AED</option>
                                <option value="SGD">SGD</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Team <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="auc.editForm.team_name" required>
                                <option value="">Select Team</option>
                                <option ng-repeat="team in auc.teams" ng-value="team" ng-bind="team"></option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" ng-model="auc.editForm.password" placeholder="Leave empty to keep current password">
                            <small class="text-muted">
                                Password must have minimum 12 characters and should include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character.<br>
                                Password needs to be changed every 90 days, and you cannot reuse the last 4 passwords
                            </small>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ng-click="auc.cancelEdit()">Cancel</button>
                <button type="button" class="btn btn-primary" ng-click="auc.updateUser()" ng-disabled="auc.updating" id="updateUserBtn" style="min-width: 100px;">
                    <span ng-if="!auc.updating">Update</span>
                    <span ng-if="auc.updating">
                        <span class="spinner-border spinner-border-sm me-2"></span>Updating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminUsersController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                // CSRF token for all state-changing admin user requests
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.users = [];
                vm.selectedUser = null;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                vm.selectAll = false;

                vm.visibleColumns = {
                    id: { visible: true, label: 'Id' },
                    name: { visible: true, label: 'Name' },
                    email: { visible: true, label: 'Email' },
                    active: { visible: true, label: 'Active' },
                    email_verified: { visible: true, label: 'Email Verified' },
                    roles: { visible: true, label: 'Roles' },
                    time_zone: { visible: true, label: 'Time Zone' },
                    two_factor_auth: { visible: true, label: '2FactorAuth' },
                    organization_name: { visible: true, label: 'Organization Name' },
                    vendor_codes: { visible: true, label: 'Vendor Codes' },
                    last_login_at: { visible: true, label: 'Last Login At' },
                    created_at: { visible: true, label: 'Created At' },
                    updated_at: { visible: true, label: 'Updated At' },
                };

                vm.loadUsers = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key] && vm.filters[key] !== 'all') {
                            params[key] = vm.filters[key];
                        }
                    });

                    $http.get('/admin/users/data', { params: params }).then(function(response) {
                        if (response.data.success) {
                            vm.users = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                last_page: response.data.pagination.last_page,
                                total: response.data.pagination.total,
                                per_page: response.data.pagination.per_page
                            };
                        } else {
                            alert('Failed to load users: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading users:', error);
                        alert('Failed to load users. Please try again.');
                    });
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadUsers();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.applyFilters();
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadUsers();
                    }
                };

                vm.getPageNumbers = function() {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                vm.selectUser = function(user) {
                    vm.selectedUser = user;
                    vm.users.forEach(function(u) {
                        u.selected = (u.id === user.id);
                    });
                };

                vm.toggleUserSelection = function(user, event) {
                    event.stopPropagation();
                    user.selected = !user.selected;
                    if (user.selected) {
                        vm.selectedUser = user;
                    } else if (vm.selectedUser && vm.selectedUser.id === user.id) {
                        vm.selectedUser = null;
                    }
                    vm.updateSelectAll();
                };

                vm.toggleSelectAll = function() {
                    vm.users.forEach(function(user) {
                        user.selected = vm.selectAll;
                    });
                    if (vm.selectAll && vm.users.length > 0) {
                        vm.selectedUser = vm.users[0];
                    } else {
                        vm.selectedUser = null;
                    }
                };

                vm.updateSelectAll = function() {
                    var selectedCount = vm.users.filter(function(u) { return u.selected; }).length;
                    vm.selectAll = selectedCount === vm.users.length && vm.users.length > 0;
                };

                vm.getSelectedCount = function() {
                    return vm.users.filter(function(u) { return u.selected; }).length;
                };

                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns[key]) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                vm.resetView = function() {
                    vm.visibleColumns = {
                        id: { visible: true, label: 'Id' },
                        name: { visible: true, label: 'Name' },
                        email: { visible: true, label: 'Email' },
                        active: { visible: true, label: 'Active' },
                        email_verified: { visible: true, label: 'Email Verified' },
                        roles: { visible: true, label: 'Roles' },
                        time_zone: { visible: true, label: 'Time Zone' },
                        two_factor_auth: { visible: true, label: '2FactorAuth' },
                        organization_name: { visible: true, label: 'Organization Name' },
                        vendor_codes: { visible: true, label: 'Vendor Codes' },
                        last_login_at: { visible: true, label: 'Last Login At' },
                        created_at: { visible: true, label: 'Created At' },
                        updated_at: { visible: true, label: 'Updated At' },
                    };
                    vm.clearFilters();
                };

                vm.toggleEmailVerification = function(user, event) {
                    if (event) event.stopPropagation();
                    if (!confirm('Are you sure you want to ' + (user.email_verified ? 'unverify' : 'verify') + ' this user\'s email?')) {
                        return;
                    }

                    $http.post('/admin/users/' + user.id + '/toggle-email-verification', {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            user.email_verified = response.data.email_verified;
                            user.email_verified_at = response.data.email_verified_at;
                            alert(response.data.message);
                            vm.loadUsers();
                        } else {
                            alert('Failed to update email verification: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        console.error('Error toggling email verification:', error);
                        alert('Failed to update email verification. Please try again.');
                    });
                };

                vm.editForm = {};
                vm.teams = [];
                vm.updating = false;

                vm.loadTeams = function() {
                    $http.get('/admin/users/teams').then(function(response) {
                        if (response.data.success) {
                            vm.teams = response.data.data || [];
                        }
                    }, function(error) {
                        console.error('Error loading teams:', error);
                    });
                };

                vm.editUser = function(user, event) {
                    if (event) event.stopPropagation();
                    vm.updating = false;
                    vm.editForm = {};
                    
                    // Load teams first
                    vm.loadTeams();
                    
                    // Load user details
                    $http.get('/admin/users/' + user.id).then(function(response) {
                        if (response.data.success) {
                            var userData = response.data.data;
                            vm.editForm = {
                                id: userData.id,
                                name: userData.name,
                                email: userData.email,
                                active: Boolean(userData.active === true || userData.active === 1 || userData.active === 'true'),
                                email_verified: Boolean(userData.email_verified === true || userData.email_verified === 1 || userData.email_verified === 'true'),
                                timezone: userData.timezone || 'Asia/Kolkata',
                                currency_code: userData.currency_code || 'INR',
                                team_name: userData.team_name || '',
                                merchant_id: userData.merchant_id,
                                merchant_name: userData.merchant_name || '',
                                password: '' // Don't pre-fill password
                            };
                            
                            // Open modal
                            var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                            modal.show();
                        } else {
                            alert('Failed to load user details: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        console.error('Error loading user:', error);
                        alert('Failed to load user details. Please try again.');
                    });
                };

                vm.openNewModal = function() {
                    alert('New user modal - to be implemented');
                };

                vm.editSelected = function() {
                    if (vm.selectedUser) {
                        vm.editUser(vm.selectedUser);
                    } else {
                        alert('Please select a user to edit');
                    }
                };

                vm.updateUser = function() {
                    console.log('updateUser called', {
                        id: vm.editForm.id,
                        form: vm.editForm,
                        updating: vm.updating
                    });
                    
                    if (!vm.editForm || !vm.editForm.id) {
                        alert('Invalid user data. Please select a user to edit.');
                        return false;
                    }

                    // Prevent multiple submissions
                    if (vm.updating) {
                        console.log('Update already in progress');
                        return false;
                    }

                    // Validate password if provided
                    if (vm.editForm.password && vm.editForm.password.length > 0) {
                        if (vm.editForm.password.length < 12) {
                            alert('Password must be at least 12 characters long');
                            return;
                        }
                        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/;
                        if (!passwordRegex.test(vm.editForm.password)) {
                            alert('Password must include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character');
                            return;
                        }
                    }

                    vm.updating = true;
                    
                    // Simplified boolean handling
                    var activeValue = Boolean(vm.editForm.active);
                    var emailVerifiedValue = Boolean(vm.editForm.email_verified);
                    
                    var updateData = {
                        name: vm.editForm.name,
                        email: vm.editForm.email,
                        active: activeValue,
                        email_verified: emailVerifiedValue, // Always send boolean value
                        timezone: vm.editForm.timezone || 'Asia/Kolkata',
                        currency_code: vm.editForm.currency_code || 'INR',
                        team_name: vm.editForm.team_name || '',
                    };
                    
                    console.log('Update data being sent:', updateData);

                    // Only include password if provided
                    if (vm.editForm.password && vm.editForm.password.length > 0) {
                        updateData.password = vm.editForm.password;
                    }

                    $http.put('/admin/users/' + vm.editForm.id, updateData, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.updating = false;
                        
                        if (response.data.success) {
                            alert(response.data.message || 'User updated successfully');
                            var modalElement = document.getElementById('editUserModal');
                            var modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                            vm.editForm = {};
                            vm.loadUsers(); // Reload users list to reflect changes
                        } else {
                            alert('Failed to update user: ' + (response.data.message || 'Unknown error'));
                            if (response.data.errors) {
                                var errorMsg = Object.values(response.data.errors).flat().join('\n');
                                alert('Validation errors:\n' + errorMsg);
                            }
                        }
                    }, function(error) {
                        vm.updating = false;
                        console.error('Error updating user:', error);
                        var errorMsg = 'Failed to update user. Please try again.';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        }
                        if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat().join('\n');
                            errorMsg += '\n\nValidation errors:\n' + errors;
                        }
                        alert(errorMsg);
                    });
                };

                vm.cancelEdit = function() {
                    vm.updating = false;
                    vm.editForm = {};
                };

                vm.deleteSelected = function() {
                    if (!vm.selectedUser) {
                        alert('Please select a user to delete');
                        return;
                    }
                    if (!confirm('Are you sure you want to delete user: ' + vm.selectedUser.name + '?')) {
                        return;
                    }

                    $http.delete('/admin/users/' + vm.selectedUser.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('User deleted successfully');
                            vm.loadUsers();
                        } else {
                            alert('Failed to delete user: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        console.error('Error deleting user:', error);
                        alert('Failed to delete user. Please try again.');
                    });
                };

                vm.duplicateSelected = function() {
                    if (!vm.selectedUser) {
                        alert('Please select a user to duplicate');
                        return;
                    }
                    alert('Duplicate user functionality - to be implemented');
                };

                vm.manageRoles = function(user, event) {
                    if (event) event.stopPropagation();
                    alert('Manage roles for user: ' + user.name);
                };

                vm.managePermissions = function(user, event) {
                    if (event) event.stopPropagation();
                    alert('Manage permissions for user: ' + user.name);
                };

                vm.toggle2FA = function(user, event) {
                    if (event) event.stopPropagation();
                    var action = user.two_factor_enabled ? 'disable' : 'enable';
                    if (!confirm('Are you sure you want to ' + action + ' 2FA for user: ' + user.name + '?')) {
                        return;
                    }

                    $http.post('/admin/users/' + user.id + '/toggle-2fa', {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            user.two_factor_enabled = response.data.two_factor_enabled;
                            user.two_factor_auth = response.data.two_factor_auth;
                            alert(response.data.message);
                            vm.loadUsers();
                        } else {
                            alert('Failed to update 2FA: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        console.error('Error toggling 2FA:', error);
                        alert('Failed to update 2FA. Please try again.');
                    });
                };

                // Initialize
                vm.loadUsers();
                vm.loadTeams();

                // Reset updating flag when modal is closed
                $timeout(function() {
                    var modalElement = document.getElementById('editUserModal');
                    if (modalElement) {
                        modalElement.addEventListener('hidden.bs.modal', function() {
                            vm.updating = false;
                            vm.editForm = {};
                            if ($scope && !$scope.$$phase) {
                                $scope.$apply();
                            }
                        });
                    }
                }, 500);
            }]);
        } catch(e) {
            console.error('Error registering AdminUsersController:', e);
            setTimeout(registerController, 100);
        }
    }
    registerController();
})();
</script>
@endpush
@endsection

