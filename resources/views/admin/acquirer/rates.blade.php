@extends('layouts.app-sidebar')

@section('title', 'Acquirer Rates - Admin - ' . config('app.name'))
@section('page-title', 'Acquirer Rates')

@push('styles')
<style>
    .acquirer-rates-table-wrapper {
        overflow-x: auto;
        width: 100%;
    }
    
    .acquirer-rates-table-wrapper table {
        min-width: 2500px;
        width: 100%;
        table-layout: auto;
    }
    
    .acquirer-rates-table-wrapper thead th {
        white-space: nowrap;
        padding: 12px 8px;
        font-size: 13px;
        font-weight: 600;
        vertical-align: middle;
    }
    
    .acquirer-rates-table-wrapper tbody td {
        white-space: nowrap;
        padding: 10px 8px;
        font-size: 13px;
        vertical-align: middle;
    }
    
    .acquirer-rates-table-wrapper .form-control-sm,
    .acquirer-rates-table-wrapper .form-select-sm {
        min-width: 100px;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="AcquirerRatesController as arc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Acquirer Details'],
        ['label'=>'Rates']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>List of Acquirer Rates</h2>
            <p class="text-muted">Manage acquirer rates and configurations</p>
        </div>
    </div>

    <!-- Action Buttons and Controls -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="arc.pagination.per_page" ng-change="arc.loadRates()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.loadRates()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in arc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="arc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="arc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="arc.openNewModal()">
                    <i class="bi bi-plus-lg"></i> New
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="arc.openEditModal()" ng-disabled="!arc.selectedRate">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-info" ng-click="arc.duplicateRate()" ng-disabled="!arc.selectedRate">
                    <i class="bi bi-files"></i> Duplicate
                </button>
                <button class="btn btn-sm btn-outline-danger" ng-click="arc.deleteRate()" ng-disabled="!arc.selectedRate">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="arc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading acquirer rates...</p>
            </div>
        </div>

        <div ng-hide="arc.loading">
            <div class="table-responsive acquirer-rates-table-wrapper">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th style="min-width: 50px; width: 50px;"></th>
                            <th ng-show="arc.visibleColumns.id.visible" style="min-width: 60px; width: 80px;">Id</th>
                            <th ng-show="arc.visibleColumns.payment_mode.visible" style="min-width: 130px; width: 150px;">Payment Mode</th>
                            <th ng-show="arc.visibleColumns.bank_code.visible" style="min-width: 100px; width: 120px;">Bank Code</th>
                            <th ng-show="arc.visibleColumns.bank_description.visible" style="min-width: 150px; width: 200px;">Bank Description</th>
                            <th ng-show="arc.visibleColumns.acquirer_name.visible" style="min-width: 130px; width: 150px;">Acquirer Name</th>
                            <th ng-show="arc.visibleColumns.account_id.visible" style="min-width: 120px; width: 150px;">Account Id</th>
                            <th ng-show="arc.visibleColumns.account_description.visible" style="min-width: 150px; width: 200px;">Account Description</th>
                            <th ng-show="arc.visibleColumns.sector.visible" style="min-width: 100px; width: 120px;">Sector</th>
                            <th ng-show="arc.visibleColumns.settlement_time_frame.visible" style="min-width: 120px; width: 150px;">Settlement Time Frame</th>
                            <th ng-show="arc.visibleColumns.settlement_time_of_day.visible" style="min-width: 150px; width: 180px;">Settlement Time of Day</th>
                            <th ng-show="arc.visibleColumns.fixed_fee_mdr.visible" style="min-width: 120px; width: 150px;">Fixed Fee Mdr</th>
                            <th ng-show="arc.visibleColumns.percentage_mdr.visible" style="min-width: 120px; width: 150px;">Percentage Mdr</th>
                            <th ng-show="arc.visibleColumns.service_tax_rates.visible" style="min-width: 130px; width: 150px;">Service Tax Rates</th>
                            <th ng-show="arc.visibleColumns.min_amount.visible" style="min-width: 120px; width: 150px;">Min Amount</th>
                            <th ng-show="arc.visibleColumns.max_amount.visible" style="min-width: 120px; width: 150px;">Max Amount</th>
                            <th ng-show="arc.visibleColumns.min_transaction_charge.visible" style="min-width: 180px; width: 200px;">Min Transaction Charge</th>
                            <th ng-show="arc.visibleColumns.max_transaction_charge.visible" style="min-width: 180px; width: 200px;">Max Transaction Charge</th>
                            <th ng-show="arc.visibleColumns.is_enabled.visible" style="min-width: 100px; width: 120px;">Enabled?</th>
                            <th ng-show="arc.visibleColumns.part_paid_id.visible" style="min-width: 120px; width: 150px;">Part Paid Id</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th ng-show="arc.visibleColumns.id.visible" style="min-width: 60px; width: 80px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.payment_mode.visible" style="min-width: 130px; width: 150px;">
                                <select class="form-select form-select-sm" ng-model="arc.filters.filter_payment_mode" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="mode in arc.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.bank_code.visible" style="min-width: 100px; width: 120px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_bank_code" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.bank_description.visible" style="min-width: 150px; width: 200px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_bank_description" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.acquirer_name.visible" style="min-width: 130px; width: 150px;">
                                <select class="form-select form-select-sm" ng-model="arc.filters.filter_acquirer_name" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="name in arc.acquirerNames" value="@{{ name }}">@{{ name }}</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.account_id.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_account_id" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.account_description.visible" style="min-width: 150px; width: 200px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_account_description" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.sector.visible" style="min-width: 100px; width: 120px;">
                                <select class="form-select form-select-sm" ng-model="arc.filters.filter_sector" ng-change="arc.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="sector in arc.sectors" value="@{{ sector }}">@{{ sector }}</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.settlement_time_frame.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_settlement_time_frame" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.settlement_time_of_day.visible" style="min-width: 150px; width: 180px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_settlement_time_of_day" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.fixed_fee_mdr.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_fixed_fee_mdr" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.percentage_mdr.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_percentage_mdr" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.service_tax_rates.visible" style="min-width: 130px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_service_tax_rates" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.min_amount.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_min_amount" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.max_amount.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_max_amount" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.min_transaction_charge.visible" style="min-width: 180px; width: 200px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_min_transaction_charge" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.max_transaction_charge.visible" style="min-width: 180px; width: 200px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_max_transaction_charge" ng-change="arc.applyFilters()">
                            </th>
                            <th ng-show="arc.visibleColumns.is_enabled.visible" style="min-width: 100px; width: 120px;">
                                <select class="form-select form-select-sm">
                                    <option value="all">All</option>
                                </select>
                            </th>
                            <th ng-show="arc.visibleColumns.part_paid_id.visible" style="min-width: 120px; width: 150px;">
                                <input type="text" class="form-control form-control-sm" placeholder="Filter..." ng-model="arc.filters.filter_part_paid_id" ng-change="arc.applyFilters()">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="rate in arc.rates" ng-click="arc.selectRate(rate)" ng-class="{'table-active': arc.selectedRate && arc.selectedRate.id === rate.id}">
                            <td>
                                <input type="radio" name="selectedRate" ng-model="arc.selectedRate" ng-value="rate">
                            </td>
                            <td ng-show="arc.visibleColumns.id.visible">@{{ rate.id }}</td>
                            <td ng-show="arc.visibleColumns.payment_mode.visible">@{{ rate.payment_mode }}</td>
                            <td ng-show="arc.visibleColumns.bank_code.visible">@{{ rate.bank_code }}</td>
                            <td ng-show="arc.visibleColumns.bank_description.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="@{{ rate.bank_description }}">@{{ rate.bank_description }}</td>
                            <td ng-show="arc.visibleColumns.acquirer_name.visible">@{{ rate.acquirer_name }}</td>
                            <td ng-show="arc.visibleColumns.account_id.visible">@{{ rate.account_id }}</td>
                            <td ng-show="arc.visibleColumns.account_description.visible" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="@{{ rate.account_description }}">@{{ rate.account_description }}</td>
                            <td ng-show="arc.visibleColumns.sector.visible">@{{ rate.sector }}</td>
                            <td ng-show="arc.visibleColumns.settlement_time_frame.visible">@{{ rate.settlement_time_frame }}</td>
                            <td ng-show="arc.visibleColumns.settlement_time_of_day.visible">@{{ rate.settlement_time_of_day }}</td>
                            <td ng-show="arc.visibleColumns.fixed_fee_mdr.visible">@{{ rate.fixed_fee_mdr | number:4 }}</td>
                            <td ng-show="arc.visibleColumns.percentage_mdr.visible">@{{ rate.percentage_mdr | number:4 }}%</td>
                            <td ng-show="arc.visibleColumns.service_tax_rates.visible">@{{ rate.service_tax_rates | number:4 }}%</td>
                            <td ng-show="arc.visibleColumns.min_amount.visible">@{{ rate.min_amount | number:2 }}</td>
                            <td ng-show="arc.visibleColumns.max_amount.visible">@{{ rate.max_amount | number:2 }}</td>
                            <td ng-show="arc.visibleColumns.min_transaction_charge.visible">@{{ rate.min_transaction_charge | number:2 }}</td>
                            <td ng-show="arc.visibleColumns.max_transaction_charge.visible">@{{ rate.max_transaction_charge | number:2 }}</td>
                            <td ng-show="arc.visibleColumns.is_enabled.visible">
                                <span class="badge" ng-class="rate.is_enabled ? 'bg-success' : 'bg-danger'">@{{ rate.is_enabled ? 'Yes' : 'No' }}</span>
                            </td>
                            <td ng-show="arc.visibleColumns.part_paid_id.visible">@{{ rate.part_paid_id }}</td>
                        </tr>
                        <tr ng-if="arc.rates.length === 0">
                            <td colspan="20" class="text-center py-4 text-muted">
                                No matching records found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ ((arc.pagination.current_page - 1) * arc.pagination.per_page) + 1 }} to @{{ Math.min(arc.pagination.current_page * arc.pagination.per_page, arc.pagination.total) }} of @{{ arc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="arc.loadRates(arc.pagination.current_page - 1)" ng-disabled="arc.pagination.current_page === 1">
                        Previous
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="arc.loadRates(arc.pagination.current_page + 1)" ng-disabled="arc.pagination.current_page >= arc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @include('admin.acquirer.partials.rate-modal')
    
    @include('admin.acquirer.angular.rates_controller')
</div>
@endsection
