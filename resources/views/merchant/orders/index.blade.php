@extends('layouts.app-sidebar')

@section('title', 'Orders - ' . config('app.name'))
@section('page-title','Orders')

@section('content')
<div ng-app="badlicashApp" ng-controller="OrdersController as oc">
    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="oc.filters.status" ng-change="oc.applyFilters()">
                    <option value="">All</option>
                    <option value="created">Created</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="oc.filters.from_date" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="oc.filters.to_date" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Per Page</label>
                <select class="form-select" ng-model="oc.perPage" ng-change="oc.applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-6">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by order ID or description" ng-model="oc.filters.search" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end gap-2">
                <button class="btn btn-success" ng-click="oc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-outline-secondary" ng-click="oc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="oc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading orders...</p>
            </div>
        </div>
        <div ng-hide="oc.loading" class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="order in oc.orders track by $index">
                    <td>@{{ (oc.pagination.current_page - 1) * oc.pagination.per_page + $index + 1 }}</td>
                    <td><code>@{{ order.order_id }}</code></td>
                    <td>@{{ order.description || 'N/A' }}</td>
                    <td><strong>@{{ order.amount | number:2 }}</strong></td>
                    <td>@{{ order.currency || 'INR' }}</td>
                    <td>
                        <span class="badge" ng-class="{'bg-success': order.status==='completed', 'bg-danger': order.status==='failed', 'bg-warning': order.status==='pending', 'bg-info': order.status==='created', 'bg-secondary': order.status==='cancelled'}">@{{ order.status | uppercase }}</span>
                    </td>
                    <td>@{{ order.created_at | date:'MMM d, y HH:mm' }}</td>
                </tr>
                <tr ng-if="oc.orders.length===0 && !oc.loading">
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-2">No orders found</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div ng-if="oc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing @{{ oc.pagination.from || 0 }} to @{{ oc.pagination.to || 0 }} of @{{ oc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="oc.pagination.current_page > 1" ng-click="oc.loadPage(oc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in oc.getPaginationPages() track by page" ng-class="{'active': page === oc.pagination.current_page}" ng-click="oc.loadPage(page)">@{{ page }}</a>
                <a href="#" class="page-link" ng-if="oc.pagination.current_page < oc.pagination.last_page" ng-click="oc.loadPage(oc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>
</div>
@endsection

@include('merchant.orders.angular.main_controller')

