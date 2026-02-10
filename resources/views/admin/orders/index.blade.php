@extends('layouts.app-sidebar')

@section('title', 'All Orders - Admin - ' . config('app.name'))
@section('page-title', 'All Orders')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminOrdersController as aoc">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard','url'=>route('admin.dashboard')],
        ['label'=>'All Orders']
    ]" />

    <div class="stat-card mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="aoc.filters.status" ng-change="aoc.applyFilters()">
                    <option value="all">All</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                    <option value="created">Created</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control" placeholder="Merchant ID" ng-model="aoc.filters.merchant_id" ng-change="aoc.applyFilters()">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by order ID, customer name, email" ng-model="aoc.filters.search" ng-change="aoc.applyFilters()">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" ng-click="aoc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="aoc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading orders...</p>
            </div>
        </div>

        <div ng-hide="aoc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Order ID</th>
                            <th>Merchant</th>
                            <th>Source</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Transactions</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="aoc.orders.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 48px;"></i>
                                <p class="mt-2">No orders found</p>
                            </td>
                        </tr>
                        <tr ng-repeat="o in aoc.orders track by $index">
                            <td>@{{ (aoc.pagination.current_page - 1) * aoc.pagination.per_page + $index + 1 }}</td>
                            <td>
                                <code class="text-primary" style="font-size: 12px;">@{{ o.order_id }}</code>
                            </td>
                            <td>
                                <div ng-if="o.merchant">
                                    <strong style="font-size: 13px;">@{{ o.merchant.name }}</strong>
                                    <br><small class="text-muted">ID: @{{ o.merchant.id }}</small>
                                </div>
                                <span ng-if="!o.merchant" class="text-muted">N/A</span>
                            </td>
                            <td>
                                <span ng-if="o.payment_link_id" class="badge bg-primary">
                                    <i class="bi bi-link-45deg"></i> Payment Link
                                </span>
                                <span ng-if="!o.payment_link_id" class="badge bg-secondary">
                                    <i class="bi bi-cart"></i> Direct Order
                                </span>
                            </td>
                            <td>
                                <div ng-if="o.customer_details">
                                    <div class="fw-semibold" style="font-size: 13px;">@{{ o.customer_details.name || 'N/A' }}</div>
                                    <small class="text-muted">@{{ o.customer_details.email }}</small>
                                    <br><small class="text-muted">@{{ o.customer_details.phone }}</small>
                                </div>
                                <span ng-if="!o.customer_details" class="text-muted">N/A</span>
                            </td>
                            <td>
                                <strong class="text-success">@{{ o.currency || 'INR' }} @{{ o.amount | number:2 }}</strong>
                            </td>
                            <td>
                                <span ng-if="o.transactions && o.transactions.length > 0" class="badge" style="background: #6366f1;">
                                    @{{ o.transactions[0].payment_method | uppercase }}
                                </span>
                                <div ng-if="o.transactions && o.transactions[0] && o.transactions[0].payment_details && o.transactions[0].payment_details.last4" style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    **** @{{ o.transactions[0].payment_details.last4 }}
                                </div>
                                <span ng-if="!o.transactions || o.transactions.length === 0" class="text-muted">-</span>
                            </td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-success': o.status==='completed',
                                    'bg-danger': o.status==='failed',
                                    'bg-warning text-dark': o.status==='pending',
                                    'bg-info': o.status==='processing',
                                    'bg-secondary': o.status==='created'
                                }">
                                    @{{ o.status | uppercase }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info" ng-if="o.transactions && o.transactions.length > 0">
                                    @{{ o.transactions.length }} TXN(s)
                                </span>
                                <span class="text-muted" ng-if="!o.transactions || o.transactions.length === 0">No TXN</span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13px;">@{{ o.created_at | date:'MMM d, y' }}</div>
                                <small class="text-muted">@{{ o.created_at | date:'HH:mm:ss' }}</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div ng-if="aoc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing @{{ aoc.pagination.from }} to @{{ aoc.pagination.to }} of @{{ aoc.pagination.total }} orders
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item" ng-class="{'disabled': aoc.pagination.current_page === 1}">
                            <a class="page-link" href="#" ng-click="aoc.loadPage(aoc.pagination.current_page - 1)">Previous</a>
                        </li>
                        <li class="page-item" ng-repeat="page in aoc.getPages()" ng-class="{'active': page === aoc.pagination.current_page}">
                            <a class="page-link" href="#" ng-click="aoc.loadPage(page)">@{{ page }}</a>
                        </li>
                        <li class="page-item" ng-class="{'disabled': aoc.pagination.current_page === aoc.pagination.last_page}">
                            <a class="page-link" href="#" ng-click="aoc.loadPage(aoc.pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
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
            app.controller('AdminOrdersController', ['$http', '$timeout', function($http, $timeout) {
                var vm = this;
                vm.orders = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1, from: 0, to: 0 };
                vm.filters = { status: 'all', merchant_id: '', search: '' };

                vm.load = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        merchant_id: vm.filters.merchant_id,
                        search: vm.filters.search
                    };

                    $http.get('/admin/orders/data', { params: params }).then(function(response) {
                        if (response.data.success) {
                            vm.orders = response.data.data;
                            vm.pagination = response.data.pagination;
                        }
                        vm.loading = false;
                    }, function() {
                        vm.loading = false;
                        alert('Failed to load orders');
                    });
                };

                var filterTimeout;
                vm.applyFilters = function() {
                    if (filterTimeout) $timeout.cancel(filterTimeout);
                    filterTimeout = $timeout(function() {
                        vm.pagination.current_page = 1;
                        vm.load();
                    }, 300);
                };

                vm.clearFilters = function() {
                    vm.filters = { status: 'all', merchant_id: '', search: '' };
                    vm.pagination.current_page = 1;
                    vm.load();
                };

                vm.loadPage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.load();
                    }
                };

                vm.getPages = function() {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                vm.load();
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


