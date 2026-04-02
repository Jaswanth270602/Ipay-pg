@extends('layouts.app-sidebar')

@section('title', 'Resellers - Admin - ' . config('app.name'))
@section('page-title', 'Reseller Management')

@section('content')
<div ng-app="ipayApp" ng-controller="AdminResellersController as arc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'User Settings'],
        ['label'=>'Resellers']
    ]" />

    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Resellers</h2>
            <p class="text-muted">List of Resellers</p>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0">Show</label>
                <select class="form-select form-select-sm" style="width: auto;" ng-model="arc.pagination.per_page" ng-change="arc.load()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.resetFilters()">Clear Filters</button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.load()">Reload</button>
                <button class="btn btn-sm btn-primary" ng-click="arc.openCreate()">+ New Reseller</button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Name</span>
                            </div>
                            <input class="form-control form-control-sm mt-1" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ng-model="arc.filters.filter_name" ng-change="arc.applyFilters()" placeholder="Filter...">
                        </th>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Company</span>
                            </div>
                            <input class="form-control form-control-sm mt-1" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ng-model="arc.filters.filter_company_name" ng-change="arc.applyFilters()" placeholder="Filter...">
                        </th>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Email</span>
                            </div>
                            <input class="form-control form-control-sm mt-1" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ng-model="arc.filters.filter_email" ng-change="arc.applyFilters()" placeholder="Filter...">
                        </th>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Phone</span>
                            </div>
                            <input id="reseller-phone-filter" class="form-control form-control-sm mt-1" type="text" inputmode="numeric" readonly onfocus="this.removeAttribute('readonly');" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" ng-model="arc.filters.filter_phone" ng-change="arc.applyFilters()" placeholder="Filter...">
                        </th>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Status</span>
                            </div>
                            <select class="form-select form-select-sm mt-1" ng-model="arc.filters.filter_status" ng-change="arc.applyFilters()">
                                <option value="all">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </th>
                        <th style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-if="!arc.rows.length">
                        <td colspan="6" class="text-center text-muted py-3">No resellers found</td>
                    </tr>
                    <tr ng-repeat="r in arc.rows track by r.id">
                        <td>@{{ r.name }}</td>
                        <td>@{{ r.company_name }}</td>
                        <td>@{{ r.email }}</td>
                        <td>@{{ r.phone }}</td>
                        <td>
                            <span class="badge" ng-class="{'bg-success': r.status==='active','bg-secondary': r.status==='inactive','bg-warning text-dark': r.status==='suspended'}">
                                @{{ r.status | uppercase }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary me-1" ng-click="arc.openEdit(r)" title="Edit"><i class="bi bi-pencil"></i></button>
                            <a class="btn btn-sm btn-outline-primary" ng-href="/admin/resellers/@{{ r.id }}" title="View"><i class="bi bi-eye"></i></a>
                            <button class="btn btn-sm btn-outline-danger ms-1" ng-click="arc.remove(r)" title="Delete"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div>Showing @{{ arc.pagination.from || 0 }} to @{{ arc.pagination.to || 0 }} of @{{ arc.pagination.total || 0 }}</div>
            <div>
                <button class="btn btn-sm btn-outline-secondary" ng-disabled="arc.pagination.current_page<=1" ng-click="arc.page(arc.pagination.current_page-1)">Previous</button>
                <span class="mx-2">...</span>
                <button class="btn btn-sm btn-outline-secondary" ng-disabled="arc.pagination.current_page>=arc.pagination.last_page" ng-click="arc.page(arc.pagination.current_page+1)">Next</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resellerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ arc.form.id ? 'Edit Reseller' : 'Create Reseller' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Name</label>
                            <input class="form-control form-control-sm" ng-class="{'is-invalid': arc.firstError('name')}" placeholder="Enter name" ng-model="arc.form.name" ng-change="arc.validateField('name')" ng-blur="arc.validateField('name')">
                            <div class="text-danger small mt-1" ng-if="arc.firstError('name')">@{{ arc.firstError('name') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Email</label>
                            <input class="form-control form-control-sm" ng-class="{'is-invalid': arc.firstError('email')}" placeholder="Enter email" ng-model="arc.form.email" ng-change="arc.validateField('email')" ng-blur="arc.validateField('email')">
                            <div class="text-danger small mt-1" ng-if="arc.firstError('email')">@{{ arc.firstError('email') }}</div>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Phone</label>
                            <input class="form-control form-control-sm" ng-class="{'is-invalid': arc.firstError('phone')}" placeholder="Enter phone" ng-model="arc.form.phone" ng-change="arc.validateField('phone')" ng-blur="arc.validateField('phone')">
                            <div class="text-danger small mt-1" ng-if="arc.firstError('phone')">@{{ arc.firstError('phone') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Company Name</label>
                            <input class="form-control form-control-sm" ng-class="{'is-invalid': arc.firstError('company_name')}" placeholder="Enter company name" ng-model="arc.form.company_name" ng-change="arc.validateField('company_name')" ng-blur="arc.validateField('company_name')">
                            <div class="text-danger small mt-1" ng-if="arc.firstError('company_name')">@{{ arc.firstError('company_name') }}</div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="alert alert-light border small mb-0" style="line-height: 1.4;">
                            <strong>Commission is configured per merchant.</strong>
                            Set reseller assignment and Admin/Reseller/Merchant shares in
                            <a href="{{ route('admin.base-rates.index') }}">Base Rates</a>.
                        </div>
                    </div>
                    <div class="row g-2 mt-1 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small mb-1 d-block">Status</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="resellerStatusToggle" ng-model="arc.form.status_toggle" ng-change="arc.syncStatusFromToggle(); arc.validateField('status')">
                                <label class="form-check-label small" for="resellerStatusToggle">
                                    @{{ arc.form.status_toggle ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                            <div class="text-danger small mt-1" ng-if="arc.firstError('status')">@{{ arc.firstError('status') }}</div>
                        </div>
                        <div class="col-md-6"></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Password @{{ arc.form.id ? '(optional)' : '' }}</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" ng-class="{'is-invalid': arc.firstError('password')}" type="@{{ arc.showPassword ? 'text' : 'password' }}" placeholder="Enter password" ng-model="arc.form.password" ng-change="arc.validateField('password')" ng-blur="arc.validateField('password')">
                                <button type="button" class="btn btn-outline-secondary" ng-click="arc.showPassword = !arc.showPassword" title="Toggle password visibility">
                                    <i class="bi" ng-class="arc.showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="text-danger small mt-1" ng-if="arc.firstError('password')">@{{ arc.firstError('password') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Confirm Password @{{ arc.form.id ? '(optional)' : '' }}</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" ng-class="{'is-invalid': arc.firstError('password_confirmation')}" type="@{{ arc.showConfirmPassword ? 'text' : 'password' }}" placeholder="Confirm password" ng-model="arc.form.password_confirmation" ng-change="arc.validateField('password_confirmation')" ng-blur="arc.validateField('password_confirmation')">
                                <button type="button" class="btn btn-outline-secondary" ng-click="arc.showConfirmPassword = !arc.showConfirmPassword" title="Toggle confirm password visibility">
                                    <i class="bi" ng-class="arc.showConfirmPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="text-danger small mt-1" ng-if="arc.firstError('password_confirmation')">@{{ arc.firstError('password_confirmation') }}</div>
                        </div>
                    </div>
                    <div class="text-danger small mt-2" ng-if="arc.error">@{{ arc.error }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" ng-click="arc.save()" ng-disabled="arc.saving || !arc.isFormValid()">
                        <span ng-if="arc.saving" class="spinner-border spinner-border-sm me-1"></span>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var app = angular.module('ipayApp');
    app.controller('AdminResellersController', ['$http', function($http) {
        var vm = this;
        vm.rows = [];
        vm.error = '';
        vm.validationErrors = {};
        vm.touched = {};
        vm.saving = false;
        vm.showPassword = false;
        vm.showConfirmPassword = false;
        vm.filters = {
            filter_name: '',
            filter_company_name: '',
            filter_email: '',
            filter_phone: '',
            filter_status: 'all'
        };
        vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
        vm.form = {};
        vm.sanitizeFilters = function() {
            if (typeof vm.filters.filter_phone === 'string' && vm.filters.filter_phone.indexOf('@') !== -1) {
                vm.filters.filter_phone = '';
            }
        };

        vm.load = function() {
            vm.sanitizeFilters();
            var p = {
                page: vm.pagination.current_page,
                per_page: vm.pagination.per_page
            };
            Object.keys(vm.filters).forEach(function(k){ if(vm.filters[k]) p[k] = vm.filters[k]; });
            $http.get('/admin/resellers/data', { params: p }).then(function(r){
                vm.rows = r.data.data || [];
                vm.pagination = r.data.pagination || vm.pagination;
            });
        };

        vm.applyFilters = function() { vm.pagination.current_page = 1; vm.load(); };
        vm.resetFilters = function() {
            vm.filters = {
                filter_name: '',
                filter_company_name: '',
                filter_email: '',
                filter_phone: '',
                filter_status: 'all'
            };
            vm.applyFilters();
        };
        vm.page = function(n) { vm.pagination.current_page = n; vm.load(); };
        vm.syncStatusFromToggle = function() {
            vm.form.status = vm.form.status_toggle ? 'active' : 'inactive';
        };
        vm.firstError = function(field) {
            return vm.validationErrors[field] && vm.validationErrors[field].length ? vm.validationErrors[field][0] : '';
        };
        vm.setError = function(field, message) {
            vm.validationErrors[field] = [message];
        };
        vm.clearError = function(field) {
            delete vm.validationErrors[field];
        };
        vm.validateField = function(field) {
            vm.touched[field] = true;

            // normalize as user types
            if (field === 'email' && vm.form.email) {
                vm.form.email = String(vm.form.email).trim().toLowerCase();
            }
            if (field === 'phone' && vm.form.phone) {
                vm.form.phone = String(vm.form.phone).replace(/\D/g, '').slice(0, 10);
            }
            if (field === 'company_name' && vm.form.company_name) {
                vm.form.company_name = String(vm.form.company_name).trim().replace(/\s+/g, ' ');
            }

            vm.clearError(field);

            var name = String(vm.form.name || '').trim();
            var email = String(vm.form.email || '').trim().toLowerCase();
            var phone = String(vm.form.phone || '').trim();
            var company = String(vm.form.company_name || '').trim();
            var password = String(vm.form.password || '');
            var confirmation = String(vm.form.password_confirmation || '');

            if (field === 'name') {
                if (!name) vm.setError('name', 'Name is required.');
                else if (name.length < 3) vm.setError('name', 'Name must be at least 3 characters.');
                else if (name.length > 100) vm.setError('name', 'Name must be at most 100 characters.');
                else if (!/^[A-Za-z\s]+$/.test(name)) vm.setError('name', 'Only alphabets and spaces are allowed.');
            }

            if (field === 'email') {
                if (!email) vm.setError('email', 'Email is required.');
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) vm.setError('email', 'Please enter a valid email address.');
            }

            if (field === 'phone') {
                if (!phone) vm.setError('phone', 'Phone is required.');
                else if (!/^\d{10}$/.test(phone)) vm.setError('phone', 'Phone must be exactly 10 digits.');
            }

            if (field === 'company_name') {
                if (!company) vm.setError('company_name', 'Company name is required.');
                else if (company.length < 2) vm.setError('company_name', 'Company name must be at least 2 characters.');
                else if (company.length > 150) vm.setError('company_name', 'Company name must be at most 150 characters.');
            }

            if (field === 'password') {
                if (!vm.form.id && !password) vm.setError('password', 'Password is required.');
                else if (password && password.length < 8) vm.setError('password', 'Password must be at least 8 characters.');
                else if (password && !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/.test(password)) {
                    vm.setError('password', 'Password must include uppercase, lowercase, number, and special character.');
                }
            }

            if (field === 'password_confirmation' || field === 'password') {
                vm.clearError('password_confirmation');
                if (!vm.form.id && !confirmation) vm.setError('password_confirmation', 'Confirm Password is required.');
                else if ((password || confirmation) && password !== confirmation) vm.setError('password_confirmation', 'Confirm Password must match Password.');
            }

            if (field === 'status') {
                if (typeof vm.form.status_toggle === 'undefined') vm.setError('status', 'Status is required.');
            }
        };

        vm.validateAll = function() {
            ['name', 'email', 'phone', 'company_name', 'status', 'password', 'password_confirmation'].forEach(function(f) {
                vm.validateField(f);
            });
            return Object.keys(vm.validationErrors).length === 0;
        };

        vm.isFormValid = function() {
            var name = String(vm.form.name || '').trim();
            var email = String(vm.form.email || '').trim().toLowerCase();
            var phone = String(vm.form.phone || '').trim();
            var company = String(vm.form.company_name || '').trim();
            var password = String(vm.form.password || '');
            var confirmation = String(vm.form.password_confirmation || '');
            var baseValid = name.length >= 3 && name.length <= 100 && /^[A-Za-z\s]+$/.test(name)
                && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
                && /^\d{10}$/.test(phone)
                && company.length >= 2 && company.length <= 150;
            if (!vm.form.id) {
                return baseValid
                    && password.length >= 8
                    && /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/.test(password)
                    && password === confirmation
                    && confirmation.length > 0
                    && !vm.saving;
            }
            var pwdValid = !password || (password.length >= 8 && /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/.test(password));
            var confValid = !password || password === confirmation;
            return baseValid && pwdValid && confValid && !vm.saving;
        };
        vm.showSuccessToast = function(message) {
            if (typeof window.showToast === 'function') {
                window.showToast(message, 'success');
                setTimeout(function() {
                    var toastElement = document.getElementById('globalToast');
                    if (!toastElement || typeof bootstrap === 'undefined') return;
                    var toastInstance = bootstrap.Toast.getInstance(toastElement);
                    if (toastInstance) toastInstance.hide();
                }, 3000);
            }
        };

        vm.openCreate = function() {
            vm.error = '';
            vm.validationErrors = {};
            vm.touched = {};
            vm.showPassword = false;
            vm.showConfirmPassword = false;
            vm.form = {
                status: 'inactive',
                status_toggle: false,
                password: '',
                password_confirmation: ''
            };
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resellerModal')).show();
        };

        vm.openEdit = function(r) {
            vm.error = '';
            vm.validationErrors = {};
            vm.touched = {};
            vm.showPassword = false;
            vm.showConfirmPassword = false;
            $http.get('/admin/resellers/' + r.id + '/data').then(function(resp) {
                vm.form = angular.copy(resp.data.data || r);
                vm.form.status_toggle = vm.form.status === 'active';
                vm.form.password = '';
                vm.form.password_confirmation = '';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('resellerModal')).show();
            }).catch(function() {
                vm.form = angular.copy(r);
                vm.form.status_toggle = vm.form.status === 'active';
                vm.form.password = '';
                vm.form.password_confirmation = '';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('resellerModal')).show();
            });
        };

        vm.save = function() {
            vm.error = '';
            vm.validationErrors = {};
            if (!vm.validateAll()) {
                var firstInvalid = Object.keys(vm.validationErrors)[0];
                if (firstInvalid) {
                    var el = document.querySelector('[ng-model="arc.form.' + firstInvalid + '"]');
                    if (el && typeof el.focus === 'function') el.focus();
                }
                vm.error = 'Validation failed';
                return;
            }
            vm.saving = true;
            vm.syncStatusFromToggle();
            vm.form.name = String(vm.form.name || '').trim();
            vm.form.email = String(vm.form.email || '').trim().toLowerCase();
            vm.form.phone = String(vm.form.phone || '').replace(/\D/g, '');
            vm.form.company_name = String(vm.form.company_name || '').trim().replace(/\s+/g, ' ');
            vm.form.status = vm.form.status_toggle ? true : false;
            var req = vm.form.id
                ? $http.put('/admin/resellers/' + vm.form.id, vm.form)
                : $http.post('/admin/resellers', vm.form);
            req.then(function(resp){
                vm.saving = false;
                if (resp.data.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('resellerModal')).hide();
                    vm.showSuccessToast(resp.data.message || (vm.form.id ? 'Reseller updated successfully.' : 'Reseller created successfully.'));
                    vm.load();
                } else {
                    vm.error = resp.data.message || 'Failed to save reseller';
                }
            }).catch(function(err){
                vm.saving = false;
                vm.validationErrors = err?.data?.errors || {};
                vm.error = err?.data?.message || 'Validation failed';
            });
        };

        vm.remove = function(r) {
            if (!confirm('Delete reseller "' + (r.name || '') + '"? This cannot be undone.')) {
                return;
            }
            $http.delete('/admin/resellers/' + r.id).then(function(resp) {
                if (!resp.data.success) {
                    vm.error = resp.data.message || 'Failed to delete reseller';
                    return;
                }
                vm.showSuccessToast(resp.data.message || 'Reseller deleted successfully.');
                vm.load();
            }).catch(function(err) {
                vm.error = err?.data?.message || 'Failed to delete reseller';
            });
        };

        vm.load();

        // Defensive cleanup for aggressive browser autofill plugins.
        setTimeout(function() {
            vm.sanitizeFilters();
            var phoneFilter = document.getElementById('reseller-phone-filter');
            if (phoneFilter && typeof phoneFilter.value === 'string' && phoneFilter.value.indexOf('@') !== -1) {
                phoneFilter.value = '';
                vm.filters.filter_phone = '';
            }
        }, 0);
    }]);
})();
</script>
@endpush

