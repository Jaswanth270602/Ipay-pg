@extends('layouts.app-sidebar')

@section('title', 'Refund Details - Admin - ' . config('app.name'))
@section('page-title', 'Refund Details')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminRefundsController as arc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Refund Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Refund Details</h2>
            <p class="text-muted">List of Refund Details</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range (optional):</label>
                <input type="text" class="form-control" ng-model="arc.dateRange" placeholder="Leave empty to show all dates">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="arc.pagination.per_page" ng-change="arc.loadRefunds()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-success" ng-click="arc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.loadRefunds()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in arc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="arc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="arc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading refunds...</p>
            </div>
        </div>

        <div ng-hide="arc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="arc.visibleColumns.refund_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Id</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="arc.sortBy('refund_id')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_merchant_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_merchant_name" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.payment_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_payment_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.customer_ip.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Customer IP</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_customer_ip" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.transaction_sequence_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Sequence Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_transaction_sequence_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_transaction_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_order_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.payer_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payer Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_payer_name" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.payer_email.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payer Email</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_payer_email" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.payer_phone.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payer Phone</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_payer_phone" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="arc.filters.filter_refund_status" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.refund_description.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Description</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_description" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_amount" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_charges.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Charges</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_charges" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_tax_on_charges.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Tax On Charges</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_tax_on_charges" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.transaction_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_transaction_amount" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_request_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Request Date</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="arc.filters.filter_refund_request_date" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_initiated_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Initiated Date</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="arc.filters.filter_refund_initiated_date" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.refund_reference_no.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Reference No</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_refund_reference_no" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.is_refund_approved.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Is Refund Approved ?</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="arc.filters.filter_is_refund_approved" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.refund_pg_completed.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refund Pg Completed</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="arc.filters.filter_refund_pg_completed" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.latest_api_response.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Latest API Response</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="arc.filters.filter_latest_api_response" ng-change="arc.applyFilters()">
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="arc.refunds.length === 0">
                            <td colspan="23" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="refund in arc.refunds track by $index">
                            <td ng-show="arc.visibleColumns.refund_id.visible">@{{ refund.refund_id }}</td>
                            <td ng-show="arc.visibleColumns.merchant_id.visible">@{{ refund.merchant_id }}</td>
                            <td ng-show="arc.visibleColumns.merchant_name.visible">@{{ refund.merchant_name }}</td>
                            <td ng-show="arc.visibleColumns.payment_id.visible">@{{ refund.payment_id }}</td>
                            <td ng-show="arc.visibleColumns.customer_ip.visible">@{{ refund.customer_ip }}</td>
                            <td ng-show="arc.visibleColumns.transaction_sequence_id.visible">@{{ refund.transaction_sequence_id }}</td>
                            <td ng-show="arc.visibleColumns.transaction_id.visible">@{{ refund.transaction_id }}</td>
                            <td ng-show="arc.visibleColumns.order_id.visible">@{{ refund.order_id }}</td>
                            <td ng-show="arc.visibleColumns.payer_name.visible">@{{ refund.payer_name }}</td>
                            <td ng-show="arc.visibleColumns.payer_email.visible">@{{ refund.payer_email }}</td>
                            <td ng-show="arc.visibleColumns.payer_phone.visible">@{{ refund.payer_phone }}</td>
                            <td ng-show="arc.visibleColumns.refund_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': refund.refund_status === 'completed',
                                    'bg-warning': refund.refund_status === 'pending',
                                    'bg-info': refund.refund_status === 'processing',
                                    'bg-danger': refund.refund_status === 'failed'
                                }">
                                    @{{ refund.refund_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="arc.visibleColumns.refund_description.visible">@{{ refund.refund_description }}</td>
                            <td ng-show="arc.visibleColumns.refund_amount.visible">@{{ refund.refund_amount }}</td>
                            <td ng-show="arc.visibleColumns.refund_charges.visible">@{{ refund.refund_charges }}</td>
                            <td ng-show="arc.visibleColumns.refund_tax_on_charges.visible">@{{ refund.refund_tax_on_charges }}</td>
                            <td ng-show="arc.visibleColumns.transaction_amount.visible">@{{ refund.transaction_amount }}</td>
                            <td ng-show="arc.visibleColumns.refund_request_date.visible">@{{ refund.refund_request_date }}</td>
                            <td ng-show="arc.visibleColumns.refund_initiated_date.visible">@{{ refund.refund_initiated_date }}</td>
                            <td ng-show="arc.visibleColumns.refund_reference_no.visible">@{{ refund.refund_reference_no }}</td>
                            <td ng-show="arc.visibleColumns.is_refund_approved.visible">@{{ refund.is_refund_approved }}</td>
                            <td ng-show="arc.visibleColumns.refund_pg_completed.visible">@{{ refund.refund_pg_completed }}</td>
                            <td ng-show="arc.visibleColumns.latest_api_response.visible">@{{ refund.latest_api_response }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="arc.viewRefund(refund)">
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
                    Showing @{{ (arc.pagination.current_page - 1) * arc.pagination.per_page + 1 }} to @{{ Math.min(arc.pagination.current_page * arc.pagination.per_page, arc.pagination.total) }} of @{{ arc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="arc.changePage(arc.pagination.current_page - 1)" 
                            ng-disabled="arc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="arc.changePage(arc.pagination.current_page + 1)" 
                            ng-disabled="arc.pagination.current_page === arc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Details Modal -->
    <div class="modal fade" id="refundDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" ng-if="arc.selectedRefund">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise"></i> Refund Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Refund ID:</strong><br>
                            <code>@{{arc.selectedRefund.refund_id}}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Transaction ID:</strong><br>
                            <code>@{{arc.selectedRefund.transaction_id}}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Merchant:</strong><br>
                            @{{arc.selectedRefund.merchant_name}}
                        </div>
                        <div class="col-md-6">
                            <strong>Refund Amount:</strong><br>
                            <span class="text-danger">@{{arc.selectedRefund.refund_amount}}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge" ng-class="{'bg-success': arc.selectedRefund.refund_status==='completed', 'bg-danger': arc.selectedRefund.refund_status==='failed'}">
                                @{{arc.selectedRefund.refund_status | uppercase}}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Request Date:</strong><br>
                            @{{arc.selectedRefund.refund_request_date}}
                        </div>
                        <div class="col-12">
                            <strong>Reason:</strong><br>
                            @{{arc.selectedRefund.refund_description}}
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
            app.controller('AdminRefundsController', ['$http', function($http) {
                var vm = this;
                vm.refunds = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = ''; // Remove default date filter to show all data
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    refund_id: { visible: true, label: 'Refund Id' },
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    payment_id: { visible: true, label: 'Payment Id' },
                    customer_ip: { visible: true, label: 'Customer IP' },
                    transaction_sequence_id: { visible: true, label: 'Transaction Sequence Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    order_id: { visible: true, label: 'Order Id' },
                    payer_name: { visible: true, label: 'Payer Name' },
                    payer_email: { visible: true, label: 'Payer Email' },
                    payer_phone: { visible: true, label: 'Payer Phone' },
                    refund_status: { visible: true, label: 'Refund Status' },
                    refund_description: { visible: true, label: 'Refund Description' },
                    refund_amount: { visible: true, label: 'Refund Amount' },
                    refund_charges: { visible: true, label: 'Refund Charges' },
                    refund_tax_on_charges: { visible: true, label: 'Refund Tax On Charges' },
                    transaction_amount: { visible: true, label: 'Transaction Amount' },
                    refund_request_date: { visible: true, label: 'Refund Request Date' },
                    refund_initiated_date: { visible: true, label: 'Refund Initiated Date' },
                    refund_reference_no: { visible: true, label: 'Refund Reference No' },
                    is_refund_approved: { visible: true, label: 'Is Refund Approved ?' },
                    refund_pg_completed: { visible: true, label: 'Refund Pg Completed' },
                    latest_api_response: { visible: true, label: 'Latest API Response' }
                };

                vm.loadRefunds = function() {
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
                    
                    $http.get('/admin/payments/refunds/data', { params: params }).then(function(response) {
                        console.log('Admin Refunds API Response:', response.data);
                        vm.refunds = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                        console.log('Refunds loaded:', vm.refunds.length, 'Total:', vm.pagination.total);
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading refunds:', error);
                        if (typeof showToast === 'function') {
                            showToast('Error loading refunds: ' + (error.data?.message || error.statusText), 'error');
                        } else {
                            alert('Error loading refunds: ' + (error.data?.message || error.statusText));
                        }
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadRefunds();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadRefunds();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.dateRange = '';
                    vm.applyFilters();
                };

                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadRefunds();
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

                vm.selectedRefund = null;
                
                vm.viewRefund = function(refund) {
                    vm.selectedRefund = refund;
                    var modal = new bootstrap.Modal(document.getElementById('refundDetailsModal'));
                    modal.show();
                };

                vm.exportCSV = function() {
                    var params = {
                        date_range: vm.dateRange,
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });

                    var queryString = Object.keys(params).map(function(key) {
                        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                    }).join('&');

                    window.location.href = '/admin/payments/refunds/export?' + queryString;
                };

                vm.loadRefunds();
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



