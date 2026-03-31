@extends('layouts.app-sidebar')

@section('title', 'Bulk Update Refund Status - ' . config('app.name'))
@section('page-title', 'Bulk Update Refund Status')

@section('content')
<style>
    .bulk-refund-page {
        font-size: 13px;
    }
    .bulk-refund-page h2 {
        font-size: 28px;
        margin-bottom: 0;
    }
    .bulk-refund-page .stat-card {
        padding: 14px 16px;
    }
    .bulk-refund-page .stat-card h5 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    .bulk-refund-page .form-label {
        font-size: 12px;
        margin-bottom: 4px;
    }
    .bulk-refund-page .form-control,
    .bulk-refund-page .form-select,
    .bulk-refund-page .btn {
        font-size: 12px;
    }
    .bulk-refund-page .btn.btn-lg {
        padding: 6px 18px;
        font-size: 13px;
    }
    .bulk-refund-page small {
        font-size: 11px;
        line-height: 1.2;
    }
    .bulk-refund-jobs-table {
        min-width: 1320px;
        width: 100%;
        font-size: 12px;
    }
    .bulk-refund-jobs-table th,
    .bulk-refund-jobs-table td {
        padding: 6px 8px !important;
    }
    .bulk-refund-jobs-table thead th {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .2px;
    }
    .bulk-refund-jobs-table .form-control.form-control-sm,
    .bulk-refund-jobs-table .form-select.form-select-sm {
        height: 28px;
        min-height: 28px;
        padding: 2px 6px;
        font-size: 11px;
    }
    .bulk-refund-jobs-table .job-detail-cell {
        max-width: 240px;
        max-height: 72px;
        overflow-y: auto;
        white-space: normal;
        word-break: break-word;
        line-height: 1.25;
        font-size: 11px;
        padding-right: 6px;
    }
    .bulk-refund-jobs-table .job-name-cell {
        min-width: 170px;
        max-width: 190px;
        white-space: normal;
        word-break: break-word;
    }
    .bulk-refund-jobs-table .compact-cell {
        white-space: nowrap;
        min-width: 88px;
    }
    .bulk-refund-jobs-table .status-cell {
        min-width: 140px;
        white-space: nowrap;
    }
    .bulk-refund-jobs-table .download-cell {
        min-width: 130px;
        white-space: nowrap;
    }
    .bulk-refund-jobs-table td {
        vertical-align: middle;
    }
    .bulk-refund-jobs-table .badge {
        font-size: 10px;
        padding: 4px 7px;
    }
