@extends('layouts.app-sidebar')

@section('title', 'Pending Settlement - Admin - ' . config('app.name'))
@section('page-title', 'Pending Settlement')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPendingSettlementController as apsc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Pending Settlement']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Pending Settlement</h2>
            <p class="text-muted">List of Pending Settlements</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="apsc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="apsc.pagination.per_page" ng-change="apsc.loadSettlements()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="apsc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="apsc.loadSettlements()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in apsc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="apsc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="apsc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="apsc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading pending settlements...</p>
            </div>
        </div>

        <div ng-hide="apsc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th ng-show="apsc.visibleColumns.settlement_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="apsc.filters.filter_settlement_id" ng-change="apsc.applyFilters()">
                            </th>
                            <th ng-show="apsc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="apsc.filters.filter_merchant_id" ng-change="apsc.applyFilters()">
                            </th>
                            <th ng-show="apsc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="apsc.filters.filter_merchant_name" ng-change="apsc.applyFilters()">
                            </th>
                            <th ng-show="apsc.visibleColumns.payout_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payout Amount</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.settlement_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="apsc.filters.filter_settlement_status" ng-change="apsc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </th>
                            <th ng-show="apsc.visibleColumns.settlement_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Date</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.bank_reference.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Reference</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.account_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account Name</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.account_number.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account Number</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.ifsc_code.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>IFSC Code</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.bank_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Name</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.bank_branch.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Branch</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.settlement_description.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Description</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.payment_start_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Start Date</span>
                                </div>
                            </th>
                            <th ng-show="apsc.visibleColumns.payment_end_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment End Date</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="apsc.settlements.length === 0">
                            <td colspan="16" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="settlement in apsc.settlements track by $index">
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="apsc.viewSettlement(settlement)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                            <td ng-show="apsc.visibleColumns.settlement_id.visible">@{{ settlement.settlement_id }}</td>
                            <td ng-show="apsc.visibleColumns.merchant_id.visible">@{{ settlement.merchant_id }}</td>
                            <td ng-show="apsc.visibleColumns.merchant_name.visible">@{{ settlement.merchant_name }}</td>
                            <td ng-show="apsc.visibleColumns.payout_amount.visible">@{{ settlement.payout_amount }}</td>
                            <td ng-show="apsc.visibleColumns.settlement_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': settlement.settlement_status === 'completed',
                                    'bg-warning': settlement.settlement_status === 'pending',
                                    'bg-info': settlement.settlement_status === 'processing'
                                }">
                                    @{{ settlement.settlement_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="apsc.visibleColumns.settlement_date.visible">@{{ settlement.settlement_date }}</td>
                            <td ng-show="apsc.visibleColumns.bank_reference.visible">@{{ settlement.bank_reference }}</td>
                            <td ng-show="apsc.visibleColumns.account_name.visible">@{{ settlement.account_name }}</td>
                            <td ng-show="apsc.visibleColumns.account_number.visible">@{{ settlement.account_number }}</td>
                            <td ng-show="apsc.visibleColumns.ifsc_code.visible">@{{ settlement.ifsc_code }}</td>
                            <td ng-show="apsc.visibleColumns.bank_name.visible">@{{ settlement.bank_name }}</td>
                            <td ng-show="apsc.visibleColumns.bank_branch.visible">@{{ settlement.bank_branch }}</td>
                            <td ng-show="apsc.visibleColumns.settlement_description.visible">@{{ settlement.settlement_description }}</td>
                            <td ng-show="apsc.visibleColumns.payment_start_date.visible">@{{ settlement.payment_start_date }}</td>
                            <td ng-show="apsc.visibleColumns.payment_end_date.visible">@{{ settlement.payment_end_date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (apsc.pagination.current_page - 1) * apsc.pagination.per_page + 1 }} to @{{ Math.min(apsc.pagination.current_page * apsc.pagination.per_page, apsc.pagination.total) }} of @{{ apsc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="apsc.changePage(apsc.pagination.current_page - 1)" 
                            ng-disabled="apsc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="apsc.changePage(apsc.pagination.current_page + 1)" 
                            ng-disabled="apsc.pagination.current_page === apsc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Details Modal -->
    <div class="modal fade" id="settlementDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" ng-if="apsc.selectedSettlement">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-receipt"></i> Settlement Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Settlement ID:</strong><br>
                            <code>@{{apsc.selectedSettlement.settlement_id}}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Merchant:</strong><br>
                            @{{apsc.selectedSettlement.merchant_name}} (ID: @{{apsc.selectedSettlement.merchant_id}})
                        </div>
                        <div class="col-md-6">
                            <strong>Payout Amount:</strong><br>
                            <span class="text-success fw-bold">@{{apsc.selectedSettlement.payout_amount}}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Settlement Status:</strong><br>
                            <span class="badge" ng-class="{
                                'bg-success': apsc.selectedSettlement.settlement_status === 'completed',
                                'bg-warning': apsc.selectedSettlement.settlement_status === 'pending',
                                'bg-info': apsc.selectedSettlement.settlement_status === 'processing'
                            }">
                                @{{apsc.selectedSettlement.settlement_status | uppercase}}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Settlement Date:</strong><br>
                            @{{apsc.selectedSettlement.settlement_date || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Bank Reference:</strong><br>
                            @{{apsc.selectedSettlement.bank_reference || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Account Name:</strong><br>
                            @{{apsc.selectedSettlement.account_name || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Account Number:</strong><br>
                            @{{apsc.selectedSettlement.account_number || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>IFSC Code:</strong><br>
                            @{{apsc.selectedSettlement.ifsc_code || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Bank Name:</strong><br>
                            @{{apsc.selectedSettlement.bank_name || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Bank Branch:</strong><br>
                            @{{apsc.selectedSettlement.bank_branch || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Payment Start Date:</strong><br>
                            @{{apsc.selectedSettlement.payment_start_date || '-'}}
                        </div>
                        <div class="col-md-6">
                            <strong>Payment End Date:</strong><br>
                            @{{apsc.selectedSettlement.payment_end_date || '-'}}
                        </div>
                        <div class="col-12" ng-if="apsc.selectedSettlement.settlement_description">
                            <strong>Settlement Description:</strong><br>
                            @{{apsc.selectedSettlement.settlement_description}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminPendingSettlementController', ['$http', function($http) {
                var vm = this;
                vm.settlements = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                
                vm.visibleColumns = {
                    settlement_id: { visible: true, label: 'Settlement Id' },
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    payout_amount: { visible: true, label: 'Payout Amount' },
                    settlement_status: { visible: true, label: 'Settlement Status' },
                    settlement_date: { visible: true, label: 'Settlement Date' },
                    bank_reference: { visible: true, label: 'Bank Reference' },
                    account_name: { visible: true, label: 'Account Name' },
                    account_number: { visible: true, label: 'Account Number' },
                    ifsc_code: { visible: true, label: 'IFSC Code' },
                    bank_name: { visible: true, label: 'Bank Name' },
                    bank_branch: { visible: true, label: 'Bank Branch' },
                    settlement_description: { visible: true, label: 'Settlement Description' },
                    payment_start_date: { visible: true, label: 'Payment Start Date' },
                    payment_end_date: { visible: true, label: 'Payment End Date' }
                };

                vm.loadSettlements = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.dateRange
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key] && vm.filters[key] !== 'all') {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/admin/manage-settlements/pending/data', { params: params }).then(function(response) {
                        vm.settlements = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading settlements:', error);
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadSettlements();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadSettlements();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.dateRange = '';
                    vm.applyFilters();
                };

                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns.hasOwnProperty(key)) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                vm.resetView = function() {
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.clearFilters();
                };

                vm.selectedSettlement = null;
                vm.viewSettlement = function(settlement) {
                    vm.selectedSettlement = settlement;
                    var modal = new bootstrap.Modal(document.getElementById('settlementDetailsModal'));
                    modal.show();
                };

                vm.loadSettlements();
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


