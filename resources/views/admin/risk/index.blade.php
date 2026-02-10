@extends('layouts.app-sidebar')

@section('title', 'Risk Management - ' . config('app.name'))
@section('page-title','Risk Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminRiskController as arc">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-value text-white">@{{ arc.stats.total_rules || 0 }}</div>
                <div class="stat-label text-white-50">Active Rules</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-gradient-warning">
                <div class="stat-value">@{{ arc.stats.total_events || 0 }}</div>
                <div class="stat-label">Unresolved Events</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-value text-white">@{{ arc.stats.critical_alerts || 0 }}</div>
                <div class="stat-label text-white-50">Critical Alerts</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-gradient-info">
                <div class="stat-value">@{{ arc.stats.high_alerts || 0 }}</div>
                <div class="stat-label">High Alerts</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#rules" ng-click="arc.loadRules()">Risk Rules</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#events" ng-click="arc.loadEvents()">Risk Events</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#alerts" ng-click="arc.loadAlerts()">Fraud Alerts</a></li>
    </ul>

    <div class="tab-content">
        <!-- Risk Rules Tab -->
        <div class="tab-pane fade show active" id="rules">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Risk Rules</h5>
                    <button class="btn btn-primary" ng-click="arc.openRuleModal()">
                        <i class="bi bi-plus-lg"></i> New Rule
                    </button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Search..." ng-model="arc.ruleSearch" ng-change="arc.loadRules()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.ruleFilters.status" ng-change="arc.loadRules()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.ruleFilters.type" ng-change="arc.loadRules()">
                            <option value="">All Types</option>
                            <option value="velocity">Velocity</option>
                            <option value="amount_limit">Amount Limit</option>
                            <option value="geo_block">Geo Block</option>
                            <option value="merchant_block">Merchant Block</option>
                            <option value="ip_block">IP Block</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Action</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="r in arc.rules.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ r.name }}</td>
                                <td><span class="badge bg-secondary">@{{ r.type }}</span></td>
                                <td><span class="badge bg-info">@{{ r.action }}</span></td>
                                <td>@{{ r.priority }}</td>
                                <td>
                                    <select class="form-select form-select-sm" ng-model="r.status" ng-change="arc.updateRule(r)">
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger" ng-click="arc.deleteRule(r)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Risk Events Tab -->
        <div class="tab-pane fade" id="events">
            <div class="stat-card mb-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Merchant ID" ng-model="arc.eventFilters.merchant_id" ng-change="arc.loadEvents()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.eventFilters.severity" ng-change="arc.loadEvents()">
                            <option value="">All Severity</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.eventFilters.resolved" ng-change="arc.loadEvents()">
                            <option value="">All</option>
                            <option value="0">Unresolved</option>
                            <option value="1">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Rule</th>
                                <th>Merchant</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Resolved</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="e in arc.events.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ e.rule?.name || '-' }}</td>
                                <td>@{{ e.merchant_id || '-' }}</td>
                                <td><span class="badge bg-secondary">@{{ e.event_type }}</span></td>
                                <td>
                                    <span class="badge" ng-class="{
                                        'bg-success': e.severity === 'low',
                                        'bg-warning': e.severity === 'medium',
                                        'bg-danger': e.severity === 'high' || e.severity === 'critical'
                                    }">@{{ e.severity }}</span>
                                </td>
                                <td>
                                    <span class="badge" ng-class="e.resolved ? 'bg-success' : 'bg-danger'">
                                        @{{ e.resolved ? 'Resolved' : 'Open' }}
                                    </span>
                                </td>
                                <td>@{{ e.created_at }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" ng-if="!e.resolved" ng-click="arc.resolveEvent(e)">
                                        Resolve
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fraud Alerts Tab -->
        <div class="tab-pane fade" id="alerts">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Fraud Alerts</h5>
                    <button class="btn btn-primary" ng-click="arc.openAlertModal()">
                        <i class="bi bi-plus-lg"></i> New Alert
                    </button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Merchant ID" ng-model="arc.alertFilters.merchant_id" ng-change="arc.loadAlerts()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.alertFilters.status" ng-change="arc.loadAlerts()">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="investigating">Investigating</option>
                            <option value="resolved">Resolved</option>
                            <option value="false_positive">False Positive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.alertFilters.severity" ng-change="arc.loadAlerts()">
                            <option value="">All Severity</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Merchant</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Risk Score</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="a in arc.alerts.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ a.merchant_id || '-' }}</td>
                                <td><span class="badge bg-secondary">@{{ a.alert_type }}</span></td>
                                <td>
                                    <span class="badge" ng-class="{
                                        'bg-success': a.severity === 'low',
                                        'bg-warning': a.severity === 'medium',
                                        'bg-danger': a.severity === 'high' || a.severity === 'critical'
                                    }">@{{ a.severity }}</span>
                                </td>
                                <td>@{{ a.risk_score }}/100</td>
                                <td>
                                    <select class="form-select form-select-sm" ng-model="a.status" ng-change="arc.updateAlert(a)">
                                        <option value="open">open</option>
                                        <option value="investigating">investigating</option>
                                        <option value="resolved">resolved</option>
                                        <option value="false_positive">false_positive</option>
                                    </select>
                                </td>
                                <td>@{{ a.created_at }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" ng-click="arc.viewAlert(a)">
                                        View
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Rule Modal -->
    <div class="modal fade" id="ruleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Risk Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input class="form-control" placeholder="Rule Name" ng-model="arc.ruleForm.name"></div>
                    <div class="mb-2">
                        <select class="form-select" ng-model="arc.ruleForm.type">
                            <option value="velocity">Velocity</option>
                            <option value="amount_limit">Amount Limit</option>
                            <option value="geo_block">Geo Block</option>
                            <option value="merchant_block">Merchant Block</option>
                            <option value="ip_block">IP Block</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Rule Config (JSON)</label>
                        <textarea class="form-control" rows="3" ng-model="arc.ruleForm.rule_config_json" placeholder='{"max_transactions": 10, "time_window": "1h"}'></textarea>
                    </div>
                    <div class="mb-2">
                        <select class="form-select" ng-model="arc.ruleForm.action">
                            <option value="block">Block</option>
                            <option value="alert">Alert</option>
                            <option value="review">Review</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" min="0" max="100" class="form-control" placeholder="Priority" ng-model="arc.ruleForm.priority">
                        </div>
                        <div class="col-6">
                            <select class="form-select" ng-model="arc.ruleForm.status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" ng-click="arc.createRule()">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Fraud Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input class="form-control" placeholder="Merchant ID" ng-model="arc.alertForm.merchant_id"></div>
                    <div class="mb-2"><input class="form-control" placeholder="Transaction ID" ng-model="arc.alertForm.transaction_id"></div>
                    <div class="mb-2">
                        <select class="form-select" ng-model="arc.alertForm.alert_type">
                            <option value="suspicious_pattern">Suspicious Pattern</option>
                            <option value="chargeback_risk">Chargeback Risk</option>
                            <option value="velocity_anomaly">Velocity Anomaly</option>
                            <option value="amount_anomaly">Amount Anomaly</option>
                            <option value="geo_anomaly">Geo Anomaly</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <select class="form-select" ng-model="arc.alertForm.severity">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" rows="3" placeholder="Description" ng-model="arc.alertForm.description"></textarea>
                    </div>
                    <div class="mb-2">
                        <input type="number" min="0" max="100" class="form-control" placeholder="Risk Score (0-100)" ng-model="arc.alertForm.risk_score">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" ng-click="arc.createAlert()">Create</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.risk.angular.main_controller')

