@extends('layouts.app-sidebar')

@section('title', 'Payment-Gateway Dashboard - ' . config('app.name'))
@section('page-title', 'Payment-Gateway Dashboard')

@push('styles')
<style>
    .dashboard-card {
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-gradient-start), var(--card-gradient-end));
    }
    
    .dashboard-card.card-gtv {
        background: #ffffff;
        color: #111827;
        --card-gradient-start: #667eea;
        --card-gradient-end: #764ba2;
    }
    
    .dashboard-card.card-transactions {
        background: #ffffff;
        color: #111827;
        --card-gradient-start: #7c3aed;
        --card-gradient-end: #ec4899;
    }
    
    .dashboard-card.card-refunded {
        background: #ffffff;
        color: #111827;
        --card-gradient-start: #ec4899;
        --card-gradient-end: #f97373;
    }
    
    .dashboard-card.card-chargeback {
        background: #ffffff;
        color: #111827;
        --card-gradient-start: #fb7185;
        --card-gradient-end: #f97373;
    }
    
    .dashboard-card .card-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        font-size: 24px;
        margin-bottom: 16px;
    }
    
    .dashboard-card .card-value {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 8px;
        line-height: 1.2;
        word-break: break-word;
        white-space: normal;
    }

    @media (max-width: 768px) {
        .dashboard-card .card-value {
            font-size: 22px;
        }
    }
    
    .dashboard-card .card-label {
        font-size: 14px;
        opacity: 0.9;
        font-weight: 500;
        margin-bottom: 4px;
    }
    
    .dashboard-card .card-period {
        font-size: 12px;
        opacity: 0.8;
    }
    
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    
    .chart-card h6 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
    }
    
    .date-range-selector {
        background: white;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .no-data-message {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
        font-size: 14px;
    }
    
    .no-data-message i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
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

    .dashboard-fx-loader-inner {
        text-align: center;
    }

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

    @keyframes dashboard-fx-spin {
        to { transform: rotate(360deg); }
    }

    .dashboard-fx-loader-text {
        font-weight: 600;
        color: #E10600;
        letter-spacing: 0.02em;
    }

    .dashboard-metrics-body.is-loading {
        opacity: 0.4;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }
</style>
@endpush

@section('content')
<div ng-app="ipayApp" ng-controller="AdminDashboardController as adc">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard']
    ]" />

    <!-- Date Range Selector -->
    <div class="date-range-selector">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0" style="color:#E10600;font-weight:700;letter-spacing:-0.01em;">Dashboard</h5>
                <small class="text-muted">Payment-Gateway Dashboard</small>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-2">Select date range:</label>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="date" class="form-control form-control-sm" ng-model="adc.dateRange.start" ng-change="adc.loadData()" style="max-width: 150px;">
                    <span>-</span>
                    <input type="date" class="form-control form-control-sm" ng-model="adc.dateRange.end" ng-change="adc.loadData()" style="max-width: 150px;">
                    <select class="form-select form-select-sm" ng-model="adc.displayCurrency" ng-change="adc.loadData()" style="max-width: 100px;" title="Display currency">
                        <option ng-repeat="c in adc.currencyOptions" ng-value="c">@{{ c }}</option>
                    </select>
                    <select class="form-select form-select-sm" ng-model="adc.fxMode" ng-change="adc.loadData()" style="max-width: 180px;" title="FX conversion mode">
                        <option value="historical">Historical rate</option>
                        <option value="live">Live current rate</option>
                    </select>
                </div>
                <small class="text-muted d-block mt-1" ng-if="adc.fxMode === 'historical'">Totals use exchange rates captured at payment time.</small>
                <small class="text-muted d-block mt-1" ng-if="adc.fxMode === 'live'">Totals use today's live exchange rates.</small>
            </div>
        </div>
    </div>

    <div ng-show="adc.loading" class="dashboard-fx-loader" aria-live="polite" aria-busy="true">
        <div class="dashboard-fx-loader-inner">
            <div class="dashboard-fx-spinner" role="status" aria-label="Loading"></div>
            <p class="dashboard-fx-loader-text mb-0">Updating dashboard…</p>
        </div>
    </div>

    <div class="dashboard-metrics-body" ng-class="{'is-loading': adc.loading}">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="dashboard-card card-gtv">
                <div class="card-icon">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div class="card-value">@{{ adc.stats.display_currency || 'KES' }} @{{ (adc.stats.total_gtv || 0) | number:2 }}</div>
                <div class="card-label">Total GTV</div>
                <div class="card-period">(@{{ adc.stats.days_label || 'Last 10 days' }})</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card card-transactions">
                <div class="card-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="card-value">@{{ adc.stats.successful_transactions || 0 }}</div>
                <div class="card-label">Successful Transactions</div>
                <div class="card-period">(@{{ adc.stats.days_label || 'Last 10 days' }})</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card card-refunded">
                <div class="card-icon">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <div class="card-value">@{{ adc.stats.display_currency || 'KES' }} @{{ (adc.stats.amount_refunded || 0) | number:2 }}</div>
                <div class="card-label">Amount Refunded</div>
                <div class="card-period">(@{{ adc.stats.days_label || 'Last 10 days' }})</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dashboard-card card-chargeback">
                <div class="card-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="card-value">@{{ adc.stats.display_currency || 'KES' }} @{{ (adc.stats.chargeback_amount || 0) | number:2 }}</div>
                <div class="card-label">ChargeBack Amount</div>
                <div class="card-period">(@{{ adc.stats.days_label || 'Last 10 days' }})</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-md-12">
            <div class="chart-card">
                <h6>Gross Transaction Value and Transaction Count (@{{ adc.stats.days_label || 'Last 10 days' }})</h6>
                <div ng-hide="adc.loading">
                    <canvas id="gtvChart" ng-if="adc.charts.gtv_and_count"></canvas>
                    <div ng-if="!adc.charts.gtv_and_count" class="no-data-message">
                        <i class="bi bi-bar-chart"></i>
                        <p>No data for selected date range</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-card">
                <h6>Payments Mode Distribution (@{{ adc.stats.days_label || 'Last 10 days' }})</h6>
                <div ng-hide="adc.loading">
                    <canvas id="paymentModeChart" ng-if="adc.charts.payment_mode_distribution && adc.charts.payment_mode_distribution.length > 0"></canvas>
                    <div ng-if="!adc.charts.payment_mode_distribution || adc.charts.payment_mode_distribution.length === 0" class="no-data-message">
                        <i class="bi bi-pie-chart"></i>
                        <p>No data for selected date range</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-card">
                <h6>Device Distribution (@{{ adc.stats.days_label || 'Last 10 days' }})</h6>
                <div ng-hide="adc.loading">
                    <canvas id="deviceChart" ng-if="adc.charts.device_distribution && adc.charts.device_distribution.length > 0"></canvas>
                    <div ng-if="!adc.charts.device_distribution || adc.charts.device_distribution.length === 0" class="no-data-message">
                        <i class="bi bi-phone"></i>
                        <p>No data for selected date range</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            app.controller('AdminDashboardController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                
                // Initialize date range (last 10 days)
                var endDate = new Date();
                var startDate = new Date();
                startDate.setDate(startDate.getDate() - 10);
                
                vm.dateRange = {
                    start: startDate.toISOString().split('T')[0],
                    end: endDate.toISOString().split('T')[0]
                };
                
                vm.displayCurrency = 'KES';
                vm.fxMode = 'historical';
                vm.currencyOptions = ['USD', 'INR', 'KES', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'];

                vm.stats = {
                    total_gtv: 0,
                    successful_transactions: 0,
                    amount_refunded: 0,
                    chargeback_amount: 0,
                    days_label: 'Last 10 days',
                    display_currency: 'KES',
                    fx_mode: 'historical'
                };
                
                vm.charts = {
                    gtv_and_count: null,
                    payment_mode_distribution: [],
                    device_distribution: []
                };
                
                vm.loading = true;
                var gtvChart = null;
                var paymentModeChart = null;
                var deviceChart = null;

                vm.loadData = function() {
                    vm.loading = true;

                    var params = {
                        start_date: vm.dateRange.start,
                        end_date: vm.dateRange.end,
                        display_currency: vm.displayCurrency,
                        fx_mode: vm.fxMode
                    };

                    $timeout(function() {
                    $http.get('/admin/dashboard/data', { params: params }).then(function(response) {
                        if (response.data && response.data.success) {
                            if (response.data.data.fx_options && response.data.data.fx_options.supported_currencies) {
                                vm.currencyOptions = response.data.data.fx_options.supported_currencies;
                            }
                            vm.stats = response.data.data.stats || vm.stats;
                            vm.displayCurrency = vm.stats.display_currency || vm.displayCurrency;
                            vm.fxMode = vm.stats.fx_mode || vm.fxMode;
                            vm.charts = response.data.data.charts || vm.charts;
                            
                            $timeout(function() {
                                vm.renderCharts();
                            }, 100);
                        }
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading dashboard data:', error);
                        var msg = (error.data && error.data.message) ? error.data.message : 'Failed to load dashboard data';
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                    }, 0);
                };

                vm.renderCharts = function() {
                    // GTV and Transaction Count Chart
                    if (vm.charts.gtv_and_count && vm.charts.gtv_and_count.gtv) {
                        var ctx = document.getElementById('gtvChart');
                        if (ctx) {
                            if (gtvChart) gtvChart.destroy();
                            
                            var labels = vm.charts.gtv_and_count.gtv.map(function(item) {
                                return new Date(item.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit' });
                            });
                            
                            gtvChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'GTV (' + (vm.stats.display_currency || 'KES') + ')',
                                        data: vm.charts.gtv_and_count.gtv.map(function(item) { return item.value; }),
                                        borderColor: 'rgb(99, 102, 241)',
                                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                        yAxisID: 'y',
                                        tension: 0.4
                                    }, {
                                        label: 'Transaction Count',
                                        data: vm.charts.gtv_and_count.count.map(function(item) { return item.value; }),
                                        borderColor: 'rgb(236, 72, 153)',
                                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                        yAxisID: 'y1',
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    interaction: {
                                        mode: 'index',
                                        intersect: false,
                                    },
                                    scales: {
                                        y: {
                                            type: 'linear',
                                            display: true,
                                            position: 'left',
                                            title: {
                                                display: true,
                                                text: 'GTV (' + (vm.stats.display_currency || 'KES') + ')'
                                            }
                                        },
                                        y1: {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            title: {
                                                display: true,
                                                text: 'Count'
                                            },
                                            grid: {
                                                drawOnChartArea: false,
                                            },
                                        }
                                    }
                                }
                            });
                        }
                    }
                    
                    // Payment Mode Distribution Chart
                    if (vm.charts.payment_mode_distribution && vm.charts.payment_mode_distribution.length > 0) {
                        var ctx2 = document.getElementById('paymentModeChart');
                        if (ctx2) {
                            if (paymentModeChart) paymentModeChart.destroy();
                            
                            paymentModeChart = new Chart(ctx2, {
                                type: 'doughnut',
                                data: {
                                    labels: vm.charts.payment_mode_distribution.map(function(item) { return item.mode; }),
                                    datasets: [{
                                        data: vm.charts.payment_mode_distribution.map(function(item) { return item.count; }),
                                        backgroundColor: [
                                            'rgba(99, 102, 241, 0.8)',
                                            'rgba(236, 72, 153, 0.8)',
                                            'rgba(251, 146, 60, 0.8)',
                                            'rgba(34, 197, 94, 0.8)',
                                            'rgba(59, 130, 246, 0.8)',
                                            'rgba(168, 85, 247, 0.8)'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        }
                    }
                    
                    // Device Distribution Chart
                    if (vm.charts.device_distribution && vm.charts.device_distribution.length > 0) {
                        var ctx3 = document.getElementById('deviceChart');
                        if (ctx3) {
                            if (deviceChart) deviceChart.destroy();
                            
                            deviceChart = new Chart(ctx3, {
                                type: 'bar',
                                data: {
                                    labels: vm.charts.device_distribution.map(function(item) { return item.device; }),
                                    datasets: [{
                                        label: 'Transactions',
                                        data: vm.charts.device_distribution.map(function(item) { return item.count; }),
                                        backgroundColor: [
                                            'rgba(99, 102, 241, 0.8)',
                                            'rgba(236, 72, 153, 0.8)',
                                            'rgba(251, 146, 60, 0.8)'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        }
                    }
                };

                // Initial load
                vm.loadData();
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
