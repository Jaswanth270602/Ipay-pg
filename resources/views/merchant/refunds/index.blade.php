@extends('layouts.app-sidebar')

@section('title', 'Refunds - ' . config('app.name'))
@section('page-title','Refunds')

@section('content')
<div ng-app="badlicashApp" ng-controller="RefundsController as rc">
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
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
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
                        <span class="badge" ng-class="{'bg-success': refund.status==='completed', 'bg-danger': refund.status==='failed', 'bg-warning': refund.status==='pending', 'bg-info': refund.status==='processing'}">@{{ refund.status | uppercase }}</span>
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
                            <span class="badge" ng-class="{'bg-success': rc.selectedRefund.status==='completed', 'bg-danger': rc.selectedRefund.status==='failed', 'bg-warning': rc.selectedRefund.status==='pending', 'bg-info': rc.selectedRefund.status==='processing'}">
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
    <div class="modal fade" id="createRefundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form ng-submit="rc.createRefund(); $event.preventDefault();">
                        <div class="mb-3">
                            <label class="form-label">Transaction ID *</label>
                            <input type="text" class="form-control" ng-model="rc.newRefund.transaction_id" required id="refundTransactionId">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" class="form-control" ng-model="rc.newRefund.amount" step="0.01" min="0.01" required id="refundAmount">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" rows="3" ng-model="rc.newRefund.reason" id="refundReason"></textarea>
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

