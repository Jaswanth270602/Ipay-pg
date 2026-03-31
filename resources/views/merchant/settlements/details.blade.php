@extends('layouts.app-sidebar')

@section('title', 'Settlement Details - Merchant - ' . config('app.name'))
@section('page-title', 'Settlement Details')

@section('content')
<div ng-app="ipayApp" ng-controller="MerchantSettlementDetailsController as msdc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Settlement Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Settlement Details</h2>
            <p class="text-muted">List of PG Settlement Details</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="msdc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" ng-click="msdc.setStatusFilter('all')" ng-class="{'active': msdc.statusFilter === 'all'}">All</button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary" ng-click="msdc.setStatusFilter('settled')" ng-class="{'active': msdc.statusFilter === 'settled'}">Settled</button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary" ng-click="msdc.setStatusFilter('not_settled')" ng-class="{'active': msdc.statusFilter === 'not_settled'}">Not Settled</button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="msdc.pagination.per_page" ng-change="msdc.loadDetails()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="msdc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="msdc.loadDetails()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in msdc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="msdc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="msdc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="msdc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlement details...</p>
            </div>
        </div>

        <div ng-hide="msdc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="msdc.visibleColumns.order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="msdc.filters.filter_order_id" ng-change="msdc.applyFilters()">
                            </th>
                            <th ng-show="msdc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="msdc.filters.filter_transaction_id" ng-change="msdc.applyFilters()">
                            </th>
                            <th ng-show="msdc.visibleColumns.tran_seq_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Tran Seq Id</span>
                                </div>
                            </th>
                            <th ng-show="msdc.visibleColumns.transaction_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Date</span>
                                </div>
                            </th>
                            <th ng-show="msdc.visibleColumns.amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Amount Paid by Customer</span>
                                </div>
                            </th>
                            <th ng-show="msdc.visibleColumns.settlement_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Amount</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="msdc.details.length === 0">
                            <td colspan="7" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="detail in msdc.details track by $index">
                            <td ng-show="msdc.visibleColumns.order_id.visible">@{{ detail.order_id }}</td>
                            <td ng-show="msdc.visibleColumns.transaction_id.visible">@{{ detail.transaction_id }}</td>
                            <td ng-show="msdc.visibleColumns.tran_seq_id.visible">@{{ detail.tran_seq_id }}</td>
                            <td ng-show="msdc.visibleColumns.transaction_date.visible">@{{ detail.transaction_date }}</td>
                            <td ng-show="msdc.visibleColumns.amount_paid_by_customer.visible">@{{ detail.amount_paid_by_customer }}</td>
                            <td ng-show="msdc.visibleColumns.settlement_amount.visible">@{{ detail.settlement_amount }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="msdc.viewDetail(detail)">
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
                    Showing @{{ (msdc.pagination.current_page - 1) * msdc.pagination.per_page + 1 }} to @{{ Math.min(msdc.pagination.current_page * msdc.pagination.per_page, msdc.pagination.total) }} of @{{ msdc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="msdc.changePage(msdc.pagination.current_page - 1)" 
                            ng-disabled="msdc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="msdc.changePage(msdc.pagination.current_page + 1)" 
                            ng-disabled="msdc.pagination.current_page === msdc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- View Settlement Detail Modal -->
<div class="modal fade" id="viewSettlementDetailModal" tabindex="-1" aria-labelledby="viewSettlementDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSettlementDetailModalLabel">Settlement Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3" ng-if="msdc.selectedDetail">
                    <div class="col-md-6" ng-repeat="field in msdc.detailFields track by $index">
                        <strong ng-bind="field.label + ':'"></strong>
                        <span ng-bind="msdc.selectedDetail[field.key] || '-'"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            app.controller('MerchantSettlementDetailsController', ['$http', function($http) {
                var vm = this;
                vm.details = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                vm.statusFilter = 'all';
                vm.selectedDetail = null;
                
                vm.visibleColumns = {
                    order_id: { visible: true, label: 'Order Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    tran_seq_id: { visible: true, label: 'Tran Seq Id' },
                    transaction_date: { visible: true, label: 'Transaction Date' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid by Customer' },
                    settlement_amount: { visible: true, label: 'Settlement Amount' }
                };

                vm.detailFields = [
                    { key: 'order_id', label: 'Order Id' },
                    { key: 'transaction_id', label: 'Transaction Id' },
                    { key: 'tran_seq_id', label: 'Tran Seq Id' },
                    { key: 'transaction_date', label: 'Transaction Date' },
                    { key: 'transaction_qualifier', label: 'Transaction Qualifier' },
                    { key: 'settlement_qualifier', label: 'Settlement Qualifier' },
                    { key: 'setl_id', label: 'Setl Id' },
                    { key: 'amount_paid_by_customer', label: 'Amount Paid by Customer' },
                    { key: 'settlement_amount', label: 'Settlement Amount' },
                    { key: 'bank_settlement_date', label: 'Bank Settlement Date' },
                    { key: 'bank_settlement_amount', label: 'Bank Settlement Amount' },
                    { key: 'bank_reference', label: 'Bank Reference' },
                    { key: 'settlement_account_name', label: 'Settlement Account Name' },
                    { key: 'settlement_account_number', label: 'Settlement Account Number' },
                    { key: 'settlement_ifsc_code', label: 'Settlement IFSC Code' },
                    { key: 'settlement_bank_name', label: 'Settlement Bank Name' },
                    { key: 'settlement_bank_branch', label: 'Settlement Bank Branch' },
                    { key: 'payment_mode', label: 'Payment Mode' },
                    { key: 'payment_channel', label: 'Payment Channel' },
                    { key: 'tdr_percentage', label: 'TDR Percentage' },
                    { key: 'tdr_fixed_fee', label: 'TDR Fixed Fee' },
                    { key: 'tdr_amount', label: 'TDR Amount' },
                    { key: 'earliest_priority_settlement_date', label: 'Earliest Priority Settlement Date' },
                    { key: 'latest_priority_settlement_date', label: 'Latest Priority Settlement Date' },
                    { key: 'tax_amount', label: 'Tax Amount' },
                    { key: 'setd_id', label: 'Setd Id' },
                    { key: 'provider', label: 'Provider' },
                    { key: 'account_id', label: 'Account ID' },
                    { key: 'acq_payment_id', label: 'Acq Payment Id' },
                    { key: 'udf1', label: 'UDF1' },
                    { key: 'udf2', label: 'UDF2' },
                    { key: 'udf3', label: 'UDF3' },
                    { key: 'udf4', label: 'UDF4' },
                    { key: 'udf5', label: 'UDF5' }
                ];

                vm.loadDetails = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.dateRange
                    };

                    if (vm.statusFilter !== 'all') {
                        params.filter_settlement_status = vm.statusFilter;
                    }

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/merchant/settlements/details/data', { params: params }).then(function(response) {
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

                vm.setStatusFilter = function(status) {
                    vm.statusFilter = status;
                    vm.pagination.current_page = 1;
                    vm.loadDetails();
                };

                vm.viewDetail = function(detail) {
                    vm.selectedDetail = detail;
                    var modal = new bootstrap.Modal(document.getElementById('viewSettlementDetailModal'));
                    modal.show();
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
                    vm.statusFilter = 'all';
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

