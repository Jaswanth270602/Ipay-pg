@extends('layouts.app-sidebar')

@section('title', 'Reseller Dashboard - ' . config('app.name'))
@section('page-title', 'Reseller Dashboard')

@push('styles')
<style>
    /* Reseller dashboard metrics: equal height, no overflow, aligned with existing PG stat-card look */
    .reseller-dashboard-metrics .stat-card.metric-tile {
        display: flex;
        flex-direction: column;
        min-height: 148px;
        height: 100%;
        overflow: hidden;
    }
    .reseller-dashboard-metrics .metric-tile-label {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.5rem;
        line-height: 1.3;
        min-height: 2.6em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .reseller-dashboard-metrics .metric-tile-value {
        font-size: clamp(1.25rem, 2.5vw, 1.5rem);
        font-weight: 700;
        line-height: 1.2;
        color: #111827;
        font-variant-numeric: tabular-nums;
        word-break: break-word;
        overflow-wrap: anywhere;
        max-width: 100%;
        flex: 1 1 auto;
        min-height: 2.75rem;
    }
    .dashboard-fx-loader {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.92);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dashboard-fx-loader-inner { text-align: center; }
    .dashboard-fx-spinner {
        width: 80px;
        height: 80px;
        margin: 0 auto 16px;
        border: 7px solid #fecdd3;
        border-top-color: #E10600;
        border-right-color: #ec4899;
        border-radius: 50%;
        animation: dashboard-fx-spin 0.7s linear infinite;
    }
    @keyframes dashboard-fx-spin { to { transform: rotate(360deg); } }
    .dashboard-fx-loader-text { font-weight: 600; color: #E10600; }
    .dashboard-metrics-body.is-loading {
        opacity: 0.4;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }

    .reseller-dashboard-metrics .metric-tile-caption {
        font-size: 0.75rem;
        line-height: 1.35;
        color: #9ca3af;
        margin-top: auto;
        min-height: 2.7em;
        max-height: 2.7em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div ng-app="ipayApp" ng-controller="ResellerDashboardController as rdc">
    <div ng-show="rdc.loading" class="dashboard-fx-loader" aria-live="polite" aria-busy="true">
        <div class="dashboard-fx-loader-inner">
            <div class="dashboard-fx-spinner" role="status" aria-label="Loading"></div>
            <p class="dashboard-fx-loader-text mb-0">Updating dashboard…</p>
        </div>
    </div>

    <div class="dashboard-metrics-body" ng-class="{'is-loading': rdc.loading}">
    <div class="row g-4">
        <div class="col-md-12 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2>Welcome, {{ $user->name }}</h2>
                <p class="text-muted mb-0">Overview of your reseller account.</p>
            </div>
            <div class="d-flex align-items-end gap-2 flex-wrap">
                <select class="form-select form-select-sm" ng-model="rdc.displayCurrency" ng-change="rdc.applyFilters()" style="width: 100px;" title="Display currency">
                    <option ng-repeat="c in rdc.currencyOptions" ng-value="c">@{{ c }}</option>
                </select>
                <select class="form-select form-select-sm" ng-model="rdc.fxMode" ng-change="rdc.applyFilters()" style="width: 170px;" title="FX mode">
                    <option value="historical">Historical rate</option>
                    <option value="live">Live current rate</option>
                </select>
                <select class="form-select form-select-sm" ng-model="rdc.reportFormat" style="width: 110px;">
                    <option value="csv">CSV</option>
                    <option value="xlsx">Excel</option>
                </select>
                <button class="btn btn-primary btn-sm" type="button" ng-click="rdc.downloadReport()">
                    <i class="bi bi-download"></i> Download Report
                </button>
            </div>
        </div>

        <div class="col-12 reseller-dashboard-metrics">
            <div class="row g-3 g-md-4">
                <div class="col-md-3 d-flex" ng-repeat="card in rdc.cards track by card.key">
                    <div class="stat-card metric-tile w-100">
                        <div class="metric-tile-label">@{{ card.label }}</div>
                        <div class="metric-tile-value">@{{ card.currency ? ((rdc.displayCurrency || 'KES') + ' ' + rdc.money(card.value)) : card.value }}</div>
                        <span class="metric-tile-caption">@{{ card.caption }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Merchant Performance</h5>
                    <a href="{{ route('reseller.profile.index') }}" class="btn btn-sm btn-outline-secondary">View Profile</a>
                </div>
                <div class="row g-3 mt-1 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Merchant</label>
                        <input class="form-control form-control-sm" type="text" ng-model="rdc.filters.search" placeholder="Merchant name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Merchant</label>
                        <select class="form-select form-select-sm" ng-model="rdc.filters.merchant_id">
                            <option value="">All</option>
                            @foreach($merchantOptions as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input class="form-control form-control-sm" type="date" ng-model="rdc.filters.date_from">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input class="form-control form-control-sm" type="date" ng-model="rdc.filters.date_to">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rows</label>
                        <select class="form-select form-select-sm" ng-model="rdc.pagination.per_page" ng-change="rdc.applyFilters()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-sm btn-primary" ng-click="rdc.applyFilters()">Apply</button>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div ng-show="rdc.loading" class="loader-overlay position-relative" style="min-height: 220px;">
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted text-center">Loading...</p>
                    </div>
                </div>
                <div ng-hide="rdc.loading" class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Merchant Name</th>
                            <th>Total Transactions</th>
                            <th>Gross Volume</th>
                            <th>Refund Amount</th>
                            <th>Net Volume</th>
                            <th>Commission Earned</th>
                            <th>Pending Commission</th>
                            <th>Paid Commission</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr ng-repeat="row in rdc.rows track by row.merchant_id">
                            <td>@{{ row.merchant_name }}</td>
                            <td>@{{ row.total_transactions }}</td>
                            <td>INR @{{ rdc.money(row.gross_volume) }}</td>
                            <td>INR @{{ rdc.money(row.refund_amount) }}</td>
                            <td>INR @{{ rdc.money(row.net_volume) }}</td>
                            <td>INR @{{ rdc.money(row.commission_earned) }}</td>
                            <td>INR @{{ rdc.money(row.pending_commission) }}</td>
                            <td>INR @{{ rdc.money(row.paid_commission) }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" type="button" ng-click="rdc.openDetails(row)">
                                    View Details
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="p-3 text-muted text-center" ng-if="rdc.rows.length === 0">No merchant data found.</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top" ng-hide="rdc.loading" ng-if="rdc.pagination.total > 0">
                    <small class="text-muted">Showing @{{ rdc.pagination.from }}–@{{ rdc.pagination.to }} of @{{ rdc.pagination.total }}</small>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" ng-disabled="rdc.pagination.current_page <= 1" ng-click="rdc.changePage(rdc.pagination.current_page - 1)">Prev</button>
                        <button type="button" class="btn btn-outline-secondary" ng-disabled="rdc.pagination.current_page >= rdc.pagination.last_page" ng-click="rdc.changePage(rdc.pagination.current_page + 1)">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" ng-if="rdc.details.visible">
            <div class="stat-card" id="merchant-details-section">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Transactions - @{{ rdc.details.merchant_name }}</h5>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="rdc.closeDetails()">Close</button>
                </div>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control form-control-sm" ng-model="rdc.details.filters.date_from">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control form-control-sm" ng-model="rdc.details.filters.date_to">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" ng-model="rdc.details.filters.status">
                            <option value="all">All</option>
                            <option value="success">Successful</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-sm btn-primary" ng-click="rdc.applyDetailsFilters()">Apply Filters</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reseller Commission</th>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr ng-repeat="row in rdc.details.rows track by $index">
                            <td>@{{ row.transaction_id }}</td>
                            <td>@{{ row.order_id }}</td>
                            <td>INR @{{ rdc.money(row.amount) }}</td>
                            <td>@{{ row.status }}</td>
                            <td>INR @{{ rdc.money(row.commission) }}</td>
                            <td>@{{ row.created_at }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="p-3 text-muted text-center" ng-if="rdc.details.rows.length === 0">No transactions found for this merchant.</div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-3" ng-if="rdc.details.pagination.total > 0">
                    <small class="text-muted">Showing @{{ rdc.details.pagination.from }}–@{{ rdc.details.pagination.to }} of @{{ rdc.details.pagination.total }}</small>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" ng-disabled="rdc.details.pagination.current_page <= 1" ng-click="rdc.changeDetailsPage(rdc.details.pagination.current_page - 1)">Prev</button>
                        <button type="button" class="btn btn-outline-secondary" ng-disabled="rdc.details.pagination.current_page >= rdc.details.pagination.last_page" ng-click="rdc.changeDetailsPage(rdc.details.pagination.current_page + 1)">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <a href="{{ route('reseller.transactions.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Merchant</th>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reseller Commission</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr ng-repeat="txn in rdc.recentRows track by txn.id">
                            <td>@{{ txn.transaction_datetime }}</td>
                            <td>@{{ txn.merchant_name }}</td>
                            <td>@{{ txn.transaction_id }}</td>
                            <td>@{{ txn.amount_paid_by_customer }} @{{ txn.currency_code }}</td>
                            <td>@{{ txn.payment_status }}</td>
                            <td>INR @{{ txn.commission || '0.00' }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" type="button" ng-click="rdc.viewTxnDetails(txn)">View Details</button></td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="p-3 text-muted text-center" ng-if="rdc.recentRows.length === 0">No recent transactions found.</div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="resellerDashTxnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" ng-if="rdc.selectedTxn">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Merchant:</strong> @{{ rdc.selectedTxn.merchant_name }}</div>
                        <div class="col-md-6"><strong>Transaction ID:</strong> @{{ rdc.selectedTxn.transaction_id }}</div>
                        <div class="col-md-6"><strong>Order:</strong> @{{ rdc.selectedTxn.transaction_order_id || rdc.selectedTxn.order_id || '-' }}</div>
                        <div class="col-md-6"><strong>Date:</strong> @{{ rdc.selectedTxn.transaction_datetime || rdc.selectedTxn.created_at }}</div>
                        <div class="col-md-6"><strong>Amount:</strong> @{{ rdc.selectedTxn.amount_paid_by_customer || rdc.money(rdc.selectedTxn.amount) }} @{{ rdc.selectedTxn.currency_code || 'INR' }}</div>
                        <div class="col-md-6"><strong>Status:</strong> @{{ rdc.selectedTxn.payment_status || rdc.selectedTxn.status }}</div>
                        <div class="col-md-6"><strong>Payment Mode:</strong> @{{ rdc.selectedTxn.payment_mode || '-' }}</div>
                        <div class="col-md-6"><strong>Reseller Commission:</strong> INR @{{ rdc.selectedTxn.commission || '0.00' }}</div>
                    </div>
                </div>
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
            app.controller('ResellerDashboardController', ['$http', '$window', '$timeout', function ($http, $window, $timeout) {
                var vm = this;
                vm.loading = false;
                vm.displayCurrency = @json($dashboard_display_currency ?? 'KES');
                vm.fxMode = @json($dashboard_fx_mode ?? 'historical');
                vm.currencyOptions = @json($fx_options['supported_currencies'] ?? ['USD', 'INR', 'KES']);
                vm.reportFormat = 'csv';
                vm.rows = [];
                vm.cards = [];
                vm.recentRows = [];
                vm.selectedTxn = null;
                vm.filters = { search: '', date_from: null, date_to: null, merchant_id: '' };
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1, from: null, to: null };
                vm.details = {
                    visible: false,
                    merchant_id: null,
                    merchant_name: '',
                    rows: [],
                    filters: { date_from: null, date_to: null, status: 'all' },
                    pagination: { current_page: 1, per_page: 10, total: 0, last_page: 1, from: null, to: null }
                };

                vm.money = function (value) {
                    var n = parseFloat(value || 0);
                    return n.toFixed(2);
                };

                vm.cardDataFromSummary = function (summary) {
                    return [
                        { key: 'tm', label: 'Total Merchants', value: summary.total_merchants || 0, currency: false, caption: 'Assigned under your account' },
                        { key: 'tt', label: 'Total Transactions', value: summary.total_transactions || 0, currency: false, caption: 'Successful transactions' },
                        { key: 'gv', label: 'Gross Volume', value: summary.gross_volume || 0, currency: true, caption: 'Successful transaction amount' },
                        { key: 'nv', label: 'Net Volume', value: summary.net_volume || 0, currency: true, caption: 'Gross minus refunds' },
                        { key: 'tr', label: 'Total Refunds', value: summary.total_refunds || 0, currency: true, caption: 'Completed refunds' },
                        { key: 'ge', label: 'Gross Earnings', value: summary.gross_earnings || 0, currency: true, caption: 'Before reversals' },
                        { key: 'ne', label: 'Net Earnings', value: summary.net_earnings || 0, currency: true, caption: 'After reversals' },
                        { key: 'pe', label: 'Paid Earnings', value: summary.paid_earnings || 0, currency: true, caption: 'Already settled' }
                    ];
                };

                vm.load = function () {
                    vm.loading = true;
                    $timeout(function () {
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        search: vm.filters.search || ''
                    };
                    if (vm.filters.merchant_id) { params.merchant_id = vm.filters.merchant_id; }
                    if (vm.filters.date_from) { params.date_from = vm.filters.date_from; }
                    if (vm.filters.date_to) { params.date_to = vm.filters.date_to; }
                    params.display_currency = vm.displayCurrency;
                    params.fx_mode = vm.fxMode;

                    $http.get("{{ route('reseller.merchant-summary') }}", { params: params }).then(function (res) {
                        vm.rows = res.data.data || [];
                        vm.pagination = res.data.pagination || vm.pagination;
                        if (res.data.fx_options && res.data.fx_options.supported_currencies) {
                            vm.currencyOptions = res.data.fx_options.supported_currencies;
                        }
                        if (res.data.summary && res.data.summary.display_currency) {
                            vm.displayCurrency = res.data.summary.display_currency;
                        }
                        vm.cards = vm.cardDataFromSummary(res.data.summary || {});
                        vm.loading = false;
                    }, function () {
                        vm.loading = false;
                        alert('Failed to load merchant performance');
                    });
                    }, 0);
                };

                vm.loadRecent = function () {
                    var params = { page: 1, per_page: 5, status: '' };
                    if (vm.filters.date_from && vm.filters.date_to) {
                        params.date_range = vm.filters.date_from + ' 00:00:00 - ' + vm.filters.date_to + ' 23:59:59';
                    }
                    if (vm.filters.merchant_id) {
                        params.merchant_id = vm.filters.merchant_id;
                    }
                    $http.get("{{ route('reseller.transactions.data') }}", { params: params }).then(function (res) {
                        vm.recentRows = res.data.data || [];
                    }, function () {
                        vm.recentRows = [];
                    });
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.load();
                    vm.loadRecent();
                };

                vm.changePage = function (page) {
                    page = parseInt(page, 10);
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.load();
                    }
                };

                vm.downloadReport = function () {
                    var params = [];
                    if (vm.filters.date_from) { params.push('date_from=' + encodeURIComponent(vm.filters.date_from)); }
                    if (vm.filters.date_to) { params.push('date_to=' + encodeURIComponent(vm.filters.date_to)); }
                    if (vm.filters.merchant_id) { params.push('merchant_id=' + encodeURIComponent(vm.filters.merchant_id)); }
                    params.push('format=' + encodeURIComponent(vm.reportFormat || 'csv'));
                    var url = "{{ route('reseller.report.download') }}" + '?' + params.join('&');
                    $window.location.href = url;
                };

                vm.loadDetails = function () {
                    if (!vm.details.merchant_id) return;
                    var params = {
                        merchant_id: vm.details.merchant_id,
                        status: vm.details.filters.status || 'all',
                        page: vm.details.pagination.current_page,
                        per_page: vm.details.pagination.per_page
                    };
                    if (vm.details.filters.date_from) params.date_from = vm.details.filters.date_from;
                    if (vm.details.filters.date_to) params.date_to = vm.details.filters.date_to;

                    $http.get("{{ route('reseller.merchant-transactions') }}", { params: params }).then(function (res) {
                        vm.details.rows = res.data.data || [];
                        vm.details.pagination = res.data.pagination || vm.details.pagination;
                    }, function () {
                        alert('Failed to load merchant transactions');
                    });
                };

                vm.openDetails = function (row) {
                    if (!row || !row.merchant_id) return;
                    vm.details.visible = true;
                    vm.details.merchant_id = row.merchant_id;
                    vm.details.merchant_name = row.merchant_name || '';
                    vm.details.filters.date_from = vm.filters.date_from || null;
                    vm.details.filters.date_to = vm.filters.date_to || null;
                    vm.details.filters.status = 'all';
                    vm.details.pagination.current_page = 1;
                    vm.loadDetails();
                    setTimeout(function () {
                        var el = document.getElementById('merchant-details-section');
                        if (el && el.scrollIntoView) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 50);
                };

                vm.closeDetails = function () {
                    vm.details.visible = false;
                };

                vm.applyDetailsFilters = function () {
                    vm.details.pagination.current_page = 1;
                    vm.loadDetails();
                };

                vm.changeDetailsPage = function (page) {
                    page = parseInt(page, 10);
                    if (page >= 1 && page <= vm.details.pagination.last_page) {
                        vm.details.pagination.current_page = page;
                        vm.loadDetails();
                    }
                };

                vm.viewTxnDetails = function (row) {
                    vm.selectedTxn = row;
                    if (window.bootstrap && document.getElementById('resellerDashTxnModal')) {
                        var modal = new bootstrap.Modal(document.getElementById('resellerDashTxnModal'));
                        modal.show();
                    }
                };

                vm.cards = vm.cardDataFromSummary(@json($stats));
                vm.load();
                vm.loadRecent();
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

