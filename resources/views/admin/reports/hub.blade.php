@extends('layouts.app-sidebar')

@section('title', 'Reports - Admin - ' . config('app.name'))
@section('page-title', 'Reports')

@section('content')
<x-breadcrumbs :items="[
    ['label'=>'Home','url'=>route('admin.dashboard')],
    ['label'=>'Reports']
]" />

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100 border-primary border-opacity-25">
            <h5><i class="bi bi-graph-up-arrow text-primary"></i> Analytics reports</h5>
            <p class="text-muted small">Transaction summary, payment method mix, success rate, refunds, settlements, daily trends.</p>
            <a href="{{ route('admin.reports.analytics') }}" class="btn btn-primary btn-sm">Open analytics</a>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <h5><i class="bi bi-table text-secondary"></i> Transaction explorer</h5>
            <p class="text-muted small">Filter and export transactions (replaces legacy admin report screen).</p>
            <a href="{{ route('admin.reports.transactions') }}" class="btn btn-outline-secondary btn-sm">Transaction export</a>
        </div>
    </div>
</div>

<h5 class="mb-3 text-muted">Canned &amp; operational reports</h5>
<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <h6>Sales reports</h6>
            <ul class="list-unstyled small mb-2">
                <li><a href="{{ route('admin.reports.sales.date-and-merchant') }}">Date × Merchant</a></li>
                <li><a href="{{ route('admin.reports.sales.date-and-acquirer') }}">Date × Acquirer</a></li>
                <li><a href="{{ route('admin.reports.sales.month-and-merchant') }}">Month × Merchant</a></li>
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <h6>Success &amp; profitability</h6>
            <ul class="list-unstyled small mb-0">
                <li><a href="{{ route('admin.reports.success-rate.bankcode-wise') }}">Bank code success rate</a></li>
                <li><a href="{{ route('admin.reports.profitability.partner-team-profit') }}">Partner team profit</a></li>
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-card h-100">
            <h6>Other</h6>
            <ul class="list-unstyled small mb-0">
                <li><a href="{{ route('admin.reports.gst-invoices.index') }}">GST invoices</a></li>
                <li><a href="{{ route('admin.reports.datatable-exports.index') }}">Datatable exports</a></li>
                <li><a href="{{ route('admin.reports.miscellaneous.index') }}">Miscellaneous / adhoc</a></li>
                <li><a href="{{ route('admin.manage-settlements.mis-report') }}">MIS settlement report</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
