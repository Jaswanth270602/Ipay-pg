@extends('layouts.app-sidebar')

@section('title', 'Earnings - Reseller - ' . config('app.name'))
@section('page-title', 'Earnings')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="ResellerEarningsController as rec">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Earnings</h2>
            <p class="text-muted mb-0">Commission from your merchants’ successful transactions.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total Earnings</h6>
                <h4 class="mb-0">INR @{{ rec.totals.total_earnings | number:2 }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Pending</h6>
                <h4 class="mb-0">INR @{{ rec.totals.pending_earnings | number:2 }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Paid</h6>
                <h4 class="mb-0">INR @{{ rec.totals.paid_earnings | number:2 }}</h4>
            </div>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Merchant</label>
                <select class="form-select form-select-sm" ng-model="rec.filters.merchant_id" ng-change="rec.applyFilters()">
                    <option value="">All merchants</option>
                    @foreach($merchantOptions as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" class="form-control form-control-sm" ng-model="rec.dateFrom">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" class="form-control form-control-sm" ng-model="rec.dateTo">
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-primary btn-sm w-100" ng-click="rec.applyFilters()">Apply</button>
            </div>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="rec.pagination.per_page" ng-change="rec.load()">
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <button class="btn btn-sm btn-outline-secondary" ng-click="rec.load()" type="button">
                <i class="bi bi-arrow-clockwise"></i> Reload
            </button>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="rec.loading" class="loader-overlay position-relative" style="min-height: 280px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading...</p>
            </div>
        </div>
        <div ng-hide="rec.loading" class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Merchant</th>
                        <th>Amount</th>
                        <th>Commission (net)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="row in rec.rows track by row.id">
                        <td>@{{ row.transaction_id }}</td>
                        <td>@{{ row.merchant_name }}</td>
                        <td>@{{ row.transaction_amount }}</td>
                        <td>@{{ row.commission }}</td>
                        <td>@{{ row.status }}</td>
                        <td>@{{ row.created_at }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="p-3 text-muted text-center" ng-if="rec.rows.length === 0">No commission rows yet.</div>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3 border-top" ng-hide="rec.loading" ng-if="rec.pagination.total > 0">
            <small class="text-muted">Showing @{{ rec.pagination.from }}–@{{ rec.pagination.to }} of @{{ rec.pagination.total }}</small>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rec.pagination.current_page <= 1" ng-click="rec.changePage(rec.pagination.current_page - 1)">Prev</button>
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rec.pagination.current_page >= rec.pagination.last_page" ng-click="rec.changePage(rec.pagination.current_page + 1)">Next</button>
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
            app.controller('ResellerEarningsController', ['$http', function($http) {
                var vm = this;
                vm.rows = [];
                vm.loading = false;
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1, from: null, to: null };
                vm.filters = { merchant_id: '' };
                vm.dateFrom = null;
                vm.dateTo = null;
                vm.totals = {
                    total_earnings: {{ $totals['total_earnings'] }},
                    pending_earnings: {{ $totals['pending_earnings'] }},
                    paid_earnings: {{ $totals['paid_earnings'] }}
                };

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
                    };
                    if (vm.filters.merchant_id) params.merchant_id = vm.filters.merchant_id;
                    var dr = vm.buildDateRange();
                    if (dr) params.date_range = dr;

                    $http.get("{{ route('reseller.earnings.data') }}", { params: params }).then(function(res) {
                        vm.rows = res.data.data || [];
                        vm.pagination = res.data.pagination || vm.pagination;
                        if (res.data.totals) {
                            vm.totals = res.data.totals;
                        }
                        vm.loading = false;
                    }, function() {
                        vm.loading = false;
                        alert('Failed to load earnings');
                    });
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.load();
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
