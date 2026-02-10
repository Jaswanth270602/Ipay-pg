@extends('layouts.app-sidebar')

@section('title', 'Admin Reports - ' . config('app.name'))
@section('page-title','Admin Reports')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminReportsController as arc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Reports']
    ]" />

    <!-- Filters Section -->
    <div class="stat-card mb-3">
        <h5 class="mb-3">Filter Transactions</h5>
        <div class="row g-3">
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control" ng-model="arc.filters.merchant_id" placeholder="e.g. 1">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="arc.filters.from_date">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="arc.filters.to_date">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="arc.filters.status">
                    <option value="all">All Status</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                    <option value="initiated">Initiated</option>
                    <option value="authorized">Authorized</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Payment Method</label>
                <select class="form-select" ng-model="arc.filters.payment_method">
                    <option value="all">All Methods</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                    <option value="netbanking">Netbanking</option>
                    <option value="wallet">Wallet</option>
                    <option value="emi">EMI</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" ng-click="arc.generateReport()" ng-disabled="arc.generating">
                    <span ng-if="arc.generating" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-search"></i> Generate
                </button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button class="btn btn-outline-primary" ng-click="arc.exportReport()" ng-disabled="arc.exporting || !arc.reportData">
                    <span ng-if="arc.exporting" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stat-card mb-3" ng-if="arc.reportData && arc.reportData.summary">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Summary</h5>
            <button class="btn btn-sm btn-outline-secondary" ng-click="arc.clearFilters()" title="Clear Filters & Close Report">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Total Transactions</div>
                <div class="h4 mb-0">@{{ arc.reportData.summary.total_transactions || 0 }}</div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Successful</div>
                <div class="h4 mb-0 text-success">@{{ arc.reportData.summary.successful || 0 }}</div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Failed</div>
                <div class="h4 mb-0 text-danger">@{{ arc.reportData.summary.failed || 0 }}</div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Pending</div>
                <div class="h4 mb-0 text-warning">@{{ arc.reportData.summary.pending || 0 }}</div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="text-muted small">Total Volume (Successful)</div>
                <div class="h3 mb-0 text-primary">@{{ arc.reportData.summary.total_amount || '0.00' }} <small class="text-muted">INR</small></div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="stat-card" ng-if="arc.reportData && arc.reportData.transactions">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Transaction Details</h5>
            <div class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 me-2">Show:</label>
                <select class="form-select form-select-sm" style="width: auto;" ng-model="arc.pagination.per_page" ng-change="arc.changePerPage()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div ng-show="arc.loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading transactions...</p>
        </div>

        <div ng-hide="arc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Order ID</th>
                            <th>Merchant</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Net Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="arc.reportData.transactions.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> No transactions found
                            </td>
                        </tr>
                        <tr ng-repeat="txn in arc.reportData.transactions">
                            <td>
                                <code class="text-primary">@{{ txn.txn_id }}</code>
                            </td>
                            <td>@{{ txn.order_id }}</td>
                            <td>
                                <div>@{{ txn.merchant_name }}</div>
                                <small class="text-muted">ID: @{{ txn.merchant_id }}</small>
                            </td>
                            <td>
                                <strong>@{{ txn.amount }}</strong>
                                <small class="text-muted">@{{ txn.currency }}</small>
                            </td>
                            <td>@{{ txn.fee_amount }}</td>
                            <td>
                                <strong>@{{ txn.net_amount }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary">@{{ txn.payment_method }}</span>
                            </td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-success': txn.status === 'success',
                                    'bg-danger': txn.status === 'failed',
                                    'bg-warning': txn.status === 'pending' || txn.status === 'initiated' || txn.status === 'authorized',
                                    'bg-secondary': txn.status === 'cancelled'
                                }">
                                    @{{ txn.status | uppercase }}
                                </span>
                            </td>
                            <td>
                                <div ng-if="txn.customer_email !== '-'">@{{ txn.customer_email }}</div>
                                <div ng-if="txn.customer_phone !== '-'"><small>@{{ txn.customer_phone }}</small></div>
                                <div ng-if="txn.customer_email === '-' && txn.customer_phone === '-'">
                                    <span class="text-muted">-</span>
                                </div>
                            </td>
                            <td>
                                <div>@{{ txn.created_at_formatted }}</div>
                                <small class="text-muted">@{{ txn.created_at }}</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3" ng-if="arc.reportData.pagination">
                <div>
                    Showing @{{ (arc.reportData.pagination.current_page - 1) * arc.reportData.pagination.per_page + 1 }} 
                    to @{{ Math.min(arc.reportData.pagination.current_page * arc.reportData.pagination.per_page, arc.reportData.pagination.total) }} 
                    of @{{ arc.reportData.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="arc.changePage(arc.reportData.pagination.current_page - 1)" 
                            ng-disabled="arc.reportData.pagination.current_page === 1">
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    <span class="mx-2">
                        Page @{{ arc.reportData.pagination.current_page }} of @{{ arc.reportData.pagination.last_page }}
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="arc.changePage(arc.reportData.pagination.current_page + 1)" 
                            ng-disabled="arc.reportData.pagination.current_page === arc.reportData.pagination.last_page">
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div class="stat-card text-center py-5" ng-if="!arc.reportData">
        <i class="bi bi-graph-up" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">Select filters and click "Generate" to view transaction reports</p>
    </div>
</div>
@endsection

@include('admin.reports.angular.main_controller')
