@extends('layouts.app-sidebar')

@section('title', 'Settlement Summary - Merchant - ' . config('app.name'))
@section('page-title', 'Settlement Summary')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantSettlementSummaryController as mssc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Settlement Summary']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Settlement Summary</h2>
            <p class="text-muted">List of Settlements</p>
        </div>
    </div>

    <!-- Date Range and Advanced Filter -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range:</label>
                <input type="text" class="form-control" ng-model="mssc.dateRange" placeholder="Leave empty to show all dates">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Advanced Filter</button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mssc.pagination.per_page" ng-change="mssc.loadSettlements()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mssc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mssc.loadSettlements()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mssc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mssc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mssc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="mssc.markAsSettled()" ng-disabled="!mssc.hasSelected()">
                    <i class="bi bi-pencil"></i> Mark as Settled
                </button>
                <button class="btn btn-sm btn-warning" ng-click="mssc.bounceSettlement()" ng-disabled="!mssc.hasSelected()">
                    Bounce Settlement
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="mssc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlements...</p>
            </div>
        </div>

        <div ng-hide="mssc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="mssc.selectAll" ng-change="mssc.toggleSelectAll()">
                            </th>
                            <th ng-show="mssc.visibleColumns.settlement_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Id</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="mssc.sortBy('settlement_id')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_settlement_id" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.partner_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Partner Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_partner_id" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.partner_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Partner Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_partner_name" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.payout_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payout Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_payout_amount" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.settlement_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mssc.filters.filter_settlement_status" ng-change="mssc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="settled">Settled</option>
                                    <option value="bounced">Bounced</option>
                                    <option value="processing">Processing</option>
                                </select>
                            </th>
                            <th ng-show="mssc.visibleColumns.settlement_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Date</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="mssc.sortBy('settlement_date')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="mssc.filters.filter_settlement_date" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.bank_reference.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Reference</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_bank_reference" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.account_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_account_name" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.account_number.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account Number</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_account_number" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.ifsc_code.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>IFSC Code</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_ifsc_code" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.bank_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_bank_name" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.bank_branch.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Branch</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_bank_branch" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.settlement_description.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Description</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mssc.filters.filter_settlement_description" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.payment_start_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Start Date</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="mssc.filters.filter_payment_start_date" ng-change="mssc.applyFilters()">
                            </th>
                            <th ng-show="mssc.visibleColumns.payment_end_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment End Date</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="mssc.filters.filter_payment_end_date" ng-change="mssc.applyFilters()">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mssc.settlements.length === 0">
                            <td colspan="16" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="settlement in mssc.settlements track by $index">
                            <td>
                                <input type="checkbox" ng-model="settlement.selected" ng-click="mssc.updateSelectAll()">
                            </td>
                            <td ng-show="mssc.visibleColumns.settlement_id.visible">@{{ settlement.settlement_id }}</td>
                            <td ng-show="mssc.visibleColumns.partner_id.visible">@{{ settlement.partner_id }}</td>
                            <td ng-show="mssc.visibleColumns.partner_name.visible">@{{ settlement.partner_name }}</td>
                            <td ng-show="mssc.visibleColumns.payout_amount.visible">@{{ settlement.payout_amount }}</td>
                            <td ng-show="mssc.visibleColumns.settlement_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': settlement.settlement_status === 'settled',
                                    'bg-warning': settlement.settlement_status === 'pending',
                                    'bg-danger': settlement.settlement_status === 'bounced',
                                    'bg-info': settlement.settlement_status === 'processing'
                                }">
                                    @{{ settlement.settlement_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="mssc.visibleColumns.settlement_date.visible">@{{ settlement.settlement_date | date:'MM/dd/yyyy' }}</td>
                            <td ng-show="mssc.visibleColumns.bank_reference.visible">@{{ settlement.bank_reference }}</td>
                            <td ng-show="mssc.visibleColumns.account_name.visible">@{{ settlement.account_name }}</td>
                            <td ng-show="mssc.visibleColumns.account_number.visible">@{{ settlement.account_number }}</td>
                            <td ng-show="mssc.visibleColumns.ifsc_code.visible">@{{ settlement.ifsc_code }}</td>
                            <td ng-show="mssc.visibleColumns.bank_name.visible">@{{ settlement.bank_name }}</td>
                            <td ng-show="mssc.visibleColumns.bank_branch.visible">@{{ settlement.bank_branch }}</td>
                            <td ng-show="mssc.visibleColumns.settlement_description.visible">@{{ settlement.settlement_description }}</td>
                            <td ng-show="mssc.visibleColumns.payment_start_date.visible">@{{ settlement.payment_start_date | date:'MM/dd/yyyy' }}</td>
                            <td ng-show="mssc.visibleColumns.payment_end_date.visible">@{{ settlement.payment_end_date | date:'MM/dd/yyyy' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (mssc.pagination.current_page - 1) * mssc.pagination.per_page + 1 }} to @{{ Math.min(mssc.pagination.current_page * mssc.pagination.per_page, mssc.pagination.total) }} of @{{ mssc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mssc.changePage(mssc.pagination.current_page - 1)" 
                            ng-disabled="mssc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mssc.changePage(mssc.pagination.current_page + 1)" 
                            ng-disabled="mssc.pagination.current_page === mssc.pagination.last_page">
                        Next
                    </button>
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
            app.controller('MerchantSettlementSummaryController', ['$http', function($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.settlements = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.selectAll = false;
                vm.dateRange = '';
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    settlement_id: { visible: true, label: 'Settlement Id' },
                    partner_id: { visible: true, label: 'Partner Id' },
                    partner_name: { visible: true, label: 'Partner Name' },
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
                        date_range: vm.dateRange,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/merchant/settlements/summary/data', { params: params }).then(function(response) {
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

                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadSettlements();
                };

                vm.toggleSelectAll = function() {
                    vm.settlements.forEach(function(settlement) {
                        settlement.selected = vm.selectAll;
                    });
                };

                vm.updateSelectAll = function() {
                    vm.selectAll = vm.settlements.every(function(settlement) {
                        return settlement.selected;
                    });
                };

                vm.hasSelected = function() {
                    return vm.settlements.some(function(settlement) {
                        return settlement.selected;
                    });
                };

                vm.markAsSettled = function() {
                    var selected = vm.settlements.filter(function(s) { return s.selected; }).map(function(s) { return s.id; });
                    if (selected.length === 0) return;
                    
                    $http.post('/merchant/settlements/summary/mark-settled', { ids: selected }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Settlements marked as settled');
                            vm.loadSettlements();
                        }
                    });
                };

                vm.bounceSettlement = function() {
                    alert('Bounce settlement functionality');
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

