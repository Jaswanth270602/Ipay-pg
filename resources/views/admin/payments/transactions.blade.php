@extends('layouts.app-sidebar')

@section('title', 'Transactions Details - Admin - ' . config('app.name'))
@section('page-title', 'Transactions Details')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminPaymentsTransactionsController as atc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Latest Transactions']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Transactions Details</h2>
            <p class="text-muted">List of Transactions</p>
        </div>
    </div>

    <!-- Advanced Filter and Status -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Advanced Filter</button>
            </div>
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm" 
                            ng-class="{'btn-primary': atc.filters.status === 'all', 'btn-outline-primary': atc.filters.status !== 'all'}"
                            ng-click="atc.setStatus('all')">All</button>
                    <button type="button" class="btn btn-sm" 
                            ng-class="{'btn-primary': atc.filters.status === 'success', 'btn-outline-primary': atc.filters.status !== 'success'}"
                            ng-click="atc.setStatus('success')">Successful</button>
                    <button type="button" class="btn btn-sm" 
                            ng-class="{'btn-primary': atc.filters.status === 'failed', 'btn-outline-primary': atc.filters.status !== 'failed'}"
                            ng-click="atc.setStatus('failed')">Failed</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="atc.pagination.per_page" ng-change="atc.loadTransactions()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-success" ng-click="atc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="atc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="atc.loadTransactions()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in atc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="atc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="atc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="atc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading transactions...</p>
            </div>
        </div>

        <div ng-hide="atc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="atc.visibleColumns.merchant_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Id</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="atc.sortBy('merchant_id')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_merchant_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.transaction_initiation_time.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Initiation Time</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="atc.sortBy('created_at')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_transaction_initiation_time" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.merchant_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_merchant_name" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.transaction_sequence_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Sequence Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_transaction_sequence_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.transaction_order_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Order Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_order_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.transaction_datetime.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction DateTime</span>
                                </div>
                                <input type="date" class="form-control form-control-sm mt-1" ng-model="atc.filters.filter_transaction_datetime" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_transaction_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Amount Paid By Customer</span>
                                </div>
                                <input type="number" step="0.01" class="form-control form-control-sm mt-1" placeholder="Amount..." ng-model="atc.filters.filter_amount_paid" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.payment_status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="atc.filters.filter_payment_status" ng-change="atc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </th>
                            <th ng-show="atc.visibleColumns.payment_mode.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Mode</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="atc.filters.filter_payment_mode" ng-change="atc.applyFilters()">
                                    <option value="">All</option>
                                    <option value="card">Card</option>
                                    <option value="netbanking">Netbanking</option>
                                    <option value="upi">UPI</option>
                                    <option value="wallet">Wallet</option>
                                    <option value="emi">EMI</option>
                                </select>
                            </th>
                            <th ng-show="atc.visibleColumns.payment_channel.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Payment Channel</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_payment_channel" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.merc_approved.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merc Approved</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="atc.filters.filter_merc_approved" ng-change="atc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th ng-show="atc.visibleColumns.currency_code.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Currency Code</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_currency_code" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.bank_reference_number.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Bank Reference Number</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_bank_reference_number" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.acq_payment_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Acq Payment Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_acq_payment_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.acq_transaction_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Acq Transaction Id</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_acq_transaction_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.provider_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Provider Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_provider_name" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.account_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account ID</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_account_id" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.tdr_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>TDR Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_tdr_amount" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.gst_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>GST Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_gst_amount" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.is_updated_by_recon.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Is Updated By Recon</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="atc.filters.filter_is_updated_by_recon" ng-change="atc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </th>
                            <th ng-show="atc.visibleColumns.tdr_amount_paid_by_merchant.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>TDR Amount Paid by Merchant</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_tdr_amount_paid_by_merchant" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.tdr_amount_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>TDR Amount Paid by Customer</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_tdr_amount_paid_by_customer" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.gst_paid_by_merchant.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>GST Paid By Merchant</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_gst_paid_by_merchant" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.gst_paid_by_customer.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>GST Paid By Customer</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_gst_paid_by_customer" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.net_settlements_amount.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Net Settlements Amount</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_net_settlements_amount" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.card_holder_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Card Holder Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_card_holder_name" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.card_number.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Card Number</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_card_number" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.customer_ip_address.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Customer IP Address</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_customer_ip_address" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.udf1.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UDF1</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_udf1" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.udf2.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UDF2</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_udf2" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.udf3.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UDF3</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_udf3" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.udf4.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UDF4</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_udf4" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.udf5.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UDF5</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_udf5" ng-change="atc.applyFilters()">
                            </th>
                            <th ng-show="atc.visibleColumns.upi_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>UPI ID</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="atc.filters.filter_upi_id" ng-change="atc.applyFilters()">
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="atc.transactions.length === 0">
                            <td colspan="35" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="transaction in atc.transactions track by $index">
                            <td ng-show="atc.visibleColumns.merchant_id.visible">@{{ transaction.merchant_id }}</td>
                            <td ng-show="atc.visibleColumns.transaction_initiation_time.visible">@{{ transaction.transaction_initiation_time }}</td>
                            <td ng-show="atc.visibleColumns.merchant_name.visible">@{{ transaction.merchant_name }}</td>
                            <td ng-show="atc.visibleColumns.transaction_sequence_id.visible">@{{ transaction.transaction_sequence_id }}</td>
                            <td ng-show="atc.visibleColumns.transaction_order_id.visible">@{{ transaction.transaction_order_id }}</td>
                            <td ng-show="atc.visibleColumns.transaction_datetime.visible">@{{ transaction.transaction_datetime }}</td>
                            <td ng-show="atc.visibleColumns.transaction_id.visible">@{{ transaction.transaction_id }}</td>
                            <td ng-show="atc.visibleColumns.amount_paid_by_customer.visible">@{{ transaction.amount_paid_by_customer }}</td>
                            <td ng-show="atc.visibleColumns.payment_status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': transaction.payment_status === 'success',
                                    'bg-danger': transaction.payment_status === 'failed',
                                    'bg-warning': transaction.payment_status === 'pending'
                                }">
                                    @{{ transaction.payment_status | uppercase }}
                                </span>
                                <div ng-if="transaction.payment_status === 'failed' && transaction.failure_reason" class="mt-1" style="font-size: 11px; color: #dc3545; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="@{{ transaction.failure_reason }}">
                                    <i class="bi bi-exclamation-circle"></i> @{{ transaction.failure_reason }}
                                </div>
                            </td>
                            <td ng-show="atc.visibleColumns.payment_mode.visible">@{{ transaction.payment_mode }}</td>
                            <td ng-show="atc.visibleColumns.payment_channel.visible">@{{ transaction.payment_channel }}</td>
                            <td ng-show="atc.visibleColumns.merc_approved.visible">@{{ transaction.merc_approved }}</td>
                            <td ng-show="atc.visibleColumns.currency_code.visible">@{{ transaction.currency_code }}</td>
                            <td ng-show="atc.visibleColumns.bank_reference_number.visible">@{{ transaction.bank_reference_number }}</td>
                            <td ng-show="atc.visibleColumns.acq_payment_id.visible">@{{ transaction.acq_payment_id }}</td>
                            <td ng-show="atc.visibleColumns.acq_transaction_id.visible">@{{ transaction.acq_transaction_id }}</td>
                            <td ng-show="atc.visibleColumns.provider_name.visible">@{{ transaction.provider_name }}</td>
                            <td ng-show="atc.visibleColumns.account_id.visible">@{{ transaction.account_id }}</td>
                            <td ng-show="atc.visibleColumns.tdr_amount.visible">@{{ transaction.tdr_amount }}</td>
                            <td ng-show="atc.visibleColumns.gst_amount.visible">@{{ transaction.gst_amount }}</td>
                            <td ng-show="atc.visibleColumns.is_updated_by_recon.visible">@{{ transaction.is_updated_by_recon }}</td>
                            <td ng-show="atc.visibleColumns.tdr_amount_paid_by_merchant.visible">@{{ transaction.tdr_amount_paid_by_merchant }}</td>
                            <td ng-show="atc.visibleColumns.tdr_amount_paid_by_customer.visible">@{{ transaction.tdr_amount_paid_by_customer }}</td>
                            <td ng-show="atc.visibleColumns.gst_paid_by_merchant.visible">@{{ transaction.gst_paid_by_merchant }}</td>
                            <td ng-show="atc.visibleColumns.gst_paid_by_customer.visible">@{{ transaction.gst_paid_by_customer }}</td>
                            <td ng-show="atc.visibleColumns.net_settlements_amount.visible">@{{ transaction.net_settlements_amount }}</td>
                            <td ng-show="atc.visibleColumns.card_holder_name.visible">@{{ transaction.card_holder_name }}</td>
                            <td ng-show="atc.visibleColumns.card_number.visible">@{{ transaction.card_number }}</td>
                            <td ng-show="atc.visibleColumns.customer_ip_address.visible">@{{ transaction.customer_ip_address }}</td>
                            <td ng-show="atc.visibleColumns.udf1.visible">@{{ transaction.udf1 }}</td>
                            <td ng-show="atc.visibleColumns.udf2.visible">@{{ transaction.udf2 }}</td>
                            <td ng-show="atc.visibleColumns.udf3.visible">@{{ transaction.udf3 }}</td>
                            <td ng-show="atc.visibleColumns.udf4.visible">@{{ transaction.udf4 }}</td>
                            <td ng-show="atc.visibleColumns.udf5.visible">@{{ transaction.udf5 }}</td>
                            <td ng-show="atc.visibleColumns.upi_id.visible">@{{ transaction.upi_id }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="atc.viewTransaction(transaction)">
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
                    Showing @{{ (atc.pagination.current_page - 1) * atc.pagination.per_page + 1 }} to @{{ Math.min(atc.pagination.current_page * atc.pagination.per_page, atc.pagination.total) }} of @{{ atc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="atc.changePage(atc.pagination.current_page - 1)" 
                            ng-disabled="atc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="atc.changePage(atc.pagination.current_page + 1)" 
                            ng-disabled="atc.pagination.current_page === atc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" ng-if="atc.selectedTransaction">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-receipt"></i> Transaction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Transaction ID:</strong><br>
                            <code>@{{atc.selectedTransaction.transaction_id}}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Merchant:</strong><br>
                            @{{atc.selectedTransaction.merchant_name}}
                        </div>
                        <div class="col-md-6">
                            <strong>Amount:</strong><br>
                            <span class="text-success">@{{atc.selectedTransaction.currency_code}} @{{atc.selectedTransaction.amount_paid_by_customer}}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge" ng-class="{'bg-success': atc.selectedTransaction.payment_status==='success', 'bg-danger': atc.selectedTransaction.payment_status==='failed'}">
                                @{{atc.selectedTransaction.payment_status | uppercase}}
                            </span>
                        </div>
                        <div class="col-12" ng-if="atc.selectedTransaction.payment_status === 'failed' && atc.selectedTransaction.failure_reason">
                            <div class="alert alert-danger py-2">
                                <strong><i class="bi bi-exclamation-triangle"></i> Failure Reason:</strong><br>
                                @{{ atc.selectedTransaction.failure_reason }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Payment Mode:</strong><br>
                            @{{atc.selectedTransaction.payment_mode}}
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong><br>
                            @{{atc.selectedTransaction.transaction_datetime}}
                        </div>
                        <div class="col-md-6">
                            <strong>TDR Amount:</strong><br>
                            @{{atc.selectedTransaction.tdr_amount}}
                        </div>
                        <div class="col-md-6">
                            <strong>Net Settlement:</strong><br>
                            @{{atc.selectedTransaction.net_settlements_amount}}
                        </div>
                        <div class="col-12" ng-if="atc.selectedTransaction.card_number !== '-'">
                            <strong>Card:</strong> @{{atc.selectedTransaction.card_number}}
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
            app.controller('AdminPaymentsTransactionsController', ['$http', function($http) {
                var vm = this;
                vm.transactions = [];
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
                vm.filters = { status: '' }; // Empty = no filter, show all
                vm.loading = false;
                vm.sortColumn = 'id';
                vm.sortDirection = 'desc';
                
                vm.visibleColumns = {
                    merchant_id: { visible: true, label: 'Merchant Id' },
                    transaction_initiation_time: { visible: true, label: 'Transaction Initiation Time' },
                    merchant_name: { visible: true, label: 'Merchant Name' },
                    transaction_sequence_id: { visible: true, label: 'Transaction Sequence Id' },
                    transaction_order_id: { visible: true, label: 'Transaction Order Id' },
                    transaction_datetime: { visible: true, label: 'Transaction DateTime' },
                    transaction_id: { visible: true, label: 'Transaction Id' },
                    amount_paid_by_customer: { visible: true, label: 'Amount Paid By Customer' },
                    payment_status: { visible: true, label: 'Payment Status' },
                    payment_mode: { visible: true, label: 'Payment Mode' },
                    payment_channel: { visible: true, label: 'Payment Channel' },
                    merc_approved: { visible: true, label: 'Merc Approved' },
                    currency_code: { visible: true, label: 'Currency Code' },
                    bank_reference_number: { visible: true, label: 'Bank Reference Number' },
                    acq_payment_id: { visible: true, label: 'Acq Payment Id' },
                    acq_transaction_id: { visible: true, label: 'Acq Transaction Id' },
                    provider_name: { visible: true, label: 'Provider Name' },
                    account_id: { visible: true, label: 'Account ID' },
                    tdr_amount: { visible: true, label: 'TDR Amount' },
                    gst_amount: { visible: true, label: 'GST Amount' },
                    is_updated_by_recon: { visible: true, label: 'Is Updated By Recon' },
                    tdr_amount_paid_by_merchant: { visible: true, label: 'TDR Amount Paid by Merchant' },
                    tdr_amount_paid_by_customer: { visible: true, label: 'TDR Amount Paid by Customer' },
                    gst_paid_by_merchant: { visible: true, label: 'GST Paid By Merchant' },
                    gst_paid_by_customer: { visible: true, label: 'GST Paid By Customer' },
                    net_settlements_amount: { visible: true, label: 'Net Settlements Amount' },
                    card_holder_name: { visible: true, label: 'Card Holder Name' },
                    card_number: { visible: true, label: 'Card Number' },
                    customer_ip_address: { visible: true, label: 'Customer IP Address' },
                    udf1: { visible: true, label: 'UDF1' },
                    udf2: { visible: true, label: 'UDF2' },
                    udf3: { visible: true, label: 'UDF3' },
                    udf4: { visible: true, label: 'UDF4' },
                    udf5: { visible: true, label: 'UDF5' },
                    upi_id: { visible: true, label: 'UPI ID' }
                };

                vm.loadTransactions = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        sort_by: vm.sortColumn,
                        sort_direction: vm.sortDirection
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (key.startsWith('filter_') && vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    console.log('Making API call with params:', params);
                    $http.get('/admin/payments/transactions/data', { params: params }).then(function(response) {
                        console.log('Admin Transactions API Response:', response.data);
                        console.log('Response pagination:', response.data.pagination);
                        vm.transactions = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                        console.log('Transactions loaded:', vm.transactions.length, 'Total:', vm.pagination.total);
                    }, function(error) {
                        console.error('Error loading admin transactions:', error);
                        if (typeof showToast === 'function') {
                            showToast('Error loading transactions: ' + (error.data?.message || error.statusText), 'error');
                        } else {
                            alert('Error loading transactions: ' + (error.data?.message || error.statusText));
                        }
                        vm.loading = false;
                        console.error('Error loading transactions:', error);
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadTransactions();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadTransactions();
                };

                vm.setStatus = function(status) {
                    console.log('Setting status filter to:', status);
                    vm.filters.status = status;
                    vm.applyFilters();
                };

                vm.sortBy = function(column) {
                    if (vm.sortColumn === column) {
                        vm.sortDirection = vm.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        vm.sortColumn = column;
                        vm.sortDirection = 'asc';
                    }
                    vm.loadTransactions();
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
                    vm.filters = { status: '' };
                    vm.applyFilters();
                };

                vm.clearFilters = function() {
                    console.log('Clearing all transaction filters');
                    vm.filters = {};
                    Object.keys(vm.filters).forEach(function(key) {
                        if (key.startsWith('filter_')) {
                            vm.filters[key] = '';
                        }
                    });
                    vm.filters.status = '';
                    vm.pagination.current_page = 1;
                    vm.loadTransactions();
                };

                vm.selectedTransaction = null;
                
                vm.viewTransaction = function(transaction) {
                    vm.selectedTransaction = transaction;
                    var modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
                    modal.show();
                };

                vm.exportCSV = function() {
                    var params = {
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (key.startsWith('filter_') && vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });

                    var queryString = Object.keys(params).map(function(key) {
                        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                    }).join('&');

                    window.location.href = '/admin/payments/transactions/export?' + queryString;
                };

                // Initialize - load data on page load
                console.log('AdminTransactionsController initialized');
                vm.loadTransactions();
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



