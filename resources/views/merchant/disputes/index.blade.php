@extends('layouts.app-sidebar')

@section('title', 'Disputes - ' . config('app.name'))
@section('page-title','Disputes')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantDisputesController as mdc">
    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="mdc.filters.status" ng-change="mdc.load()">
                    <option value="">All</option>
                    <option value="action_required">Action Required</option>
                    <option value="under_review">Under Review</option>
                    <option value="insufficient_evidence">Insufficient Evidence</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="col-md-8 d-flex align-items-end justify-content-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newDisputeModal">
                    <i class="bi bi-plus-lg"></i> New Dispute
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transaction</th>
                        <th>Reason</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="d in mdc.items.data">
                        <td>@{{ ($index + 1) }}</td>
                        <td>@{{ d.transaction_id || d.order_id || '-' }}</td>
                        <td>@{{ d.reason_formatted || d.reason }}</td>
                        <td><strong>@{{ d.currency || 'INR' }} @{{ d.amount || 0 | number:2 }}</strong></td>
                        <td>
                            <span class="badge" ng-class="{
                                'bg-info': d.status === 'action_required',
                                'bg-primary': d.status === 'under_review',
                                'bg-warning text-dark': d.status === 'insufficient_evidence',
                                'bg-success': d.status === 'won',
                                'bg-danger': d.status === 'lost',
                                'bg-secondary': d.status === 'closed'
                            }" ng-bind="d.status_formatted || d.status"></span>
                        </td>
                        <td>@{{ d.created_at | date:'MMM d, y HH:mm' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="newDisputeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Dispute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Transaction ID <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" ng-model="mdc.form.transaction_id" placeholder="Enter transaction ID (e.g., TXN_123 or 123)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order ID <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" ng-model="mdc.form.order_id" placeholder="e.g., order_123456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <select class="form-select" ng-model="mdc.form.reason" required>
                            <option value="">Select Reason</option>
                            <option value="fraud">Fraud</option>
                            <option value="product_not_received">Product Not Received</option>
                            <option value="product_not_as_described">Product Not As Described</option>
                            <option value="duplicate_charge">Duplicate Charge</option>
                            <option value="refund_not_processed">Refund Not Processed</option>
                            <option value="subscription_canceled">Subscription Canceled</option>
                            <option value="no_authorization">No Authorization</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" ng-model="mdc.form.amount" placeholder="0.00" required min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" ng-model="mdc.form.currency">
                                <option value="INR" selected>INR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Card Network <span class="text-muted">(optional)</span></label>
                        <select class="form-select" ng-model="mdc.form.card_network">
                            <option value="">Select Card Network</option>
                            <option value="VISA">VISA</option>
                            <option value="MASTERCARD">MASTERCARD</option>
                            <option value="RUPAY">RUPAY</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Internal Notes <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" ng-model="mdc.form.internal_notes" rows="3" placeholder="Add any additional notes about this dispute..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" ng-click="mdc.create()" ng-disabled="mdc.creating || !mdc.form.reason || !mdc.form.amount || mdc.form.amount <= 0">
                        <span ng-if="mdc.creating" class="spinner-border spinner-border-sm me-2"></span>
                        <span ng-if="!mdc.creating">Create</span>
                        <span ng-if="mdc.creating">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('merchant.disputes.angular.main_controller')


