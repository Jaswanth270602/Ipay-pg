@extends('layouts.app-sidebar')

@section('title', 'Split Transactions - Admin - ' . config('app.name'))
@section('page-title', 'Split Transactions')

@section('content')
<div ng-app="ipayApp" ng-controller="AdminSplitTransactionsController as astc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Split Transaction Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Transactions Split</h2>
            <p class="text-muted">List of Transactions Split</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="astc.dateRange" placeholder="22/12/2025 12:11:52 - 06/01/2026 12:11:52">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="astc.pagination.per_page" ng-change="astc.loadTransactions()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="astc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="astc.loadTransactions()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in astc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="astc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="astc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" 
                        ng-click="astc.changePage(astc.pagination.current_page - 1)" 
                        ng-disabled="astc.pagination.current_page === 1">
                    Previous
                </button>
                <button class="btn btn-sm btn-outline-secondary" 
                        ng-click="astc.changePage(astc.pagination.current_page + 1)" 
                        ng-disabled="astc.pagination.current_page === astc.pagination.last_page">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="astc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading split transactions...</p>
            </div>
        </div>

        <div ng-hide="astc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="astc.visibleColumns.transaction_date.visible">
                                <i class="bi bi-diamond"></i> Transaction Date
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_transaction_date" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.merchant_id.visible">
                                <i class="bi bi-diamond"></i> Merchant Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_merchant_id" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.merchant_name.visible">
                                <i class="bi bi-diamond"></i> Merchant Name
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_merchant_name" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.msac_code.visible">
                                <i class="bi bi-diamond"></i> MSAC Code
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_msac_code" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.tran_id.visible">
                                <i class="bi bi-diamond"></i> Tran Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_tran_id" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.transaction_id.visible">
                                <i class="bi bi-diamond"></i> Transaction Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_transaction_id" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.order_id.visible">
                                <i class="bi bi-diamond"></i> Order Id
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_order_id" ng-change="astc.applyFilters()">
                            </th>
                            <th ng-show="astc.visibleColumns.amount_paid_by_customer.visible">
                                <i class="bi bi-diamond"></i> Amount Paid By Customer
                                <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.filters.filter_amount" ng-change="astc.applyFilters()">
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="astc.transactions.length === 0 && !astc.loading">
                            <td colspan="9" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="transaction in astc.transactions track by $index">
                            <td ng-show="astc.visibleColumns.transaction_date.visible">@{{ transaction.transaction_date }}</td>
                            <td ng-show="astc.visibleColumns.merchant_id.visible">@{{ transaction.merchant_id }}</td>
                            <td ng-show="astc.visibleColumns.merchant_name.visible">@{{ transaction.merchant_name }}</td>
                            <td ng-show="astc.visibleColumns.msac_code.visible">@{{ transaction.msac_code }}</td>
                            <td ng-show="astc.visibleColumns.tran_id.visible">@{{ transaction.tran_id }}</td>
                            <td ng-show="astc.visibleColumns.transaction_id.visible">@{{ transaction.transaction_id }}</td>
                            <td ng-show="astc.visibleColumns.order_id.visible">@{{ transaction.order_id }}</td>
                            <td ng-show="astc.visibleColumns.amount_paid_by_customer.visible">@{{ transaction.amount_paid_by_customer }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="astc.viewSplitDetails(transaction)">
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
                    Showing @{{ (astc.pagination.current_page - 1) * astc.pagination.per_page + 1 }} to @{{ Math.min(astc.pagination.current_page * astc.pagination.per_page, astc.pagination.total) }} of @{{ astc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="astc.changePage(astc.pagination.current_page - 1)" 
                            ng-disabled="astc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="astc.changePage(astc.pagination.current_page + 1)" 
                            ng-disabled="astc.pagination.current_page === astc.pagination.last_page">
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
                    <div ng-show="astc.loadingSplitDetails" class="text-center py-5">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted">Loading split details...</p>
                    </div>
                    
                    <div ng-hide="astc.loadingSplitDetails">
                        <div ng-if="astc.selectedTransaction" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <strong>Transaction ID:</strong><br>
                                    <code>@{{astc.selectedTransaction.transaction_id || astc.selectedTransaction.tran_id}}</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order ID:</strong><br>
                                    @{{astc.selectedTransaction.order_id}}
                                </div>
                                <div class="col-md-6">
                                    <strong>Amount:</strong><br>
                                    <span class="text-success fw-bold">@{{astc.selectedTransaction.amount_paid_by_customer}}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Merchant:</strong><br>
                                    @{{astc.selectedTransaction.merchant_name}}
                                </div>
                            </div>
                            <hr>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Split Details</h6>
                            <button class="btn btn-sm btn-outline-secondary" ng-click="astc.clearSplitFilters()">
                                <i class="bi bi-funnel"></i> Clear Filters
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <i class="bi bi-diamond"></i> Order Id
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.splitFilters.filter_order_id" ng-change="astc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Amount Paid By Customer
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.splitFilters.filter_amount" ng-change="astc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Account Holder Name
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.splitFilters.filter_account_holder_name" ng-change="astc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Account Number
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="astc.splitFilters.filter_account_number" ng-change="astc.applySplitFilters()">
                                        </th>
                                        <th>
                                            <i class="bi bi-diamond"></i> Split Type
                                            <i class="bi bi-arrow-down-up ms-1" style="font-size: 10px;"></i>
                                            <select class="form-select form-select-sm mt-1" ng-model="astc.splitFilters.filter_split_type" ng-change="astc.applySplitFilters()">
                                                <option value="all">All</option>
                                                <option value="Primary">Primary</option>
                                                <option value="Secondary">Secondary</option>
                                                <option value="Vendor">Vendor</option>
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
                                    <tr ng-if="!astc.filteredSplitDetails || astc.filteredSplitDetails.length === 0">
                                        <td colspan="7" class="text-center text-danger py-4">No split details found</td>
                                    </tr>
                                    <tr ng-repeat="split in astc.filteredSplitDetails track by $index">
                                        <td>@{{ split.order_id }}</td>
                                        <td>@{{ split.amount_paid_by_customer }}</td>
                                        <td>@{{ split.account_holder_name }}</td>
                                        <td>@{{ split.account_number }}</td>
                                        <td>
                                            <span class="badge" ng-class="{
                                                'bg-primary': split.split_type === 'Primary',
                                                'bg-success': split.split_type === 'Secondary',
                                                'bg-secondary': split.split_type === 'Vendor',
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
            var app = angular.module('ipayApp');
            app.controller('AdminSplitTransactionsController', ['$http', function($http) {
                var vm = this;
                vm.transactions = [];
                vm.splitDetails = [];
                vm.filteredSplitDetails = [];
                vm.selectedTransaction = null;
                vm.loadingSplitDetails = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.splitFilters = {
                    filter_order_id: '',
                    filter_amount: '',
                    filter_account_holder_name: '',
                    filter_account_number: '',
                    filter_split_type: 'all'
                };
                vm.loading = false;
                vm.dateRange = '';
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    transaction_date: { visible: true, label: 'Transaction Date' },
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    msac_code: { visible: true, label: 'MSAC Code' },
                    tran_id: { visible: true, label: 'Tran Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    order_id: { visible: true, label: 'Order Id' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid By Customer' }
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
                    
                    $http.get('/admin/payments/split-transactions/data', { params: params }).then(function(response) {
                        if (response.data.success) {
                            vm.transactions = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                last_page: response.data.pagination.last_page,
                                total: response.data.pagination.total,
                                per_page: response.data.pagination.per_page
                            };
                        } else {
                            console.error('Error:', response.data.message);
                            alert('Failed to load transactions: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading split transactions:', error);
                        alert('Failed to load split transactions. Please try again.');
                    });
                };

                vm.applySplitFilters = function() {
                    vm.filteredSplitDetails = vm.splitDetails.filter(function(split) {
                        // Order ID filter
                        if (vm.splitFilters.filter_order_id && !split.order_id.toString().toLowerCase().includes(vm.splitFilters.filter_order_id.toLowerCase())) {
                            return false;
                        }
                        
                        // Amount filter
                        if (vm.splitFilters.filter_amount && !split.amount_paid_by_customer.toString().toLowerCase().includes(vm.splitFilters.filter_amount.toLowerCase())) {
                            return false;
                        }
                        
                        // Account Holder Name filter
                        if (vm.splitFilters.filter_account_holder_name && !split.account_holder_name.toString().toLowerCase().includes(vm.splitFilters.filter_account_holder_name.toLowerCase())) {
                            return false;
                        }
                        
                        // Account Number filter
                        if (vm.splitFilters.filter_account_number && !split.account_number.toString().toLowerCase().includes(vm.splitFilters.filter_account_number.toLowerCase())) {
                            return false;
                        }
                        
                        // Split Type filter
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
                    vm.filteredSplitDetails = [];
                    vm.splitFilters = {
                        filter_order_id: '',
                        filter_amount: '',
                        filter_account_holder_name: '',
                        filter_account_number: '',
                        filter_split_type: 'all'
                    };
                    
                    // Get transaction ID - try both id and transaction_id fields
                    var transactionId = transaction.id || transaction.transaction_id;
                    
                    $http.get('/admin/payments/split-transactions/' + transactionId + '/details').then(function(response) {
                        if (response.data.success) {
                            vm.splitDetails = response.data.data || [];
                            vm.applySplitFilters(); // Apply initial filter to show all
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

                // Debug: Log when controller loads
                console.log('AdminSplitTransactionsController initialized');
                
                vm.loadTransactions();
            }]);
        } catch(e) {
            console.error('Error registering AdminSplitTransactionsController:', e);
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
