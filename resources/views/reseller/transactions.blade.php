@extends('layouts.app-sidebar')

@section('title', 'Transactions - Reseller - ' . config('app.name'))
@section('page-title', 'Transactions')

@section('content')
<div ng-app="ipayApp" ng-controller="ResellerTransactionsController as rtc">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Transactions</h2>
            <p class="text-muted mb-0">Payments for merchants under your account.</p>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Merchant</label>
                <select class="form-select form-select-sm" ng-model="rtc.filters.merchant_id" ng-change="rtc.applyFilters()">
                    <option value="">All merchants</option>
                    @foreach($merchantOptions as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" class="form-control form-control-sm" ng-model="rtc.dateFrom">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" class="form-control form-control-sm" ng-model="rtc.dateTo">
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-primary btn-sm w-100" ng-click="rtc.applyFilters()">Apply date range</button>
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-8">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rtc.filters.status === 'all' || rtc.filters.status === '', 'btn-outline-primary': rtc.filters.status !== 'all' && rtc.filters.status !== ''}"
                            ng-click="rtc.setStatus('all')">All</button>
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rtc.filters.status === 'success', 'btn-outline-primary': rtc.filters.status !== 'success'}"
                            ng-click="rtc.setStatus('success')">Successful</button>
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rtc.filters.status === 'failed', 'btn-outline-primary': rtc.filters.status !== 'failed'}"
                            ng-click="rtc.setStatus('failed')">Failed</button>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="rtc.pagination.per_page" ng-change="rtc.load()">
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <button class="btn btn-sm btn-outline-secondary" ng-click="rtc.load()" type="button">
                <i class="bi bi-arrow-clockwise"></i> Reload
            </button>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="rtc.loading" class="loader-overlay position-relative" style="min-height: 280px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading...</p>
            </div>
        </div>
        <div ng-hide="rtc.loading" class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Merchant</th>
                        <th>Transaction ID</th>
                        <th>Order</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="row in rtc.rows track by row.id">
                        <td>@{{ row.transaction_datetime }}</td>
                        <td>@{{ row.merchant_name }}</td>
                        <td>@{{ row.transaction_id }}</td>
                        <td>@{{ row.transaction_order_id }}</td>
                        <td>@{{ row.amount_paid_by_customer }} @{{ row.currency_code }}</td>
                        <td>@{{ row.payment_status }}</td>
                        <td>@{{ row.payment_mode }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="p-3 text-muted text-center" ng-if="rtc.rows.length === 0">No transactions found.</div>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3 border-top" ng-hide="rtc.loading" ng-if="rtc.pagination.total > 0">
            <small class="text-muted">Showing @{{ rtc.pagination.from }}–@{{ rtc.pagination.to }} of @{{ rtc.pagination.total }}</small>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rtc.pagination.current_page <= 1" ng-click="rtc.changePage(rtc.pagination.current_page - 1)">Prev</button>
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rtc.pagination.current_page >= rtc.pagination.last_page" ng-click="rtc.changePage(rtc.pagination.current_page + 1)">Next</button>
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
            app.controller('ResellerTransactionsController', ['$http', function($http) {
                var vm = this;
                vm.rows = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1, from: null, to: null };
                vm.filters = { status: 'all', merchant_id: '' };
                vm.dateFrom = null;
                vm.dateTo = null;

                vm.buildDateRange = function() {
                    if (!vm.dateFrom || !vm.dateTo) return '';
                    var a = typeof vm.dateFrom === 'string' ? vm.dateFrom : (vm.dateFrom && vm.dateFrom.toISOString ? vm.dateFrom.toISOString().slice(0, 10) : '');
                    var b = typeof vm.dateTo === 'string' ? vm.dateTo : (vm.dateTo && vm.dateTo.toISOString ? vm.dateTo.toISOString().slice(0, 10) : '');
                    if (!a || !b) return '';
                    return a + ' 00:00:00 - ' + b + ' 23:59:59';
                };

                vm.load = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                    };
                    if (vm.filters.merchant_id) {
                        params.merchant_id = vm.filters.merchant_id;
                    }
                    var dr = vm.buildDateRange();
                    if (dr) params.date_range = dr;

                    $http.get("{{ route('reseller.transactions.data') }}", { params: params }).then(function(res) {
                        vm.rows = res.data.data || [];
                        vm.pagination = res.data.pagination || vm.pagination;
                        vm.loading = false;
                    }, function(err) {
                        vm.loading = false;
                        alert('Failed to load transactions');
                    });
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.load();
                };

                vm.setStatus = function(s) {
                    vm.filters.status = s;
                    vm.applyFilters();
                };

                vm.changePage = function(p) {
                    p = parseInt(p, 10);
                    if (p >= 1 && p <= vm.pagination.last_page) {
                        vm.pagination.current_page = p;
                        vm.load();
                    }
                };

                vm.load();
            }]);
        } catch (e) {
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
