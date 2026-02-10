@extends('layouts.app-sidebar')

@section('title', 'All Transactions - Admin - ' . config('app.name'))
@section('page-title', 'All Transactions')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminTransactionsController as atc">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard','url'=>route('admin.dashboard')],
        ['label'=>'All Transactions']
    ]" />

    <div class="stat-card mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="atc.filters.status" ng-change="atc.applyFilters()">
                    <option value="all">All</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                    <option value="initiated">Initiated</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control" placeholder="Merchant ID" ng-model="atc.filters.merchant_id" ng-change="atc.applyFilters()">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by transaction ID, order ID" ng-model="atc.filters.search" ng-change="atc.applyFilters()">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" ng-click="atc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="atc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading transactions...</p>
            </div>
        </div>

        <div ng-hide="atc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Transaction ID</th>
                            <th>Merchant</th>
                            <th>Order ID</th>
                            <th>Source</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="atc.transactions.length === 0">
                            <td colspan="11" class="text-center text-muted py-4">No transactions found</td>
                        </tr>
                        <tr ng-repeat="t in atc.transactions track by $index">
                            <td>@{{ (atc.pagination.current_page - 1) * atc.pagination.per_page + $index + 1 }}</td>
                            <td>
                                <code class="text-primary" style="font-size: 12px;">@{{ t.transaction_id || t.txn_id }}</code>
                            </td>
                            <td>
                                <div ng-if="t.merchant">
                                    <strong style="font-size: 13px;">@{{ t.merchant.name }}</strong>
                                    <br><small class="text-muted">ID: @{{ t.merchant.id }}</small>
                                </div>
                                <span ng-if="!t.merchant" class="text-muted">N/A</span>
                            </td>
                            <td>
                                <code class="text-info" style="font-size: 12px;">@{{ (t.order && t.order.order_id) || 'N/A' }}</code>
                            </td>
                            <td>
                                <span ng-if="t.order && t.order.payment_link_id" class="badge bg-primary">
                                    <i class="bi bi-link-45deg"></i> Payment Link
                                </span>
                                <span ng-if="!t.order || !t.order.payment_link_id" class="badge bg-secondary">
                                    <i class="bi bi-cart"></i> Direct
                                </span>
                            </td>
                            <td>
                                <div ng-if="t.customer_email || (t.order && t.order.customer_details)">
                                    <div class="fw-semibold" style="font-size: 13px;">@{{ t.customer_email || (t.order && t.order.customer_details.name) || 'N/A' }}</div>
                                    <small class="text-muted">@{{ t.customer_phone || (t.order && t.order.customer_details.phone) || '' }}</small>
                                </div>
                                <span ng-if="!t.customer_email && (!t.order || !t.order.customer_details)" class="text-muted">N/A</span>
                            </td>
                            <td>
                                <strong class="text-success">@{{ t.currency || 'INR' }} @{{ t.amount | number:2 }}</strong>
                                <div ng-if="t.fee_amount" style="font-size: 11px; color: #94a3b8;">Fee: @{{ t.currency }} @{{ t.fee_amount | number:2 }}</div>
                            </td>
                            <td>
                                <span class="badge" style="background: #6366f1;">@{{ t.payment_method | uppercase }}</span>
                                <div ng-if="t.payment_details && t.payment_details.card_number" style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    **** @{{ t.payment_details.card_number }}
                                </div>
                            </td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-success': t.status==='success' || t.status==='completed',
                                    'bg-danger': t.status==='failed',
                                    'bg-warning text-dark': t.status==='pending',
                                    'bg-info': t.status==='processing',
                                    'bg-secondary': t.status==='initiated'
                                }">
                                    @{{ t.status | uppercase }}
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13px;">@{{ t.created_at | date:'MMM d, y' }}</div>
                                <small class="text-muted">@{{ t.created_at | date:'HH:mm:ss' }}</small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="atc.viewDetails(t)" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div ng-if="atc.pagination.last_page > 1" class="pagination-wrapper">
                <ul class="pagination justify-content-center">
                    <li class="page-item" ng-class="{'disabled': atc.pagination.current_page === 1}">
                        <a class="page-link" href="#" ng-click="atc.changePage(atc.pagination.current_page - 1)">Previous</a>
                    </li>
                    <li class="page-item" ng-repeat="page in atc.getPageNumbers() track by $index" ng-class="{'active': page === atc.pagination.current_page}">
                        <a class="page-link" href="#" ng-click="atc.changePage(page)">@{{ page }}</a>
                    </li>
                    <li class="page-item" ng-class="{'disabled': atc.pagination.current_page === atc.pagination.last_page}">
                        <a class="page-link" href="#" ng-click="atc.changePage(atc.pagination.current_page + 1)">Next</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.transactions.angular.main_controller')
