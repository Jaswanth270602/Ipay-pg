@extends('layouts.app-sidebar')

@section('title', 'Federal Direct VPA Payments - ' . config('app.name'))
@section('page-title', 'Federal Direct VPA Payments')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantFederalVPAController as mfvc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Federal Direct VPA Payments']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Federal Direct VPA Payments</h2>
            <p class="text-muted">List of Federal Direct VPA Statements</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="mfvc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mfvc.pagination.per_page" ng-change="mfvc.loadPayments()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mfvc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mfvc.loadPayments()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mfvc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mfvc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mfvc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="mfvc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading payments...</p>
            </div>
        </div>

        <div ng-hide="mfvc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="mfvc.visibleColumns.reference_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Reference Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mfvc.filters.filter_reference_id" ng-change="mfvc.applyFilters()">
                            </th>
                            <th ng-show="mfvc.visibleColumns.payment_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mfvc.filters.filter_payment_status" ng-change="mfvc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processed">Processed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th ng-show="mfvc.visibleColumns.response_received.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Response Received</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mfvc.filters.filter_response_received" ng-change="mfvc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th ng-show="mfvc.visibleColumns.created_at.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Created At</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mfvc.payments.length === 0">
                            <td colspan="5" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="payment in mfvc.payments track by $index">
                            <td ng-show="mfvc.visibleColumns.reference_id.visible">@{{ payment.reference_id }}</td>
                            <td ng-show="mfvc.visibleColumns.payment_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': payment.payment_status === 'processed',
                                    'bg-warning': payment.payment_status === 'pending',
                                    'bg-danger': payment.payment_status === 'failed'
                                }">
                                    @{{ payment.payment_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="mfvc.visibleColumns.response_received.visible">@{{ payment.response_received }}</td>
                            <td ng-show="mfvc.visibleColumns.created_at.visible">@{{ payment.created_at }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="mfvc.viewPayment(payment)">
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
                    Showing @{{ (mfvc.pagination.current_page - 1) * mfvc.pagination.per_page + 1 }} to @{{ Math.min(mfvc.pagination.current_page * mfvc.pagination.per_page, mfvc.pagination.total) }} of @{{ mfvc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mfvc.changePage(mfvc.pagination.current_page - 1)" 
                            ng-disabled="mfvc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mfvc.changePage(mfvc.pagination.current_page + 1)" 
                            ng-disabled="mfvc.pagination.current_page === mfvc.pagination.last_page">
                        Next
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
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('badlicashApp');
            app.controller('MerchantFederalVPAController', ['$http', function($http) {
                var vm = this;
                vm.payments = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    reference_id: { visible: true, label: 'Reference Id' },
                    payment_status: { visible: true, label: 'Payment Status' },
                    response_received: { visible: true, label: 'Response Received' },
                    created_at: { visible: true, label: 'Created At' }
                };

                vm.loadPayments = function() {
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
                    
                    $http.get('/merchant/payments/federal-vpa/data', { params: params }).then(function(response) {
                        vm.payments = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading payments:', error);
                        alert('Failed to load payments. Please try again.');
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadPayments();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadPayments();
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

                vm.viewPayment = function(payment) {
                    alert('View payment: ' + payment.reference_id);
                };

                vm.loadPayments();
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

