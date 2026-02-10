@extends('layouts.app-sidebar')

@section('title', 'Partner Management - Admin - ' . config('app.name'))
@section('page-title', 'Partner Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPartnersController as apc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Pg Partners']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Partner Management</h2>
            <p class="text-muted">List of Partner Details</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="apc.pagination.per_page" ng-change="apc.loadPartners()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="apc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="apc.loadPartners()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.id" checked> Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.name" checked> Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.team_name" checked> Team Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.team_type" checked> Team Type</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.organization_name" checked> Organization Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.phone" checked> Phone</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.email" checked> Email</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.is_approved" checked> Is Approved</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.is_internal" checked> Is Internal</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.ref" checked> Ref</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.whitelabel_url" checked> WhiteLabel URL</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="apc.visibleColumns.registration_date" checked> Registration Date</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="apc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="apc.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="apc.editSelected()" ng-disabled="!apc.selectedPartner">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
        </div>
    </div>

    <!-- Partners table -->
    <div class="stat-card">
        <div ng-show="apc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading partners...</p>
            </div>
        </div>

        <div ng-hide="apc.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="apc.selectAll" ng-change="apc.toggleSelectAll()"> Id
                            </th>
                            <th>Name</th>
                            <th>Team Name</th>
                            <th>Team Type</th>
                            <th>Organization Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Is Approved</th>
                            <th>Is Internal</th>
                            <th>Ref</th>
                            <th>WhiteLabel URL</th>
                            <th>Registration Date</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.id" ng-change="apc.applyFilters()" placeholder="Id"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.name" ng-change="apc.applyFilters()" placeholder="Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.team_name" ng-change="apc.applyFilters()" placeholder="Team Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.team_type" ng-change="apc.applyFilters()" placeholder="Team Type"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="apc.filters.organization_name" ng-change="apc.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="org in apc.uniqueOrganizations" value="@{{ org }}">@{{ org }}</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.phone" ng-change="apc.applyFilters()" placeholder="Phone"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.email" ng-change="apc.applyFilters()" placeholder="Email"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="apc.filters.is_approved" ng-change="apc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="y">Yes</option>
                                    <option value="n">No</option>
                                </select>
                            </th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="apc.filters.is_internal" ng-change="apc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="y">Yes</option>
                                    <option value="n">No</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.ref" ng-change="apc.applyFilters()" placeholder="Ref"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.whitelabel_url" ng-change="apc.applyFilters()" placeholder="WhiteLabel URL"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="apc.filters.registration_date" placeholder="MM/DD/YYYY-MM" ng-change="apc.applyFilters()"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="apc.partners.length === 0">
                            <td colspan="13" class="text-center text-muted py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="partner in apc.partners track by partner.id"
                            ng-class="{'table-active': apc.selectedPartner && apc.selectedPartner.id === partner.id}"
                            ng-click="apc.selectPartner(partner)">
                            <td>
                                <input type="checkbox" ng-model="partner.selected" ng-click="$event.stopPropagation(); apc.updateSelectionState()">
                                @{{ partner.id }}
                            </td>
                            <td><strong>@{{ partner.name }}</strong></td>
                            <td>@{{ partner.team_name || '-' }}</td>
                            <td>@{{ partner.team_type || '-' }}</td>
                            <td>@{{ partner.organization_name || '-' }}</td>
                            <td>@{{ partner.phone || '-' }}</td>
                            <td>@{{ partner.email }}</td>
                            <td>
                                <span class="badge" ng-class="partner.is_approved ? 'bg-success' : 'bg-secondary'">
                                    @{{ partner.is_approved ? 'y' : 'n' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" ng-class="partner.is_internal ? 'bg-info' : 'bg-secondary'">
                                    @{{ partner.is_internal ? 'y' : 'n' }}
                                </span>
                            </td>
                            <td>@{{ partner.ref || '-' }}</td>
                            <td>
                                <a ng-if="partner.whitelabel_url" href="@{{ partner.whitelabel_url }}" target="_blank" class="text-primary text-decoration-none" title="Open WhiteLabel URL">
                                    @{{ partner.whitelabel_url.length > 30 ? (partner.whitelabel_url.substring(0, 30) + '...') : partner.whitelabel_url }}
                                </a>
                                <span ng-if="!partner.whitelabel_url" class="text-muted">-</span>
                            </td>
                            <td>@{{ partner.registration_date || '-' }}</td>
                            <td>
                                <div class="d-flex gap-1 align-items-center">
                                    <button class="btn btn-sm btn-success shadow-sm action-btn-view" 
                                            ng-click="apc.viewPartner(partner); $event.stopPropagation();" 
                                            title="View Details"
                                            style="min-width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: none;">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary shadow-sm action-btn-edit" 
                                            ng-click="apc.editPartner(partner); $event.stopPropagation();" 
                                            title="Edit Partner"
                                            style="min-width: 36px; height: 36px; border-radius: 6px; font-weight: 600; transition: all 0.2s ease; border: none;">
                                        M
                                    </button>
                                    <button class="btn btn-sm shadow-sm action-btn-tdr" 
                                            ng-click="apc.viewTDR(partner); $event.stopPropagation();" 
                                            title="View TDR Details"
                                            style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; border: none; min-width: 50px; height: 36px; border-radius: 6px; font-weight: 600; font-size: 11px; transition: all 0.2s ease;">
                                        TDR
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
                    Showing @{{ (apc.pagination.current_page - 1) * apc.pagination.per_page + 1 }}
                    to @{{ Math.min(apc.pagination.current_page * apc.pagination.per_page, apc.pagination.total) }}
                    of @{{ apc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="apc.changePage(apc.pagination.current_page - 1)"
                            ng-disabled="apc.pagination.current_page === 1">
                        Previous
                    </button>
                    <button class="btn btn-sm btn-primary mx-2" ng-repeat="page in apc.getPageNumbers() track by $index"
                            ng-class="{'active': page === apc.pagination.current_page}"
                            ng-click="apc.changePage(page)">
                        @{{ page }}
                    </button>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="apc.changePage(apc.pagination.current_page + 1)"
                            ng-disabled="apc.pagination.current_page === apc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="partnerModal" tabindex="-1" aria-labelledby="partnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="partnerModalLabel">
                        <i class="bi bi-people"></i> @{{ apc.isEditing ? 'Edit Partner' : 'Create new entry' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form novalidate>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Organization:</label>
                                <select class="form-select" ng-model="apc.form.organization_name">
                                    <option value="">Select Organization</option>
                                    <option ng-repeat="org in apc.uniqueOrganizations" value="@{{ org }}">@{{ org }}</option>
                                    <option value="Badilicash">Badilicash</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><span class="text-danger">*</span> Partner Name:</label>
                                <input type="text" class="form-control" ng-model="apc.form.name" required placeholder="Enter partner name">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><span class="text-danger">*</span> User Name:</label>
                                <input type="text" class="form-control" ng-model="apc.form.user_name" required placeholder="Enter user name">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><span class="text-danger">*</span> Mobile:</label>
                                <input type="text" class="form-control" ng-model="apc.form.phone" required placeholder="Enter mobile number">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><span class="text-danger">*</span> Email:</label>
                                <input type="email" class="form-control" ng-model="apc.form.email" required placeholder="Enter email address">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><span class="text-danger">*</span> Is Approved:</label>
                                <select class="form-select" ng-model="apc.form.is_approved" required>
                                    <option value="false">No</option>
                                    <option value="true">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Team Name:</label>
                                <input type="text" class="form-control" ng-model="apc.form.team_name" placeholder="Enter team name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Team Type:</label>
                                <select class="form-select" ng-model="apc.form.team_type">
                                    <option value="partner">Partner</option>
                                    <option value="internal">Internal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referral Code:</label>
                                <input type="text" class="form-control" ng-model="apc.form.referral_code" ng-disabled="apc.isEditing" placeholder="Auto-generated if empty">
                                <small class="text-muted">Auto-generated if left empty</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ref:</label>
                                <input type="text" class="form-control" ng-model="apc.form.ref" placeholder="Enter reference">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">WhiteLabel URL:</label>
                                <input type="url" class="form-control" ng-model="apc.form.whitelabel_url" placeholder="Enter whitelabel URL">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registration Date:</label>
                                <input type="date" class="form-control" ng-model="apc.form.registration_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Is Internal:</label>
                                <select class="form-select" ng-model="apc.form.is_internal">
                                    <option value="false">No</option>
                                    <option value="true">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes:</label>
                                <textarea class="form-control" rows="3" ng-model="apc.form.notes" placeholder="Enter notes"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="apc.savePartner()" ng-disabled="apc.saving">
                        <span ng-if="apc.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!apc.saving"></i> @{{ apc.isEditing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Partner Modal -->
    <div class="modal fade" id="viewPartnerModal" tabindex="-1" aria-labelledby="viewPartnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewPartnerModalLabel">
                        <i class="bi bi-eye"></i> Partner Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" ng-if="apc.viewPartnerData">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>ID:</strong> @{{ apc.viewPartnerData.id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Name:</strong> @{{ apc.viewPartnerData.name }}
                        </div>
                        <div class="col-md-6">
                            <strong>User Name:</strong> @{{ apc.viewPartnerData.user_name || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> @{{ apc.viewPartnerData.email }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong> @{{ apc.viewPartnerData.phone || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Team Name:</strong> @{{ apc.viewPartnerData.team_name || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Team Type:</strong> @{{ apc.viewPartnerData.team_type || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Organization Name:</strong> @{{ apc.viewPartnerData.organization_name || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Referral Code:</strong> @{{ apc.viewPartnerData.referral_code || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Ref:</strong> @{{ apc.viewPartnerData.ref || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>WhiteLabel URL:</strong> @{{ apc.viewPartnerData.whitelabel_url || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Registration Date:</strong> @{{ apc.viewPartnerData.registration_date || '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Is Approved:</strong> 
                            <span class="badge" ng-class="apc.viewPartnerData.is_approved ? 'bg-success' : 'bg-secondary'">
                                @{{ apc.viewPartnerData.is_approved ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Is Internal:</strong> 
                            <span class="badge" ng-class="apc.viewPartnerData.is_internal ? 'bg-info' : 'bg-secondary'">
                                @{{ apc.viewPartnerData.is_internal ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="col-md-12" ng-if="apc.viewPartnerData.notes">
                            <strong>Notes:</strong>
                            <p>@{{ apc.viewPartnerData.notes }}</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" ng-click="apc.editPartner(apc.viewPartnerData); $event.stopPropagation();">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .action-btn-view:hover {
        background-color: #22c55e !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3) !important;
    }
    
    .action-btn-edit:hover {
        background-color: #3b82f6 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3) !important;
    }
    
    .action-btn-tdr:hover {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(2, 132, 199, 0.3) !important;
    }
    
    .action-btn-view:active,
    .action-btn-edit:active,
    .action-btn-tdr:active {
        transform: translateY(0);
    }
    
    .table thead th {
        position: relative;
        font-weight: 600;
        color: #1f2937;
        background-color: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .table tbody tr:hover {
        background-color: #f3f4f6;
    }
    
    .table tbody tr.table-active {
        background-color: #e0e7ff;
    }
</style>
@endpush

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
            app.controller('AdminPartnersController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.partners = [];
                vm.loading = false;
                vm.saving = false;
                vm.isEditing = false;
                vm.selectedPartner = null;
                vm.viewPartnerData = null;
                vm.selectAll = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.visibleColumns = {
                    id: true,
                    name: true,
                    team_name: true,
                    team_type: true,
                    organization_name: true,
                    phone: true,
                    email: true,
                    is_approved: true,
                    is_internal: true,
                    ref: true,
                    whitelabel_url: true,
                    registration_date: true
                };

                vm.filters = {
                    id: '',
                    name: '',
                    team_name: '',
                    team_type: '',
                    organization_name: 'all',
                    phone: '',
                    email: '',
                    is_approved: 'all',
                    is_internal: 'all',
                    ref: '',
                    whitelabel_url: '',
                    registration_date: ''
                };

                vm.uniqueOrganizations = [];

                vm.loadPartners = function () {
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

                    $http.get("{{ route('admin.partners.data') }}", { params: params })
                        .then(function (response) {
                            vm.partners = (response.data.data || []).map(function(p) {
                                p.selected = false;
                                return p;
                            });
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                            vm.selectAll = false;
                            
                            // Extract unique organizations
                            var orgs = vm.partners.map(function(p) { return p.organization_name; }).filter(function(o) { return o; });
                            vm.uniqueOrganizations = [...new Set(orgs)];
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load partners', 'error');
                            } else {
                                alert('Failed to load partners');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadPartners();
                    }
                };

                vm.getPageNumbers = function () {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadPartners();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        id: '',
                        name: '',
                        team_name: '',
                        team_type: '',
                        organization_name: 'all',
                        phone: '',
                        email: '',
                        is_approved: 'all',
                        is_internal: 'all',
                        ref: '',
                        whitelabel_url: '',
                        registration_date: ''
                    };
                    vm.applyFilters();
                };

                vm.resetView = function () {
                    vm.clearFilters();
                    vm.pagination.current_page = 1;
                    vm.pagination.per_page = 5;
                };

                vm.selectPartner = function (partner) {
                    vm.selectedPartner = partner;
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        name: '',
                        user_name: '',
                        email: '',
                        team_name: '',
                        team_type: 'partner',
                        organization_name: 'Badilicash',
                        phone: '',
                        is_approved: 'true',
                        is_internal: 'false',
                        referral_code: '',
                        whitelabel_url: '',
                        registration_date: '',
                        ref: '',
                        notes: ''
                    };
                    var modal = new bootstrap.Modal(document.getElementById('partnerModal'));
                    modal.show();
                };

                vm.editPartner = function (partner) {
                    vm.isEditing = true;
                    vm.form = angular.copy(partner);
                    vm.form.is_approved = vm.form.is_approved ? 'true' : 'false';
                    vm.form.is_internal = vm.form.is_internal ? 'true' : 'false';
                    var modal = new bootstrap.Modal(document.getElementById('partnerModal'));
                    modal.show();
                };

                vm.editSelected = function () {
                    if (vm.selectedPartner) {
                        vm.editPartner(vm.selectedPartner);
                    }
                };

                vm.viewPartner = function (partner) {
                    $http.get("{{ url('admin/partners') }}/" + partner.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            vm.viewPartnerData = response.data.data;
                            var modal = new bootstrap.Modal(document.getElementById('viewPartnerModal'));
                            modal.show();
                        }
                    });
                };

                vm.savePartner = function () {
                    if (!vm.form.name || !vm.form.email || !vm.form.phone) {
                        if (typeof showToast === 'function') {
                            showToast('Please fill all required fields (Partner Name, Email, Mobile)', 'error');
                        } else {
                            alert('Please fill all required fields (Partner Name, Email, Mobile)');
                        }
                        return;
                    }

                    vm.saving = true;
                    var url, method;
                    if (vm.isEditing) {
                        url = "{{ url('admin/partners') }}/" + vm.form.id;
                        method = 'POST';
                    } else {
                        url = "{{ route('admin.partners.store') }}";
                        method = 'POST';
                    }

                    // Convert boolean strings to actual booleans
                    var formData = angular.copy(vm.form);
                    formData.is_approved = formData.is_approved === 'true' || formData.is_approved === true;
                    formData.is_internal = formData.is_internal === 'true' || formData.is_internal === true;

                    $http({
                        method: method,
                        url: url,
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('partnerModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Partner saved', 'success');
                            } else {
                                alert(response.data.message || 'Partner saved');
                            }
                            vm.loadPartners();
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Failed to save partner', 'error');
                            } else {
                                alert(response.data.message || 'Failed to save partner');
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var msg = 'Failed to save partner';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            msg = errors.join(', ');
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.deletePartner = function (partner) {
                    if (!confirm('Are you sure you want to delete partner "' + partner.name + '"?')) {
                        return;
                    }

                    $http.delete("{{ url('admin/partners') }}/" + partner.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Partner deleted', 'success');
                            } else {
                                alert(response.data.message || 'Partner deleted');
                            }
                            vm.loadPartners();
                            if (vm.selectedPartner && vm.selectedPartner.id === partner.id) {
                                vm.selectedPartner = null;
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Failed to delete partner', 'error');
                            } else {
                                alert(response.data.message || 'Failed to delete partner');
                            }
                        }
                    }, function (error) {
                        var msg = 'Failed to delete partner';
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

                vm.viewTDR = function (partner) {
                    window.location.href = "{{ route('admin.partners.tdr') }}?partner_id=" + partner.id;
                };

                vm.toggleSelectAll = function () {
                    vm.partners.forEach(function (p) {
                        p.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.partners.length > 0 && vm.partners.every(function (p) { return p.selected; });
                };

                // Initialize
                vm.loadPartners();
            }]);
        } catch(e) {
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

