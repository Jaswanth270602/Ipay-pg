@extends('layouts.app-sidebar')

@section('title', 'Reseller Profile - ' . config('app.name'))
@section('page-title', 'Reseller Profile')

@push('styles')
<style>
    .reseller-profile-wrap {
        max-width: 960px;
    }
    .reseller-profile-card {
        border-radius: 10px;
    }
    .reseller-profile-hero {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }
    .reseller-profile-hero .reseller-id-chip {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
        letter-spacing: 0.02em;
        color: #64748b;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.25rem 0.6rem;
        display: inline-block;
        margin-top: 0.35rem;
    }
    .reseller-profile-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .reseller-profile-field {
        margin-bottom: 1.1rem;
    }
    .reseller-profile-field:last-child {
        margin-bottom: 0;
    }
    .reseller-profile-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .reseller-profile-field .value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }
    .reseller-profile-field .value-sm {
        font-size: 0.875rem;
        font-weight: 500;
    }
    .reseller-profile-icon {
        color: #9ca3af;
        font-size: 1rem;
        margin-right: 0.35rem;
        vertical-align: -0.125em;
    }
</style>
@endpush

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-12 reseller-profile-wrap">
        <p class="text-muted small mb-0">Partner account details used for reporting and commission. Contact support to update sensitive fields.</p>
    </div>

    @if($reseller)
    @php
        $ctype = strtolower($reseller->commission_type ?? 'percentage');
        $cval = (float) ($reseller->commission_value ?? 0);
        $commissionDisplay = $ctype === 'fixed'
            ? 'INR ' . number_format($cval, 2)
            : number_format($cval, 2) . '%';
        $commissionHint = $ctype === 'fixed'
            ? 'Flat amount per successful transaction (capped by transaction amount).'
            : 'Applied on successful transaction amount before reversals.';
    @endphp
    <div class="col-12 reseller-profile-wrap">
        <div class="stat-card reseller-profile-card p-0">
            <div class="reseller-profile-hero p-4 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1" style="min-width: 200px;">
                        <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing: 0.06em;">Account</div>
                        <h1 class="h4 mb-0 fw-bold text-dark">{{ $reseller->name ?? 'Reseller' }}</h1>
                        @if(!empty($reseller->company_name))
                            <div class="text-muted mt-1 small">{{ $reseller->company_name }}</div>
                        @endif
                        <div class="reseller-id-chip mt-2" title="Reseller ID">{{ $reseller->reseller_unique_id ?? '—' }}</div>
                    </div>
                    <div class="text-md-end">
                        @php $st = strtolower($reseller->status ?? 'inactive'); @endphp
                        <span class="badge rounded-pill px-3 py-2 {{ $st === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ strtoupper($reseller->status ?? 'inactive') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-4">
                <div class="row g-4 g-lg-5">
                    <div class="col-md-6">
                        <div class="reseller-profile-section-title">Contact</div>
                        <div class="reseller-profile-field">
                            <label><i class="bi bi-envelope reseller-profile-icon"></i>Email</label>
                            <div class="value value-sm">{{ $reseller->email ?? '—' }}</div>
                        </div>
                        <div class="reseller-profile-field">
                            <label><i class="bi bi-telephone reseller-profile-icon"></i>Phone</label>
                            <div class="value value-sm">{{ $reseller->phone ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="reseller-profile-section-title">Commission</div>
                        <div class="reseller-profile-field">
                            <label><i class="bi bi-sliders reseller-profile-icon"></i>Model</label>
                            <div class="value">{{ strtoupper($ctype) }}</div>
                        </div>
                        <div class="reseller-profile-field">
                            <label><i class="bi bi-currency-rupee reseller-profile-icon"></i>Value</label>
                            <div class="value">{{ $commissionDisplay }}</div>
                            <div class="text-muted small mt-1" style="line-height: 1.4;">{{ $commissionHint }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="col-12 reseller-profile-wrap">
        <div class="stat-card border-warning bg-warning bg-opacity-10">
            <p class="mb-0 text-dark"><strong>No reseller profile</strong> is linked to this login. Please contact support.</p>
        </div>
    </div>
    @endif
</div>
@endsection
