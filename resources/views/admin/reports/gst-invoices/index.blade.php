@extends('layouts.app-sidebar')

@section('title', 'GST Invoices Report - Admin - ' . config('app.name'))
@section('page-title', 'GST Invoices Report')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminGSTInvoicesController as gst">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Canned Report']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">GST INVOICES</h2>
                <small class="text-muted">GST Invoices Report</small>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="border-bottom mb-3" style="border-color: #6366f1 !important; border-width: 2px !important;"></div>

    <!-- Toolbar -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;"
                        ng-model="gst.pagination.per_page" ng-change="gst.loadInvoices()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="gst.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="gst.loadInvoices()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.invoice_number" checked> Invoice Number</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.month" checked> Month</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.year" checked> Year</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.merchant_id" checked> Merchant Id</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.gst_provided_by" checked> GST Provided By</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.gst_payer_name" checked> GST Payer Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.payer_gstin" checked> Payer GSTIN</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.payer_gstin_state" checked> Payer GSTIN State</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.non_taxable_tdr" checked> Non-Taxable TDR</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.taxable_tdr" checked> Taxable TDR</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.sgst" checked> SGST</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.cgst" checked> CGST</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.igst" checked> IGST</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.utgst" checked> UTGST</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.invoice_value" checked> Invoice Value</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.invoice_date" checked> Invoice Date</label></li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="gst.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="gst.openCreateModal()">
                    <i class="bi bi-plus-lg"></i> Create
                </button>
            </div>
        </div>
    </div>

    <!-- GST Invoices Table -->
    <div class="stat-card">
        <div ng-show="gst.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading GST invoices...</p>
            </div>
        </div>

        <div ng-hide="gst.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Merchant Id</th>
                            <th>GST Provided By</th>
                            <th>GST Payer Name</th>
                            <th>Payer GSTIN</th>
                            <th>Payer GSTIN State</th>
                            <th>Non-Taxable TDR</th>
                            <th>Taxable TDR</th>
                            <th>SGST</th>
                            <th>CGST</th>
                            <th>IGST</th>
                            <th>UTGST</th>
                            <th>Invoice Value</th>
                            <th>Invoice Date</th>
                            <th>ID</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.invoice_number" ng-change="gst.applyFilters()" placeholder="Invoice Number"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="gst.filters.month" ng-change="gst.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.year" ng-change="gst.applyFilters()" placeholder="Year"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.merchant_id" ng-change="gst.applyFilters()" placeholder="Merchant ID"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.gst_provided_by" ng-change="gst.applyFilters()" placeholder="GST Provided By"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.gst_payer_name" ng-change="gst.applyFilters()" placeholder="Payer Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.payer_gstin" ng-change="gst.applyFilters()" placeholder="GSTIN"></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="gst.filters.payer_gstin_state" ng-change="gst.applyFilters()">
                                    <option value="all">All</option>
                                    <option ng-repeat="state in gst.states" value="@{{ state }}">@{{ state }}</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.non_taxable_tdr" ng-change="gst.applyFilters()" placeholder="Non-Taxable TDR"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.taxable_tdr" ng-change="gst.applyFilters()" placeholder="Taxable TDR"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.sgst" ng-change="gst.applyFilters()" placeholder="SGST"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.cgst" ng-change="gst.applyFilters()" placeholder="CGST"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.igst" ng-change="gst.applyFilters()" placeholder="IGST"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.utgst" ng-change="gst.applyFilters()" placeholder="UTGST"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.invoice_value" ng-change="gst.applyFilters()" placeholder="Invoice Value"></th>
                            <th><input type="date" class="form-control form-control-sm" ng-model="gst.filters.invoice_date" ng-change="gst.applyFilters()"></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="gst.invoices.length === 0">
                            <td colspan="18" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="invoice in gst.invoices track by invoice.id"
                            ng-class="{'table-active': gst.selectedInvoice && gst.selectedInvoice.id === invoice.id}"
                            ng-click="gst.selectInvoice(invoice)">
                            <td>@{{ invoice.invoice_number || '-' }}</td>
                            <td>@{{ invoice.month || '-' }}</td>
                            <td>@{{ invoice.year || '-' }}</td>
                            <td>@{{ invoice.merchant_id || '-' }}</td>
                            <td>@{{ invoice.gst_provided_by || '-' }}</td>
                            <td>@{{ invoice.gst_payer_name || '-' }}</td>
                            <td>@{{ invoice.payer_gstin || '-' }}</td>
                            <td>@{{ invoice.payer_gstin_state || '-' }}</td>
                            <td>@{{ invoice.non_taxable_tdr || '0.00' }}</td>
                            <td>@{{ invoice.taxable_tdr || '0.00' }}</td>
                            <td>@{{ invoice.sgst || '0.00' }}</td>
                            <td>@{{ invoice.cgst || '0.00' }}</td>
                            <td>@{{ invoice.igst || '0.00' }}</td>
                            <td>@{{ invoice.utgst || '0.00' }}</td>
                            <td>@{{ invoice.invoice_value || '0.00' }}</td>
                            <td>@{{ invoice.invoice_date || '-' }}</td>
                            <td>@{{ invoice.id }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" ng-click="gst.viewInvoice(invoice); $event.stopPropagation();" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" ng-click="gst.editInvoice(invoice); $event.stopPropagation();" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" ng-click="gst.deleteInvoice(invoice); $event.stopPropagation();" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (gst.pagination.current_page - 1) * gst.pagination.per_page + 1 }}
                    to @{{ Math.min(gst.pagination.current_page * gst.pagination.per_page, gst.pagination.total) }}
                    of @{{ gst.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="gst.changePage(gst.pagination.current_page - 1)"
                            ng-disabled="gst.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ gst.pagination.current_page }} of @{{ gst.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary"
                            ng-click="gst.changePage(gst.pagination.current_page + 1)"
                            ng-disabled="gst.pagination.current_page === gst.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="gstInvoiceModal" tabindex="-1" aria-labelledby="gstInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gstInvoiceModalLabel">@{{ gst.isEditing ? 'Edit' : 'Create' }} GST Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form ng-submit="gst.saveInvoice()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="gst.form.month" required>
                                <option value="">Select Month</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="gst.form.year" min="2020" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merchant</label>
                            <select class="form-select" ng-model="gst.form.merchant_id">
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in gst.merchants" value="@{{ merchant.id }}">@{{ merchant.business_name || merchant.merchant_id }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Provided By</label>
                            <input type="text" class="form-control" ng-model="gst.form.gst_provided_by">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Payer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="gst.form.gst_payer_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payer GSTIN</label>
                            <input type="text" class="form-control" ng-model="gst.form.payer_gstin" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payer GSTIN State</label>
                            <select class="form-select" ng-model="gst.form.payer_gstin_state">
                                <option value="">Select State</option>
                                <option ng-repeat="state in gst.states" value="@{{ state }}">@{{ state }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Non-Taxable TDR</label>
                            <input type="number" class="form-control" ng-model="gst.form.non_taxable_tdr" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Taxable TDR</label>
                            <input type="number" class="form-control" ng-model="gst.form.taxable_tdr" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.sgst" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.cgst" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.igst" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">UTGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.utgst" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Value <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="gst.form.invoice_value" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" class="form-control" ng-model="gst.form.invoice_date">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" ng-model="gst.form.notes" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" ng-click="gst.saveInvoice()" ng-disabled="gst.saving">
                    <span ng-if="gst.saving" class="spinner-border spinner-border-sm me-1"></span>
                    @{{ gst.isEditing ? 'Update' : 'Create' }}
                </button>
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
            app.controller('AdminGSTInvoicesController', ['$http', function ($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                vm.invoices = [];
                vm.states = [];
                vm.merchants = [];
                vm.loading = false;
                vm.selectedInvoice = null;
                vm.isEditing = false;
                vm.saving = false;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.visibleColumns = {
                    invoice_number: true,
                    month: true,
                    year: true,
                    merchant_id: true,
                    gst_provided_by: true,
                    gst_payer_name: true,
                    payer_gstin: true,
                    payer_gstin_state: true,
                    non_taxable_tdr: true,
                    taxable_tdr: true,
                    sgst: true,
                    cgst: true,
                    igst: true,
                    utgst: true,
                    invoice_value: true,
                    invoice_date: true
                };

                vm.filters = {
                    invoice_number: '',
                    month: 'all',
                    year: '',
                    merchant_id: '',
                    gst_provided_by: '',
                    gst_payer_name: '',
                    payer_gstin: '',
                    payer_gstin_state: 'all',
                    non_taxable_tdr: '',
                    taxable_tdr: '',
                    sgst: '',
                    cgst: '',
                    igst: '',
                    utgst: '',
                    invoice_value: '',
                    invoice_date: ''
                };

                vm.form = {
                    month: '',
                    year: new Date().getFullYear(),
                    merchant_id: '',
                    gst_provided_by: '',
                    gst_payer_name: '',
                    payer_gstin: '',
                    payer_gstin_state: '',
                    non_taxable_tdr: 0,
                    taxable_tdr: 0,
                    sgst: 0,
                    cgst: 0,
                    igst: 0,
                    utgst: 0,
                    invoice_value: 0,
                    invoice_date: '',
                    notes: ''
                };

                vm.loadStates = function () {
                    $http.get("{{ route('admin.reports.gst-invoices.states') }}").then(function (response) {
                        vm.states = response.data.data || [];
                    });
                };

                vm.loadMerchants = function () {
                    $http.get("{{ route('admin.reports.gst-invoices.merchants') }}").then(function (response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.loadInvoices = function () {
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

                    $http.get("{{ route('admin.reports.gst-invoices.data') }}", { params: params })
                        .then(function (response) {
                            vm.invoices = response.data.data || [];
                            vm.pagination = {
                                current_page: response.data.pagination.current_page,
                                per_page: response.data.pagination.per_page,
                                total: response.data.pagination.total,
                                last_page: response.data.pagination.last_page
                            };
                            vm.loading = false;
                        }, function () {
                            vm.loading = false;
                            if (typeof showToast === 'function') {
                                showToast('Failed to load GST invoices', 'error');
                            } else {
                                alert('Failed to load GST invoices');
                            }
                        });
                };

                vm.changePage = function (page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadInvoices();
                    }
                };

                vm.applyFilters = function () {
                    vm.pagination.current_page = 1;
                    vm.loadInvoices();
                };

                vm.clearFilters = function () {
                    vm.filters = {
                        invoice_number: '',
                        month: 'all',
                        year: '',
                        merchant_id: '',
                        gst_provided_by: '',
                        gst_payer_name: '',
                        payer_gstin: '',
                        payer_gstin_state: 'all',
                        non_taxable_tdr: '',
                        taxable_tdr: '',
                        sgst: '',
                        cgst: '',
                        igst: '',
                        utgst: '',
                        invoice_value: '',
                        invoice_date: ''
                    };
                    vm.applyFilters();
                };

                vm.resetView = function () {
                    vm.clearFilters();
                    vm.loadInvoices();
                };

                vm.selectInvoice = function (invoice) {
                    vm.selectedInvoice = invoice;
                };

                vm.openCreateModal = function () {
                    vm.isEditing = false;
                    vm.form = {
                        month: '',
                        year: new Date().getFullYear(),
                        merchant_id: '',
                        gst_provided_by: '',
                        gst_payer_name: '',
                        payer_gstin: '',
                        payer_gstin_state: '',
                        non_taxable_tdr: 0,
                        taxable_tdr: 0,
                        sgst: 0,
                        cgst: 0,
                        igst: 0,
                        utgst: 0,
                        invoice_value: 0,
                        invoice_date: '',
                        notes: ''
                    };
                    var modal = new bootstrap.Modal(document.getElementById('gstInvoiceModal'));
                    modal.show();
                };

                vm.editInvoice = function (invoice) {
                    vm.isEditing = true;
                    vm.form = angular.copy(invoice);
                    vm.form.id = invoice.id;
                    var modal = new bootstrap.Modal(document.getElementById('gstInvoiceModal'));
                    modal.show();
                };

                vm.saveInvoice = function () {
                    vm.saving = true;
                    var url = vm.isEditing ? "{{ url('admin/reports/gst-invoices') }}/" + vm.form.id : "{{ route('admin.reports.gst-invoices.store') }}";
                    var method = 'POST';

                    $http({
                        method: method,
                        url: url,
                        data: vm.form,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('gstInvoiceModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'GST invoice saved', 'success');
                            } else {
                                alert(response.data.message || 'GST invoice saved');
                            }
                            vm.loadInvoices();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to save GST invoice';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        var msg = 'Failed to save GST invoice';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            msg = errors.join(', ');
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                    });
                };

                vm.viewInvoice = function (invoice) {
                    $http.get("{{ url('admin/reports/gst-invoices') }}/" + invoice.id).then(function (response) {
                        if (response.data && response.data.success) {
                            var invoiceData = response.data.data;
                            var details = 'Invoice Number: ' + invoiceData.invoice_number + '\n' +
                                        'Month: ' + invoiceData.month + '\n' +
                                        'Year: ' + invoiceData.year + '\n' +
                                        'GST Payer Name: ' + invoiceData.gst_payer_name + '\n' +
                                        'Payer GSTIN: ' + (invoiceData.payer_gstin || 'N/A') + '\n' +
                                        'Invoice Value: ' + invoiceData.invoice_value;
                            alert(details);
                        }
                    });
                };

                vm.deleteInvoice = function (invoice) {
                    if (!confirm('Are you sure you want to delete this GST invoice?')) {
                        return;
                    }

                    $http.delete("{{ url('admin/reports/gst-invoices') }}/" + invoice.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'GST invoice deleted', 'success');
                            } else {
                                alert(response.data.message || 'GST invoice deleted');
                            }
                            vm.loadInvoices();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to delete GST invoice';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        var msg = 'Failed to delete GST invoice';
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

                // Initialize
                vm.loadStates();
                vm.loadMerchants();
                vm.loadInvoices();
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

