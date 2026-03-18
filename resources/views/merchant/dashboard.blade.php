@extends('layouts.app-sidebar')

@section('title', 'Dashboard - ' . config('app.name'))
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .stat-card.card-transactions,
    .stat-card.card-volume,
    .stat-card.card-refunds,
    .stat-card.card-success-rate {
        position: relative;
        overflow: hidden;
        border: 1px solid #e5e7eb !important;
    }
    
    .stat-card.card-transactions::before,
    .stat-card.card-volume::before,
    .stat-card.card-refunds::before,
    .stat-card.card-success-rate::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #7c3aed, #ec4899);
    }
    
    .stat-card.card-transactions {
        background: #ffffff;
        color: #111827;
    }
    
    .stat-card.card-volume {
        background: #ffffff;
        color: #111827;
    }

    .stat-card.card-volume::before {
        background: linear-gradient(90deg, #ec4899, #f97373);
    }
    
    .stat-card.card-refunds {
        background: #ffffff;
        color: #111827;
    }

    .stat-card.card-refunds::before {
        background: linear-gradient(90deg, #fb7185, #f97373);
    }
    
    .stat-card.card-success-rate {
        background: #ffffff;
        color: #111827;
    }

    .stat-card.card-success-rate::before {
        background: linear-gradient(90deg, #7c3aed, #ec4899);
    }
    
    /* Ensure large numbers don't overflow cards */
    .stat-card h3.fw-bold {
        font-size: 24px;
        line-height: 1.2;
        word-break: break-word;
        white-space: normal;
    }

    .stat-card.card-transactions:hover,
    .stat-card.card-volume:hover,
    .stat-card.card-refunds:hover,
    .stat-card.card-success-rate:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    /* Quick Actions Cards */
    .quick-action-card {
        border: none !important;
        border-radius: 12px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        text-decoration: none !important;
    }
    
    .quick-action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
    }
    
    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        text-decoration: none !important;
    }
    
    .quick-action-card.card-payment-link,
    .quick-action-card.card-transactions,
    .quick-action-card.card-integration,
    .quick-action-card.card-api-keys {
        background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
        color: #ffffff;
    }
    
    .quick-action-card h6,
    .quick-action-card small,
    .quick-action-card i {
        color: #ffffff;
    }

    /* Recent transactions header styling */
    .recent-transactions-table thead {
        background: #ffe4e0 !important; /* light brownish red */
        color: #7c2d12 !important;
    }
</style>
@endpush

@section('content')
<div ng-app="ipayApp" ng-controller="DashboardController as dc">
    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="fw-bold" style="color:#E10600;">Welcome, {{ $user->name }}</h3>
            <p class="text-muted">{{ $merchant->name }} - <span class="badge {{ $merchant->test_mode ? 'bg-warning' : 'bg-success' }}">{{ $merchant->test_mode ? 'TEST MODE' : 'LIVE MODE' }}</span></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card card-transactions">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Total Transactions</h6>
                    <i class="bi bi-credit-card-2-front"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($stats['total_transactions']) }}</h3>
                <small>
                    <i class="bi bi-check-circle"></i> {{ number_format($stats['successful_transactions']) }} successful
                </small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-volume">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Total Volume</h6>
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $merchant->default_currency }} {{ number_format($stats['total_volume'], 2) }}</h3>
                <small>Lifetime</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-refunds">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Pending Refunds</h6>
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($stats['pending_refunds']) }}</h3>
                <small>Awaiting processing</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card card-success-rate">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Success Rate</h6>
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3 class="fw-bold mb-1">
                    @if($stats['total_transactions'] > 0)
                        {{ number_format(($stats['successful_transactions'] / $stats['total_transactions']) * 100, 1) }}%
                    @else
                        0%
                    @endif
                </h3>
                <small>Payment success</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Info -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="stat-card">
                <h5 class="mb-3"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('merchant.payment_links.index') }}" class="card quick-action-card card-payment-link">
                            <div class="card-body">
                                <i class="bi bi-link-45deg fs-4"></i>
                                <h6 class="mt-2 mb-1">Create Payment Link</h6>
                                <small>Generate a payment link for customers</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('merchant.transactions.index') }}" class="card quick-action-card card-transactions">
                            <div class="card-body">
                                <i class="bi bi-credit-card-2-front fs-4"></i>
                                <h6 class="mt-2 mb-1">View Transactions</h6>
                                <small>Browse all payment transactions</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('merchant.integration.index') }}" class="card quick-action-card card-integration">
                            <div class="card-body">
                                <i class="bi bi-code-square fs-4"></i>
                                <h6 class="mt-2 mb-1">Integration Guide</h6>
                                <small>Get integration code for your app</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('merchant.api_keys.index') }}" class="card quick-action-card card-api-keys">
                            <div class="card-body">
                                <i class="bi bi-key fs-4"></i>
                                <h6 class="mt-2 mb-1">API Keys</h6>
                                <small>Manage your API credentials</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Account Information</h5>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Webhook URL</small>
                    <code class="small d-block text-break">{{ $merchant->webhook_url ?? 'Not configured' }}</code>
                    @if(!$merchant->webhook_url)
                        <a href="{{ route('merchant.webhooks.index') }}" class="btn btn-sm btn-primary mt-2">Configure</a>
                    @endif
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">API Documentation</small>
                    <a href="/docs/api" class="btn btn-sm btn-outline-primary">View API Docs</a>
                </div>
                @if($merchant->onboarding_status !== 'completed')
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Onboarding Incomplete</strong>
                        <p class="mb-0 small">Complete your KYC to enable live mode.</p>
                        <a href="{{ route('merchant.onboarding.index') }}" class="btn btn-sm btn-warning mt-2">Complete Onboarding</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="stat-card" style="border-radius: 16px; border: 1px solid #e5e7eb;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-clock-history me-2" style="color:#7c2d12;"></i>
                        Recent Transactions
                    </h5>
                    <a href="{{ route('merchant.transactions.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>

                <div ng-show="dc.loading" class="text-center py-5">
                    <div class="spinner-violet mb-2"></div>
                    <p class="text-muted mb-0">Loading your latest payments...</p>
                </div>

                <div ng-hide="dc.loading">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 recent-transactions-table">
                            <thead>
                                <tr>
                                    <th style="width: 28%;">TXN ID</th>
                                    <th style="width: 18%;">Amount</th>
                                    <th style="width: 14%;">Method</th>
                                    <th style="width: 14%;">Status</th>
                                    <th style="width: 18%;">Date</th>
                                    <th style="width: 8%; text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="txn in dc.recentTransactions" class="align-middle">
                                    <td>
                                        <a href="{{ route('merchant.transactions.index') }}"
                                           class="text-decoration-none"
                                           style="color:#e11d48; font-weight:500;">
                                            @{{ txn.txn_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="color:#111827;">
                                            @{{ txn.currency }} @{{ txn.amount | number:2 }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              style="background:#16a34a; color:#ffffff; min-width:64px;">
                                            @{{ txn.payment_method | uppercase }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              ng-class="{
                                                'bg-success': txn.status === 'success',
                                                'bg-danger': txn.status === 'failed',
                                                'bg-warning text-dark': txn.status === 'pending'
                                              }">
                                            @{{ txn.status | uppercase }}
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        @{{ txn.created_at | date:'dd-MM-yyyy HH:mm:ss' }}
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                ng-click="dc.viewTransaction(txn)"
                                                title="View transaction details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr ng-if="dc.recentTransactions.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No recent transactions
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transaction Details Modal -->
    <div class="modal fade" id="recentTransactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" ng-if="dc.selectedTransaction">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>
                        Transaction Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Transaction ID</strong>
                            <div class="text-monospace small text-wrap">@{{ dc.selectedTransaction.txn_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <strong>Amount</strong>
                            <div class="fw-semibold">@{{ dc.selectedTransaction.currency }} @{{ dc.selectedTransaction.amount | number:2 }}</div>
                        </div>
                        <div class="col-md-6">
                            <strong>Method</strong>
                            <div>
                                <span class="badge rounded-pill" style="background:#111827; color:#ffffff;">
                                    @{{ dc.selectedTransaction.payment_method | uppercase }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Status</strong>
                            <div>
                                <span class="badge rounded-pill"
                                      ng-class="{
                                        'bg-success': dc.selectedTransaction.status === 'success',
                                        'bg-danger': dc.selectedTransaction.status === 'failed',
                                        'bg-warning text-dark': dc.selectedTransaction.status === 'pending'
                                      }">
                                    @{{ dc.selectedTransaction.status | uppercase }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Date</strong>
                            <div class="text-muted">@{{ dc.selectedTransaction.created_at | date:'dd-MM-yyyy HH:mm:ss' }}</div>
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

@include('merchant.dashboard.angular.main_controller')
