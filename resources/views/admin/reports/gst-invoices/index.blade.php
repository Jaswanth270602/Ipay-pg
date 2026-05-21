@extends('layouts.app-sidebar')

@section('title', 'VAT Invoices Report - Admin - ' . config('app.name'))
@section('page-title', 'VAT Invoices Report')

@push('styles')
<style>
    .gst-vat-invoices-page .gst-action-group.btn-group > .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.35rem;
        padding: 0.35rem 0.45rem;
        line-height: 1;
        border-radius: 0;
    }
    .gst-vat-invoices-page .gst-action-group.btn-group > .btn:first-child {
        border-top-left-radius: 0.375rem;
        border-bottom-left-radius: 0.375rem;
    }
    .gst-vat-invoices-page .gst-action-group.btn-group > .btn:last-child {
        border-top-right-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
    }
    .gst-vat-invoices-page .gst-action-group .btn i {
        font-size: 1rem;
    }
    .gst-vat-invoices-page .gst-view-detail-table th {
        width: 32%;
        max-width: 280px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #4b5563;
        padding: 0.65rem 1rem !important;
        vertical-align: middle;
        background: #f3f4f6 !important;
        border-color: #e5e7eb !important;
        white-space: nowrap;
    }
    .gst-vat-invoices-page .gst-view-detail-table td {
        font-size: 0.875rem;
        padding: 0.65rem 1rem !important;
        vertical-align: middle;
        border-color: #e5e7eb !important;
        word-break: break-word;
    }
    .gst-vat-invoices-page #gstInvoiceViewModal .modal-content {
        border-radius: 1rem;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div ng-cloak class="gst-vat-invoices-page" ng-app="ipayApp" ng-controller="AdminGSTInvoicesController as gst">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Canned Report']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">VAT INVOICES</h2>
                <small class="text-muted">VAT Invoices Report</small>
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
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.gst_provided_by" checked> VAT Provided By</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.gst_payer_name" checked> VAT Payer Name</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.payer_gstin" checked> Payer VATIN</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" ng-model="gst.visibleColumns.payer_gstin_state" checked> Payer VATIN State</label></li>
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

    <!-- VAT Invoices Table -->
    <div class="stat-card">
        <div ng-show="gst.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading VAT invoices...</p>
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
                            <th>VAT Provided By</th>
                            <th>VAT Payer Name</th>
                            <th>Payer VATIN</th>
                            <th>Payer VATIN State</th>
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
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.gst_provided_by" ng-change="gst.applyFilters()" placeholder="VAT Provided By"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.gst_payer_name" ng-change="gst.applyFilters()" placeholder="Payer Name"></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="gst.filters.payer_gstin" ng-change="gst.applyFilters()" placeholder="VATIN"></th>
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
                            <td class="text-end text-nowrap align-middle">
                                <div class="btn-group btn-group-sm gst-action-group" role="group" aria-label="Invoice actions">
                                    <button type="button" class="btn btn-outline-success" ng-click="gst.viewInvoice(invoice); $event.stopPropagation();" title="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" ng-click="gst.editInvoice(invoice); $event.stopPropagation();" title="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" ng-click="gst.deleteInvoice(invoice); $event.stopPropagation();" title="Delete">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <span ng-if="gst.pagination.total > 0">Showing @{{ (gst.pagination.current_page - 1) * gst.pagination.per_page + 1 }}
                    to @{{ Math.min(gst.pagination.current_page * gst.pagination.per_page, gst.pagination.total) }}
                    of @{{ gst.pagination.total }} entries</span>
                    <span ng-if="gst.pagination.total === 0">Showing 0 entries</span>
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
<!-- Create/Edit Modal -->
<div class="modal fade" id="gstInvoiceModal" tabindex="-1" aria-labelledby="gstInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gstInvoiceModalLabel">@{{ gst.isEditing ? 'Edit' : 'Create' }} VAT Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form ng-submit="gst.saveInvoice()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="gst.form.month" ng-class="{'is-invalid': gst.formErrors.month}" ng-change="gst.validateForm()" required>
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
                            <div class="invalid-feedback" ng-if="gst.formErrors.month">@{{ gst.formErrors.month }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="gst.form.year" ng-class="{'is-invalid': gst.formErrors.year}" ng-change="gst.validateForm()" min="2020" max="2099" required>
                            <div class="invalid-feedback" ng-if="gst.formErrors.year">@{{ gst.formErrors.year }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merchant</label>
                            <select class="form-select" ng-model="gst.form.merchant_id" ng-class="{'is-invalid': gst.formErrors.merchant_id}">
                                <option value="">Select Merchant</option>
                                <option ng-repeat="merchant in gst.merchants" ng-value="merchant.id">@{{ merchant.business_name || merchant.name || merchant.merchant_id }}</option>
                            </select>
                            <div class="invalid-feedback" ng-if="gst.formErrors.merchant_id">@{{ gst.formErrors.merchant_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VAT Provided By</label>
                            <input type="text" class="form-control" ng-model="gst.form.gst_provided_by" ng-class="{'is-invalid': gst.formErrors.gst_provided_by}">
                            <div class="invalid-feedback" ng-if="gst.formErrors.gst_provided_by">@{{ gst.formErrors.gst_provided_by }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VAT Payer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="gst.form.gst_payer_name" ng-class="{'is-invalid': gst.formErrors.gst_payer_name}" ng-change="gst.validateForm()" required>
                            <div class="invalid-feedback" ng-if="gst.formErrors.gst_payer_name">@{{ gst.formErrors.gst_payer_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payer VATIN</label>
                            <input type="text" class="form-control" ng-model="gst.form.payer_gstin" ng-class="{'is-invalid': gst.formErrors.payer_gstin}" ng-change="gst.enforceGstin(); gst.validateForm()" maxlength="15">
                            <div class="invalid-feedback" ng-if="gst.formErrors.payer_gstin">@{{ gst.formErrors.payer_gstin }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payer VATIN State</label>
                            <select class="form-select" ng-model="gst.form.payer_gstin_state" ng-class="{'is-invalid': gst.formErrors.payer_gstin_state}">
                                <option value="">Select State</option>
                                <option ng-repeat="state in gst.states" value="@{{ state }}">@{{ state }}</option>
                            </select>
                            <div class="invalid-feedback" ng-if="gst.formErrors.payer_gstin_state">@{{ gst.formErrors.payer_gstin_state }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Non-Taxable TDR</label>
                            <input type="number" class="form-control" ng-model="gst.form.non_taxable_tdr" ng-class="{'is-invalid': gst.formErrors.non_taxable_tdr}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.non_taxable_tdr">@{{ gst.formErrors.non_taxable_tdr }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Taxable TDR</label>
                            <input type="number" class="form-control" ng-model="gst.form.taxable_tdr" ng-class="{'is-invalid': gst.formErrors.taxable_tdr}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.taxable_tdr">@{{ gst.formErrors.taxable_tdr }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.sgst" ng-class="{'is-invalid': gst.formErrors.sgst}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.sgst">@{{ gst.formErrors.sgst }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.cgst" ng-class="{'is-invalid': gst.formErrors.cgst}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.cgst">@{{ gst.formErrors.cgst }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.igst" ng-class="{'is-invalid': gst.formErrors.igst}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.igst">@{{ gst.formErrors.igst }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">UTGST</label>
                            <input type="number" class="form-control" ng-model="gst.form.utgst" ng-class="{'is-invalid': gst.formErrors.utgst}" ng-change="gst.validateForm()" step="0.01" min="0">
                            <div class="invalid-feedback" ng-if="gst.formErrors.utgst">@{{ gst.formErrors.utgst }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Value <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="gst.form.invoice_value" ng-class="{'is-invalid': gst.formErrors.invoice_value}" ng-change="gst.validateForm()" step="0.01" min="0" required>
                            <div class="invalid-feedback" ng-if="gst.formErrors.invoice_value">@{{ gst.formErrors.invoice_value }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" class="form-control" ng-model="gst.form.invoice_date" ng-class="{'is-invalid': gst.formErrors.invoice_date}">
                            <div class="invalid-feedback" ng-if="gst.formErrors.invoice_date">@{{ gst.formErrors.invoice_date }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" ng-model="gst.form.notes" ng-class="{'is-invalid': gst.formErrors.notes}" rows="3"></textarea>
                            <div class="invalid-feedback" ng-if="gst.formErrors.notes">@{{ gst.formErrors.notes }}</div>
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

<!-- View invoice (read-only) -->
<div class="modal fade" id="gstInvoiceViewModal" tabindex="-1" aria-labelledby="gstInvoiceViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2 pt-4 px-4 align-items-start">
                <div class="flex-grow-1">
                    <span class="badge bg-success rounded-pill px-3 py-2">View only</span>
                    <h5 class="modal-title mt-2 mb-0" id="gstInvoiceViewModalLabel">VAT invoice details</h5>
                    <p class="text-muted small mb-0 mt-1" ng-if="gst.viewDetail && gst.viewDetail.invoice_number">
                        <span class="font-monospace">@{{ gst.viewDetail.invoice_number }}</span>
                    </p>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-0 pb-3">
                <p class="text-muted small mb-3" ng-if="gst.viewDetail">Review all fields stored for this invoice.</p>
                <div ng-if="gst.viewDetail" class="table-responsive rounded-3 border border-light">
                    <table class="table table-sm align-middle mb-0 gst-view-detail-table">
                        <tbody>
                            <tr><th scope="row">ID</th><td>@{{ gst.viewDetail.id }}</td></tr>
                            <tr><th scope="row">Invoice number</th><td><span class="font-monospace">@{{ gst.viewDetail.invoice_number || '—' }}</span></td></tr>
                            <tr><th scope="row">Period</th><td>@{{ gst.monthLabel(gst.viewDetail.month) }} @{{ gst.viewDetail.year }}</td></tr>
                            <tr><th scope="row">Merchant</th><td>@{{ gst.merchantLabel(gst.viewDetail) }}</td></tr>
                            <tr><th scope="row">VAT provided by</th><td>@{{ gst.viewDetail.gst_provided_by || '—' }}</td></tr>
                            <tr><th scope="row">VAT payer name</th><td>@{{ gst.viewDetail.gst_payer_name || '—' }}</td></tr>
                            <tr><th scope="row">Payer VATIN</th><td><span class="font-monospace text-uppercase">@{{ gst.viewDetail.payer_gstin || '—' }}</span></td></tr>
                            <tr><th scope="row">Payer VATIN state</th><td>@{{ gst.viewDetail.payer_gstin_state || '—' }}</td></tr>
                            <tr><th scope="row">Non-taxable TDR</th><td>@{{ gst.viewDetail.non_taxable_tdr | number:2 }}</td></tr>
                            <tr><th scope="row">Taxable TDR</th><td>@{{ gst.viewDetail.taxable_tdr | number:2 }}</td></tr>
                            <tr><th scope="row">SGST</th><td>@{{ gst.viewDetail.sgst | number:2 }}</td></tr>
                            <tr><th scope="row">CGST</th><td>@{{ gst.viewDetail.cgst | number:2 }}</td></tr>
                            <tr><th scope="row">IGST</th><td>@{{ gst.viewDetail.igst | number:2 }}</td></tr>
                            <tr><th scope="row">UTGST</th><td>@{{ gst.viewDetail.utgst | number:2 }}</td></tr>
                            <tr><th scope="row">Invoice value</th><td><strong>@{{ gst.viewDetail.invoice_value | number:2 }}</strong></td></tr>
                            <tr><th scope="row">Invoice date</th><td>@{{ gst.viewDetail.invoice_date || '—' }}</td></tr>
                            <tr><th scope="row">Notes</th><td class="text-break text-muted">@{{ gst.viewDetail.notes || '—' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
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
            var app = angular.module('ipayApp');
            app.controller('AdminGSTInvoicesController', ['$http', '$timeout', function ($http, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                var MONTH_LABELS = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

                vm.invoices = [];
                vm.states = [];
                vm.merchants = [];
                vm.loading = false;
                vm.selectedInvoice = null;
                vm.isEditing = false;
                vm.saving = false;
                vm.formErrors = {};
                vm.viewDetail = null;
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };

                vm.monthLabel = function (m) {
                    var n = parseInt(m, 10);
                    return (n >= 1 && n <= 12) ? MONTH_LABELS[n] : (m !== undefined && m !== null && m !== '') ? String(m) : '—';
                };

                vm.merchantLabel = function (inv) {
                    if (!inv) {
                        return '—';
                    }
                    if (inv.merchant) {
                        return inv.merchant.business_name || inv.merchant.name || ('Merchant #' + inv.merchant.id);
                    }
                    return inv.merchant_id ? ('#' + inv.merchant_id) : '—';
                };

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
                                showToast('Failed to load VAT invoices', 'error');
                            } else {
                                alert('Failed to load VAT invoices');
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
                    vm.formErrors = {};
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
                    vm.formErrors = {};
                    vm.form = angular.copy(invoice);
                    vm.form.id = invoice.id;
                    var modal = new bootstrap.Modal(document.getElementById('gstInvoiceModal'));
                    modal.show();
                };

                vm.enforceGstin = function () {
                    if (!vm.form.payer_gstin) {
                        return;
                    }
                    vm.form.payer_gstin = String(vm.form.payer_gstin).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 15);
                };

                vm.validateForm = function () {
                    vm.formErrors = {};
                    var month = Number(vm.form.month);
                    var year = Number(vm.form.year);
                    var invoiceValue = Number(vm.form.invoice_value);
                    var gstin = (vm.form.payer_gstin || '').trim();
                    var nonNegativeFields = ['non_taxable_tdr', 'taxable_tdr', 'sgst', 'cgst', 'igst', 'utgst'];

                    if (!month || month < 1 || month > 12) vm.formErrors.month = 'Month is required.';
                    if (!year || year < 2020 || year > 2099) vm.formErrors.year = 'Year must be between 2020 and 2099.';
                    if (!vm.form.gst_payer_name || !String(vm.form.gst_payer_name).trim()) vm.formErrors.gst_payer_name = 'VAT payer name is required.';
                    if (gstin && !/^[A-Z0-9]{15}$/.test(gstin)) vm.formErrors.payer_gstin = 'Payer VATIN must be exactly 15 uppercase letters/numbers.';
                    if (vm.form.invoice_value === '' || vm.form.invoice_value === null || vm.form.invoice_value === undefined || Number.isNaN(invoiceValue) || invoiceValue < 0) {
                        vm.formErrors.invoice_value = 'Invoice value must be 0 or greater.';
                    }
                    nonNegativeFields.forEach(function (field) {
                        if (vm.form[field] === '' || vm.form[field] === null || vm.form[field] === undefined) {
                            return;
                        }
                        var value = Number(vm.form[field]);
                        if (Number.isNaN(value) || value < 0) {
                            vm.formErrors[field] = 'This value must be 0 or greater.';
                        }
                    });

                    return Object.keys(vm.formErrors).length === 0;
                };

                vm.saveInvoice = function () {
                    vm.enforceGstin();
                    if (!vm.validateForm()) {
                        return;
                    }

                    vm.saving = true;
                    var url = vm.isEditing ? "{{ url('admin/reports/gst-invoices') }}/" + vm.form.id : "{{ route('admin.reports.gst-invoices.store') }}";
                    var method = 'POST';
                    var payload = angular.copy(vm.form);
                    payload.month = payload.month ? Number(payload.month) : null;
                    payload.year = payload.year ? Number(payload.year) : null;
                    payload.merchant_id = payload.merchant_id ? Number(payload.merchant_id) : null;
                    payload.gst_provided_by = payload.gst_provided_by ? String(payload.gst_provided_by).trim() : '';
                    payload.gst_payer_name = payload.gst_payer_name ? String(payload.gst_payer_name).trim() : '';
                    payload.payer_gstin_state = payload.payer_gstin_state ? String(payload.payer_gstin_state).trim() : '';
                    payload.notes = payload.notes ? String(payload.notes).trim() : '';
                    ['non_taxable_tdr', 'taxable_tdr', 'sgst', 'cgst', 'igst', 'utgst', 'invoice_value'].forEach(function (field) {
                        if (payload[field] === '' || payload[field] === null || payload[field] === undefined) {
                            payload[field] = 0;
                        } else {
                            payload[field] = Number(payload[field]);
                        }
                    });

                    $http({
                        method: method,
                        url: url,
                        data: payload,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function (response) {
                        vm.saving = false;
                        var modalEl = document.getElementById('gstInvoiceModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (response.data && response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast(response.data.message || 'VAT invoice saved', 'success');
                            } else {
                                alert(response.data.message || 'VAT invoice saved');
                            }
                            vm.loadInvoices();
                        } else {
                            var msg = (response.data && response.data.message) || 'Failed to save VAT invoice';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        vm.saving = false;
                        vm.formErrors = {};
                        var msg = 'Failed to save VAT invoice';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (error.data && error.data.errors) {
                            vm.formErrors = Object.keys(error.data.errors).reduce(function (acc, key) {
                                acc[key] = (error.data.errors[key] || [])[0] || 'Invalid value';
                                return acc;
                            }, {});
                            var errors = Object.values(vm.formErrors);
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
                    vm.viewDetail = null;
                    $http.get("{{ url('admin/reports/gst-invoices') }}/" + invoice.id).then(function (response) {
                        if (response.data && response.data.success) {
                            vm.viewDetail = response.data.data;
                            $timeout(function () {
                                var el = document.getElementById('gstInvoiceViewModal');
                                if (el) {
                                    bootstrap.Modal.getOrCreateInstance(el).show();
                                }
                            }, 0);
                        } else {
                            var msg = (response.data && response.data.message) || 'Could not load invoice';
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }, function (error) {
                        var msg = 'Could not load invoice';
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

                vm.deleteInvoice = function (invoice) {
                    function doDelete() {
                        $http.delete("{{ url('admin/reports/gst-invoices') }}/" + invoice.id, {
                            headers: { 'X-CSRF-TOKEN': csrf }
                        }).then(function (response) {
                            if (response.data && response.data.success) {
                                if (typeof showToast === 'function') {
                                    showToast(response.data.message || 'VAT invoice deleted', 'success');
                                } else {
                                    alert(response.data.message || 'VAT invoice deleted');
                                }
                                vm.loadInvoices();
                            } else {
                                var msg = (response.data && response.data.message) || 'Failed to delete VAT invoice';
                                if (typeof showToast === 'function') {
                                    showToast(msg, 'error');
                                } else {
                                    alert(msg);
                                }
                            }
                        }, function (error) {
                            var msg = 'Failed to delete VAT invoice';
                            if (error.data && error.data.message) {
                                msg = error.data.message;
                            }
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                alert(msg);
                            }
                        });
                    }

                    ipayConfirm('Are you sure you want to delete this VAT invoice? This cannot be undone.', 'danger', {
                        okText: 'Delete',
                        cancelText: 'Cancel',
                        title: 'Delete VAT invoice'
                    }).then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        $timeout(function () {
                            doDelete();
                        });
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

