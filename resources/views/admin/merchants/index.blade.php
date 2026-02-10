@extends('layouts.app-sidebar')

@section('title', 'Merchants - Admin - ' . config('app.name'))
@section('page-title', 'Merchants Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminMerchantsController as amc">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard','url'=>route('admin.dashboard')],
        ['label'=>'Merchants']
    ]" />

    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Merchants</h2>
            <p class="text-muted">Manage all merchants in the system</p>
        </div>
    </div>

    <div class="stat-card mb-4">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Search merchants..." ng-model="amc.filters.search" ng-change="amc.applyFilters()">
            </div>
            <div class="col-md-3">
                <select class="form-select" ng-model="amc.filters.status" ng-change="amc.applyFilters()">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" ng-click="amc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="amc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading merchants...</p>
            </div>
        </div>

        <div ng-hide="amc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Merchant ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Test Mode</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="amc.merchants.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">No merchants found</td>
                        </tr>
                        <tr ng-repeat="merchant in amc.merchants track by $index">
                            <td>@{{ (amc.pagination.current_page - 1) * amc.pagination.per_page + $index + 1 }}</td>
                            <td><code>@{{ merchant.id }}</code></td>
                            <td><strong>@{{ merchant.name }}</strong></td>
                            <td>@{{ merchant.email }}</td>
                            <td>
                                <span class="badge" ng-class="{'bg-success': merchant.status==='active', 'bg-danger': merchant.status==='inactive', 'bg-warning': merchant.status==='pending'}">
                                    @{{ merchant.status | uppercase }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" ng-class="{'bg-warning': merchant.test_mode, 'bg-info': !merchant.test_mode}">
                                    @{{ merchant.test_mode ? 'TEST' : 'LIVE' }}
                                </span>
                            </td>
                            <td>@{{ merchant.created_at | date:'MMM d, y HH:mm' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="amc.viewMerchant(merchant)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div ng-if="amc.pagination.last_page > 1" class="pagination-wrapper">
                <ul class="pagination justify-content-center">
                    <li class="page-item" ng-class="{'disabled': amc.pagination.current_page === 1}">
                        <a class="page-link" href="#" ng-click="amc.changePage(amc.pagination.current_page - 1)">Previous</a>
                    </li>
                    <li class="page-item" ng-repeat="page in amc.getPageNumbers() track by $index" ng-class="{'active': page === amc.pagination.current_page}">
                        <a class="page-link" href="#" ng-click="amc.changePage(page)">@{{ page }}</a>
                    </li>
                    <li class="page-item" ng-class="{'disabled': amc.pagination.current_page === amc.pagination.last_page}">
                        <a class="page-link" href="#" ng-click="amc.changePage(amc.pagination.current_page + 1)">Next</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.merchants.angular.main_controller')
