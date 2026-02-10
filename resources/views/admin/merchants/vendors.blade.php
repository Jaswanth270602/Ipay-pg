@extends('layouts.app-sidebar')

@section('title', 'Merchant Vendors - Admin - ' . config('app.name'))
@section('page-title', 'Merchant Vendors')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminMerchantVendorsController as mvc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Merchants'],
        ['label'=>'Merchant Vendors']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Merchant Vendors</h2>
            <p class="text-muted">Manage vendor bank accounts linked to merchants</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="mvc.pagination.per_page" ng-change="mvc.loadVendors()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mvc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mvc.loadVendors()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mvc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="mvc.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
                <button class="btn btn-sm btn-outline-danger" ng-click="mvc.deleteSelected()" ng-disabled="!mvc.hasSelection()">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false" ng-disabled="!mvc.hasSelection()">
                        <i class="bi bi-check2-square"></i> Approve/Disapprove
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" ng-click="mvc.changeStatusForSelected('approved')">
                            <i class="bi bi-check-circle text-success me-1"></i> Approve
                        </a></li>
                        <li><a class="dropdown-item" href="#" ng-click="mvc.changeStatusForSelected('disapproved')">
                            <i class="bi bi-x-circle text-danger me-1"></i> Disapprove
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendors table -->
    <div class="stat-card">
        <div ng-show="mvc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading merchant vendors...</p>
            </div>
        </div>

        <div ng-hide="mvc.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="mvc.selectAll" ng-change="mvc.toggleSelectAll()">
                            </th>
                            <th>Vendor Id</th>
                            <th>Vendor Name</th>
                            <th>Vendor Code</th>
                            <th>Merchant Name</th>
                            <th>Merchant Id</th>
                            <th>Vendor Email</th>
                            <th>Vendor Phone</th>
                            <th>Vendor Address</th>
                            <th>Bank Account Name</th>
                            <th>Bank Account Number</th>
                            <th>Bank Account IFSC Code</th>
                            <th>Bank Name</th>
                            <th>Bank Branch</th>
                            <th>Account Type</th>
                            <th>UPI Id</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_id" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_name" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_code" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.merchant_name" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.merchant_id" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_email" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_phone" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.vendor_address" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.bank_account_holder_name" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.bank_account_number" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.bank_account_ifsc" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.bank_name" ng-change="mvc.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.bank_branch" ng-change="mvc.applyFilters()"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="mvc.filters.account_type" ng-change="mvc.applyFilters()">
                                    <option value="">All</option>
                                    <option value="Savings Account">Savings Account</option>
                                    <option value="Current Account">Current Account</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mvc.filters.upi_id" ng-change="mvc.applyFilters()"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="mvc.filters.status" ng-change="mvc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="disapproved">Disapproved</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mvc.vendors.length === 0">
                            <td colspan="18" class="text-center text-muted py-4">No merchant vendors found</td>
                        </tr>
                        <tr ng-repeat="vendor in mvc.vendors track by vendor.id"
                            ng-class="{'table-active': mvc.selectedVendor && mvc.selectedVendor.id === vendor.id}"
                            ng-click="mvc.selectVendor(vendor)">
                            <td>
                                <input type="checkbox" ng-model="vendor.selected" ng-click="$event.stopPropagation(); mvc.updateSelectionState()">
                            </td>
                            <td>@{{ vendor.id }}</td>
                            <td>@{{ vendor.vendor_name }}</td>
                            <td>@{{ vendor.vendor_code }}</td>
                            <td>@{{ vendor.merchant ? vendor.merchant.name : '-' }}</td>
                            <td>@{{ vendor.merchant_id }}</td>
                            <td>@{{ vendor.vendor_email }}</td>
                            <td>@{{ vendor.vendor_phone || '-' }}</td>
                            <td>@{{ vendor.vendor_address || '-' }}</td>
                            <td>@{{ vendor.bank_account_holder_name }}</td>
                            <td>@{{ vendor.bank_account_number }}</td>
                            <td>@{{ vendor.bank_account_ifsc }}</td>
                            <td>@{{ vendor.bank_name || '-' }}</td>
                            <td>@{{ vendor.bank_branch || '-' }}</td>
                            <td>@{{ vendor.account_type }}</td>
                            <td>@{{ vendor.upi_id || '-' }}</td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-warning': vendor.status === 'pending',
                                    'bg-success': vendor.status === 'approved',
                                    'bg-danger': vendor.status === 'disapproved'
                                }">
                                    @{{ vendor.status | uppercase }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="mvc.editVendor(vendor); $event.stopPropagation();">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (mvc.pagination.current_page - 1) * mvc.pagination.per_page + 1 }}
                    to @{{ Math.min(mvc.pagination.current_page * mvc.pagination.per_page, mvc.pagination.total) }}
                    of @{{ mvc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mvc.changePage(mvc.pagination.current_page - 1)"
                            ng-disabled="mvc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ mvc.pagination.current_page }} of @{{ mvc.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mvc.changePage(mvc.pagination.current_page + 1)"
                            ng-disabled="mvc.pagination.current_page === mvc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Vendor Modal -->
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="vendorModalLabel">
                        <i class="bi bi-people"></i> @{{ mvc.isEditing ? 'Edit Vendor' : 'Create new entry' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <select class="form-select" ng-model="mvc.form.merchant_id" required>
                                    <option value="">Select an merchant</option>
                                    <option ng-repeat="m in mvc.merchants" value="@{{ m.id }}">@{{ m.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Bank Account Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.bank_account_number" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_code" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.bank_account_holder_name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Account IFSC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.bank_account_ifsc" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" ng-model="mvc.form.vendor_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.bank_branch" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Contact No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.bank_name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Contact Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" ng-model="mvc.form.account_type" required>
                                    <option value="Savings Account">Savings Account</option>
                                    <option value="Current Account">Current Account</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor PAN No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_pan_no" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Note</label>
                                <input type="text" class="form-control" ng-model="mvc.form.note">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Login ID</label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_login_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Description 1</label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_description_1">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor Description 2</label>
                                <input type="text" class="form-control" ng-model="mvc.form.vendor_description_2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference ID</label>
                                <input type="text" class="form-control" ng-model="mvc.form.reference_id">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">UPI Id</label>
                                <input type="text" class="form-control" ng-model="mvc.form.upi_id">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="mvc.saveVendor()" ng-disabled="mvc.saving">
                        <span ng-if="mvc.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!mvc.saving"></i> @{{ mvc.isEditing ? 'Update' : 'Create' }}
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
            app.controller('AdminMerchantVendorsController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.vendors = [];
                vm.merchants = [];
                vm.loading = false;
                vm.saving = false;
                vm.isEditing = false;
                vm.selectedVendor = null;
                vm.selectAll = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.filters = {
                    vendor_id: '',
                    vendor_name: '',
                    vendor_code: '',
                    merchant_name: '',
                    merchant_id: '',
                    vendor_email: '',
                    vendor_phone: '',
                    vendor_address: '',
                    bank_account_holder_name: '',
                    bank_account_number: '',
                    bank_account_ifsc: '',
                    bank_name: '',
                    bank_branch: '',
                    account_type: '',
                    upi_id: '',
                    status: 'all'
                };

                vm.loadMerchants = function () {
                    $http.get("{{ route('admin.merchant-vendors.merchants') }}").then(function (response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.loadVendors = function () {
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

                    $http.get("{{ route('admin.merchant-vendors.data') }}", { params: params })
                        .then(function (response) {
                            vm.vendors = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                            vm.selectAll = false;
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load merchant vendors', 'error');
                            } else {
                                alert('Failed to load merchant vendors');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadVendors();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadVendors();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        vendor_id: '',
                        vendor_name: '',
                        vendor_code: '',
                        merchant_name: '',
                        merchant_id: '',
                        vendor_email: '',
                        vendor_phone: '',
                        vendor_address: '',
                        bank_account_holder_name: '',
                        bank_account_number: '',
                        bank_account_ifsc: '',
                        bank_name: '',
                        bank_branch: '',
                        account_type: '',
                        upi_id: '',
                        status: 'all'
                    };
                    vm.applyFilters();
                };

                vm.resetView = function () {
                    vm.clearFilters();
                    vm.pagination.current_page = 1;
                };

                vm.toggleSelectAll = function () {
                    vm.vendors.forEach(function (v) {
                        v.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.vendors.length > 0 && vm.vendors.every(function (v) { return v.selected; });
                };

                vm.hasSelection = function () {
                    return vm.vendors.some(function (v) { return v.selected; });
                };

                vm.selectVendor = function (vendor) {
                    vm.selectedVendor = vendor;
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        merchant_id: '',
                        vendor_code: '',
                        vendor_name: '',
                        vendor_email: '',
                        vendor_phone: '',
                        vendor_address: '',
                        vendor_pan_no: '',
                        vendor_login_id: '',
                        vendor_description_1: '',
                        vendor_description_2: '',
                        bank_account_number: '',
                        bank_account_ifsc: '',
                        bank_name: '',
                        bank_branch: '',
                        bank_account_holder_name: '',
                        account_type: 'Savings Account',
                        upi_id: '',
                        note: '',
                        reference_id: ''
                    };
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('vendorModal'));
                    modal.show();
                };

                vm.editVendor = function (vendor) {
                    vm.isEditing = true;
                    vm.form = angular.copy(vendor);
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('vendorModal'));
                    modal.show();
                };

                vm.saveVendor = function () {
                    if (!vm.form.merchant_id || !vm.form.vendor_code || !vm.form.vendor_name ||
                        !vm.form.vendor_email || !vm.form.vendor_phone || !vm.form.vendor_address ||
                        !vm.form.vendor_pan_no || !vm.form.bank_account_number || !vm.form.bank_account_ifsc ||
                        !vm.form.bank_name || !vm.form.bank_branch || !vm.form.bank_account_holder_name ||
                        !vm.form.account_type) {
                        if (typeof showToast === 'function') {
                            showToast('Please fill all required fields', 'error');
                        } else {
                            alert('Please fill all required fields');
                        }
                        return;
                    }

                    vm.saving = true;
                    var url, method;
                    if (vm.isEditing) {
                        url = "{{ url('admin/merchant-vendors') }}/" + vm.form.id;
                        method = 'POST';
                    } else {
                        url = "{{ route('admin.merchant-vendors.store') }}";
                        method = 'POST';
                    }

                    $http({
                        method: method,
                        url: url,
                        data: vm.form,
                        headers: {
                            'X-CSRF-TOKEN': csrf
                        }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('vendorModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Vendor saved', 'success');
                            } else {
                                alert(response.data.message || 'Vendor saved');
                            }
                            vm.loadVendors();
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Failed to save vendor', 'error');
                            } else {
                                alert(response.data.message || 'Failed to save vendor');
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var msg = 'Failed to save vendor';
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

                vm.getSelectedIds = function () {
                    return vm.vendors.filter(function (v) { return v.selected; }).map(function (v) { return v.id; });
                };

                vm.deleteSelected = function () {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;
                    if (!confirm('Are you sure you want to delete selected vendors?')) {
                        return;
                    }

                    // Delete one by one to keep it simple
                    var requests = ids.map(function (id) {
                        return $http.delete("{{ url('admin/merchant-vendors') }}/" + id, {
                            headers: { 'X-CSRF-TOKEN': csrf }
                        });
                    });

                    Promise.all(requests.map(function (p) { return p.then(function () {}, function () {}); }))
                        .then(function () {
                            if (typeof showToast === 'function') {
                                showToast('Selected vendors deleted successfully', 'success');
                            } else {
                                alert('Selected vendors deleted successfully');
                            }
                            vm.loadVendors();
                        });
                };

                vm.changeStatusForSelected = function (status) {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;

                    $http.post("{{ route('admin.merchant-vendors.bulk-status') }}", {
                        vendor_ids: ids,
                        status: status
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Status updated', 'success');
                            } else {
                                alert(response.data.message || 'Status updated');
                            }
                            vm.loadVendors();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to update status';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        var msg = 'Failed to update status';
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

                // Init
                vm.loadMerchants();
                vm.loadVendors();
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


