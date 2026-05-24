@extends('layouts.app-sidebar')

@section('title', 'Refunds - ' . config('app.name'))
@section('page-title','Refunds')

@section('content')
<style>
    .refund-create-modal .modal-content {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(17, 24, 39, 0.18);
        overflow: hidden;
    }
    .refund-create-modal .modal-header {
        border-bottom: 1px solid #eef0f4;
        background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
        padding: 1rem 1.25rem;
    }
    .refund-create-modal .modal-title {
        font-weight: 700;
        font-size: 1.2rem;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .refund-create-modal .modal-body {
        padding: 1rem 1.25rem 0.75rem;
    }
    .refund-create-modal .refund-approval-note {
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 10px;
        padding: 0.7rem 0.85rem;
        margin-bottom: 0.9rem;
        font-size: 0.9rem;
        line-height: 1.45;
    }
    .refund-create-modal .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }
    .refund-create-modal .form-control,
    .refund-create-modal .form-select {
        border-radius: 10px;
        border-color: #d8dee9;
        min-height: 44px;
    }
    .refund-create-modal textarea.form-control {
        min-height: 96px;
    }
    .refund-create-modal .form-control:focus,
    .refund-create-modal .form-select:focus {
        border-color: #9b87f5;
        box-shadow: 0 0 0 0.2rem rgba(155, 135, 245, 0.16);
    }
    .refund-create-modal .field-hint {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 0.35rem;
    }
    .refund-payment-currency {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        padding: 0.55rem 0.75rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        font-size: 0.84rem;
        color: #1e40af;
    }
    .refund-payment-currency .badge {
        font-size: 0.78rem;
        letter-spacing: 0.03em;
    }
    .refund-payment-currency.is-error {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border-color: #fecaca;
        color: #991b1b;
    }
    .refund-payment-currency.is-loading {
        background: #f9fafb;
        border-color: #e5e7eb;
        color: #6b7280;
    }
    .refund-create-modal .modal-footer {
        border-top: 1px solid #eef0f4;
        padding: 0.9rem 1.25rem 1rem;
        gap: 0.45rem;
    }
    .refund-create-modal .btn {
        border-radius: 10px;
        min-width: 110px;
        font-weight: 600;
    }
