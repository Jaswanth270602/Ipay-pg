@extends('layouts.app-sidebar')

@section('title', 'Merchants - Admin - ' . config('app.name'))
@section('page-title', 'Merchants Management')

@section('content')
<div ng-app="ipayApp" ng-controller="AdminMerchantsController as amc">
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
                <input type="text"
                       class="form-control"
                       placeholder="Search by merchant name, email, ID or acquirer type..."
                       ng-model="amc.filters.search"
                       ng-change="amc.applyFilters()">
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
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <button class="btn btn-sm btn-success me-2"
                        ng-click="amc.openBulkConfirm('activate')"
                        ng-disabled="!amc.hasSelection()">
                    Bulk Activate
                </button>
                <button class="btn btn-sm btn-warning me-2"
                        ng-click="amc.openBulkConfirm('deactivate')"
                        ng-disabled="!amc.hasSelection()">
                    Bulk Deactivate
                </button>
                <button class="btn btn-sm btn-danger"
                        ng-click="amc.openBulkConfirm('delete')"
                        ng-disabled="!amc.hasSelection()">
                    Bulk Delete
                </button>
            </div>
            <div class="text-muted small" ng-if="amc.selectedIds.length">
                @{{ amc.selectedIds.length }} selected
            </div>
        </div>

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
                            <th>
                                <input type="checkbox" ng-model="amc.selectAll" ng-change="amc.toggleSelectAll()">
                            </th>
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
                            <td>
                                <input type="checkbox"
                                       ng-model="amc.selected[merchant.id]"
                                       ng-change="amc.syncSelection()">
                            </td>
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

    <!-- Merchant details modal -->
    <div class="modal fade" id="merchantDetailModal" tabindex="-1" aria-labelledby="merchantDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="merchantDetailLabel">
                        <i class="bi bi-building me-1"></i> Merchant Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" ng-if="amc.merchantDetail">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Merchant ID:</strong> @{{ amc.merchantDetail.id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Name:</strong> @{{ amc.merchantDetail.name }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong> @{{ amc.merchantDetail.email }}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <span class="badge" ng-class="{
                                'bg-success': amc.merchantDetail.status === 'active',
                                'bg-danger': amc.merchantDetail.status === 'inactive',
                                'bg-warning': amc.merchantDetail.status === 'pending'
                            }">
                                @{{ amc.merchantDetail.status | uppercase }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Mode:</strong>
                            <span class="badge" ng-class="{'bg-warning': amc.merchantDetail.test_mode, 'bg-info': !amc.merchantDetail.test_mode}">
                                @{{ amc.merchantDetail.test_mode ? 'TEST' : 'LIVE' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Created At:</strong> @{{ amc.merchantDetail.created_at | date:'MMM d, y HH:mm' }}
                        </div>
                    </div>
                    <div class="row mb-3" ng-if="amc.merchantDetail.acquirer_account">
                        <div class="col-md-6">
                            <strong>Acquirer:</strong> @{{ amc.merchantDetail.acquirer_account.acquirer_name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Acquirer Mode:</strong> @{{ amc.merchantDetail.acquirer_account.mode | uppercase }}
                        </div>
                    </div>
                </div>
                <div class="modal-body text-center py-4" ng-if="!amc.merchantDetail">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading merchant details...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk action confirmation modal (inside controller scope) -->
    <div class="modal fade" id="bulkConfirmModal" tabindex="-1" aria-labelledby="bulkConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkConfirmLabel">@{{ amc.bulkConfirmTitle }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">@{{ amc.bulkConfirmMessage }}</p>
                    <p class="text-muted small mb-0" ng-if="amc.selectedIds.length">
                        @{{ amc.selectedIds.length }} merchant(s) selected.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button"
                            class="btn btn-sm"
                            ng-class="amc.bulkConfirmBtnClass"
                            data-bs-dismiss="modal"
                            ng-click="amc.confirmBulk()">
                        @{{ amc.bulkConfirmButtonLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.merchants.angular.main_controller')
