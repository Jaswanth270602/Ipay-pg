@extends('layouts.app-sidebar')

@section('title', 'Chargebacks - ' . config('app.name'))
@section('page-title', 'Chargebacks')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="MerchantChargebacksController as mcc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Chargebacks']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Chargebacks</h2>
            <p class="text-muted">View and create chargebacks for your account (current Test/Live mode).</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="mcc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mcc.pagination.per_page" ng-change="mcc.loadChargebacks()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mcc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mcc.loadChargebacks()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mcc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mcc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mcc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="btn btn-sm btn-primary" ng-click="mcc.openCreateModal()">
                    <i class="bi bi-plus-lg me-1"></i> New chargeback
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="mcc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading chargebacks...</p>
            </div>
        </div>

        <div ng-hide="mcc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="mcc.visibleColumns.chargeback_request_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Chargeback Request Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mcc.filters.filter_chargeback_request_id" ng-change="mcc.applyFilters()">
                            </th>
                            <th ng-show="mcc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                            </th>
                            <th ng-show="mcc.visibleColumns.chargeback_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Chargeback Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mcc.filters.filter_chargeback_status" ng-change="mcc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="disputed">Disputed</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                    <option value="withdrawn">Withdrawn</option>
                                </select>
                            </th>
                            <th ng-show="mcc.visibleColumns.chargeback_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Chargeback Amount</span>
                                </div>
                            </th>
                            <th ng-show="mcc.visibleColumns.chargeback_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Refunded</span>
                                </div>
                            </th>
                            <th ng-show="mcc.visibleColumns.chargeback_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Contested</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mcc.chargebacks.length === 0">
                            <td colspan="7" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="chargeback in mcc.chargebacks track by $index">
                            <td ng-show="mcc.visibleColumns.chargeback_request_id.visible">@{{ chargeback.chargeback_request_id }}</td>
                            <td ng-show="mcc.visibleColumns.transaction_id.visible">@{{ chargeback.transaction_id }}</td>
                            <td ng-show="mcc.visibleColumns.chargeback_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': chargeback.chargeback_status === 'won',
                                    'bg-warning': chargeback.chargeback_status === 'pending',
                                    'bg-info': chargeback.chargeback_status === 'disputed',
                                    'bg-danger': chargeback.chargeback_status === 'lost'
                                }">
                                    @{{ chargeback.chargeback_status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="mcc.visibleColumns.chargeback_amount.visible">@{{ chargeback.chargeback_amount }}</td>
                            <td ng-show="mcc.visibleColumns.chargeback_status.visible">@{{ chargeback.refunded_or_not }}</td>
                            <td ng-show="mcc.visibleColumns.chargeback_status.visible">@{{ chargeback.contested }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" ng-click="mcc.viewChargeback(chargeback)" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning ms-1"
                                        ng-if="chargeback.chargeback_status === 'pending' || chargeback.chargeback_status === 'processing'"
                                        ng-click="mcc.contestChargeback(chargeback)" title="Contest">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing <span ng-bind="mcc.paginationFrom"></span> to <span ng-bind="mcc.paginationTo"></span> of <span ng-bind="mcc.pagination.total"></span> entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mcc.changePage(mcc.pagination.current_page - 1)" 
                            ng-disabled="mcc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mcc.changePage(mcc.pagination.current_page + 1)" 
                            ng-disabled="mcc.pagination.current_page === mcc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('payments.partials.chargeback-create-modal', ['ng' => 'mcc'])
    @include('payments.partials.chargeback-detail-modal', ['ng' => 'mcc', 'showMerchant' => true])
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
            var app = angular.module('ipayApp');
            app.controller('MerchantChargebacksController', ['$http', '$timeout', function($http, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]');
                var csrfToken = csrf ? csrf.content : '';
                var createModalId = 'chargeback-create-modal-mcc';
                var storeUrl = @json(route('merchant.payments.chargebacks.store'));
                var lookupUrl = @json(route('merchant.payments.chargebacks.lookup-transaction'));
                var contestUrlBase = @json(url('/merchant/payments/chargebacks'));

                vm.chargebacks = [];
                vm.selectedChargeback = null;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.paginationFrom = 0;
                vm.paginationTo = 0;
                vm.filters = {};
                vm.loading = false;
                vm.createSubmitting = false;
                vm.createFormError = '';
                vm.lookupLoading = false;
                vm.txnPreview = null;
                vm.createForm = {
                    chargeback_request_id: '',
                    transaction_id: '',
                    chargeback_amount: null,
                    chargeback_status: 'pending',
                    target_date: '',
                    notes: ''
                };
                vm.dateRange = '';
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    chargeback_request_id: { visible: true, label: 'Chargeback Request Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    chargeback_status: { visible: true, label: 'Chargeback Status' },
                    chargeback_amount: { visible: true, label: 'Chargeback Amount' }
                };

                vm.refreshPaginationDisplay = function() {
                    var p = vm.pagination;
                    if (!p || !p.total) {
                        vm.paginationFrom = 0;
                        vm.paginationTo = 0;
                        return;
                    }
                    vm.paginationFrom = (p.current_page - 1) * p.per_page + 1;
                    vm.paginationTo = Math.min(p.current_page * p.per_page, p.total);
                };

                vm.notify = function(message, type) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, type || 'success');
                    } else if (typeof window.ipayAlert === 'function') {
                        window.ipayAlert(message, type === 'error' ? 'danger' : (type || 'info'));
                    }
                };

                vm.resetCreateForm = function() {
                    vm.createForm = {
                        chargeback_request_id: '',
                        transaction_id: '',
                        chargeback_amount: null,
                        chargeback_status: 'pending',
                        target_date: '',
                        notes: ''
                    };
                    vm.createFormError = '';
                    vm.txnPreview = null;
                };

                vm.lookupTransaction = function() {
                    var q = (vm.createForm.transaction_id || '').trim();
                    if (!q) {
                        vm.createFormError = 'Enter a payment reference to look up.';
                        return;
                    }
                    vm.lookupLoading = true;
                    vm.createFormError = '';
                    $http.get(lookupUrl, { params: { q: q } }).then(function(response) {
                        vm.lookupLoading = false;
                        if (response.data && response.data.success) {
                            vm.txnPreview = response.data.data;
                            vm.createForm.transaction_id = vm.txnPreview.txn_id;
                            if (!vm.createForm.chargeback_request_id) {
                                vm.createForm.chargeback_request_id = vm.txnPreview.suggested_request_id;
                            }
                            vm.createForm.chargeback_amount = vm.txnPreview.suggested_amount;
                            vm.createForm.target_date = vm.txnPreview.suggested_target_date;
                        }
                    }, function(error) {
                        vm.lookupLoading = false;
                        vm.txnPreview = null;
                        vm.createFormError = (error.data && error.data.message) ? error.data.message : 'Payment not found.';
                    });
                };

                vm.openCreateModal = function() {
                    vm.resetCreateForm();
                    vm.createSubmitting = false;
                    var el = document.getElementById(createModalId);
                    if (el && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static' }).show();
                    }
                };

                vm.closeCreateModal = function() {
                    var el = document.getElementById(createModalId);
                    if (el && window.bootstrap && window.bootstrap.Modal) {
                        var inst = window.bootstrap.Modal.getInstance(el);
                        if (inst) inst.hide();
                    }
                };

                vm.submitCreateChargeback = function($event) {
                    if ($event && $event.preventDefault) $event.preventDefault();
                    if (vm.createSubmitting) return;

                    vm.createFormError = '';
                    var payload = {
                        chargeback_request_id: (vm.createForm.chargeback_request_id || '').trim(),
                        transaction_id: (vm.createForm.transaction_id || '').trim(),
                        chargeback_amount: vm.createForm.chargeback_amount,
                        chargeback_status: vm.createForm.chargeback_status || 'pending',
                        target_date: vm.createForm.target_date || null,
                        notes: (vm.createForm.notes || '').trim() || null
                    };

                    if (!vm.txnPreview || !payload.transaction_id || !payload.chargeback_amount) {
                        vm.createFormError = 'Look up a valid payment first and enter an amount.';
                        return;
                    }

                    vm.createSubmitting = true;
                    $http.post(storeUrl, payload, {
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }
                    }).then(function(response) {
                        vm.createSubmitting = false;
                        if (response.data && response.data.success) {
                            vm.closeCreateModal();
                            vm.notify(response.data.message || 'Chargeback created.', 'success');
                            vm.pagination.current_page = 1;
                            vm.loadChargebacks();
                        } else {
                            vm.createFormError = (response.data && response.data.message) ? response.data.message : 'Could not create chargeback.';
                        }
                    }, function(error) {
                        vm.createSubmitting = false;
                        var msg = 'Failed to create chargeback.';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var firstKey = Object.keys(error.data.errors)[0];
                            if (firstKey && error.data.errors[firstKey][0]) {
                                msg = error.data.errors[firstKey][0];
                            }
                        }
                        vm.createFormError = msg;
                    });
                };

                vm.loadChargebacks = function() {
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
                    
                    $http.get('/merchant/payments/chargebacks/data', { params: params }).then(function(response) {
                        vm.chargebacks = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.refreshPaginationDisplay();
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        var errMsg = (error.data && error.data.message) ? error.data.message : 'Failed to load chargebacks.';
                        vm.notify(errMsg, 'error');
                        console.error('Error loading chargebacks:', error);
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadChargebacks();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadChargebacks();
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

                vm.contestChargeback = function(chargeback) {
                    if (!chargeback || !chargeback.id) return;
                    var notes = window.prompt('Contest notes (optional):', '') || '';
                    $http.post(contestUrlBase + '/' + chargeback.id + '/contest', {
                        notes: notes.trim() || null
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }
                    }).then(function(response) {
                        if (response.data && response.data.success) {
                            vm.notify(response.data.message, 'success');
                            vm.loadChargebacks();
                        }
                    }, function(error) {
                        var msg = (error.data && error.data.message) ? error.data.message : 'Failed to contest chargeback.';
                        vm.notify(msg, 'error');
                    });
                };

                vm.viewChargeback = function(chargeback) {
                    vm.selectedChargeback = chargeback;
                    $timeout(function() {
                        var el = document.getElementById('chargeback-detail-modal-mcc');
                        if (el && window.bootstrap && window.bootstrap.Modal) {
                            var inst = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el);
                            inst.show();
                        }
                    });
                };

                vm.loadChargebacks();
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
