@extends('layouts.app-sidebar')

@section('title', 'Reports - ' . config('app.name'))
@section('page-title','Reports')

@section('content')
<div ng-app="badlicashApp" ng-controller="ReportsController as rptc">
    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="rptc.filters.from_date">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="rptc.filters.to_date">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" ng-click="rptc.generateReport()" ng-disabled="rptc.generating">
                    <span ng-if="rptc.generating" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-file-earmark-text"></i> Generate Report
                </button>
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100" ng-click="rptc.exportReport()" ng-disabled="rptc.exporting">
                    <span ng-if="rptc.exporting" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card" ng-if="rptc.reportData">
        <h5 class="mb-3">Report Summary</h5>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <h6 class="text-muted">Total Transactions</h6>
                    <h3 class="mb-0">@{{ rptc.reportData.total_transactions || 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <h6 class="text-muted">Total Amount</h6>
                    <h3 class="mb-0">INR @{{ rptc.reportData.total_amount || 0 | number:2 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <h6 class="text-muted">Successful</h6>
                    <h3 class="mb-0 text-success">@{{ rptc.reportData.successful || 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <h6 class="text-muted">Failed</h6>
                    <h3 class="mb-0 text-danger">@{{ rptc.reportData.failed || 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

@include('merchant.reports.angular.main_controller')
@endsection

