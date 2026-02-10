@extends('layouts.app-sidebar')

@section('title', 'Federal Direct VPA Payments - Admin - ' . config('app.name'))
@section('page-title', 'Federal Direct VPA Payments')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminFederalVPAController as afvc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
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
                <input type="text" class="form-control" ng-model="afvc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="afvc.pagination.per_page" ng-change="afvc.loadPayments()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="afvc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="afvc.loadPayments()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in afvc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="afvc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="afvc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="afvc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading payments...</p>
            </div>
        </div>

        <div ng-hide="afvc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="afvc.visibleColumns.reference_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Reference Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="afvc.filters.filter_reference_id" ng-change="afvc.applyFilters()">
                            </th>
                            <th ng-show="afvc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="afvc.filters.filter_merchant_id" ng-change="afvc.applyFilters()">
                            </th>
                            <th ng-show="afvc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="afvc.filters.filter_merchant_name" ng-change="afvc.applyFilters()">
                            </th>
                            <th ng-show="afvc.visibleColumns.payment_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="afvc.filters.filter_payment_status" ng-change="afvc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processed">Processed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th ng-show="afvc.visibleColumns.response_received.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Response Received</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="afvc.filters.filter_response_received" ng-change="afvc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="afvc.payments.length === 0">
                            <td colspan="6" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="payment in afvc.payments track by $index">
                            <td ng-show="afvc.visibleColumns.reference_id.visible">@{{ payment.reference_id }}</td>
                            <td ng-show="afvc.visibleColumns.merchant_id.visible">@{{ payment.merchant_id }}</td>
                            <td ng-show="afvc.visibleColumns.merchant_name.visible">@{{ payment.merchant_name }}</td>
                            <td ng-show="afvc.visibleColumns.payment_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': payment.payment_status === 'processed',
                                    'bg-warning': payment.payment_status === 'pending',
                                    'bg-danger': payment.payment_status === 'failed'
                                }">
                                    @{{ payment.payment_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="afvc.visibleColumns.response_received.visible">@{{ payment.response_received }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="afvc.viewPayment(payment)">
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
                    Showing @{{ (afvc.pagination.current_page - 1) * afvc.pagination.per_page + 1 }} to @{{ Math.min(afvc.pagination.current_page * afvc.pagination.per_page, afvc.pagination.total) }} of @{{ afvc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="afvc.changePage(afvc.pagination.current_page - 1)" 
                            ng-disabled="afvc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="afvc.changePage(afvc.pagination.current_page + 1)" 
                            ng-disabled="afvc.pagination.current_page === afvc.pagination.last_page">
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
            app.controller('AdminFederalVPAController', ['$http', function($http) {
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
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    payment_status: { visible: true, label: 'Payment Status' },
                    response_received: { visible: true, label: 'Response Received' }
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
                    
                    $http.get('/admin/payments/federal-vpa/data', { params: params }).then(function(response) {
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


