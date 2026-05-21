@extends('layouts.app-sidebar')

@section('title', 'Merchant Transactions - Reseller - ' . config('app.name'))
@section('page-title', 'Merchant Transaction Drilldown')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="ResellerMerchantTransactionsController as rmtc">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2>Merchant Transactions</h2>
                <p class="text-muted mb-0">
                    Transaction-level details for selected merchant.
                    @if(!empty($merchantName))
                    <span class="ms-1">({{ $merchantName }})</span>
                    @endif
                </p>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reseller.dashboard') }}">Back to Dashboard</a>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control form-control-sm" ng-model="rmtc.filters.merchant_id" min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" class="form-control form-control-sm" ng-model="rmtc.filters.date_from">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" class="form-control form-control-sm" ng-model="rmtc.filters.date_to">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary btn-sm" ng-click="rmtc.applyFilters()">Apply Filters</button>
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-8">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rmtc.filters.status === 'all', 'btn-outline-primary': rmtc.filters.status !== 'all'}"
                            ng-click="rmtc.setStatus('all')">All</button>
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rmtc.filters.status === 'success', 'btn-outline-primary': rmtc.filters.status !== 'success'}"
                            ng-click="rmtc.setStatus('success')">Successful</button>
                    <button type="button" class="btn btn-sm"
                            ng-class="{'btn-primary': rmtc.filters.status === 'failed', 'btn-outline-primary': rmtc.filters.status !== 'failed'}"
                            ng-click="rmtc.setStatus('failed')">Failed</button>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="rmtc.pagination.per_page" ng-change="rmtc.applyFilters()">
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="rmtc.loading" class="loader-overlay position-relative" style="min-height: 220px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading...</p>
            </div>
        </div>
        <div ng-hide="rmtc.loading" class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Commission</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="row in rmtc.rows track by $index">
                    <td>@{{ row.transaction_id }}</td>
                    <td>@{{ row.order_id }}</td>
                    <td>INR @{{ rmtc.money(row.amount) }}</td>
                    <td>@{{ row.status }}</td>
                    <td>INR @{{ rmtc.money(row.commission) }}</td>
                    <td>@{{ row.created_at }}</td>
                </tr>
                </tbody>
            </table>
            <div class="p-3 text-muted text-center" ng-if="rmtc.rows.length === 0">No transactions found.</div>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3 border-top" ng-hide="rmtc.loading" ng-if="rmtc.pagination.total > 0">
            <small class="text-muted">Showing @{{ rmtc.pagination.from }}–@{{ rmtc.pagination.to }} of @{{ rmtc.pagination.total }}</small>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rmtc.pagination.current_page <= 1" ng-click="rmtc.changePage(rmtc.pagination.current_page - 1)">Prev</button>
                <button type="button" class="btn btn-outline-secondary" ng-disabled="rmtc.pagination.current_page >= rmtc.pagination.last_page" ng-click="rmtc.changePage(rmtc.pagination.current_page + 1)">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('ipayApp');
            app.controller('ResellerMerchantTransactionsController', ['$http', function ($http) {
                var vm = this;
                vm.loading = false;
                vm.rows = [];
                vm.selectedMerchantName = @json($merchantName ?? '');
                vm.filters = {
                    merchant_id: {{ (int) ($merchantId ?? 0) }},
                    status: 'all',
                    date_from: null,
                    date_to: null
                };
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1, from: null, to: null };

                vm.money = function (value) {
                    return parseFloat(value || 0).toFixed(2);
                };

                vm.load = function () {
                    if (!vm.filters.merchant_id) {
                        vm.rows = [];
                        return;
                    }
                    vm.loading = true;
                    var params = {
                        merchant_id: vm.filters.merchant_id,
                        status: vm.filters.status,
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };
                    if (vm.filters.date_from) { params.date_from = vm.filters.date_from; }
                    if (vm.filters.date_to) { params.date_to = vm.filters.date_to; }

                    $http.get("{{ route('reseller.merchant-transactions') }}", { params: params }).then(function (res) {
                        vm.rows = res.data.data || [];
                        vm.pagination = res.data.pagination || vm.pagination;
                        vm.loading = false;
                    }, function (err) {
                        vm.loading = false;
                        alert((err.data && err.data.message) ? err.data.message : 'Failed to load merchant transactions');
                    });
                };

                vm.setStatus = function (status) {
                    vm.filters.status = status;
                    vm.applyFilters();
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.load();
                };

                vm.changePage = function (page) {
                    page = parseInt(page, 10);
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
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
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerController);
    } else {
        registerController();
    }
})();
</script>
@endpush
