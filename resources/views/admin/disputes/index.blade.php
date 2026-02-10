@extends('layouts.app-sidebar')

@section('title', 'Disputes - Admin - ' . config('app.name'))
@section('page-title', 'Disputes')

@push('styles')
<style>
    .dispute-summary-card {
        border-radius: 12px;
        padding: 20px;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid;
    }
    
    .dispute-summary-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    .dispute-summary-card.due-today {
        border-left-color: #dc3545;
    }
    
    .dispute-summary-card.due-tomorrow {
        border-left-color: #fd7e14;
    }
    
    .dispute-summary-card.insufficient-evidence {
        border-left-color: #6c757d;
    }
    
    .summary-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 12px;
    }
    
    .summary-badge.urgent {
        background: #fee;
        color: #dc3545;
    }
    
    .summary-badge.critical {
        background: #fff4e6;
        color: #fd7e14;
    }
    
    .summary-badge.info {
        background: #e9ecef;
        color: #6c757d;
    }
    
    .dispute-tabs {
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 24px;
    }
    
    .dispute-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #6b7280;
        font-weight: 500;
        padding: 12px 24px;
        margin-bottom: -2px;
    }
    
    .dispute-tabs .nav-link.active {
        color: #667eea;
        border-bottom-color: #667eea;
        background: transparent;
    }
    
    .dispute-status-badge {
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .dispute-status-badge.action_required {
        background: #fef3c7;
        color: #92400e;
    }
    
    .dispute-status-badge.under_review {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .dispute-status-badge.insufficient_evidence {
        background: #f3f4f6;
        color: #374151;
    }
    
    .dispute-status-badge.won {
        background: #d1fae5;
        color: #065f46;
    }
    
    .dispute-status-badge.lost {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .dispute-status-badge.closed {
        background: #e5e7eb;
        color: #374151;
    }
    
    .due-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .due-badge.overdue {
        background: #fee;
        color: #dc3545;
    }
    
    .due-badge.due-today {
        background: #fff4e6;
        color: #fd7e14;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminDisputesController as adc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Disputes']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Disputes</h2>
            <p class="text-muted">Manage chargebacks and disputes</p>
        </div>
    </div>

    <!-- Summary Cards (Action Required Tab Only) -->
    <div ng-if="adc.activeTab === 'action_required'" class="row mb-4">
        <div class="col-md-4">
            <div class="dispute-summary-card due-today">
                <span class="summary-badge urgent">Urgent</span>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Due Today</div>
                        <div class="h4 mb-0">@{{ adc.summary.due_today_count || 0 }}</div>
                        <div class="text-muted small mt-1">₹@{{ adc.summary.due_today_amount || 0 | number:2 }}</div>
                    </div>
                    <div class="text-danger" style="font-size: 32px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dispute-summary-card due-tomorrow">
                <span class="summary-badge critical">Critical</span>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Due Tomorrow</div>
                        <div class="h4 mb-0">@{{ adc.summary.due_tomorrow_count || 0 }}</div>
                        <div class="text-muted small mt-1">₹@{{ adc.summary.due_tomorrow_amount || 0 | number:2 }}</div>
                    </div>
                    <div class="text-warning" style="font-size: 32px;">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dispute-summary-card insufficient-evidence">
                <span class="summary-badge info">Insufficient Evidence</span>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Count</div>
                        <div class="h4 mb-0">@{{ adc.summary.insufficient_evidence_count || 0 }}</div>
                        <div class="text-muted small mt-1">₹@{{ adc.summary.insufficient_evidence_amount || 0 | number:2 }}</div>
                    </div>
                    <div class="text-muted" style="font-size: 32px;">
                        <i class="bi bi-arrow-repeat" ng-click="adc.loadSummary()" style="cursor: pointer;" title="Refresh"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav dispute-tabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link" ng-class="{active: adc.activeTab === 'action_required'}" 
                    ng-click="adc.setTab('action_required')">
                Action Required
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" ng-class="{active: adc.activeTab === 'under_review'}" 
                    ng-click="adc.setTab('under_review')">
                Under Review
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" ng-class="{active: adc.activeTab === 'closed'}" 
                    ng-click="adc.setTab('closed')">
                Closed
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" ng-class="{active: adc.activeTab === 'all'}" 
                    ng-click="adc.setTab('all')">
                All Disputes
            </button>
        </li>
    </ul>

    <!-- Filters and Actions -->
    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <select class="form-select" ng-model="adc.filters.date_range" ng-change="adc.applyDateRange()">
                    <option value="">All Time</option>
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="180">Last 180 days</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" 
                       placeholder="Dispute ID, Payment ID, Order ID" 
                       ng-model="adc.filters.search" 
                       ng-keyup="$event.keyCode === 13 && adc.loadDisputes()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control" 
                       placeholder="Filter by Merchant" 
                       ng-model="adc.filters.merchant_id" 
                       ng-change="adc.loadDisputes()">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" ng-click="adc.loadDisputes()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <div>
                    <label class="form-label me-2">Show</label>
                    <select class="form-select form-select-sm d-inline-block" style="width: auto;" 
                            ng-model="adc.pagination.per_page" ng-change="adc.loadDisputes()">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="ms-2 text-muted">entries</span>
                </div>
                <div>
                    <button class="btn btn-sm btn-success" ng-click="adc.exportCSV()">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="adc.clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Disputes Table -->
    <div class="stat-card">
        <div ng-show="adc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading disputes...</p>
            </div>
        </div>

        <div ng-hide="adc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Updated On</th>
                            <th>Created On</th>
                            <th>Dispute ID</th>
                            <th>Order ID</th>
                            <th>Payment ID</th>
                            <th>Reason</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Due By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="adc.disputes.length === 0">
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0">No disputes found</p>
                                    <small ng-if="adc.activeTab === 'action_required'">No new disputes added today.</small>
                                </div>
                            </td>
                        </tr>
                        <tr ng-repeat="dispute in adc.disputes">
                            <td>@{{ dispute.updated_at_formatted }}</td>
                            <td>@{{ dispute.created_at_formatted }}</td>
                            <td><code>@{{ dispute.dispute_id }}</code></td>
                            <td>@{{ dispute.order_id || '-' }}</td>
                            <td>@{{ dispute.payment_id || '-' }}</td>
                            <td>@{{ dispute.reason }}</td>
                            <td><strong>@{{ dispute.currency || 'INR' }} @{{ dispute.amount | number:2 }}</strong></td>
                            <td>
                                <span class="dispute-status-badge" ng-class="dispute.status">
                                    @{{ dispute.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td>
                                <span ng-if="dispute.due_by">
                                    @{{ dispute.due_by_formatted }}
                                    <span class="due-badge" 
                                          ng-class="{'overdue': dispute.is_past_due, 'due-today': !dispute.is_past_due && dispute.due_by_human.includes('hours')}">
                                        @{{ dispute.is_past_due ? 'Overdue' : dispute.due_by_human }}
                                    </span>
                                </span>
                                <span ng-if="!dispute.due_by">-</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" ng-click="adc.viewDispute(dispute.id)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <button ng-if="dispute.status === 'action_required' && !dispute.evidence_submitted" 
                                        class="btn btn-sm btn-outline-primary" 
                                        ng-click="adc.viewDispute(dispute.id)">
                                    <i class="bi bi-upload"></i> Upload
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3" ng-if="adc.pagination.total > 0">
                <div class="text-muted">
                    Showing @{{ adc.pagination.from }} to @{{ adc.pagination.to }} of @{{ adc.pagination.total }} entries
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item" ng-class="{disabled: adc.pagination.current_page === 1}">
                            <a class="page-link" href="#" ng-click="adc.loadDisputes(adc.pagination.current_page - 1)">Previous</a>
                        </li>
                        <li class="page-item" ng-repeat="page in adc.getPageNumbers() track by $index" 
                            ng-class="{active: page === adc.pagination.current_page}">
                            <a class="page-link" href="#" ng-click="adc.loadDisputes(page)">@{{ page }}</a>
                        </li>
                        <li class="page-item" ng-class="{disabled: adc.pagination.current_page === adc.pagination.last_page}">
                            <a class="page-link" href="#" ng-click="adc.loadDisputes(adc.pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.disputes.angular.main_controller')
