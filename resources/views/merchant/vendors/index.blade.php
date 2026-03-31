@extends('layouts.app-sidebar')

@section('title', 'Vendors - ' . config('app.name'))
@section('page-title', 'Vendors')

@section('content')
<div ng-app="ipayApp" ng-controller="MerchantVendorsController as mvc">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Vendors']
    ]" />

    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Vendor Dashboard</h2>
            <p class="text-muted mb-0">Create vendors, manage login access, and track links/orders/refunds/balances.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" ng-click="mvc.openCreateModal()">
                <i class="bi bi-plus-circle"></i> Create Vendor
            </button>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="mvc.loading" class="loader-overlay position-relative" style="min-height: 200px;">
            <div class="position-absolute top-50 start-50 translate-middle text-center">
                <div class="spinner-violet"></div>
                <p class="text-muted mt-2">Loading vendors...</p>
            </div>
        </div>

        <div ng-hide="mvc.loading" class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Vendor Login</th>
                    <th>Vendor Base Rate %</th>
                    <th>Approval</th>
                    <th>Payment Links</th>
                    <th>Orders</th>
                    <th>Refund Tracking</th>
                    <th>Collected</th>
                    <th>Settled</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-if="mvc.vendors.length === 0">
                    <td colspan="11" class="text-center text-muted py-4">No vendors yet</td>
                </tr>
                <tr ng-repeat="v in mvc.vendors track by v.id">
                    <td>
                        <strong>@{{ v.vendor_name }}</strong>
                        <div class="small text-muted">@{{ v.vendor_code }} | @{{ v.vendor_email }}</div>
                    </td>
                    <td>
                        <div class="small"><strong>ID:</strong> @{{ v.vendor_login_id || '-' }}</div>
                        <div class="small text-muted">Use Login ID + Password</div>
                    </td>
                    <td>
                        <span class="badge bg-info">@{{ v.vendor_base_rate_percentage | number:2 }}%</span>
                    </td>
                    <td>
                        <span class="badge" ng-class="{
                            'bg-warning': v.status === 'pending',
                            'bg-success': v.status === 'approved',
                            'bg-danger': v.status === 'disapproved'
                        }">@{{ v.status | uppercase }}</span>
                    </td>
                    <td>@{{ v.payment_links_count }}</td>
                    <td>@{{ v.orders_count }}</td>
                    <td>
                        <div class="small">Count: <strong>@{{ v.refunds_count }}</strong></div>
                        <div class="small">Amount: <strong>@{{ v.refund_amount | currency:'INR ' }}</strong></div>
                    </td>
                    <td>@{{ v.collected_amount | currency:'INR ' }}</td>
                    <td>@{{ v.settled_amount | currency:'INR ' }}</td>
                    <td>@{{ v.balance_amount | currency:'INR ' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info ms-1" ng-click="mvc.viewVendor(v)">
                            View
                        </button>
                        <button class="btn btn-sm btn-outline-primary ms-1" ng-click="mvc.openEditModal(v)">
                            Edit
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="merchantVendorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.vendor_code" placeholder="Vendor Code *" maxlength="255"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.vendor_name" placeholder="Vendor Name *" maxlength="255"></div>
                        <div class="col-md-6"><input type="email" class="form-control" ng-model="mvc.form.vendor_email" placeholder="Vendor Email *" maxlength="255"></div>
                        <div class="col-md-6"><input type="tel" class="form-control" ng-model="mvc.form.vendor_phone" placeholder="Vendor Mobile (10 digits) *" pattern="[0-9]{10}" maxlength="10"></div>
                        <div class="col-md-12"><input class="form-control" ng-model="mvc.form.vendor_address" placeholder="Vendor Address *" maxlength="500"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.vendor_pan_no" placeholder="PAN *" maxlength="20"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.vendor_login_id" placeholder="Vendor Login ID *" maxlength="255"></div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input id="create_vendor_password" type="password" class="form-control" ng-model="mvc.form.vendor_password" placeholder="Vendor Password (strong, min 8 chars) *" minlength="8" maxlength="100" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,100}$">
                                <button class="btn btn-outline-secondary" type="button" ng-click="mvc.togglePassword('create_vendor_password', $event)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input id="create_vendor_password_confirmation" type="password" class="form-control" ng-model="mvc.form.vendor_password_confirmation" placeholder="Confirm Vendor Password *" minlength="8" maxlength="100">
                                <button class="btn btn-outline-secondary" type="button" ng-click="mvc.togglePassword('create_vendor_password_confirmation', $event)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.bank_account_holder_name" placeholder="Account Holder Name *" maxlength="255"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.bank_account_number" placeholder="Account Number *" maxlength="50"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.bank_account_ifsc" placeholder="IFSC *" maxlength="20"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.bank_name" placeholder="Bank Name *" maxlength="255"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.bank_branch" placeholder="Branch *" maxlength="255"></div>
                        <div class="col-md-6">
                            <select class="form-select" ng-model="mvc.form.account_type">
                                <option value="Savings Account">Savings Account</option>
                                <option value="Current Account">Current Account</option>
                            </select>
                        </div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.form.upi_id" placeholder="UPI ID" maxlength="255"></div>
                        <div class="col-md-6"><input type="number" step="0.01" class="form-control" ng-model="mvc.form.vendor_base_rate_percentage" placeholder="Vendor Base Rate % (default 100)"></div>
                    </div>
                    <small class="text-muted d-block mt-2">Password must be strong: uppercase + lowercase + number + special character.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="mvc.createVendor()" ng-disabled="mvc.saving">Save Vendor</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="merchantVendorViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vendor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" ng-if="mvc.viewingVendor">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Name:</strong> @{{ mvc.viewingVendor.vendor_name }}</div>
                        <div class="col-md-6"><strong>Code:</strong> @{{ mvc.viewingVendor.vendor_code }}</div>
                        <div class="col-md-6"><strong>Email:</strong> @{{ mvc.viewingVendor.vendor_email }}</div>
                        <div class="col-md-6"><strong>Phone:</strong> @{{ mvc.viewingVendor.vendor_phone }}</div>
                        <div class="col-md-6"><strong>Login ID:</strong> @{{ mvc.viewingVendor.vendor_login_id || '-' }}</div>
                        <div class="col-md-6"><strong>Status:</strong> @{{ mvc.viewingVendor.status | uppercase }}</div>
                        <div class="col-md-12"><strong>Address:</strong> @{{ mvc.viewingVendor.vendor_address }}</div>
                        <div class="col-md-6"><strong>PAN:</strong> @{{ mvc.viewingVendor.vendor_pan_no }}</div>
                        <div class="col-md-6"><strong>IFSC:</strong> @{{ mvc.viewingVendor.bank_account_ifsc }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="merchantVendorEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">Set new password only when you want to reset vendor access.</div>
                    <div class="row g-3">
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.vendor_name" placeholder="Vendor Name *" maxlength="255"></div>
                        <div class="col-md-6"><input type="email" class="form-control" ng-model="mvc.editForm.vendor_email" placeholder="Vendor Email *" maxlength="255"></div>
                        <div class="col-md-6"><input type="tel" class="form-control" ng-model="mvc.editForm.vendor_phone" placeholder="Vendor Mobile *" pattern="[0-9]{10}" maxlength="10"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.vendor_login_id" placeholder="Vendor Login ID *" maxlength="255"></div>
                        <div class="col-md-12"><input class="form-control" ng-model="mvc.editForm.vendor_address" placeholder="Vendor Address *" maxlength="500"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.vendor_pan_no" placeholder="PAN *" maxlength="20"></div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input id="edit_vendor_password" type="password" class="form-control" ng-model="mvc.editForm.vendor_password" placeholder="New Password (optional)" minlength="8" maxlength="100" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,100}$">
                                <button class="btn btn-outline-secondary" type="button" ng-click="mvc.togglePassword('edit_vendor_password', $event)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input id="edit_vendor_password_confirmation" type="password" class="form-control" ng-model="mvc.editForm.vendor_password_confirmation" placeholder="Confirm New Password" minlength="8" maxlength="100">
                                <button class="btn btn-outline-secondary" type="button" ng-click="mvc.togglePassword('edit_vendor_password_confirmation', $event)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.bank_account_holder_name" placeholder="Account Holder Name *" maxlength="255"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.bank_account_number" placeholder="Account Number *" maxlength="50"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.bank_account_ifsc" placeholder="IFSC *" maxlength="20"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.bank_name" placeholder="Bank Name *" maxlength="255"></div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.bank_branch" placeholder="Branch *" maxlength="255"></div>
                        <div class="col-md-6">
                            <select class="form-select" ng-model="mvc.editForm.account_type">
                                <option value="Savings Account">Savings Account</option>
                                <option value="Current Account">Current Account</option>
                            </select>
                        </div>
                        <div class="col-md-6"><input class="form-control" ng-model="mvc.editForm.upi_id" placeholder="UPI ID" maxlength="255"></div>
                        <div class="col-md-6"><input type="number" step="0.01" class="form-control" ng-model="mvc.editForm.vendor_base_rate_percentage" placeholder="Vendor Base Rate %"></div>
                    </div>
                    <small class="text-muted d-block mt-2">If setting new password, use strong password with uppercase + lowercase + number + special character.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="mvc.updateVendor()" ng-disabled="mvc.saving">Update Vendor</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('ipayApp');
            app.controller('MerchantVendorsController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                vm.vendors = [];
                vm.loading = false;
                vm.saving = false;
                vm.form = { account_type: 'Savings Account' };
                vm.editForm = { account_type: 'Savings Account' };
                vm.viewingVendor = null;

                vm.load = function () {
                    vm.loading = true;
                    $http.get("{{ route('merchant.vendors.data') }}").then(function (res) {
                        vm.vendors = (res.data && res.data.data) ? res.data.data : [];
                        vm.loading = false;
                    }, function () { vm.loading = false; });
                };

                vm.togglePassword = function (fieldId, $event) {
                    var input = document.getElementById(fieldId);
                    if (!input) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                    var icon = $event && $event.currentTarget ? $event.currentTarget.querySelector('i') : null;
                    if (icon) {
                        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
                    }
                };

                vm.openCreateModal = function () {
                    vm.form = { account_type: 'Savings Account' };
                    vm.form.vendor_password = '';
                    vm.form.vendor_password_confirmation = '';
                    vm.form.vendor_base_rate_percentage = 100;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('merchantVendorModal')).show();
                };

                vm.createVendor = function () {
                    // Handle browser autofill cases where Angular model is stale
                    vm.form.vendor_password = vm.form.vendor_password || document.getElementById('create_vendor_password')?.value || '';
                    vm.form.vendor_password_confirmation = vm.form.vendor_password_confirmation || document.getElementById('create_vendor_password_confirmation')?.value || '';
                    vm.saving = true;
                    $http.post("{{ route('merchant.vendors.store') }}", vm.form, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (res) {
                        vm.saving = false;
                        if (res.data && res.data.success) {
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('merchantVendorModal')).hide();
                            if (typeof showToast === 'function') showToast(res.data.message || 'Vendor created', 'success');
                            vm.load();
                        } else if (typeof showToast === 'function') {
                            showToast((res.data && res.data.message) || 'Failed to create vendor', 'error');
                        }
                    }, function (err) {
                        vm.saving = false;
                        var msg = (err.data && err.data.message) ? err.data.message : 'Failed to create vendor';
                        if (err.data && err.data.errors) {
                            var lines = Object.values(err.data.errors).flat();
                            msg = lines.join(', ');
                        }
                        if (typeof showToast === 'function') showToast(msg, 'error');
                    });
                };

                vm.viewVendor = function (vendor) {
                    $http.get("{{ url('/merchant/vendors') }}/" + vendor.id).then(function (res) {
                        vm.viewingVendor = (res.data && res.data.data) ? res.data.data : null;
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('merchantVendorViewModal')).show();
                    });
                };

                vm.openEditModal = function (vendor) {
                    $http.get("{{ url('/merchant/vendors') }}/" + vendor.id).then(function (res) {
                        vm.editForm = (res.data && res.data.data) ? angular.copy(res.data.data) : {};
                        vm.editForm.vendor_password = '';
                        vm.editForm.vendor_password_confirmation = '';
                        vm.editForm.vendor_base_rate_percentage = vm.editForm.vendor_base_rate_percentage || 100;
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('merchantVendorEditModal')).show();
                    });
                };

                vm.updateVendor = function () {
                    vm.editForm.vendor_password = vm.editForm.vendor_password || document.getElementById('edit_vendor_password')?.value || '';
                    vm.editForm.vendor_password_confirmation = vm.editForm.vendor_password_confirmation || document.getElementById('edit_vendor_password_confirmation')?.value || '';
                    vm.saving = true;
                    $http.post("{{ url('/merchant/vendors') }}/" + vm.editForm.id, vm.editForm, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (res) {
                        vm.saving = false;
                        if (res.data && res.data.success) {
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('merchantVendorEditModal')).hide();
                            if (typeof showToast === 'function') showToast(res.data.message || 'Vendor updated', 'success');
                            vm.load();
                        } else if (typeof showToast === 'function') {
                            showToast((res.data && res.data.message) || 'Failed to update vendor', 'error');
                        }
                    }, function (err) {
                        vm.saving = false;
                        var msg = (err.data && err.data.message) ? err.data.message : 'Failed to update vendor';
                        if (err.data && err.data.errors) {
                            var lines = Object.values(err.data.errors).flat();
                            msg = lines.join(', ');
                        }
                        if (typeof showToast === 'function') showToast(msg, 'error');
                    });
                };

                vm.load();
            }]);
        } catch (e) {
            setTimeout(registerController, 50);
        }
    }
    registerController();
})();
</script>
@endpush

