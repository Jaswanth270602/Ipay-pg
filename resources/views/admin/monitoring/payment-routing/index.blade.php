@extends('layouts.app-sidebar')

@section('title', 'Payment routing monitor - Admin - ' . config('app.name'))
@section('page-title', 'Acquirer monitoring')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="PaymentRoutingMonitorsController as prm">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Acquirer Details'],
        ['label'=>'Routing attempts']
    ]" />

    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Payment routing attempts</h2>
            <p class="text-muted small mb-0">Orchestration API (live): one row per attempt with acquirer health trace. No card data.</p>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" ng-model="prm.filters.status" ng-change="prm.load()">
                    <option value="all">All</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Txn ID</label>
                <input type="text" class="form-control form-control-sm" ng-model="prm.filters.txn_id" ng-change="prm.debounceLoad()">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Per page</label>
                <select class="form-select form-select-sm" ng-model="prm.pagination.per_page" ng-change="prm.load()">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" ng-click="prm.load()">Reload</button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Merchant</th>
                        <th>Txn</th>
                        <th>Method</th>
                        <th>Final acquirer</th>
                        <th>Status</th>
                        <th>At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-if="prm.loading">
                        <td colspan="8" class="text-center text-muted py-4">Loading…</td>
                    </tr>
                    <tr ng-repeat="r in prm.rows track by r.id" ng-if="!prm.loading">
                        <td>@{{ r.id }}</td>
                        <td>@{{ r.merchant_name || r.merchant_id }}</td>
                        <td><code class="small">@{{ r.txn_id || '—' }}</code></td>
                        <td>@{{ r.payment_method || '—' }}</td>
                        <td>@{{ r.final_acquirer_name || '—' }}</td>
                        <td>
                            <span class="badge" ng-class="{'bg-success': r.status==='success','bg-danger': r.status==='failed','bg-warning text-dark': r.status==='pending'}">@{{ r.status }}</span>
                        </td>
                        <td class="small">@{{ r.created_at | date:'medium' }}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" ng-href="/admin/payment-routing-monitors/@{{ r.id }}">View trace</a>
                        </td>
                    </tr>
                    <tr ng-if="!prm.loading && prm.rows.length===0">
                        <td colspan="8" class="text-center text-muted py-4">No records yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2" ng-if="prm.pagination.last_page > 1">
            <span class="text-muted small">@{{ prm.pagination.from }}–@{{ prm.pagination.to }} of @{{ prm.pagination.total }}</span>
            <div>
                <button class="btn btn-sm btn-outline-secondary" ng-disabled="prm.pagination.current_page<=1" ng-click="prm.page(prm.pagination.current_page-1)">Prev</button>
                <button class="btn btn-sm btn-outline-secondary" ng-disabled="prm.pagination.current_page>=prm.pagination.last_page" ng-click="prm.page(prm.pagination.current_page+1)">Next</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var app = angular.module('ipayApp');
    app.controller('PaymentRoutingMonitorsController', ['$http', '$timeout', function($http, $timeout) {
        var vm = this;
        vm.rows = [];
        vm.loading = false;
        vm.filters = { status: 'all', txn_id: '' };
        vm.pagination = { current_page: 1, per_page: 15, total: 0, last_page: 1, from: 0, to: 0 };
        var deb;
        vm.debounceLoad = function() {
            $timeout.cancel(deb);
            deb = $timeout(function() { vm.pagination.current_page = 1; vm.load(); }, 400);
        };
        vm.load = function() {
            vm.loading = true;
            var p = { page: vm.pagination.current_page, per_page: vm.pagination.per_page };
            if (vm.filters.status && vm.filters.status !== 'all') p.status = vm.filters.status;
            if (vm.filters.txn_id) p.txn_id = vm.filters.txn_id;
            $http.get('/admin/payment-routing-monitors/data', { params: p }).then(function(r) {
                vm.rows = r.data.data || [];
                vm.pagination = r.data.pagination || vm.pagination;
                vm.loading = false;
            }, function() { vm.loading = false; });
        };
        vm.page = function(n) { vm.pagination.current_page = n; vm.load(); };
        vm.load();
    }]);
})();
</script>
@endpush
@endsection
