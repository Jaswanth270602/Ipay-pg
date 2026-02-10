@extends('layouts.app-sidebar')

@section('title', 'Partner Settlement Summary - Admin - ' . config('app.name'))
@section('page-title', 'Partner Settlement Summary')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPartnerSettlementsSummaryController as pss">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Partner Settlement']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Partner Settlement Summary</h2>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="pss.pagination.per_page" ng-change="pss.loadSettlements()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="pss.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="pss.loadSettlements()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.organization_name" checked> Organization Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.settlement_id" checked> Settlement ID</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.partner_name" checked> Partner Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.settlement_amount" checked> Settlement Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.net_settlement_amount" checked> Net Settlement Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.tds_percentage" checked> TDS Percentage</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.tds_amount" checked> TDS Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.gst_amount" checked> GST Amount</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.settlement_status" checked> Settlement Status</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="pss.visibleColumns.settlement_date" checked> Settlement Date</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-primary" ng-click="pss.markAsSettled()" ng-disabled="!pss.hasSelection()">
                    <i class="bi bi-pencil"></i> Mark as Settled
                </button>
                <button class="btn btn-sm btn-outline-success" ng-click="pss.transferByIMPS()" ng-disabled="!pss.hasSelection()">
                    <i class="bi bi-currency-dollar"></i> Transfer Amount By IMPS
                </button>
                <button class="btn btn-sm btn-outline-info" ng-click="pss.transferByNEFT()" ng-disabled="!pss.hasSelection()">
                    <i class="bi bi-currency-dollar"></i> Transfer Amount By NEFT
                </button>
                <button class="btn btn-sm btn-outline-warning" ng-click="pss.checkStatus()" ng-disabled="!pss.hasSelection()">
                    <i class="bi bi-currency-dollar"></i> Check Status
                </button>
            </div>
        </div>
    </div>

    <!-- Settlements Table -->
    <div class="stat-card">
        <div ng-show="pss.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlements...</p>
            </div>
        </div>

        <div ng-hide="pss.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="pss.selectAll" ng-change="pss.toggleSelectAll()"> Organization Name
                            </th>
                            <th>Settlement ID</th>
                            <th>Partner Name</th>
                            <th>Settlement Amount</th>
                            <th>Net Settlement Amount</th>
                            <th>TDS Percentage</th>
                            <th>TDS Amount</th>
                            <th>GST Amount</th>
                            <th>Settlement Status</th>
                            <th>Settlement Date</th>
                            <th>Bank Reference Id</th>
                            <th>Account Holder Name</th>
                            <th>Account Number</th>
                            <th>Bank Name</th>
                            <th>Bank IFSC</th>
                            <th>Settlement Start Time</th>
                            <th>Settlement End Time</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th>
                                <select class="form-select form-select-sm" ng-model="pss.filters.organization_name" ng-change="pss.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="org in pss.organizations" value="@{{ org }}">@{{ org }}</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.settlement_id" ng-change="pss.applyFilters()" placeholder="Settlement ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.partner_name" ng-change="pss.applyFilters()" placeholder="Partner Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.settlement_amount" ng-change="pss.applyFilters()" placeholder="Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.net_settlement_amount" ng-change="pss.applyFilters()" placeholder="Net Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.tds_percentage" ng-change="pss.applyFilters()" placeholder="TDS %"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.tds_amount" ng-change="pss.applyFilters()" placeholder="TDS Amount"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.gst_amount" ng-change="pss.applyFilters()" placeholder="GST Amount"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="pss.filters.settlement_status" ng-change="pss.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="settled">Settled</option>
                                    <option value="bounced">Bounced</option>
                                    <option value="processing">Processing</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.settlement_date" placeholder="MM/DD/YYYY-MM" ng-change="pss.applyFilters()"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.bank_reference_id" ng-change="pss.applyFilters()" placeholder="Bank Ref ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.account_holder_name" ng-change="pss.applyFilters()" placeholder="Account Holder"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.account_number" ng-change="pss.applyFilters()" placeholder="Account Number"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.bank_name" ng-change="pss.applyFilters()" placeholder="Bank Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="pss.filters.bank_ifsc" ng-change="pss.applyFilters()" placeholder="Bank IFSC"></th>
                            <th><input type="datetime-local" class="form-control form-control-sm" ng-model="pss.filters.settlement_start_time" ng-change="pss.applyFilters()"></th>
                            <th><input type="datetime-local" class="form-control form-control-sm" ng-model="pss.filters.settlement_end_time" ng-change="pss.applyFilters()"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="pss.settlements.length === 0">
                            <td colspan="18" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="settlement in pss.settlements track by settlement.id"
                            ng-class="{'table-active': pss.selectedSettlement && pss.selectedSettlement.id === settlement.id}"
                            ng-click="pss.selectSettlement(settlement)">
                            <td>
                                <input type="checkbox" ng-model="settlement.selected" ng-click="$event.stopPropagation(); pss.updateSelectionState()">
                                @{{ settlement.organization_name || '-' }}
                            </td>
                            <td>@{{ settlement.settlement_id || '-' }}</td>
                            <td>@{{ settlement.partner_name || '-' }}</td>
                            <td>@{{ settlement.settlement_amount || '0.00' }}</td>
                            <td>@{{ settlement.net_settlement_amount || '0.00' }}</td>
                            <td>@{{ settlement.tds_percentage || '0.00' }}</td>
                            <td>@{{ settlement.tds_amount || '0.00' }}</td>
                            <td>@{{ settlement.gst_amount || '0.00' }}</td>
                            <td>
                                <span class="badge" ng-class="{
                                    'bg-warning': settlement.settlement_status === 'pending',
                                    'bg-success': settlement.settlement_status === 'settled',
                                    'bg-danger': settlement.settlement_status === 'bounced' || settlement.settlement_status === 'failed',
                                    'bg-info': settlement.settlement_status === 'processing'
                                }">
                                    @{{ settlement.settlement_status | uppercase }}
                                </span>
                            </td>
                            <td>@{{ settlement.settlement_date || '-' }}</td>
                            <td>@{{ settlement.bank_reference_id || '-' }}</td>
                            <td>@{{ settlement.account_holder_name || '-' }}</td>
                            <td>@{{ settlement.account_number || '-' }}</td>
                            <td>@{{ settlement.bank_name || '-' }}</td>
                            <td>@{{ settlement.bank_ifsc || '-' }}</td>
                            <td>@{{ settlement.settlement_start_time || '-' }}</td>
                            <td>@{{ settlement.settlement_end_time || '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" ng-click="pss.viewSettlement(settlement); $event.stopPropagation();" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" ng-click="pss.editSettlement(settlement); $event.stopPropagation();" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (pss.pagination.current_page - 1) * pss.pagination.per_page + 1 }}
                    to @{{ Math.min(pss.pagination.current_page * pss.pagination.per_page, pss.pagination.total) }}
                    of @{{ pss.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="pss.changePage(pss.pagination.current_page - 1)"
                            ng-disabled="pss.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ pss.pagination.current_page }} of @{{ pss.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="pss.changePage(pss.pagination.current_page + 1)"
                            ng-disabled="pss.pagination.current_page === pss.pagination.last_page">
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
(function () {
    'use strict';

    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }

        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminPartnerSettlementsSummaryController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.settlements = [];
                vm.organizations = [];
                vm.loading = false;
                vm.selectedSettlement = null;
                vm.selectAll = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    organization_name: true,
                    settlement_id: true,
                    partner_name: true,
                    settlement_amount: true,
                    net_settlement_amount: true,
                    tds_percentage: true,
                    tds_amount: true,
                    gst_amount: true,
                    settlement_status: true,
                    settlement_date: true
                };

                vm.filters = {
                    organization_name: 'all',
                    settlement_id: '',
                    partner_name: '',
                    settlement_amount: '',
                    net_settlement_amount: '',
                    tds_percentage: '',
                    tds_amount: '',
                    gst_amount: '',
                    settlement_status: 'all',
                    settlement_date: '',
                    bank_reference_id: '',
                    account_holder_name: '',
                    account_number: '',
                    bank_name: '',
                    bank_ifsc: '',
                    settlement_start_time: '',
                    settlement_end_time: ''
                };

                vm.loadOrganizations = function () {
                    $http.get("{{ route('admin.partner-settlements.organizations') }}").then(function (response) {
                        vm.organizations = response.data.data || [];
                    });
                };

                vm.loadSettlements = function () {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function (key) {
                        if (vm.filters[key] !== undefined && vm.filters[key] !== null && vm.filters[key] !== '' && vm.filters[key] !== 'all') {
                            params[key] = vm.filters[key];
                        }
                    });

                    $http.get("{{ route('admin.partner-settlements.data') }}", { params: params })
                        .then(function (response) {
                            vm.settlements = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                            vm.selectAll = false;
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load settlements', 'error');
                            } else {
                                alert('Failed to load settlements');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadSettlements();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadSettlements();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        organization_name: 'all',
                        settlement_id: '',
                        partner_name: '',
                        settlement_amount: '',
                        net_settlement_amount: '',
                        tds_percentage: '',
                        tds_amount: '',
                        gst_amount: '',
                        settlement_status: 'all',
                        settlement_date: '',
                        bank_reference_id: '',
                        account_holder_name: '',
                        account_number: '',
                        bank_name: '',
                        bank_ifsc: '',
                        settlement_start_time: '',
                        settlement_end_time: ''
                    };
                    vm.applyFilters();
                };

                vm.toggleSelectAll = function () {
                    vm.settlements.forEach(function (s) {
                        s.selected = vm.selectAll;
                    });
                };

                vm.updateSelectionState = function () {
                    vm.selectAll = vm.settlements.length > 0 && vm.settlements.every(function (s) { return s.selected; });
                };

                vm.hasSelection = function () {
                    return vm.settlements.some(function (s) { return s.selected; });
                };

                vm.getSelectedIds = function () {
                    return vm.settlements.filter(function (s) { return s.selected; }).map(function (s) { return s.id; });
                };

                vm.selectSettlement = function (settlement) {
                    vm.selectedSettlement = settlement;
                };

                vm.markAsSettled = function () {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;

                    $http.post("{{ route('admin.partner-settlements.mark-settled') }}", {
                        settlement_ids: ids
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'Settlements marked as settled', 'success');
                            } else {
                                alert(response.data.message || 'Settlements marked as settled');
                            }
                            vm.loadSettlements();
                        }
                    }, function (error) {
                        var msg = 'Failed to mark as settled';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.transferByIMPS = function () {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;

                    $http.post("{{ route('admin.partner-settlements.transfer-imps') }}", {
                        settlement_ids: ids
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'IMPS transfer initiated', 'success');
                            } else {
                                alert(response.data.message || 'IMPS transfer initiated');
                            }
                            vm.loadSettlements();
                        }
                    }, function (error) {
                        var msg = 'Failed to initiate IMPS transfer';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.transferByNEFT = function () {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;

                    $http.post("{{ route('admin.partner-settlements.transfer-neft') }}", {
                        settlement_ids: ids
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'NEFT transfer initiated', 'success');
                            } else {
                                alert(response.data.message || 'NEFT transfer initiated');
                            }
                            vm.loadSettlements();
                        }
                    }, function (error) {
                        var msg = 'Failed to initiate NEFT transfer';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.checkStatus = function () {
                    var ids = vm.getSelectedIds();
                    if (!ids.length) return;

                    $http.post("{{ route('admin.partner-settlements.check-status') }}", {
                        settlement_ids: ids
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            var statuses = response.data.data || [];
                            var statusMsg = 'Transfer Status:\n';
                            statuses.forEach(function (s) {
                                statusMsg += s.settlement_id + ': ' + (s.transfer_status || 'N/A') + ' (' + (s.transfer_method || 'N/A') + ')\n';
                            });
                            alert(statusMsg);
                            vm.loadSettlements();
                        }
                    }, function (error) {
                        var msg = 'Failed to check status';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.viewSettlement = function (settlement) {
                    // TODO: Implement view functionality
                    alert('View Settlement: ' + settlement.settlement_id);
                };

                vm.editSettlement = function (settlement) {
                    // TODO: Implement edit functionality
                    alert('Edit Settlement: ' + settlement.settlement_id);
                };

                // Initialize
                vm.loadOrganizations();
                vm.loadSettlements();
            }]);
        } catch (e) {
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

