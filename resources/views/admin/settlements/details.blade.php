@extends('layouts.app-sidebar')

@section('title', 'Settlement Details - Admin - ' . config('app.name'))
@section('page-title', 'Settlement Details')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminSettlementDetailsController as asdc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Settlement Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Settlement Details</h2>
            <p class="text-muted">List of Settlement Details</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="asdc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" ng-click="asdc.openCreateModal()">
                    <i class="bi bi-plus-circle"></i> + Settlement Detail
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="asdc.pagination.per_page" ng-change="asdc.loadDetails()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.loadDetails()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in asdc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="asdc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="asdc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlement details...</p>
            </div>
        </div>

        <div ng-hide="asdc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="asdc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_merchant_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_merchant_name" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_order_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_transaction_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.tran_seq_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Tran Seq Id</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.transaction_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Date</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Amount Paid by Customer</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.settlement_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Amount</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="asdc.details.length === 0">
                            <td colspan="9" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="detail in asdc.details track by $index">
                            <td ng-show="asdc.visibleColumns.merchant_id.visible">@{{ detail.merchant_id }}</td>
                            <td ng-show="asdc.visibleColumns.merchant_name.visible">@{{ detail.merchant_name }}</td>
                            <td ng-show="asdc.visibleColumns.order_id.visible">@{{ detail.order_id }}</td>
                            <td ng-show="asdc.visibleColumns.transaction_id.visible">@{{ detail.transaction_id }}</td>
                            <td ng-show="asdc.visibleColumns.tran_seq_id.visible">@{{ detail.tran_seq_id }}</td>
                            <td ng-show="asdc.visibleColumns.transaction_date.visible">@{{ detail.transaction_date }}</td>
                            <td ng-show="asdc.visibleColumns.amount_paid_by_customer.visible">@{{ detail.amount_paid_by_customer }}</td>
                            <td ng-show="asdc.visibleColumns.settlement_amount.visible">@{{ detail.settlement_amount }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="asdc.viewDetail(detail)">
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
                    Showing @{{ (asdc.pagination.current_page - 1) * asdc.pagination.per_page + 1 }} to @{{ Math.min(asdc.pagination.current_page * asdc.pagination.per_page, asdc.pagination.total) }} of @{{ asdc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="asdc.changePage(asdc.pagination.current_page - 1)" 
                            ng-disabled="asdc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="asdc.changePage(asdc.pagination.current_page + 1)" 
                            ng-disabled="asdc.pagination.current_page === asdc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Settlement Detail Modal -->
<div class="modal fade" id="createSettlementDetailModal" tabindex="-1" aria-labelledby="createSettlementDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSettlementDetailModalLabel">Create Settlement Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSettlementDetailForm" ng-submit="asdc.submitSettlementDetail()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">* Merchant</label>
                            <select class="form-select" ng-model="asdc.form.merchant_id" required>
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in asdc.merchants" value="@{{ merchant.id }}">@{{ merchant.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Order Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.order_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.transaction_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tran Seq Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.tran_seq_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Transaction Date</label>
                            <input type="datetime-local" class="form-control" ng-model="asdc.form.transaction_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Qualifier</label>
                            <input type="text" class="form-control" ng-model="asdc.form.transaction_qualifier">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Qualifier</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_qualifier">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Setl Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.setl_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Amount Paid by Customer</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.amount_paid_by_customer" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.settlement_amount" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Settlement Date</label>
                            <input type="date" class="form-control" ng-model="asdc.form.bank_settlement_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Settlement Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.bank_settlement_amount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Reference</label>
                            <input type="text" class="form-control" ng-model="asdc.form.bank_reference">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Account Name</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_account_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Account Number</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_account_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement IFSC Code</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_ifsc_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Bank Name</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_bank_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Bank Branch</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_bank_branch">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Source</label>
                            <select class="form-select" ng-model="asdc.form.payment_mode">
                                <option value="">Select Payment Source</option>
                                <option value="card">Card</option>
                                <option value="netbanking">Netbanking</option>
                                <option value="upi">UPI</option>
                                <option value="wallet">Wallet</option>
                                <option value="emi">EMI</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="bbps">BBPS</option>
                                <option value="bharat_qr">Bharat QR</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Channel</label>
                            <select class="form-select" ng-model="asdc.form.payment_channel">
                                <option value="">Select Payment Channel</option>
                                <option value="web">Web</option>
                                <option value="mobile">Mobile</option>
                                <option value="pos">POS</option>
                                <option value="api">API</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Percentage</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_percentage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Fixed Fee</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_fixed_fee">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_amount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tax_amount">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Create Settlement Detail</button>
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
            app.controller('AdminSettlementDetailsController', ['$http', function($http) {
                var vm = this;
                vm.details = [];
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                vm.form = {};
                
                vm.visibleColumns = {
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    order_id: { visible: true, label: 'Order Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    tran_seq_id: { visible: true, label: 'Tran Seq Id' },
                    transaction_date: { visible: true, label: 'Transaction Date' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid by Customer' },
                    settlement_amount: { visible: true, label: 'Settlement Amount' }
                };

                vm.loadDetails = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.dateRange
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/admin/settlements/details/data', { params: params }).then(function(response) {
                        vm.details = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading details:', error);
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
                    var modal = new bootstrap.Modal(document.getElementById('createSettlementDetailModal'));
                    modal.show();
                };

                vm.submitSettlementDetail = function() {
                    $http.post('/admin/settlements/details', vm.form).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Settlement detail created successfully!', 'success');
                            } else {
                                alert('Settlement detail created successfully!');
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById('createSettlementDetailModal'));
                            modal.hide();
                            vm.loadDetails();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function(error) {
                        var errorMsg = 'Error creating settlement detail: ' + (error.data?.message || 'Unknown error');
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadDetails();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadDetails();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.dateRange = '';
                    vm.applyFilters();
                };

                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns.hasOwnProperty(key)) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                vm.resetView = function() {
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.clearFilters();
                };

                vm.viewDetail = function(detail) {
                    alert('View detail: ' + detail.id);
                };

                vm.loadDetails();
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


