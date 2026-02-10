@extends('layouts.app-sidebar')

@section('title', 'Settlements - ' . config('app.name'))
@section('page-title','Settlements')

@section('content')
<div ng-app="badlicashApp" ng-controller="SettlementsController as sc">
    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="sc.filters.status" ng-change="sc.applyFilters()">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="sc.filters.from_date" ng-change="sc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="sc.filters.to_date" ng-change="sc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Per Page</label>
                <select class="form-select" ng-model="sc.perPage" ng-change="sc.applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-6">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by settlement ID or reference number" ng-model="sc.filters.search" ng-change="sc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end gap-2">
                <button class="btn btn-success" ng-click="sc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-outline-secondary" ng-click="sc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="sc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlements...</p>
            </div>
        </div>
        <div ng-hide="sc.loading" class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Settlement ID</th>
                    <th>Reference Number</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Settlement Date</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="settlement in sc.settlements track by $index">
                    <td>@{{ (sc.pagination.current_page - 1) * sc.pagination.per_page + $index + 1 }}</td>
                    <td><code>@{{ settlement.settlement_id }}</code></td>
                    <td>@{{ settlement.reference_number || 'N/A' }}</td>
                    <td><strong>@{{ settlement.amount | number:2 }}</strong></td>
                    <td>@{{ settlement.currency || 'INR' }}</td>
                    <td>@{{ settlement.settlement_date | date:'MMM d, y' }}</td>
                    <td>
                        <span class="badge" ng-class="{'bg-success': settlement.status==='completed', 'bg-danger': settlement.status==='failed', 'bg-warning': settlement.status==='pending', 'bg-info': settlement.status==='processing'}">@{{ settlement.status | uppercase }}</span>
                    </td>
                    <td>@{{ settlement.created_at | date:'MMM d, y HH:mm' }}</td>
                </tr>
                <tr ng-if="sc.settlements.length===0 && !sc.loading">
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-2">No settlements found</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div ng-if="sc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing @{{ sc.pagination.from || 0 }} to @{{ sc.pagination.to || 0 }} of @{{ sc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="sc.pagination.current_page > 1" ng-click="sc.loadPage(sc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in sc.getPaginationPages() track by page" ng-class="{'active': page === sc.pagination.current_page}" ng-click="sc.loadPage(page)">@{{ page }}</a>
                <a href="#" class="page-link" ng-if="sc.pagination.current_page < sc.pagination.last_page" ng-click="sc.loadPage(sc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>
</div>

@include('merchant.settlements.angular.main_controller')
@endsection

