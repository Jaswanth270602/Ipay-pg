@extends('layouts.app-sidebar')

@section('title', 'Risk Management - ' . config('app.name'))
@section('page-title','Risk Management')

@section('content')
<style>
    /* Scoped: Risk Management KPI cards */
    .risk-kpis .risk-kpi {
        position: relative;
        overflow: hidden;
        padding: 18px 18px 16px 18px;
        min-height: 98px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .risk-kpis .risk-kpi::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(120px 80px at 16% 0%, var(--kpi-tint, rgba(124,58,237,.10)), transparent 70%);
        pointer-events: none;
    }
    .risk-kpis .risk-kpi::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 4px;
        width: 100%;
        background: var(--kpi-accent, var(--primary-violet));
        opacity: .95;
        pointer-events: none;
    }
    .risk-kpis .risk-kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        position: relative;
        z-index: 1;
        flex: 1 1 auto;
    }
    .risk-kpis .risk-kpi-metrics {
        flex: 1 1 auto;
        min-width: 0;
    }
    .risk-kpis .risk-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: var(--kpi-tint, rgba(124,58,237,.10));
        color: var(--kpi-accent, var(--primary-violet));
        flex: 0 0 auto;
    }
    .risk-kpis .stat-value {
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
        color: #111827;
        letter-spacing: -0.02em;
    }
    .risk-kpis .stat-label {
        margin-top: 6px;
        color: #6b7280;
        font-weight: 600;
        font-size: 13px;
        position: relative;
        z-index: 1;
        line-height: 1.15;
        min-height: 30px; /* reserve space so all KPI cards match height */
    }
    @media (max-width: 991.98px) {
        .risk-kpis .risk-kpi { padding: 16px; }
        .risk-kpis .stat-value { font-size: 26px; }
    }
