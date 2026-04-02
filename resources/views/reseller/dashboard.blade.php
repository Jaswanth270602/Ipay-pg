@extends('layouts.app-sidebar')

@section('title', 'Reseller Dashboard - ' . config('app.name'))
@section('page-title', 'Reseller Dashboard')

@push('styles')
<style>
    /* Reseller dashboard metrics: equal height, no overflow, aligned with existing PG stat-card look */
    .reseller-dashboard-metrics .stat-card.metric-tile {
        display: flex;
        flex-direction: column;
        min-height: 148px;
        height: 100%;
        overflow: hidden;
    }
    .reseller-dashboard-metrics .metric-tile-label {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.5rem;
        line-height: 1.3;
        min-height: 2.6em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .reseller-dashboard-metrics .metric-tile-value {
        font-size: clamp(1.25rem, 2.5vw, 1.5rem);
        font-weight: 700;
        line-height: 1.2;
        color: #111827;
        font-variant-numeric: tabular-nums;
        word-break: break-word;
        overflow-wrap: anywhere;
        max-width: 100%;
        flex: 1 1 auto;
        min-height: 2.75rem;
    }
    .reseller-dashboard-metrics .metric-tile-caption {
        font-size: 0.75rem;
        line-height: 1.35;
        color: #9ca3af;
        margin-top: auto;
        min-height: 2.7em;
        max-height: 2.7em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-md-12">
        <h2>Welcome, {{ $user->name }}</h2>
        <p class="text-muted mb-0">Overview of your reseller account.</p>
    </div>

    <div class="col-12 reseller-dashboard-metrics">
        <div class="row g-3 g-md-4">
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Total Merchants</div>
            <div class="metric-tile-value">{{ $stats['total_merchants'] }}</div>
            <span class="metric-tile-caption">Assigned under your account</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Total Transactions</div>
            <div class="metric-tile-value">{{ $stats['total_transactions'] }}</div>
            <span class="metric-tile-caption">Successful payments</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Total Volume</div>
            <div class="metric-tile-value">INR {{ number_format($stats['total_volume'], 2) }}</div>
            <span class="metric-tile-caption">Successful payments only</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Total Earnings</div>
            <div class="metric-tile-value">INR {{ number_format($stats['total_earnings'], 2) }}</div>
            <span class="metric-tile-caption">Net commission after reversals</span>
        </div>
    </div>

    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Today's Transactions</div>
            <div class="metric-tile-value">{{ $stats['today_transactions'] }}</div>
            <span class="metric-tile-caption">Successful payments today</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Today's Volume</div>
            <div class="metric-tile-value">INR {{ number_format($stats['today_volume'], 2) }}</div>
            <span class="metric-tile-caption">Successful volume today</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Pending Earnings</div>
            <div class="metric-tile-value">INR {{ number_format($stats['pending_earnings'], 2) }}</div>
            <span class="metric-tile-caption">Awaiting payout</span>
        </div>
    </div>
    <div class="col-md-3 d-flex">
        <div class="stat-card metric-tile w-100">
            <div class="metric-tile-label">Paid Earnings</div>
            <div class="metric-tile-value">INR {{ number_format($stats['paid_earnings'], 2) }}</div>
            <span class="metric-tile-caption">Already paid out</span>
        </div>
    </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="stat-card">
            <h5 class="mb-3">Profile</h5>
            @if($reseller)
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Reseller ID</small>
                    <strong>{{ $reseller->reseller_unique_id ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge {{ ($reseller->status ?? 'inactive') === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ strtoupper($reseller->status ?? 'inactive') }}
                    </span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Name</small>
                    <strong>{{ $reseller->name ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Email</small>
                    <strong>{{ $reseller->email ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Phone</small>
                    <strong>{{ $reseller->phone ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Company</small>
                    <strong>{{ $reseller->company_name ?? '-' }}</strong>
                </div>
            </div>
            @else
            <p class="text-muted mb-0">Reseller profile not linked.</p>
            @endif
            <div class="mt-3 d-flex flex-wrap gap-2">
                <a href="{{ route('reseller.merchants.index') }}" class="btn btn-sm btn-outline-primary">Merchants</a>
                <a href="{{ route('reseller.transactions.index') }}" class="btn btn-sm btn-outline-primary">Transactions</a>
                <a href="{{ route('reseller.earnings.index') }}" class="btn btn-sm btn-outline-primary">Earnings</a>
                <a href="{{ route('reseller.profile.index') }}" class="btn btn-sm btn-outline-secondary">View Full Profile</a>
            </div>
        </div>
    </div>
</div>
@endsection
