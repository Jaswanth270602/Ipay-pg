@extends('layouts.app-sidebar')

@section('title', 'Fund Transfer - Admin - ' . config('app.name'))
@section('page-title', 'Fund Transfer')

@section('content')
<div ng-app="ipayApp" ng-controller="AdminFundTransferController as aftc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Fund Transfer']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Fund Transfer</h2>
            <p class="text-muted">List of Fund Transfers</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <button class="btn btn-primary" ng-click="aftc.openCreateModal()">
                    <i class="bi bi-plus-circle"></i> New
                </button>
            </div>
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="aftc.pagination.per_page" ng-change="aftc.loadTransfers()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="aftc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="aftc.loadTransfers()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="aftc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading fund transfers...</p>
            </div>
        </div>

        <div ng-hide="aftc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reference Id</th>
                            <th>Merchant Id</th>
                            <th>Merchant Name</th>
                            <th>Transfer Qualifier</th>
                            <th>Transfer Date</th>
                            <th>Transfer Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="aftc.transfers.length === 0">
                            <td colspan="8" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="transfer in aftc.transfers track by $index">
                            <td>@{{ transfer.reference_id }}</td>
                            <td>@{{ transfer.merchant_id }}</td>
                            <td>@{{ transfer.merchant_name }}</td>
                            <td>@{{ transfer.transfer_qualifier }}</td>
                            <td>@{{ transfer.transfer_date }}</td>
                            <td>@{{ transfer.transfer_amount }}</td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-success': transfer.status === 'completed',
                                    'bg-warning': transfer.status === 'pending',
                                    'bg-info': transfer.status === 'processing',
                                    'bg-danger': transfer.status === 'failed'
                                }">
                                    @{{ transfer.status | uppercase }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="aftc.viewTransfer(transfer)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (aftc.pagination.current_page - 1) * aftc.pagination.per_page + 1 }} to @{{ Math.min(aftc.pagination.current_page * aftc.pagination.per_page, aftc.pagination.total) }} of @{{ aftc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="aftc.changePage(aftc.pagination.current_page - 1)" 
                            ng-disabled="aftc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="aftc.changePage(aftc.pagination.current_page + 1)" 
                            ng-disabled="aftc.pagination.current_page === aftc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
<!-- Create Fund Transfer Modal -->
<div class="modal fade" id="createFundTransferModal" tabindex="-1" aria-labelledby="createFundTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFundTransferModalLabel">Create new entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createFundTransferForm" ng-submit="aftc.submitFundTransfer()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">* Merchant</label>
                            <select class="form-select" ng-model="aftc.form.merchant_id" ng-class="{'is-invalid': aftc.formErrors.merchant_id}" ng-change="aftc.formErrors.merchant_id = null" required>
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in aftc.merchants" value="@{{ merchant.id }}">@{{ merchant.name }}</option>
                            </select>
                            <div class="invalid-feedback" ng-if="aftc.formErrors.merchant_id">@{{ aftc.formErrors.merchant_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Transfer Qualifier</label>
                            <select class="form-select" ng-model="aftc.form.transfer_qualifier" required>
                                <option value="">Select Qualifier</option>
                                <option value="MERCHANT LEDGER">MERCHANT LEDGER</option>
                                <option value="SETTLEMENT">SETTLEMENT</option>
                                <option value="REFUND">REFUND</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Transfer Date</label>
                            <input type="date" class="form-control" ng-model="aftc.form.transfer_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Transfer Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.transfer_amount" ng-class="{'is-invalid': aftc.formErrors.transfer_amount}" ng-change="aftc.validateFundTransferField('transfer_amount')" ng-blur="aftc.validateFundTransferField('transfer_amount')" required>
                            <div class="invalid-feedback" ng-if="aftc.formErrors.transfer_amount">@{{ aftc.formErrors.transfer_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Reference Id</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_reference_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose of Payment</label>
                            <input type="text" class="form-control" ng-model="aftc.form.purpose_of_payment" ng-class="{'is-invalid': aftc.formErrors.purpose_of_payment}" ng-change="aftc.validateFundTransferField('purpose_of_payment')" ng-blur="aftc.validateFundTransferField('purpose_of_payment')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.purpose_of_payment">@{{ aftc.formErrors.purpose_of_payment }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Reference No</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_reference_no" ng-class="{'is-invalid': aftc.formErrors.transfer_reference_no}" ng-change="aftc.validateFundTransferField('transfer_reference_no')" ng-blur="aftc.validateFundTransferField('transfer_reference_no')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.transfer_reference_no">@{{ aftc.formErrors.transfer_reference_no }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Mode</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_mode" value="SFTI ADJ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Credited Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.credited_amount" ng-class="{'is-invalid': aftc.formErrors.credited_amount}" ng-change="aftc.validateFundTransferField('credited_amount')" ng-blur="aftc.validateFundTransferField('credited_amount')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.credited_amount">@{{ aftc.formErrors.credited_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Debited Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.debited_amount" ng-class="{'is-invalid': aftc.formErrors.debited_amount}" ng-change="aftc.validateFundTransferField('debited_amount')" ng-blur="aftc.validateFundTransferField('debited_amount')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.debited_amount">@{{ aftc.formErrors.debited_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Account</label>
                            <input type="text" class="form-control" ng-model="aftc.form.to_account" ng-class="{'is-invalid': aftc.formErrors.to_account}" ng-change="aftc.validateFundTransferField('to_account')" ng-blur="aftc.validateFundTransferField('to_account')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.to_account">@{{ aftc.formErrors.to_account }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name (CA)</label>
                            <input type="text" class="form-control" ng-model="aftc.form.bank_name_ca" ng-class="{'is-invalid': aftc.formErrors.bank_name_ca}" ng-change="aftc.validateFundTransferField('bank_name_ca')" ng-blur="aftc.validateFundTransferField('bank_name_ca')">
                            <div class="invalid-feedback" ng-if="aftc.formErrors.bank_name_ca">@{{ aftc.formErrors.bank_name_ca }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fund Received</label>
                            <select class="form-select" ng-model="aftc.form.fund_received">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fund Received with Commission</label>
                            <select class="form-select" ng-model="aftc.form.fund_received_with_commission">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" rows="3" ng-model="aftc.form.notes"></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Create Fund Transfer</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

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
            var app = angular.module('ipayApp');
            app.controller('AdminFundTransferController', ['$http', function($http) {
                var vm = this;
                vm.transfers = [];
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.form = {};
                vm.formErrors = {};

                vm.loadTransfers = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/admin/settlements/fund-transfer/data', { params: params }).then(function(response) {
                        vm.transfers = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading transfers:', error);
                    });
                };

                vm.loadMerchants = function() {
                    $http.get('/admin/settlements/merchants').then(function(response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.openCreateModal = function() {
                    vm.formErrors = {};
                    vm.form = { fund_received: 'No', fund_received_with_commission: 'No' };
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('createFundTransferModal'));
                    modal.show();
                };

                vm.validateFundTransferField = function(field) {
                    if (!vm.formErrors) vm.formErrors = {};
                    delete vm.formErrors[field];
                    var f = vm.form || {};
                    var value = f[field];
                    var asString = String(value == null ? '' : value).trim();

                    if (field === 'transfer_amount' || field === 'credited_amount' || field === 'debited_amount') {
                        if (field === 'transfer_amount' && !asString) {
                            vm.formErrors[field] = 'Transfer amount is required.';
                            return;
                        }
                        if (!asString) return;
                        if (!/^\d+(\.\d+)?$/.test(asString)) {
                            vm.formErrors[field] = 'Only numbers and decimal are allowed.';
                            return;
                        }
                        if (Number(asString) < 0) {
                            vm.formErrors[field] = 'This value cannot be negative.';
                        }
                        return;
                    }

                    if (field === 'purpose_of_payment' || field === 'transfer_reference_no') {
                        if (asString && !/^[A-Za-z0-9 ]+$/.test(asString)) {
                            vm.formErrors[field] = 'Only letters, numbers, and spaces are allowed.';
                        }
                        return;
                    }

                    if (field === 'to_account' || field === 'bank_name_ca') {
                        if (asString && !/^[A-Za-z0-9 ]+$/.test(asString)) {
                            vm.formErrors[field] = 'Only letters, numbers, and spaces are allowed.';
                        }
                    }
                };

                vm.validateFundTransferForm = function() {
                    vm.formErrors = {};
                    if (!vm.form || !vm.form.merchant_id) {
                        vm.formErrors.merchant_id = 'Merchant is required.';
                    }
                    vm.validateFundTransferField('transfer_amount');
                    vm.validateFundTransferField('credited_amount');
                    vm.validateFundTransferField('debited_amount');
                    vm.validateFundTransferField('purpose_of_payment');
                    vm.validateFundTransferField('transfer_reference_no');
                    vm.validateFundTransferField('to_account');
                    vm.validateFundTransferField('bank_name_ca');
                    return Object.keys(vm.formErrors).length === 0;
                };

                vm.submitFundTransfer = function() {
                    if (!vm.validateFundTransferForm()) {
                        if (typeof showToast === 'function') {
                            showToast('Please correct the highlighted errors.', 'error');
                        } else {
                            alert('Please correct the highlighted errors.');
                        }
                        return;
                    }
                    $http.post('/admin/settlements/fund-transfer', vm.form).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Fund transfer created successfully!', 'success');
                            } else {
                                alert('Fund transfer created successfully!');
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById('createFundTransferModal'));
                            modal.hide();
                            vm.loadTransfers();
                        } else {
                            var msg = 'Error: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function(error) {
                        if (error.data && error.data.errors) {
                            vm.formErrors = Object.keys(error.data.errors).reduce(function(acc, key) {
                                acc[key] = (error.data.errors[key] || [])[0] || 'Invalid value';
                                return acc;
                            }, {});
                        }
                        var msg = 'Error creating fund transfer: ' + ((error.data && error.data.message) || 'Unknown error');
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadTransfers();
                    }
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.loadTransfers();
                };

                vm.viewTransfer = function(transfer) {
                    alert('View transfer: ' + transfer.reference_id);
                };

                vm.loadTransfers();
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