</style>
<div ng-cloak ng-app="ipayApp" ng-controller="AdminRiskController as arc">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4 risk-kpis">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#2563eb; --kpi-tint:rgba(37,99,235,.12);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.total_rules || 0 }}</div>
                        <div class="stat-label">Active Rules</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-sliders"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#d97706; --kpi-tint:rgba(217,119,6,.14);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.total_events || 0 }}</div>
                        <div class="stat-label">Unresolved Events</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#0ea5e9; --kpi-tint:rgba(14,165,233,.14);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.fds_review || 0 }}</div>
                        <div class="stat-label">FDS Review</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-person-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#111827; --kpi-tint:rgba(17,24,39,.10);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.fds_block || 0 }}</div>
                        <div class="stat-label">FDS Block</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-shield-lock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#dc2626; --kpi-tint:rgba(220,38,38,.12);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.critical_alerts || 0 }}</div>
                        <div class="stat-label">Critical Alerts</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-exclamation-octagon"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card risk-kpi" style="--kpi-accent:#16a34a; --kpi-tint:rgba(22,163,74,.12);">
                <div class="risk-kpi-top">
                    <div class="risk-kpi-metrics">
                        <div class="stat-value">@{{ arc.stats.high_alerts || 0 }}</div>
                        <div class="stat-label">High Alerts</div>
                    </div>
                    <div class="risk-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#rules" ng-click="arc.loadRules()">Risk Rules</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#events" ng-click="arc.loadEvents()">Risk Events</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#alerts" ng-click="arc.loadAlerts()">Fraud Alerts</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#fds-decisions" ng-click="arc.loadFdsDecisions()">FDS Decisions</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#fds-events" ng-click="arc.loadFdsEvents()">FDS Events</a></li>
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

        <!-- FDS Decisions Tab -->
        <div class="tab-pane fade" id="fds-decisions">
            <div class="stat-card mb-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Merchant ID" ng-model="arc.fdsDecisionFilters.merchant_id" ng-change="arc.loadFdsDecisions()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.fdsDecisionFilters.decision" ng-change="arc.loadFdsDecisions()">
                            <option value="">All Decisions</option>
                            <option value="allow">allow</option>
                            <option value="review">review</option>
                            <option value="block">block</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input class="form-control" placeholder="Transaction ID (contains)" ng-model="arc.fdsDecisionFilters.transaction_id" ng-change="arc.loadFdsDecisions()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Txn</th>
                                <th>Merchant</th>
                                <th>Score</th>
                                <th>Decision</th>
                                <th>Reasons</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="d in arc.fdsDecisions.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ d.transaction_id || '-' }}</td>
                                <td>@{{ d.merchant_id || '-' }}</td>
                                <td>@{{ d.risk_score }}/100</td>
                                <td>
                                    <span class="badge" ng-class="{
                                        'bg-success': d.decision === 'allow',
                                        'bg-warning': d.decision === 'review',
                                        'bg-danger': d.decision === 'block'
                                    }">@{{ d.decision }}</span>
                                </td>
                                <td style="max-width: 420px;">
                                    <span ng-if="d.reasons && d.reasons.length">@{{ d.reasons.join(', ') }}</span>
                                    <span ng-if="!d.reasons || !d.reasons.length">-</span>
                                </td>
                                <td>@{{ d.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FDS Events Tab -->
        <div class="tab-pane fade" id="fds-events">
            <div class="stat-card mb-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Fraud Txn ID" ng-model="arc.fdsEventFilters.fraud_transaction_id" ng-change="arc.loadFdsEvents()">
                    </div>
                    <div class="col-md-3">
                        <input class="form-control" placeholder="Rule name (exact)" ng-model="arc.fdsEventFilters.rule_name" ng-change="arc.loadFdsEvents()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" ng-model="arc.fdsEventFilters.triggered" ng-change="arc.loadFdsEvents()">
                            <option value="">All</option>
                            <option value="1">Triggered</option>
                            <option value="0">Not Triggered</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fraud Txn</th>
                                <th>Rule</th>
                                <th>Triggered</th>
                                <th>Score</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="e in arc.fdsEvents.data">
                                <td>@{{ $index + 1 }}</td>
                                <td>@{{ e.fraud_transaction_id || '-' }}</td>
                                <td>@{{ e.rule_name || '-' }}</td>
                                <td>
                                    <span class="badge" ng-class="e.triggered ? 'bg-danger' : 'bg-secondary'">
                                        @{{ e.triggered ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>@{{ e.score || 0 }}</td>
                                <td>@{{ e.created_at }}</td>
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
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input class="form-control" ng-model="arc.ruleForm.name" placeholder="e.g. High Velocity - 1 hour">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Rule Type <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="arc.ruleForm.type" ng-change="arc.applyRuleTemplate()">
                                <option value="velocity">Velocity</option>
                                <option value="amount_limit">Amount Limit</option>
                                <option value="geo_block">Geo Block</option>
                                <option value="merchant_block">Merchant Block</option>
                                <option value="ip_block">IP Block</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-1">Rule Config (JSON) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-secondary" ng-click="arc.applyRuleTemplate()">
                                Use Template
                            </button>
                        </div>
                        <textarea class="form-control font-monospace" rows="6" ng-model="arc.ruleForm.rule_config_json" placeholder='{"max_transactions": 10, "time_window": "1h"}'></textarea>
                        <div class="form-text">Provide valid JSON config for selected rule type.</div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Action</label>
                            <select class="form-select" ng-model="arc.ruleForm.action">
                                <option value="block">Block</option>
                                <option value="alert">Alert</option>
                                <option value="review">Review</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <input type="number" min="0" max="100" class="form-control" placeholder="0 to 100" ng-model="arc.ruleForm.priority">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" ng-model="arc.ruleForm.status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" ng-click="arc.createRule()" ng-disabled="arc.ruleSubmitting || !arc.ruleForm.name">
                        <span ng-if="!arc.ruleSubmitting">Create</span>
                        <span ng-if="arc.ruleSubmitting">Creating...</span>
                    </button>
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

