@extends('layouts.app-sidebar')

@section('title', 'Disputes - ' . config('app.name'))
@section('page-title','Disputes')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="MerchantDisputesController as mdc">
    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle me-2"></i>
        Disputes are managed by the platform admin. You can view status updates here; contact support if you have questions.
    </div>

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
                        <th>Due By</th>
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
                            <span class="badge"
                                  ng-class="{
                                'bg-info': d.status === 'action_required',
                                'bg-primary': d.status === 'under_review',
                                'bg-warning text-dark': d.status === 'insufficient_evidence',
                                'bg-success': d.status === 'won',
                                'bg-danger': d.status === 'lost',
                                'bg-secondary': d.status === 'closed'
                            }"
                                  ng-attr-title="@{{ d.status_formatted || d.status }}"
                                  ng-bind="d.status_formatted || d.status"></span>
                        </td>
                        <td>@{{ d.due_by_formatted || '-' }}</td>
                        <td>@{{ d.created_at | date:'MMM d, y HH:mm' }}</td>
                    </tr>
                    <tr ng-if="!mdc.items.data || mdc.items.data.length === 0">
                        <td colspan="7" class="text-center text-muted py-4">No disputes found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('merchant.disputes.angular.main_controller')