</style>
<div ng-cloak ng-app="ipayApp" ng-controller="RefundsController as rc">
    <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
        <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
        <div class="small">
            <strong>Refund approval rules</strong>
            — Refunds below <strong>{{ number_format($refundApprovalThreshold, 0) }}</strong> (in the <strong>same currency</strong> as the original payment) can be submitted directly when your account rules allow.
            Refunds of <strong>{{ number_format($refundApprovalThreshold, 0) }} or more</strong> are sent for <strong>admin approval</strong> first; they appear as <span class="badge bg-warning text-dark">PENDING_APPROVAL</span> until approved or rejected.
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRefundModal">
                <i class="bi bi-plus-circle"></i> Create Refund
            </button>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="rc.filters.status" ng-change="rc.applyFilters()">
                    <option value="">All</option>
                    <option value="pending_approval">Pending approval</option>
                    <option value="pending_processing">Pending processing</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="rc.filters.from_date" ng-change="rc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="rc.filters.to_date" ng-change="rc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Per Page</label>
                <select class="form-select" ng-model="rc.perPage" ng-change="rc.applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-6">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by refund ID or transaction ID" ng-model="rc.filters.search" ng-change="rc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end gap-2">
                <button class="btn btn-success" ng-click="rc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-outline-secondary" ng-click="rc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="rc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading refunds...</p>
            </div>
        </div>
        <div ng-hide="rc.loading" class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Refund ID</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="refund in rc.refunds track by $index">
                    <td>@{{ (rc.pagination.current_page - 1) * rc.pagination.per_page + $index + 1 }}</td>
                    <td><code>@{{ refund.refund_id }}</code></td>
                    <td><code>@{{ (refund.transaction && refund.transaction.txn_id) || refund.transaction_id || 'N/A' }}</code></td>
                    <td><strong>@{{ refund.amount | number:2 }}</strong></td>
                    <td>@{{ refund.currency || 'INR' }}</td>
                    <td>
                        <span class="badge" ng-class="{'bg-success': refund.status==='completed', 'bg-danger': refund.status==='failed', 'bg-secondary': refund.status==='cancelled', 'bg-warning text-dark': refund.status==='pending' || refund.status==='pending_approval', 'bg-info': refund.status==='processing' || refund.status==='pending_processing'}">@{{ refund.status | uppercase }}</span>
                    </td>
                    <td>@{{ refund.reason || 'N/A' }}</td>
                    <td>@{{ refund.created_at | date:'MMM d, y HH:mm' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" ng-click="rc.viewRefund(refund)" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr ng-if="rc.refunds.length===0 && !rc.loading">
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-2">No refunds found</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div ng-if="rc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing @{{ rc.pagination.from || 0 }} to @{{ rc.pagination.to || 0 }} of @{{ rc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="rc.pagination.current_page > 1" ng-click="rc.loadPage(rc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in rc.getPaginationPages() track by page" ng-class="{'active': page === rc.pagination.current_page}" ng-click="rc.loadPage(page)">@{{ page }}</a>
                <a href="#" class="page-link" ng-if="rc.pagination.current_page < rc.pagination.last_page" ng-click="rc.loadPage(rc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>

    <!-- Refund Details Modal -->
    <div class="modal fade" id="refundDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" ng-if="rc.selectedRefund">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise"></i> Refund Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Refund ID:</strong><br>
                            <code>@{{ rc.selectedRefund.refund_id }}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Transaction ID:</strong><br>
                            <code>@{{ (rc.selectedRefund.transaction && rc.selectedRefund.transaction.txn_id) || rc.selectedRefund.transaction_id || 'N/A' }}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Refund Amount:</strong><br>
                            <span class="text-danger fw-bold">@{{ rc.selectedRefund.currency || 'INR' }} @{{ rc.selectedRefund.amount | number:2 }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge" ng-class="{'bg-success': rc.selectedRefund.status==='completed', 'bg-danger': rc.selectedRefund.status==='failed', 'bg-secondary': rc.selectedRefund.status==='cancelled', 'bg-warning text-dark': rc.selectedRefund.status==='pending' || rc.selectedRefund.status==='pending_approval', 'bg-info': rc.selectedRefund.status==='processing' || rc.selectedRefund.status==='pending_processing'}">
                                @{{ rc.selectedRefund.status | uppercase }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Type:</strong><br>
                            <span class="badge" ng-class="{'bg-info': rc.selectedRefund.is_partial, 'bg-success': !rc.selectedRefund.is_partial}">
                                @{{ rc.selectedRefund.is_partial ? 'Partial Refund' : 'Full Refund' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Currency:</strong><br>
                            @{{ rc.selectedRefund.currency || 'INR' }}
                        </div>
                        <div class="col-md-12">
                            <strong>Reason:</strong><br>
                            <p class="mb-0">@{{ rc.selectedRefund.reason || 'No reason provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Created At:</strong><br>
                            @{{ rc.selectedRefund.created_at | date:'MMM d, y HH:mm:ss' }}
                        </div>
                        <div class="col-md-6" ng-if="rc.selectedRefund.processed_at">
                            <strong>Processed At:</strong><br>
                            @{{ rc.selectedRefund.processed_at | date:'MMM d, y HH:mm:ss' }}
                        </div>
                        <div class="col-md-12" ng-if="rc.selectedRefund.gateway_refund_id">
                            <strong>Gateway Refund ID:</strong><br>
                            <code>@{{ rc.selectedRefund.gateway_refund_id }}</code>
                        </div>
                        <div class="col-md-12" ng-if="rc.selectedRefund.notes">
                            <strong>Notes:</strong><br>
                            <p class="mb-0">@{{ rc.selectedRefund.notes }}</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Refund Modal -->
    <div class="modal fade refund-create-modal" id="createRefundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise"></i> Create Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="refund-approval-note">
                        <i class="bi bi-info-circle me-1"></i>
                        Amounts <strong>≥ {{ number_format($refundApprovalThreshold, 0) }}</strong> (same currency as the transaction) require <strong>admin approval</strong> before processing.
                    </div>
                    <form ng-submit="rc.createRefund(); $event.preventDefault();">
                        <div class="mb-3">
                            <label class="form-label">Transaction ID *</label>
                            <input type="text" class="form-control" ng-model="rc.newRefund.transaction_id" required id="refundTransactionId"
                                   ng-change="rc.onTransactionIdChange()"
                                   ng-blur="rc.lookupPayment()">
                            <div class="field-hint">Enter original payment transaction ID. Refund currency is always the same as the payment.</div>
                            <div class="refund-payment-currency is-loading" ng-if="rc.lookupLoading">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Looking up payment…
                            </div>
                            <div class="refund-payment-currency is-error" ng-if="!rc.lookupLoading && rc.lookupError">
                                <i class="bi bi-exclamation-circle"></i>
                                @{{ rc.lookupError }}
                            </div>
                            <div class="refund-payment-currency" ng-if="!rc.lookupLoading && rc.paymentInfo && !rc.lookupError">
                                <i class="bi bi-currency-exchange"></i>
                                <span>Refund in</span>
                                <span class="badge bg-primary">@{{ rc.paymentInfo.currency }}</span>
                                <span class="text-muted">· max @{{ rc.paymentInfo.currency }} @{{ rc.paymentInfo.refundable_amount | number:2 }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text" ng-if="rc.paymentInfo">@{{ rc.paymentInfo.currency }}</span>
                                <input type="number" class="form-control" ng-model="rc.newRefund.amount" step="0.01" min="0.01" required id="refundAmount">
                            </div>
                            <div class="field-hint" ng-if="rc.paymentInfo">Enter amount in @{{ rc.paymentInfo.currency }} (original payment currency).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" rows="3" ng-model="rc.newRefund.reason" id="refundReason"></textarea>
                            <div class="field-hint">Optional note for reference/audit trail.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ng-disabled="rc.creating">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="rc.createRefund()" ng-disabled="rc.creating">
                        <span ng-if="rc.creating" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span ng-if="!rc.creating">Create Refund</span>
                        <span ng-if="rc.creating">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('merchant.refunds.angular.main_controller')
@endsection

