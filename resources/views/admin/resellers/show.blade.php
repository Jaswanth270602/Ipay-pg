@extends('layouts.app-sidebar')

@section('title', 'Reseller Details - Admin - ' . config('app.name'))
@section('page-title', 'Reseller Details')

@section('content')
<x-breadcrumbs :items="[
    ['label'=>'Home','url'=>route('admin.dashboard')],
    ['label'=>'User Settings'],
    ['label'=>'Resellers','url'=>route('admin.resellers.index')],
    ['label'=>$reseller->name]
]" />

<div class="row mb-4">
    <div class="col-md-12">
        <div class="mb-2">
            <a href="{{ route('admin.resellers.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Resellers
            </a>
        </div>
        <h2>Reseller Overview</h2>
        <p class="text-muted">Summary and profile details</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="text-muted small">Total Merchants</div>
            <h4 class="mb-0">{{ $stats['total_merchants'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="text-muted small">Total Transactions</div>
            <h4 class="mb-0">{{ $stats['total_transactions'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="text-muted small">Total Volume</div>
            <h4 class="mb-0">INR {{ number_format((float) $stats['total_volume'], 2) }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="text-muted small">Total Earnings</div>
            <h4 class="mb-0">INR {{ number_format((float) $stats['total_earnings'], 2) }}</h4>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="stat-card">
            <h6 class="mb-3">Profile</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Name</small>
                    <strong>{{ $reseller->name }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Company</small>
                    <strong>{{ $reseller->company_name }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Email</small>
                    <strong>{{ $reseller->email }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Phone</small>
                    <strong>{{ $reseller->phone }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Commission Type</small>
                    <strong>{{ strtoupper($reseller->commission_type) }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Commission Value</small>
                    <strong>{{ number_format((float) $reseller->commission_value, 2) }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge {{ $reseller->status === 'active' ? 'bg-success' : ($reseller->status === 'inactive' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                        {{ strtoupper($reseller->status) }}
                    </span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Reseller ID</small>
                    <strong>{{ $reseller->reseller_unique_id }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-12">
        <div class="stat-card">
            <h6 class="mb-3">Assigned merchants</h6>
            @if($assignedMerchants->isEmpty())
                <p class="text-muted mb-0">No merchants are linked to this reseller yet. Link them from <strong>Merchants → Merchant Accounts</strong> when creating or editing a merchant (Is Merchant Reseller).</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedMerchants as $m)
                                <tr>
                                    <td><code>{{ $m->id }}</code></td>
                                    <td>{{ $m->name }}</td>
                                    <td>{{ $m->email }}</td>
                                    <td>
                                        <span class="badge {{ $m->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ strtoupper($m->status ?? 'inactive') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($stats['total_merchants'] > 100)
                    <p class="text-muted small mt-2 mb-0">Showing first 100 of {{ $stats['total_merchants'] }} merchants.</p>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

