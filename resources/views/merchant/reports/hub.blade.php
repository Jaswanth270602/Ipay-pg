@extends('layouts.app-sidebar')

@section('title', 'Reports - ' . config('app.name'))
@section('page-title', 'Reports')

@section('content')
<x-breadcrumbs :items="[
    ['label'=>'Home','url'=>route('dashboard')],
    ['label'=>'Reports']
]" />

<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-1">Analytics reports</h5>
                    <p class="text-muted small mb-3">Transaction summary, payment methods, success rate, refunds, settlements, and daily trends.</p>
                    <a href="{{ route('merchant.reports.analytics') }}" class="btn btn-primary btn-sm">Open analytics</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-file-earmark-spreadsheet text-secondary fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-1">Transaction export</h5>
                    <p class="text-muted small mb-3">Quick CSV export with date filters for your current test/live mode.</p>
                    <a href="{{ route('merchant.reports.transactions') }}" class="btn btn-outline-secondary btn-sm">Export transactions</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
