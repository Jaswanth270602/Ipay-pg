@extends('layouts.app-sidebar')

@section('title', 'Split Transactions - ' . config('app.name'))
@section('page-title', 'Split Transactions')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantSplitTransactionsController as mstc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Split Transactions']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Split Transactions</h2>
            <p class="text-muted">List of Split Transactions</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="mstc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mstc.pagination.per_page" ng-change="mstc.loadTransactions()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mstc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mstc.loadTransactions()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mstc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mstc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mstc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="mstc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading split transactions...</p>
            </div>
        </div>

        <div ng-hide="mstc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="mstc.visibleColumns.transaction_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Date</span>
                                </div>
                            </th>
                            <th ng-show="mstc.visibleColumns.msac_code.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>MSAC Code</span>
                                </div>
                            </th>
                            <th ng-show="mstc.visibleColumns.tran_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Tran Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.filters.filter_transaction_id" ng-change="mstc.applyFilters()">
                            </th>
                            <th ng-show="mstc.visibleColumns.order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.filters.filter_order_id" ng-change="mstc.applyFilters()">
                            </th>
                            <th ng-show="mstc.visibleColumns.amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Amount Paid by Customer</span>
                                </div>
                            </th>
                            <th ng-show="mstc.visibleColumns.account.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mstc.transactions.length === 0">
                            <td colspan="7" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="transaction in mstc.transactions track by $index">
                            <td ng-show="mstc.visibleColumns.transaction_date.visible">@{{ transaction.transaction_date }}</td>
                            <td ng-show="mstc.visibleColumns.msac_code.visible">@{{ transaction.msac_code }}</td>
                            <td ng-show="mstc.visibleColumns.tran_id.visible">@{{ transaction.tran_id }}</td>
                            <td ng-show="mstc.visibleColumns.order_id.visible">@{{ transaction.order_id }}</td>
                            <td ng-show="mstc.visibleColumns.amount_paid_by_customer.visible">@{{ transaction.amount_paid_by_customer }}</td>
                            <td ng-show="mstc.visibleColumns.account.visible">@{{ transaction.account }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="mstc.viewSplitDetails(transaction)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (mstc.pagination.current_page - 1) * mstc.pagination.per_page + 1 }} to @{{ Math.min(mstc.pagination.current_page * mstc.pagination.per_page, mstc.pagination.total) }} of @{{ mstc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mstc.changePage(mstc.pagination.current_page - 1)" 
                            ng-disabled="mstc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mstc.changePage(mstc.pagination.current_page + 1)" 
                            ng-disabled="mstc.pagination.current_page === mstc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Split Details Modal -->
    <div class="modal fade" id="splitDetailsModal" tabindex="-1" aria-labelledby="splitDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="splitDetailsModalLabel">
                        <i class="bi bi-diagram-3"></i> Split Transaction Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div ng-show="mstc.loadingSplitDetails" class="text-center py-5">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted">Loading split details...</p>
                    </div>
                    
                    <div ng-hide="mstc.loadingSplitDetails">
                        <div ng-if="mstc.selectedTransaction" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <strong>Transaction ID:</strong><br>
                                    <code>@{{mstc.selectedTransaction.transaction_id || mstc.selectedTransaction.tran_id}}</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order ID:</strong><br>
                                    @{{mstc.selectedTransaction.order_id}}
                                </div>
                                <div class="col-md-6">
                                    <strong>Amount:</strong><br>
                                    <span class="text-success fw-bold">@{{mstc.selectedTransaction.amount_paid_by_customer}}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Transaction Date:</strong><br>
                                    @{{mstc.selectedTransaction.transaction_date}}
                                </div>
                            </div>
                            <hr>
                        </div>

                        <h6 class="mb-3">Split Details</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <i class="bi bi-diamond"></i> Order Id
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.splitFilters.filter_order_id" ng-change="mstc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Amount Paid By Customer
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.splitFilters.filter_amount" ng-change="mstc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Account Holder Name
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.splitFilters.filter_account_holder_name" ng-change="mstc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Account Number
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mstc.splitFilters.filter_account_number" ng-change="mstc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Split Type
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <select class="form-select form-select-sm mt-1" ng-model="mstc.splitFilters.filter_split_type" ng-change="mstc.applySplitFilters()">
                                                <option value="all">All</option>
                                                <option value="Primary">Primary</option>
                                                <option value="Secondary">Secondary</option>
                                                <option value="Split">Split</option>
                                            </select>
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Split Amount
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Split Percentage (%)
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr ng-if="!mstc.filteredSplitDetails || mstc.filteredSplitDetails.length === 0">
                                        <td colspan="7" class="text-center text-danger py-4">No split details found</td>
                                    </tr>
                                    <tr ng-repeat="split in mstc.filteredSplitDetails track by $index">
                                        <td>@{{ split.order_id }}</td>
                                        <td>@{{ split.amount_paid_by_customer }}</td>
                                        <td>@{{ split.account_holder_name }}</td>
                                        <td>@{{ split.account_number }}</td>
                                        <td>
                                            <span class="badge" ng-class="{
                                                'bg-primary': split.split_type === 'Primary',
                                                'bg-success': split.split_type === 'Secondary',
                                                'bg-info': split.split_type === 'Split'
                                            }">
                                                @{{ split.split_type }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">@{{ split.split_amount }}</td>
                                        <td>@{{ split.split_percentage }}</td>
                                    </tr>
                                </tbody>
                            </table>
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
            var app = angular.module('badlicashApp');
            app.controller('MerchantSplitTransactionsController', ['$http', function($http) {
                var vm = this;
                vm.transactions = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    transaction_date: { visible: true, label: 'Transaction Date' },
                    msac_code: { visible: true, label: 'MSAC Code' },
                    tran_id: { visible: true, label: 'Tran Id' },
                    order_id: { visible: true, label: 'Order Id' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid by Customer' },
                    account: { visible: true, label: 'Account' }
                };

                vm.loadTransactions = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.dateRange,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/merchant/payments/split-transactions/data', { params: params }).then(function(response) {
                        vm.transactions = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading split transactions:', error);
                        alert('Failed to load split transactions. Please try again.');
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadTransactions();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadTransactions();
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

                vm.selectedTransaction = null;
                vm.splitDetails = [];
                vm.filteredSplitDetails = [];
                vm.loadingSplitDetails = false;
                vm.splitFilters = {
                    filter_order_id: '',
                    filter_amount: '',
                    filter_account_holder_name: '',
                    filter_account_number: '',
                    filter_split_type: 'all'
                };
                
                vm.applySplitFilters = function() {
                    vm.filteredSplitDetails = vm.splitDetails.filter(function(split) {
                        if (vm.splitFilters.filter_order_id && !split.order_id.toString().toLowerCase().includes(vm.splitFilters.filter_order_id.toLowerCase())) {
                            return false;
                        }
                        if (vm.splitFilters.filter_amount && !split.amount_paid_by_customer.toString().toLowerCase().includes(vm.splitFilters.filter_amount.toLowerCase())) {
                            return false;
                        }
                        if (vm.splitFilters.filter_account_holder_name && !split.account_holder_name.toString().toLowerCase().includes(vm.splitFilters.filter_account_holder_name.toLowerCase())) {
                            return false;
                        }
                        if (vm.splitFilters.filter_account_number && !split.account_number.toString().toLowerCase().includes(vm.splitFilters.filter_account_number.toLowerCase())) {
                            return false;
                        }
                        if (vm.splitFilters.filter_split_type && vm.splitFilters.filter_split_type !== 'all' && split.split_type !== vm.splitFilters.filter_split_type) {
                            return false;
                        }
                        return true;
                    });
                };

                vm.clearSplitFilters = function() {
                    vm.splitFilters = {
                        filter_order_id: '',
                        filter_amount: '',
                        filter_account_holder_name: '',
                        filter_account_number: '',
                        filter_split_type: 'all'
                    };
                    vm.applySplitFilters();
                };
                
                vm.viewSplitDetails = function(transaction) {
                    vm.selectedTransaction = transaction;
                    vm.loadingSplitDetails = true;
                    vm.splitDetails = [];
                    
                    var transactionId = transaction.id || transaction.transaction_id;
                    
                    $http.get('/merchant/payments/split-transactions/' + transactionId + '/details').then(function(response) {
                        if (response.data.success) {
                            vm.splitDetails = response.data.data || [];
                            vm.splitFilters = {
                                filter_order_id: '',
                                filter_amount: '',
                                filter_account_holder_name: '',
                                filter_account_number: '',
                                filter_split_type: 'all'
                            };
                            vm.applySplitFilters();
                            var modal = new bootstrap.Modal(document.getElementById('splitDetailsModal'));
                            modal.show();
                        } else {
                            alert('Failed to load split details: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.loadingSplitDetails = false;
                    }, function(error) {
                        vm.loadingSplitDetails = false;
                        console.error('Error loading split details:', error);
                        alert('Failed to load split details. Please try again.');
                    });
                };

                vm.viewTransaction = vm.viewSplitDetails; // Alias for backward compatibility

                vm.loadTransactions();
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