</style>
<div class="bulk-refund-page" ng-app="ipayApp" ng-controller="MerchantBulkRefundUpdateController as mbruc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Bulk Upload for refund Status']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Bulk Update Refund Status</h2>
        </div>
    </div>

    <!-- Bulk Update Refund Status Section -->
    <div class="stat-card mb-4">
        <h5 class="mb-3">Bulk Update Refund Status</h5>
        <form id="bulkRefundUploadForm" enctype="multipart/form-data">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Select File :</label>
                </div>
                <div class="col-md-4">
                    <input type="file" class="form-control" id="refundFile" accept=".csv" onchange="document.getElementById('fileNameDisplay').value = this.files[0]?.name || 'No Files Selected'">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="fileNameDisplay" placeholder="No Files Selected" readonly>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" ng-click="mbruc.downloadTemplate()">
                        <i class="bi bi-download"></i> Download CSV Template
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <small class="text-muted d-block">(* Upload CSV only. Required columns: transaction_id, amount, reason)</small>
                    <small class="text-warning d-block">Sample transaction IDs are included in the template for reference only. Replace them with valid transaction_id values before upload.</small>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-success btn-lg" ng-click="mbruc.uploadFile()" ng-disabled="mbruc.uploading">
                        <span ng-if="!mbruc.uploading">Upload</span>
                        <span ng-if="mbruc.uploading">
                            <span class="spinner-border spinner-border-sm me-2"></span>Uploading...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- List of PgRefunds Section -->
    <div class="stat-card">
        <h5 class="mb-3">List of PgRefunds</h5>
        
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mbruc.pagination.per_page" ng-change="mbruc.loadJobs()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbruc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbruc.loadJobs()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mbruc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mbruc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbruc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>

        <div ng-show="mbruc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading jobs...</p>
            </div>
        </div>

        <div ng-hide="mbruc.loading">
            <div class="table-responsive">
                <table class="table table-hover bulk-refund-jobs-table">
                    <thead>
                        <tr>
                            <th ng-show="mbruc.visibleColumns.job_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Job Id</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mbruc.filters.filter_job_id" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.job_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Job Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mbruc.filters.filter_job_name" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.progress.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Progress</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mbruc.filters.filter_progress" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mbruc.filters.filter_status" ng-change="mbruc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="completed_with_errors">Completed With Errors</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th ng-show="mbruc.visibleColumns.download_status_file.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Download Status File</span>
                                </div>
                            </th>
                            <th ng-show="mbruc.visibleColumns.started_at.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Started At</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="mbruc.filters.filter_started_at" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.finished_at.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Finished At</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="mbruc.filters.filter_finished_at" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.error.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Error</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mbruc.filters.filter_error" ng-change="mbruc.applyFilters()">
                            </th>
                            <th ng-show="mbruc.visibleColumns.status_info.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Status Info</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="mbruc.filters.filter_status_info" ng-change="mbruc.applyFilters()">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mbruc.jobs.length === 0">
                            <td colspan="9" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="job in mbruc.jobs track by $index">
                            <td ng-show="mbruc.visibleColumns.job_id.visible">
                                <div class="compact-cell">@{{ job.job_id }}</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.job_name.visible">
                                <div class="job-name-cell">@{{ job.job_name }}</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.progress.visible">
                                <div class="compact-cell">@{{ job.progress }}%</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.status.visible">
                                <div class="status-cell">
                                    <span class="badge" ng-class="{
                                        'bg-success': job.status === 'completed',
                                        'bg-warning': job.status === 'pending',
                                        'bg-info': job.status === 'processing',
                                        'bg-warning text-dark': job.status === 'completed_with_errors',
                                        'bg-danger': job.status === 'failed'
                                    }">
                                        @{{ ((job.status || '').split('_').join(' ')) | uppercase }}
                                    </span>
                                </div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.download_status_file.visible">
                                <div class="download-cell">
                                    <button class="btn btn-sm btn-outline-primary" ng-click="mbruc.downloadStatusFile(job)" ng-if="job.export_files !== '-'">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                    <span ng-if="job.export_files === '-'">-</span>
                                </div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.started_at.visible">
                                <div class="compact-cell">@{{ job.started_at }}</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.finished_at.visible">
                                <div class="compact-cell">@{{ job.finished_at }}</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.error.visible">
                                <div class="job-detail-cell">@{{ job.error }}</div>
                            </td>
                            <td ng-show="mbruc.visibleColumns.status_info.visible">
                                <div class="job-detail-cell">@{{ job.status_info }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (mbruc.pagination.current_page - 1) * mbruc.pagination.per_page + 1 }} to @{{ Math.min(mbruc.pagination.current_page * mbruc.pagination.per_page, mbruc.pagination.total) }} of @{{ mbruc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mbruc.changePage(mbruc.pagination.current_page - 1)" 
                            ng-disabled="mbruc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mbruc.changePage(mbruc.pagination.current_page + 1)" 
                            ng-disabled="mbruc.pagination.current_page === mbruc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
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
            var app = angular.module('ipayApp');
            app.controller('MerchantBulkRefundUpdateController', ['$http', function($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.jobs = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.filters = {};
                vm.loading = false;
                vm.uploading = false;
                vm.selectedFile = null;
                
                vm.visibleColumns = {
                    job_id: { visible: true, label: 'Job Id' },
                    job_name: { visible: true, label: 'Job Name' },
                    progress: { visible: true, label: 'Progress' },
                    status: { visible: true, label: 'Status' },
                    download_status_file: { visible: true, label: 'Download Status File' },
                    started_at: { visible: true, label: 'Started At' },
                    finished_at: { visible: true, label: 'Finished At' },
                    error: { visible: true, label: 'Error' },
                    status_info: { visible: true, label: 'Status Info' }
                };

                vm.loadJobs = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key]) {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get("{{ route('merchant.payments.bulk-refund-update.jobs') }}", { params: params }).then(function(response) {
                        vm.jobs = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading jobs:', error);
                    });
                };

                vm.uploadFile = function() {
                    var notify = function(message, type) {
                        if (typeof window.showToast === 'function') {
                            window.showToast(message, type || 'info');
                        } else {
                            console.warn('Toast unavailable:', message);
                        }
                    };

                    var fileInput = document.getElementById('refundFile');
                    if (!fileInput.files.length) {
                        notify('Please select a CSV file first.', 'warning');
                        return;
                    }

                    var formData = new FormData();
                    formData.append('file', fileInput.files[0]);

                    vm.uploading = true;
                    $http.post("{{ route('merchant.payments.bulk-refund-update.upload') }}", formData, {
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': undefined
                        },
                        transformRequest: angular.identity
                    }).then(function(response) {
                        vm.uploading = false;
                        if (response.data.success) {
                            notify('File uploaded successfully.', 'success');
                            fileInput.value = '';
                            document.getElementById('fileNameDisplay').value = 'No Files Selected';
                            vm.loadJobs();
                        } else {
                            var failMessage = response.data.message || 'Unknown error';
                            notify('Upload failed: ' + failMessage, 'error');
                        }
                    }, function(error) {
                        vm.uploading = false;
                        var msg = 'Upload failed';
                        if (error && error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        notify(msg, 'error');
                        console.error('Error:', error);
                    });
                };

                vm.downloadTemplate = function() {
                    window.location.href = "{{ route('merchant.payments.bulk-refund-update.template') }}";
                };

                vm.downloadStatusFile = function(job) {
                    if (job.export_files && job.export_files !== '-') {
                        window.location.href = '/merchant/payments/bulk-refund-update/download/' + job.id;
                    }
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadJobs();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadJobs();
                };

                vm.clearFilters = function() {
                    vm.filters = {};
                    vm.applyFilters();
                };

                vm.toggleColumn = function(key) {
                    if (vm.visibleColumns.hasOwnProperty(key)) {
                        vm.visibleColumns[key].visible = !vm.visibleColumns[key].visible;
                    }
                };

                vm.resetView = function() {
                    Object.keys(vm.visibleColumns).forEach(function(key) {
                        vm.visibleColumns[key].visible = true;
                    });
                    vm.clearFilters();
                };

                vm.loadJobs();
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

