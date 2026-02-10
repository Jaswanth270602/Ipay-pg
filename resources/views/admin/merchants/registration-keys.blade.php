@extends('layouts.app-sidebar')

@section('title', 'Merchant Registration Keys - Admin - ' . config('app.name'))
@section('page-title', 'Merchant Registration Keys')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminMerchantRegistrationKeysController as mrk">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Merchants'],
        ['label'=>'Merchant Registration Keys']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Merchant Registration Keys</h2>
            <p class="text-muted">Manage registration keys used for merchant integrations</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="mrk.pagination.per_page" ng-change="mrk.loadKeys()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mrk.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mrk.loadKeys()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mrk.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="mrk.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
            </div>
        </div>
    </div>

    <!-- Keys table -->
    <div class="stat-card">
        <div ng-show="mrk.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading merchant registration keys...</p>
            </div>
        </div>

        <div ng-hide="mrk.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Key Description</th>
                            <th>Merchant</th>
                            <th>Registration Key</th>
                            <th>Copy Merchant Params</th>
                            <th>Copy Velocity Checks</th>
                            <th>Copy Routing Randomize</th>
                            <th>Copy Account Whitelisting</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mrk.filters.id" ng-change="mrk.applyFilters()"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="mrk.filters.status" ng-change="mrk.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Active">Active</option>
                                    <option value="Not-Active">Not-Active</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mrk.filters.ip_address" ng-change="mrk.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="mrk.filters.key_description" ng-change="mrk.applyFilters()"></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mrk.keys.length === 0">
                            <td colspan="11" class="text-center text-muted py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="key in mrk.keys track by key.id">
                            <td>@{{ key.id }}</td>
                            <td>
                                <span class="badge" ng-class="key.status === 'active' ? 'bg-success' : 'bg-secondary'">
                                    @{{ key.status === 'active' ? 'ACTIVE' : 'NOT-ACTIVE' }}
                                </span>
                            </td>
                            <td>@{{ key.ip_address || '-' }}</td>
                            <td>@{{ key.key_description }}</td>
                            <td>@{{ key.merchant ? key.merchant.name : '-' }}</td>
                            <td><code>@{{ key.registration_key }}</code></td>
                            <td>@{{ key.copy_merchant_params ? 'Yes' : 'No' }}</td>
                            <td>@{{ key.copy_velocity_checks ? 'Yes' : 'No' }}</td>
                            <td>@{{ key.copy_routing_randomize ? 'Yes' : 'No' }}</td>
                            <td>@{{ key.copy_account_whitelisting ? 'Yes' : 'No' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="mrk.editKey(key)">
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
                    Showing @{{ (mrk.pagination.current_page - 1) * mrk.pagination.per_page + 1 }}
                    to @{{ Math.min(mrk.pagination.current_page * mrk.pagination.per_page, mrk.pagination.total) }}
                    of @{{ mrk.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mrk.changePage(mrk.pagination.current_page - 1)"
                            ng-disabled="mrk.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ mrk.pagination.current_page }} of @{{ mrk.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="mrk.changePage(mrk.pagination.current_page + 1)"
                            ng-disabled="mrk.pagination.current_page === mrk.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="registrationKeyModal" tabindex="-1" aria-labelledby="registrationKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="registrationKeyModalLabel">
                        <i class="bi bi-key"></i> @{{ mrk.isEditing ? 'Edit Merchant Registration Key' : 'Create Merchant Registration Key' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form novalidate>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Merchant Id <span class="text-danger" ng-if="!mrk.isEditing">*</span></label>
                                <select class="form-select" ng-model="mrk.form.merchant_id" ng-disabled="mrk.isEditing">
                                    <option value="">Type one or more letters to search</option>
                                    <option ng-repeat="m in mrk.merchants" value="@{{ m.id }}">@{{ m.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Key Description <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" ng-model="mrk.form.key_description" placeholder="Key Description">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" ng-model="mrk.form.status">
                                    <option value="Not-Active">Not-Active</option>
                                    <option value="Active">Active</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">IP Address</label>
                                <input type="text" class="form-control" ng-model="mrk.form.ip_address" placeholder="IP Address">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Copy Merchant Params</label>
                                <select class="form-select" ng-model="mrk.form.copy_merchant_params">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Copy Velocity Checks</label>
                                <select class="form-select" ng-model="mrk.form.copy_velocity_checks">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Copy Routing Randomize</label>
                                <select class="form-select" ng-model="mrk.form.copy_routing_randomize">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Copy Account Whitelisting</label>
                                <select class="form-select" ng-model="mrk.form.copy_account_whitelisting">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="mrk.saveKey()" ng-disabled="mrk.saving">
                        <span ng-if="mrk.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!mrk.saving"></i> Submit
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
            app.controller('AdminMerchantRegistrationKeysController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.keys = [];
                vm.merchants = [];
                vm.loading = false;
                vm.saving = false;
                vm.isEditing = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.filters = {
                    id: '',
                    status: 'all',
                    ip_address: '',
                    key_description: ''
                };

                vm.loadMerchants = function () {
                    $http.get("{{ route('admin.merchant-registration-keys.merchants') }}").then(function (response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.loadKeys = function () {
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

                    $http.get("{{ route('admin.merchant-registration-keys.data') }}", { params: params })
                        .then(function (response) {
                            vm.keys = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load merchant registration keys', 'error');
                            } else {
                                alert('Failed to load merchant registration keys');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadKeys();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadKeys();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        id: '',
                        status: 'all',
                        ip_address: '',
                        key_description: ''
                    };
                    vm.applyFilters();
                };

                vm.resetView = function () {
                    vm.clearFilters();
                    vm.pagination.current_page = 1;
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        merchant_id: '',
                        key_description: '',
                        status: 'Not-Active',
                        ip_address: '',
                        copy_merchant_params: '0',
                        copy_velocity_checks: '0',
                        copy_routing_randomize: '0',
                        copy_account_whitelisting: '0'
                    };
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('registrationKeyModal'));
                    modal.show();
                };

                vm.editKey = function (key) {
                    vm.isEditing = true;
                    vm.form = {
                        id: key.id,
                        merchant_id: key.merchant_id,
                        key_description: key.key_description,
                        status: key.status === 'active' ? 'Active' : 'Not-Active',
                        ip_address: key.ip_address,
                        copy_merchant_params: key.copy_merchant_params ? '1' : '0',
                        copy_velocity_checks: key.copy_velocity_checks ? '1' : '0',
                        copy_routing_randomize: key.copy_routing_randomize ? '1' : '0',
                        copy_account_whitelisting: key.copy_account_whitelisting ? '1' : '0'
                    };
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('registrationKeyModal'));
                    modal.show();
                };

                vm.saveKey = function () {
                    if (!vm.form.key_description || !vm.form.status || (!vm.isEditing && !vm.form.merchant_id)) {
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
                        url = "{{ url('admin/merchant-registration-keys') }}/" + vm.form.id;
                        method = 'POST';
                    } else {
                        url = "{{ route('admin.merchant-registration-keys.store') }}";
                        method = 'POST';
                    }

                    // Convert "0"/"1" to booleans for API
                    var payload = angular.copy(vm.form);
                    payload.copy_merchant_params = payload.copy_merchant_params === '1';
                    payload.copy_velocity_checks = payload.copy_velocity_checks === '1';
                    payload.copy_routing_randomize = payload.copy_routing_randomize === '1';
                    payload.copy_account_whitelisting = payload.copy_account_whitelisting === '1';

                    $http({
                        method: method,
                        url: url,
                        data: payload,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('registrationKeyModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Registration key saved', 'success');
                            } else {
                                alert(response.data.message || 'Registration key saved');
                            }
                            vm.loadKeys();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to save registration key';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var msg = 'Failed to save registration key';
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

                // Init
                vm.loadMerchants();
                vm.loadKeys();
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


