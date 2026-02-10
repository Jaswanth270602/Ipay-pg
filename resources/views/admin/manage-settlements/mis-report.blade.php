@extends('layouts.app-sidebar')

@section('title', 'Download MIS Report - Admin - ' . config('app.name'))
@section('page-title', 'Download MIS Report')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminMISReportController as amrc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Download MIS Report']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Download MIS Report</h2>
            <p class="text-muted">Generate and download MIS reports</p>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="stat-card mb-4">
        <h5 class="mb-3">Report Filters</h5>
        <form id="misReportForm" ng-submit="amrc.generateReport()">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">* Start Date</label>
                    <input type="date" class="form-control" ng-model="amrc.filters.start_date" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">* End Date</label>
                    <input type="date" class="form-control" ng-model="amrc.filters.end_date" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Merchant</label>
                    <select class="form-select" ng-model="amrc.filters.merchant_id">
                        <option value="">All Merchants</option>
                        <option ng-repeat="merchant in amrc.merchants" value="@{{ merchant.id }}">@{{ merchant.name }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select class="form-select" ng-model="amrc.filters.format">
                        <option value="csv">CSV</option>
                        <option value="xlsx">Excel (XLSX)</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" ng-disabled="amrc.generating">
                        <span ng-if="!amrc.generating">
                            <i class="bi bi-download"></i> Download Report
                        </span>
                        <span ng-if="amrc.generating">
                            <span class="spinner-border spinner-border-sm me-2"></span>Generating...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Report History (Optional) -->
    <div class="stat-card">
        <h5 class="mb-3">Report Information</h5>
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle"></i> Instructions:</h6>
            <ul class="mb-0">
                <li>Select a date range for the report</li>
                <li>Optionally filter by a specific merchant</li>
                <li>Choose your preferred format (CSV or Excel)</li>
                <li>Click "Download Report" to generate and download the MIS report</li>
            </ul>
        </div>
        <div class="alert alert-warning">
            <strong>Note:</strong> The report will include all settlement data within the selected date range, including Settlement ID, Merchant details, Payout amounts, Settlement status, Bank details, and Payment date ranges.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminMISReportController', ['$http', function($http) {
                var vm = this;
                vm.merchants = [];
                vm.filters = {
                    start_date: '',
                    end_date: '',
                    merchant_id: '',
                    format: 'csv'
                };
                vm.generating = false;

                vm.loadMerchants = function() {
                    $http.get('/admin/merchants/data', { params: { per_page: 1000 } }).then(function(response) {
                        vm.merchants = response.data.data || [];
                    });
                };

                vm.generateReport = function() {
                    if (!vm.filters.start_date || !vm.filters.end_date) {
                        alert('Please select both start and end dates');
                        return;
                    }

                    if (new Date(vm.filters.start_date) > new Date(vm.filters.end_date)) {
                        alert('Start date must be before or equal to end date');
                        return;
                    }

                    vm.generating = true;
                    
                    var params = {
                        start_date: vm.filters.start_date,
                        end_date: vm.filters.end_date,
                        format: vm.filters.format
                    };

                    if (vm.filters.merchant_id) {
                        params.merchant_id = vm.filters.merchant_id;
                    }

                    // Create a form and submit it to trigger download
                    var form = document.createElement('form');
                    form.method = 'GET';
                    form.action = '/admin/manage-settlements/mis-report/download';
                    
                    Object.keys(params).forEach(function(key) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = params[key];
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                    
                    vm.generating = false;
                };

                vm.loadMerchants();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', registerController);
        } else {
            registerController();
        }
    }
})();
</script>
@endpush


