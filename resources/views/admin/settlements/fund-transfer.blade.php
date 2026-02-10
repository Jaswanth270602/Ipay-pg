@extends('layouts.app-sidebar')

@section('title', 'Fund Transfer - Admin - ' . config('app.name'))
@section('page-title', 'Fund Transfer')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminFundTransferController as aftc">
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
                            <select class="form-select" ng-model="aftc.form.merchant_id" required>
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in aftc.merchants" value="@{{ merchant.id }}">@{{ merchant.name }}</option>
                            </select>
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
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.transfer_amount" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Reference Id</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_reference_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose of Payment</label>
                            <input type="text" class="form-control" ng-model="aftc.form.purpose_of_payment">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Reference No</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_reference_no">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Mode</label>
                            <input type="text" class="form-control" ng-model="aftc.form.transfer_mode" value="SFTI ADJ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Credited Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.credited_amount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Debited Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="aftc.form.debited_amount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Account</label>
                            <input type="text" class="form-control" ng-model="aftc.form.to_account">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name (CA)</label>
                            <input type="text" class="form-control" ng-model="aftc.form.bank_name_ca">
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
            var app = angular.module('badlicashApp');
            app.controller('AdminFundTransferController', ['$http', function($http) {
                var vm = this;
                vm.transfers = [];
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.form = {};

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
                    $http.get('/admin/merchants/data', { params: { per_page: 1000 } }).then(function(response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.openCreateModal = function() {
                    vm.form = {};
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('createFundTransferModal'));
                    modal.show();
                };

                vm.submitFundTransfer = function() {
                    $http.post('/admin/settlements/fund-transfer', vm.form).then(function(response) {
                        if (response.data.success) {
                            alert('Fund transfer created successfully!');
                            var modal = bootstrap.Modal.getInstance(document.getElementById('createFundTransferModal'));
                            modal.hide();
                            vm.loadTransfers();
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        alert('Error creating fund transfer: ' + (error.data?.message || 'Unknown error'));
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


