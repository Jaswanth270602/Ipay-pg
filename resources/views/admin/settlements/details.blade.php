@extends('layouts.app-sidebar')

@section('title', 'Settlement Details - Admin - ' . config('app.name'))
@section('page-title', 'Settlement Details')

@section('content')
<div ng-app="ipayApp" ng-controller="AdminSettlementDetailsController as asdc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Settlement Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Settlement Details</h2>
            <p class="text-muted">List of Settlement Details</p>
        </div>
    </div>

    <!-- Date Range -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date Range :</label>
                <input type="text" class="form-control" ng-model="asdc.dateRange" placeholder="14/11/2025 00:00:00 - 29/11/2025 23:59:59">
            </div>
            <div class="col-md-3">
                <label class="form-label">Environment</label>
                <select class="form-select" ng-model="asdc.mode" ng-change="asdc.applyFilters()">
                    <option value="all">All</option>
                    <option value="test">Test</option>
                    <option value="live">Live</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" ng-click="asdc.openCreateModal()">
                    <i class="bi bi-plus-circle"></i> + Settlement Detail
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="asdc.pagination.per_page" ng-change="asdc.loadDetails()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.loadDetails()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in asdc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="asdc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="asdc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="asdc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading settlement details...</p>
            </div>
        </div>

        <div ng-hide="asdc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="asdc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_merchant_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_merchant_name" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_order_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="asdc.filters.filter_transaction_id" ng-change="asdc.applyFilters()">
                            </th>
                            <th ng-show="asdc.visibleColumns.tran_seq_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Tran Seq Id</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.transaction_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Date</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Amount Paid by Customer</span>
                                </div>
                            </th>
                            <th ng-show="asdc.visibleColumns.settlement_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Settlement Amount</span>
                                </div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="asdc.details.length === 0">
                            <td colspan="9" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="detail in asdc.details track by $index">
                            <td ng-show="asdc.visibleColumns.merchant_id.visible">@{{ detail.merchant_id }}</td>
                            <td ng-show="asdc.visibleColumns.merchant_name.visible">@{{ detail.merchant_name }}</td>
                            <td ng-show="asdc.visibleColumns.order_id.visible">@{{ detail.order_id }}</td>
                            <td ng-show="asdc.visibleColumns.transaction_id.visible">@{{ detail.transaction_id }}</td>
                            <td ng-show="asdc.visibleColumns.tran_seq_id.visible">@{{ detail.tran_seq_id }}</td>
                            <td ng-show="asdc.visibleColumns.transaction_date.visible">@{{ detail.transaction_date }}</td>
                            <td ng-show="asdc.visibleColumns.amount_paid_by_customer.visible">@{{ detail.amount_paid_by_customer }}</td>
                            <td ng-show="asdc.visibleColumns.settlement_amount.visible">@{{ detail.settlement_amount }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="asdc.viewDetail(detail)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (asdc.pagination.current_page - 1) * asdc.pagination.per_page + 1 }} to @{{ Math.min(asdc.pagination.current_page * asdc.pagination.per_page, asdc.pagination.total) }} of @{{ asdc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="asdc.changePage(asdc.pagination.current_page - 1)" 
                            ng-disabled="asdc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="asdc.changePage(asdc.pagination.current_page + 1)" 
                            ng-disabled="asdc.pagination.current_page === asdc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Settlement Detail Modal -->
    <div class="modal fade" id="viewSettlementDetailModal" tabindex="-1" aria-labelledby="viewSettlementDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSettlementDetailModalLabel">Settlement Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" ng-if="asdc.selectedDetail">
                        <div class="col-md-6" ng-repeat="field in asdc.detailFields track by $index">
                            <strong ng-bind="field.label + ':'"></strong>
                            <span ng-bind="asdc.selectedDetail[field.key] || '-'"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<!-- Create Settlement Detail Modal -->
<div class="modal fade" id="createSettlementDetailModal" tabindex="-1" aria-labelledby="createSettlementDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSettlementDetailModalLabel">Create Settlement Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSettlementDetailForm" ng-submit="asdc.submitSettlementDetail()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">* Merchant</label>
                            <select class="form-select" ng-model="asdc.form.merchant_id" ng-class="{'is-invalid': asdc.formErrors.merchant_id}" required>
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in asdc.merchants" value="@{{ merchant.id }}">@{{ merchant.name }}</option>
                            </select>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.merchant_id">@{{ asdc.formErrors.merchant_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Order Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.order_id" ng-class="{'is-invalid': asdc.formErrors.order_id}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.order_id">@{{ asdc.formErrors.order_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.transaction_id" ng-class="{'is-invalid': asdc.formErrors.transaction_id}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.transaction_id">@{{ asdc.formErrors.transaction_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tran Seq Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.tran_seq_id" ng-class="{'is-invalid': asdc.formErrors.tran_seq_id}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.tran_seq_id">@{{ asdc.formErrors.tran_seq_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Transaction Date</label>
                            <input type="datetime-local" class="form-control" ng-model="asdc.form.transaction_date" ng-class="{'is-invalid': asdc.formErrors.transaction_date}" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.transaction_date">@{{ asdc.formErrors.transaction_date }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transaction Qualifier</label>
                            <input type="text" class="form-control" ng-model="asdc.form.transaction_qualifier" ng-class="{'is-invalid': asdc.formErrors.transaction_qualifier}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.transaction_qualifier">@{{ asdc.formErrors.transaction_qualifier }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Qualifier</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_qualifier" ng-class="{'is-invalid': asdc.formErrors.settlement_qualifier}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_qualifier">@{{ asdc.formErrors.settlement_qualifier }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Setl Id</label>
                            <input type="text" class="form-control" ng-model="asdc.form.setl_id" ng-class="{'is-invalid': asdc.formErrors.setl_id}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.setl_id">@{{ asdc.formErrors.setl_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Amount Paid by Customer</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.amount_paid_by_customer" ng-class="{'is-invalid': asdc.formErrors.amount_paid_by_customer}" ng-change="asdc.validateCreateField('amount_paid_by_customer')" ng-blur="asdc.validateCreateField('amount_paid_by_customer')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.amount_paid_by_customer">@{{ asdc.formErrors.amount_paid_by_customer }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.settlement_amount" ng-class="{'is-invalid': asdc.formErrors.settlement_amount}" ng-change="asdc.validateCreateField('settlement_amount')" ng-blur="asdc.validateCreateField('settlement_amount')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_amount">@{{ asdc.formErrors.settlement_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Settlement Date</label>
                            <input type="date" class="form-control" ng-model="asdc.form.bank_settlement_date" ng-class="{'is-invalid': asdc.formErrors.bank_settlement_date}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.bank_settlement_date">@{{ asdc.formErrors.bank_settlement_date }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Settlement Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.bank_settlement_amount" ng-class="{'is-invalid': asdc.formErrors.bank_settlement_amount}" ng-change="asdc.validateCreateField('bank_settlement_amount')" ng-blur="asdc.validateCreateField('bank_settlement_amount')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.bank_settlement_amount">@{{ asdc.formErrors.bank_settlement_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Reference</label>
                            <input type="text" class="form-control" ng-model="asdc.form.bank_reference" ng-class="{'is-invalid': asdc.formErrors.bank_reference}">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.bank_reference">@{{ asdc.formErrors.bank_reference }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Account Name</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_account_name" ng-class="{'is-invalid': asdc.formErrors.settlement_account_name}" ng-change="asdc.validateCreateField('settlement_account_name')" ng-blur="asdc.validateCreateField('settlement_account_name')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_account_name">@{{ asdc.formErrors.settlement_account_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Account Number</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_account_number" ng-class="{'is-invalid': asdc.formErrors.settlement_account_number}" ng-change="asdc.validateCreateField('settlement_account_number')" ng-blur="asdc.validateCreateField('settlement_account_number')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_account_number">@{{ asdc.formErrors.settlement_account_number }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement IFSC Code</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_ifsc_code" ng-class="{'is-invalid': asdc.formErrors.settlement_ifsc_code}" ng-change="asdc.validateCreateField('settlement_ifsc_code')" ng-blur="asdc.validateCreateField('settlement_ifsc_code')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_ifsc_code">@{{ asdc.formErrors.settlement_ifsc_code }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Settlement Bank Name</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_bank_name" ng-class="{'is-invalid': asdc.formErrors.settlement_bank_name}" ng-change="asdc.validateCreateField('settlement_bank_name')" ng-blur="asdc.validateCreateField('settlement_bank_name')" required>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_bank_name">@{{ asdc.formErrors.settlement_bank_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Bank Branch</label>
                            <input type="text" class="form-control" ng-model="asdc.form.settlement_bank_branch" ng-class="{'is-invalid': asdc.formErrors.settlement_bank_branch}" ng-change="asdc.validateCreateField('settlement_bank_branch')" ng-blur="asdc.validateCreateField('settlement_bank_branch')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.settlement_bank_branch">@{{ asdc.formErrors.settlement_bank_branch }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Source</label>
                            <select class="form-select" ng-model="asdc.form.payment_mode" ng-class="{'is-invalid': asdc.formErrors.payment_mode}">
                                <option value="">Select Payment Source</option>
                                <option value="card">Card</option>
                                <option value="netbanking">Netbanking</option>
                                <option value="upi">UPI</option>
                                <option value="wallet">Wallet</option>
                                <option value="emi">EMI</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="bbps">BBPS</option>
                                <option value="bharat_qr">Bharat QR</option>
                            </select>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.payment_mode">@{{ asdc.formErrors.payment_mode }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Channel</label>
                            <select class="form-select" ng-model="asdc.form.payment_channel" ng-class="{'is-invalid': asdc.formErrors.payment_channel}">
                                <option value="">Select Payment Channel</option>
                                <option value="web">Web</option>
                                <option value="mobile">Mobile</option>
                                <option value="pos">POS</option>
                                <option value="api">API</option>
                            </select>
                            <div class="invalid-feedback" ng-if="asdc.formErrors.payment_channel">@{{ asdc.formErrors.payment_channel }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Percentage</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_percentage" ng-class="{'is-invalid': asdc.formErrors.tdr_percentage}" ng-change="asdc.validateCreateField('tdr_percentage')" ng-blur="asdc.validateCreateField('tdr_percentage')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.tdr_percentage">@{{ asdc.formErrors.tdr_percentage }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Fixed Fee</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_fixed_fee" ng-class="{'is-invalid': asdc.formErrors.tdr_fixed_fee}" ng-change="asdc.validateCreateField('tdr_fixed_fee')" ng-blur="asdc.validateCreateField('tdr_fixed_fee')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.tdr_fixed_fee">@{{ asdc.formErrors.tdr_fixed_fee }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tdr_amount" ng-class="{'is-invalid': asdc.formErrors.tdr_amount}" ng-change="asdc.validateCreateField('tdr_amount')" ng-blur="asdc.validateCreateField('tdr_amount')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.tdr_amount">@{{ asdc.formErrors.tdr_amount }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" step="0.01" class="form-control" ng-model="asdc.form.tax_amount" ng-class="{'is-invalid': asdc.formErrors.tax_amount}" ng-change="asdc.validateCreateField('tax_amount')" ng-blur="asdc.validateCreateField('tax_amount')">
                            <div class="invalid-feedback" ng-if="asdc.formErrors.tax_amount">@{{ asdc.formErrors.tax_amount }}</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Create Settlement Detail</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
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
            var app = angular.module('ipayApp');
            app.controller('AdminSettlementDetailsController', ['$http', function($http) {
                var vm = this;
                vm.details = [];
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.dateRange = '';
                vm.mode = 'all';
                vm.form = {};
                vm.formErrors = {};
                vm.selectedDetail = null;
                
                vm.visibleColumns = {
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    order_id: { visible: true, label: 'Order Id' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    tran_seq_id: { visible: true, label: 'Tran Seq Id' },
                    transaction_date: { visible: true, label: 'Transaction Date' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid by Customer' },
                    settlement_amount: { visible: true, label: 'Settlement Amount' }
                };

                vm.detailFields = [
                    { key: 'merchant_id', label: 'Merchant Id' },
                    { key: 'merchant_name', label: 'Merchant Name' },
                    { key: 'order_id', label: 'Order Id' },
                    { key: 'transaction_id', label: 'Transaction Id' },
                    { key: 'tran_seq_id', label: 'Tran Seq Id' },
                    { key: 'transaction_date', label: 'Transaction Date' },
                    { key: 'transaction_qualifier', label: 'Transaction Qualifier' },
                    { key: 'settlement_qualifier', label: 'Settlement Qualifier' },
                    { key: 'setl_id', label: 'Setl Id' },
                    { key: 'amount_paid_by_customer', label: 'Amount Paid by Customer' },
                    { key: 'settlement_amount', label: 'Settlement Amount' },
                    { key: 'bank_settlement_date', label: 'Bank Settlement Date' },
                    { key: 'bank_settlement_amount', label: 'Bank Settlement Amount' },
                    { key: 'bank_reference', label: 'Bank Reference' },
                    { key: 'settlement_account_name', label: 'Settlement Account Name' },
                    { key: 'settlement_account_number', label: 'Settlement Account Number' },
                    { key: 'settlement_ifsc_code', label: 'Settlement IFSC Code' },
                    { key: 'settlement_bank_name', label: 'Settlement Bank Name' },
                    { key: 'settlement_bank_branch', label: 'Settlement Bank Branch' },
                    { key: 'payment_mode', label: 'Payment Mode' },
                    { key: 'payment_channel', label: 'Payment Channel' },
                    { key: 'tdr_percentage', label: 'TDR Percentage' },
                    { key: 'tdr_fixed_fee', label: 'TDR Fixed Fee' },
                    { key: 'tdr_amount', label: 'TDR Amount' },
                    { key: 'earliest_priority_settlement_date', label: 'Earliest Priority Settlement Date' },
                    { key: 'latest_priority_settlement_date', label: 'Latest Priority Settlement Date' },
                    { key: 'tax_amount', label: 'Tax Amount' },
                    { key: 'setd_id', label: 'Setd Id' },
                    { key: 'provider', label: 'Provider' },
                    { key: 'account_id', label: 'Account ID' },
                    { key: 'acq_payment_id', label: 'Acq Payment Id' }
                ];

                vm.loadDetails = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        date_range: vm.dateRange
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    if (vm.mode && vm.mode !== 'all') {
                        params.mode = vm.mode;
                    }
                    
                    $http.get('/admin/settlements/details/data', { params: params }).then(function(response) {
                        vm.details = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading details:', error);
                    });
                };

                vm.loadMerchants = function() {
                    $http.get('/admin/merchants/data', { params: { per_page: 1000 } }).then(function(response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.openCreateModal = function() {
                    vm.formErrors = {};
                    vm.form = {
                        payment_mode: '',
                        payment_channel: ''
                    };
                    vm.loadMerchants();
                    var modal = new bootstrap.Modal(document.getElementById('createSettlementDetailModal'));
                    modal.show();
                };

                vm.validateCreateSettlementForm = function () {
                    vm.formErrors = {};
                    var f = vm.form || {};
                    function add(field, msg) {
                        vm.formErrors[field] = msg;
                    }

                    var merchantId = Number(f.merchant_id);
                    if (!f.merchant_id || Number.isNaN(merchantId) || merchantId <= 0) {
                        add('merchant_id', 'Merchant is required.');
                    }
                    if (f.transaction_id !== '' && f.transaction_id !== null && f.transaction_id !== undefined) {
                        var txnId = Number(f.transaction_id);
                        if (!Number.isInteger(txnId) || txnId <= 0) {
                            add('transaction_id', 'Transaction Id must be a valid numeric transaction record id.');
                        }
                    }
                    if (f.settlement_id !== '' && f.settlement_id !== null && f.settlement_id !== undefined) {
                        var settlementId = Number(f.settlement_id);
                        if (!Number.isInteger(settlementId) || settlementId <= 0) {
                            add('settlement_id', 'Settlement Id must be a valid numeric settlement record id.');
                        }
                    }
                    ['order_id', 'tran_seq_id', 'setl_id', 'setd_id', 'account_id', 'acq_payment_id'].forEach(function (field) {
                        var value = String(f[field] || '').trim();
                        if (value && !/^[A-Za-z0-9_\-\/]+$/.test(value)) {
                            add(field, 'This field may contain only letters, numbers, -, _, and /.');
                        }
                    });
                    ['transaction_qualifier', 'settlement_qualifier'].forEach(function (field) {
                        var value = String(f[field] || '').trim();
                        if (value && !/^[A-Za-z0-9 _\-\/]+$/.test(value)) {
                            add(field, 'This field may contain only letters, numbers, spaces, -, _, and /.');
                        }
                    });
                    var bankReference = String(f.bank_reference || '').trim();
                    if (bankReference && !/^[A-Za-z0-9 _\-\/]+$/.test(bankReference)) {
                        add('bank_reference', 'Bank reference may contain only letters, numbers, spaces, -, _, and /.');
                    }
                    var provider = String(f.provider || '').trim();
                    if (provider && !/^[A-Za-z0-9 _\-\.]+$/.test(provider)) {
                        add('provider', 'Provider may contain only letters, numbers, spaces, -, _, and .');
                    }
                    if (!f.transaction_date) {
                        add('transaction_date', 'Transaction date is required.');
                    }
                    if (f.bank_settlement_date && f.transaction_date) {
                        var txnDate = new Date(f.transaction_date);
                        var bankDate = new Date(f.bank_settlement_date);
                        if (!Number.isNaN(txnDate.getTime()) && !Number.isNaN(bankDate.getTime()) && bankDate < txnDate) {
                            add('bank_settlement_date', 'Bank settlement date cannot be before transaction date.');
                        }
                    }

                    if (f.amount_paid_by_customer === '' || f.amount_paid_by_customer === null || f.amount_paid_by_customer === undefined || Number(f.amount_paid_by_customer) < 0) {
                        add('amount_paid_by_customer', 'Amount paid by customer must be 0 or greater.');
                    }
                    if (f.settlement_amount === '' || f.settlement_amount === null || f.settlement_amount === undefined || Number(f.settlement_amount) < 0) {
                        add('settlement_amount', 'Settlement amount must be 0 or greater.');
                    }
                    if (f.bank_settlement_amount !== '' && f.bank_settlement_amount !== null && f.bank_settlement_amount !== undefined && Number(f.bank_settlement_amount) < 0) {
                        add('bank_settlement_amount', 'Bank settlement amount must be 0 or greater.');
                    }

                    var accountName = String(f.settlement_account_name || '').trim();
                    if (!accountName) {
                        add('settlement_account_name', 'Settlement account name is required.');
                    } else if (!/^[A-Za-z0-9 ]+$/.test(accountName)) {
                        add('settlement_account_name', 'Settlement account name may contain only letters, numbers, and spaces.');
                    }

                    var accountNo = String(f.settlement_account_number || '').trim();
                    if (!accountNo) {
                        add('settlement_account_number', 'Settlement account number is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(accountNo)) {
                        add('settlement_account_number', 'Settlement account number may contain only letters and numbers.');
                    } else if (accountNo.length < 6 || accountNo.length > 34) {
                        add('settlement_account_number', 'Settlement account number must be between 6 and 34 characters.');
                    }

                    var ifsc = String(f.settlement_ifsc_code || '').trim().toUpperCase();
                    if (ifsc) {
                        vm.form.settlement_ifsc_code = ifsc;
                    }
                    if (!ifsc) {
                        add('settlement_ifsc_code', 'Settlement IFSC code is required.');
                    } else if (!/^[A-Za-z0-9]+$/.test(ifsc)) {
                        add('settlement_ifsc_code', 'Settlement IFSC code may contain only letters and numbers.');
                    }

                    var bankName = String(f.settlement_bank_name || '').trim();
                    if (!bankName) {
                        add('settlement_bank_name', 'Settlement bank name is required.');
                    } else if (!/^[A-Za-z ]+$/.test(bankName)) {
                        add('settlement_bank_name', 'Settlement bank name may contain only letters and spaces.');
                    }

                    var branch = String(f.settlement_bank_branch || '').trim();
                    if (branch && !/^[A-Za-z0-9 ]+$/.test(branch)) {
                        add('settlement_bank_branch', 'Settlement bank branch may contain only letters, numbers, and spaces.');
                    }
                    if (f.payment_mode && ['card', 'netbanking', 'upi', 'wallet', 'emi', 'cash', 'bank_transfer', 'bbps', 'bharat_qr'].indexOf(String(f.payment_mode)) === -1) {
                        add('payment_mode', 'Payment source is invalid.');
                    }
                    if (f.payment_channel && ['web', 'mobile', 'pos', 'api'].indexOf(String(f.payment_channel)) === -1) {
                        add('payment_channel', 'Payment channel is invalid.');
                    }

                    if (f.tdr_percentage !== '' && f.tdr_percentage !== null && f.tdr_percentage !== undefined) {
                        var tdrPct = Number(f.tdr_percentage);
                        if (Number.isNaN(tdrPct) || tdrPct < 0 || tdrPct > 100) {
                            add('tdr_percentage', 'TDR percentage must be between 0 and 100.');
                        }
                    }
                    ['tdr_fixed_fee', 'tdr_amount', 'tax_amount'].forEach(function (field) {
                        if (f[field] === '' || f[field] === null || f[field] === undefined) return;
                        var value = Number(f[field]);
                        if (Number.isNaN(value) || value < 0) {
                            add(field, 'This value must be 0 or greater.');
                        }
                    });

                    return Object.keys(vm.formErrors).length === 0;
                };

                vm.validateCreateField = function (field) {
                    var f = vm.form || {};
                    if (!vm.formErrors) vm.formErrors = {};
                    delete vm.formErrors[field];

                    function set(msg) {
                        vm.formErrors[field] = msg;
                    }

                    var value;
                    switch (field) {
                    case 'amount_paid_by_customer':
                    case 'settlement_amount':
                    case 'bank_settlement_amount':
                    case 'tdr_percentage':
                    case 'tdr_fixed_fee':
                    case 'tdr_amount':
                    case 'tax_amount':
                        value = f[field];
                        if (value === '' || value === null || value === undefined) {
                            if (field === 'amount_paid_by_customer' || field === 'settlement_amount') {
                                set(field === 'amount_paid_by_customer' ? 'Amount paid by customer is required.' : 'Settlement amount is required.');
                            }
                            return;
                        }
                        if (Number.isNaN(Number(value)) || !/^\d+(\.\d+)?$/.test(String(value).trim())) {
                            set('Only numbers and decimal are allowed.');
                            return;
                        }
                        if (Number(value) < 0) {
                            set('This value must be 0 or greater.');
                            return;
                        }
                        if (field === 'tdr_percentage' && Number(value) > 100) {
                            set('TDR percentage must be between 0 and 100.');
                        }
                        return;
                    case 'settlement_account_name':
                        value = String(f.settlement_account_name || '').trim();
                        if (!value) {
                            set('Settlement account name is required.');
                        } else if (!/^[A-Za-z0-9 ]+$/.test(value)) {
                            set('Settlement account name may contain only letters, numbers, and spaces.');
                        }
                        return;
                    case 'settlement_account_number':
                        value = String(f.settlement_account_number || '').trim();
                        if (!value) {
                            set('Settlement account number is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                            set('Settlement account number may contain only letters and numbers.');
                        } else if (value.length < 6 || value.length > 34) {
                            set('Settlement account number must be between 6 and 34 characters.');
                        }
                        return;
                    case 'settlement_ifsc_code':
                        value = String(f.settlement_ifsc_code || '').trim().toUpperCase();
                        if (value) vm.form.settlement_ifsc_code = value;
                        if (!value) {
                            set('Settlement IFSC code is required.');
                        } else if (!/^[A-Za-z0-9]+$/.test(value)) {
                            set('Settlement IFSC code may contain only letters and numbers.');
                        }
                        return;
                    case 'settlement_bank_name':
                        value = String(f.settlement_bank_name || '').trim();
                        if (!value) {
                            set('Settlement bank name is required.');
                        } else if (!/^[A-Za-z ]+$/.test(value)) {
                            set('Settlement bank name may contain only letters and spaces.');
                        }
                        return;
                    case 'settlement_bank_branch':
                        value = String(f.settlement_bank_branch || '').trim();
                        if (value && !/^[A-Za-z0-9 ]+$/.test(value)) {
                            set('Settlement bank branch may contain only letters, numbers, and spaces.');
                        }
                        return;
                    default:
                        return;
                    }
                };

                vm.submitSettlementDetail = function() {
                    if (!vm.validateCreateSettlementForm()) {
                        if (typeof showToast === 'function') {
                            showToast('Please correct the highlighted errors.', 'error');
                        } else {
                            alert('Please correct the highlighted errors.');
                        }
                        return;
                    }
                    $http.post('/admin/settlements/details', vm.form).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Settlement detail created successfully!', 'success');
                            } else {
                                alert('Settlement detail created successfully!');
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById('createSettlementDetailModal'));
                            modal.hide();
                            vm.loadDetails();
                        } else {
                            var errorMsg = 'Error: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function(error) {
                        var errorMsg = 'Error creating settlement detail: ' + ((error.data && error.data.message) ? error.data.message : 'Unknown error');
                        if (error.data && error.data.errors) {
                            vm.formErrors = Object.keys(error.data.errors).reduce(function (acc, key) {
                                acc[key] = (error.data.errors[key] || [])[0] || 'Invalid value';
                                return acc;
                            }, {});
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadDetails();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadDetails();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.dateRange = '';
                    vm.mode = 'all';
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

                vm.viewDetail = function(detail) {
                    vm.selectedDetail = detail;
                    var modal = new bootstrap.Modal(document.getElementById('viewSettlementDetailModal'));
                    modal.show();
                };

                vm.loadDetails();
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


