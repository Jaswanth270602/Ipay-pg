@extends('layouts.app-sidebar')

@section('title', 'Admin Subscriptions - ' . config('app.name'))
@section('page-title','Admin Subscriptions')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminSubscriptionsController as asc">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Plans</h5>
                    <button class="btn btn-sm btn-primary" ng-click="asc.openPlanModal()">
                        <i class="bi bi-plus-lg"></i> New Plan
                    </button>
                </div>
                <div class="mb-2">
                    <input class="form-control" placeholder="Search plans..." ng-model="asc.planSearch" ng-change="asc.loadPlans()">
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Interval</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="p in asc.plans.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ p.name }}</td>
                                <td>INR @{{ p.amount | number:2 }}</td>
                                <td>@{{ p.interval_count }} @{{ p.interval }}</td>
                                <td>
                                    <select class="form-select form-select-sm" ng-model="p.status" ng-change="asc.updatePlan(p)">
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Subscriptions</h5>
                    <button class="btn btn-sm btn-primary" ng-click="asc.openSubscriptionModal()">
                        <i class="bi bi-plus-lg"></i> New Subscription
                    </button>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <input class="form-control" placeholder="Merchant ID" ng-model="asc.filters.merchant_id" ng-change="asc.loadSubscriptions()">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" ng-model="asc.filters.status" ng-change="asc.loadSubscriptions()">
                            <option value="">All Status</option>
                            <option value="active">active</option>
                            <option value="past_due">past_due</option>
                            <option value="canceled">canceled</option>
                            <option value="expired">expired</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Merchant</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Period</th>
                                <th>Cancel at End</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="s in asc.subscriptions.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ s.merchant_id }}</td>
                                <td>@{{ s.plan?.name }}</td>
                                <td>
                                    <select class="form-select form-select-sm" ng-model="s.status" ng-change="asc.updateSubscription(s)">
                                        <option value="active">active</option>
                                        <option value="past_due">past_due</option>
                                        <option value="canceled">canceled</option>
                                        <option value="expired">expired</option>
                                    </select>
                                </td>
                                <td>@{{ s.current_period_start }} → @{{ s.current_period_end }}</td>
                                <td>
                                    <input type="checkbox" class="form-check-input" ng-model="s.cancel_at_period_end" ng-change="asc.updateSubscription(s)">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Plan Modal -->
    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input class="form-control" placeholder="Name" ng-model="asc.planForm.name"></div>
                    <div class="mb-2"><input class="form-control" placeholder="Code" ng-model="asc.planForm.code"></div>
                    <div class="mb-2"><input type="number" step="0.01" class="form-control" placeholder="Amount" ng-model="asc.planForm.amount"></div>
                    <div class="mb-2"><input class="form-control" placeholder="Currency" ng-model="asc.planForm.currency"></div>
                    <div class="row g-2">
                        <div class="col-6">
                            <select class="form-select" ng-model="asc.planForm.interval">
                                <option value="day">day</option>
                                <option value="week">week</option>
                                <option value="month">month</option>
                                <option value="year">year</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="number" min="1" class="form-control" placeholder="Interval Count" ng-model="asc.planForm.interval_count">
                        </div>
                    </div>
                    <div class="mt-2"><input type="number" min="0" class="form-control" placeholder="Trial Days" ng-model="asc.planForm.trial_days"></div>
                    <div class="mt-2">
                        <select class="form-select" ng-model="asc.planForm.status">
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" ng-click="asc.createPlan()">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Subscription Modal -->
    <div class="modal fade" id="subscriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input class="form-control" placeholder="Merchant ID" ng-model="asc.subscriptionForm.merchant_id"></div>
                    <div class="mb-2">
                        <select class="form-select" ng-model="asc.subscriptionForm.plan_id">
                            <option ng-repeat="p in asc.allPlans" value="@{{ p.id }}">@{{ p.name }} - INR @{{ p.amount | number:2 }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" ng-click="asc.createSubscription()">Create</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.subscriptions.angular.main_controller')


