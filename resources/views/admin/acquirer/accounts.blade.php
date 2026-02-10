@extends('layouts.app-sidebar')

@section('title', 'Acquirer Accounts - Admin - ' . config('app.name'))
@section('page-title', 'Acquirer Details')

@push('styles')
<style>
    .acquirer-table-wrapper {
        overflow-x: auto;
        width: 100%;
    }
    
    .acquirer-table-wrapper table {
        min-width: 2000px;
        width: 100%;
        table-layout: auto;
    }
    
    .acquirer-table-wrapper thead th {
        white-space: nowrap;
        padding: 12px 8px;
        font-size: 13px;
        font-weight: 600;
        vertical-align: middle;
    }
    
    .acquirer-table-wrapper tbody td {
        white-space: nowrap;
        padding: 10px 8px;
        font-size: 13px;
        vertical-align: middle;
    }
    
    .acquirer-table-wrapper .form-control-sm,
    .acquirer-table-wrapper .form-select-sm {
        min-width: 100px;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminAcquirerAccountsController as aac">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Acquirer Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>List of Acquirer Accounts</h2>
            <p class="text-muted">Manage acquirer accounts and configurations</p>
        </div>
    </div>

    <div id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <label class="form-label me-2">Show</label>
                        <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="aac.pagination.per_page" ng-change="aac.loadAccounts()">
                            <option value="5">5 entries</option>
                            <option value="10">10 entries</option>
                            <option value="25">25 entries</option>
                            <option value="50">50 entries</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-secondary" ng-click="aac.clearFilters()">
                            <i class="bi bi-funnel"></i> Clear Filters
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" ng-click="aac.loadAccounts()">
                            <i class="bi bi-arrow-clockwise"></i> Reload
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-eye"></i> Columns
                            </button>
                            <ul class="dropdown-menu">
                                <li ng-repeat="(key, col) in aac.visibleColumns">
                                    <a class="dropdown-item" href="#" ng-click="aac.toggleColumn(key)">
                                        <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" ng-click="aac.resetView()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                        <button class="btn btn-sm btn-primary" ng-click="aac.openNewModal()">
                            <i class="bi bi-plus-lg"></i> New
                        </button>
                        <button class="btn btn-sm btn-outline-primary" ng-click="aac.openEditModal()" ng-disabled="!aac.selectedAccount">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" ng-click="aac.deleteAccount()" ng-disabled="!aac.selectedAccount">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" disabled>
                                <i class="bi bi-paperclip"></i> Manage Acquirer Account
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item disabled" href="#">Add Bank Codes</a></li>
                                <li><a class="dropdown-item disabled" href="#">Add Rates</a></li>
                                <li><a class="dropdown-item disabled" href="#">Duplicate Rates</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="stat-card">
                <div ng-show="aac.loading" class="loader-overlay position-relative" style="min-height: 400px;">
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted text-center">Loading acquirer accounts...</p>
                    </div>
                </div>

                <div ng-hide="aac.loading">
                    <div class="table-responsive acquirer-table-wrapper">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th style="min-width: 50px; width: 50px;"></th>
                                    <th ng-show="aac.visibleColumns.id.visible" style="min-width: 60px; width: 80px;">Id</th>
                                    <th ng-show="aac.visibleColumns.account_id.visible" style="min-width: 120px; width: 150px;">Account Id</th>
                                    <th ng-show="aac.visibleColumns.acquirer_name.visible" style="min-width: 130px; width: 150px;">Acquirer Name</th>
                                    <th ng-show="aac.visibleColumns.team.visible" style="min-width: 100px; width: 120px;">Team</th>
                                    <th ng-show="aac.visibleColumns.description.visible" style="min-width: 150px; width: 200px;">Description</th>
                                    <th ng-show="aac.visibleColumns.whitelist_url.visible" style="min-width: 150px; width: 200px;">Whitelist Url</th>
                                    <th ng-show="aac.visibleColumns.mode.visible" style="min-width: 80px; width: 100px;">Mode</th>
                                    <th ng-show="aac.visibleColumns.sector.visible" style="min-width: 100px; width: 120px;">Sector</th>
                                    <th ng-show="aac.visibleColumns.hdfc_me_code.visible" style="min-width: 120px; width: 150px;">Hdfc Me Code</th>
                                    <th ng-show="aac.visibleColumns.settlement_account_name.visible" style="min-width: 180px; width: 220px;">Settlement Account Name</th>
                                    <th ng-show="aac.visibleColumns.refund_allowed.visible" style="min-width: 120px; width: 140px;">Refund Allowed</th>
                                    <th ng-show="aac.visibleColumns.settlements_to_be_created.visible" style="min-width: 200px; width: 250px;">Settlements to be created for this TID ?</th>
                                    <th ng-show="aac.visibleColumns.mask_pii.visible" style="min-width: 100px; width: 120px;">Mask Pii</th>
                                    <th ng-show="aac.visibleColumns.email_ids.visible" style="min-width: 150px; width: 200px;">Email Ids</th>
                                    <th ng-show="aac.visibleColumns.live_request_url.visible" style="min-width: 180px; width: 220px;">Live Request URL</th>
                                    <th ng-show="aac.visibleColumns.live_query_url.visible" style="min-width: 180px; width: 220px;">Live Query URL</th>
                                    <th ng-show="aac.visibleColumns.live_refund_url.visible" style="min-width: 180px; width: 220px;">Live Refund URL</th>
                                    <th ng-show="aac.visibleColumns.test_request_url.visible" style="min-width: 180px; width: 220px;">Test Request URL</th>
                                    <th ng-show="aac.visibleColumns.test_query_url.visible" style="min-width: 180px; width: 220px;">Test Query URL</th>
                                    <th ng-show="aac.visibleColumns.test_refund_url.visible" style="min-width: 180px; width: 220px;">Test Refund URL</th>
                                    <th ng-show="aac.visibleColumns.merchants.visible" style="min-width: 150px; width: 200px;">Merchants</th>
                                    <th style="min-width: 80px; width: 100px;">Action</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th ng-show="aac.visibleColumns.id.visible" style="min-width: 60px; width: 80px;">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_id" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.account_id.visible" style="min-width: 120px; width: 150px;">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_account_id" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.acquirer_name.visible">
                                        <select class="form-select form-select-sm" ng-model="aac.filters.filter_acquirer_name" ng-change="aac.applyFilters()">
                                            <option value="all">All</option>
                                            <option ng-repeat="name in aac.acquirerNames" value="@{{ name }}">@{{ name }}</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.team.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_team" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.description.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_description" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.whitelist_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_whitelist_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.mode.visible">
                                        <select class="form-select form-select-sm" ng-model="aac.filters.filter_mode" ng-change="aac.applyFilters()">
                                            <option value="all">All</option>
                                            <option value="TEST">TEST</option>
                                            <option value="LIVE">LIVE</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.sector.visible">
                                        <select class="form-select form-select-sm" ng-model="aac.filters.filter_sector" ng-change="aac.applyFilters()">
                                            <option value="all">All</option>
                                            <option ng-repeat="sector in aac.sectors" value="@{{ sector }}">@{{ sector }}</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.hdfc_me_code.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_hdfc_me_code" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.settlement_account_name.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_settlement_account_name" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.refund_allowed.visible">
                                        <select class="form-select form-select-sm">
                                            <option value="all">All</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.settlements_to_be_created.visible">
                                        <select class="form-select form-select-sm">
                                            <option value="all">All</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.mask_pii.visible">
                                        <select class="form-select form-select-sm">
                                            <option value="all">All</option>
                                        </select>
                                    </th>
                                    <th ng-show="aac.visibleColumns.email_ids.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_email_ids" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.live_request_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_live_request_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.live_query_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_live_query_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.live_refund_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_live_refund_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.test_request_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_test_request_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.test_query_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_test_query_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.test_refund_url.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_test_refund_url" ng-change="aac.applyFilters()">
                                    </th>
                                    <th ng-show="aac.visibleColumns.merchants.visible">
                                        <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="aac.filters.filter_merchants" ng-change="aac.applyFilters()">
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="account in aac.accounts" ng-click="aac.selectAccount(account)" ng-class="{'table-active': aac.selectedAccount && aac.selectedAccount.id === account.id}">
                                    <td>
                                        <input type="radio" name="selectedAccount" ng-model="aac.selectedAccount" ng-value="account">
                                    </td>
                                    <td ng-show="aac.visibleColumns.id.visible">@{{ account.id }}</td>
                                    <td ng-show="aac.visibleColumns.account_id.visible">@{{ account.account_id }}</td>
                                    <td ng-show="aac.visibleColumns.acquirer_name.visible">@{{ account.acquirer_name }}</td>
                                    <td ng-show="aac.visibleColumns.team.visible">@{{ account.team }}</td>
                                    <td ng-show="aac.visibleColumns.description.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="@{{ account.description }}">@{{ account.description }}</td>
                                    <td ng-show="aac.visibleColumns.whitelist_url.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.whitelist_url }}</td>
                                    <td ng-show="aac.visibleColumns.mode.visible">
                                        <span class="badge" ng-class="account.mode === 'TEST' ? 'bg-warning' : 'bg-success'">@{{ account.mode }}</span>
                                    </td>
                                    <td ng-show="aac.visibleColumns.sector.visible">@{{ account.sector }}</td>
                                    <td ng-show="aac.visibleColumns.hdfc_me_code.visible">@{{ account.hdfc_me_code }}</td>
                                    <td ng-show="aac.visibleColumns.settlement_account_name.visible">@{{ account.settlement_account_name }}</td>
                                    <td ng-show="aac.visibleColumns.refund_allowed.visible">
                                        <span class="badge" ng-class="account.refund_allowed ? 'bg-success' : 'bg-danger'">@{{ account.refund_allowed ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td ng-show="aac.visibleColumns.settlements_to_be_created.visible">
                                        <span class="badge" ng-class="account.settlements_to_be_created ? 'bg-success' : 'bg-danger'">@{{ account.settlements_to_be_created ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td ng-show="aac.visibleColumns.mask_pii.visible">
                                        <span class="badge" ng-class="account.mask_pii ? 'bg-info' : 'bg-secondary'">@{{ account.mask_pii ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td ng-show="aac.visibleColumns.email_ids.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.email_ids }}</td>
                                    <td ng-show="aac.visibleColumns.live_request_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.live_request_url }}</td>
                                    <td ng-show="aac.visibleColumns.live_query_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.live_query_url }}</td>
                                    <td ng-show="aac.visibleColumns.live_refund_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.live_refund_url }}</td>
                                    <td ng-show="aac.visibleColumns.test_request_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.test_request_url }}</td>
                                    <td ng-show="aac.visibleColumns.test_query_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.test_query_url }}</td>
                                    <td ng-show="aac.visibleColumns.test_refund_url.visible" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.test_refund_url }}</td>
                                    <td ng-show="aac.visibleColumns.merchants.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ account.merchants }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" ng-click="aac.openEditModal(account); $event.stopPropagation();">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr ng-if="aac.accounts.length === 0">
                                    <td colspan="22" class="text-center py-4 text-muted">
                                        No data available in table
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing @{{ ((aac.pagination.current_page - 1) * aac.pagination.per_page) + 1 }} to @{{ Math.min(aac.pagination.current_page * aac.pagination.per_page, aac.pagination.total) }} of @{{ aac.pagination.total }} entries
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" ng-click="aac.loadAccounts(aac.pagination.current_page - 1)" ng-disabled="aac.pagination.current_page === 1">
                                Previous
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" ng-click="aac.loadAccounts(aac.pagination.current_page + 1)" ng-disabled="aac.pagination.current_page >= aac.pagination.last_page">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @include('admin.acquirer.partials.account-modal')
    
    @include('admin.acquirer.angular.accounts_controller')
</div>
@endsection

