@extends('layouts.app-sidebar')

@section('title', 'Bulk Chargebacks Upload - ' . config('app.name'))
@section('page-title', 'Bulk Chargebacks Upload')

@section('content')
<div ng-app="badlicashApp" ng-controller="MerchantBulkChargebacksController as mbcc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Bulk Chargebacks Upload']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Bulk Chargebacks Upload</h2>
        </div>
    </div>

    <!-- Bulk Chargebacks Upload Section -->
    <div class="stat-card mb-4">
        <h5 class="mb-3">Bulk Chargebacks Upload</h5>
        <form id="bulkChargebackUploadForm" enctype="multipart/form-data">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Select File :</label>
                </div>
                <div class="col-md-4">
                    <input type="file" class="form-control" id="chargebackFile" accept=".csv,.xlsx,.xls" ng-model="mbcc.selectedFile" onchange="document.getElementById('chargebackFileNameDisplay').value = this.files[0]?.name || 'No Files Selected'">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="chargebackFileNameDisplay" placeholder="No Files Selected" readonly>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" ng-click="mbcc.downloadTemplate()">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <small class="text-muted">(* Max number of rows/transactions allowed per file upload is 1000)</small>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-success btn-lg" ng-click="mbcc.uploadFile()" ng-disabled="!mbcc.selectedFile || mbcc.uploading">
                        <span ng-if="!mbcc.uploading">Upload</span>
                        <span ng-if="mbcc.uploading">
                            <span class="spinner-border spinner-border-sm me-2"></span>Uploading...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- List of Chargeback Jobs Section -->
    <div class="stat-card">
        <h5 class="mb-3">List of Chargeback Uploads</h5>
        
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="mbcc.pagination.per_page" ng-change="mbcc.loadJobs()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbcc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbcc.loadJobs()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in mbcc.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="mbcc.toggleColumn(key)">
                                <i class="bi" ng-class="col.visible ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="mbcc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>

        <div ng-show="mbcc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading jobs...</p>
            </div>
        </div>

        <div ng-hide="mbcc.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th ng-show="mbcc.visibleColumns.job_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Job Id</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.job_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Job Name</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.progress.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Progress</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="mbcc.filters.filter_status" ng-change="mbcc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </th>
                            <th ng-show="mbcc.visibleColumns.download_status_file.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Download Status File</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.started_at.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Started At</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.finished_at.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Finished At</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.error.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Error</span>
                                </div>
                            </th>
                            <th ng-show="mbcc.visibleColumns.user_name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>User Name</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="mbcc.jobs.length === 0">
                            <td colspan="9" class="text-center text-danger py-4">No data available in table</td>
                        </tr>
                        <tr ng-repeat="job in mbcc.jobs track by $index">
                            <td ng-show="mbcc.visibleColumns.job_id.visible">@{{ job.job_id }}</td>
                            <td ng-show="mbcc.visibleColumns.job_name.visible">@{{ job.job_name }}</td>
                            <td ng-show="mbcc.visibleColumns.progress.visible">@{{ job.progress }}%</td>
                            <td ng-show="mbcc.visibleColumns.status.visible">
                                <span class="badge" ng-class="{
                                    'bg-success': job.status === 'completed',
                                    'bg-warning': job.status === 'pending',
                                    'bg-info': job.status === 'processing',
                                    'bg-danger': job.status === 'failed'
                                }">
                                    @{{ job.status | uppercase }}
                                </span>
                            </td>
                            <td ng-show="mbcc.visibleColumns.download_status_file.visible">
                                <button class="btn btn-sm btn-outline-primary" ng-click="mbcc.downloadStatusFile(job)" ng-if="job.export_files !== '-'">
                                    <i class="bi bi-download"></i> Download
                                </button>
                                <span ng-if="job.export_files === '-'">-</span>
                            </td>
                            <td ng-show="mbcc.visibleColumns.started_at.visible">@{{ job.started_at }}</td>
                            <td ng-show="mbcc.visibleColumns.finished_at.visible">@{{ job.finished_at }}</td>
                            <td ng-show="mbcc.visibleColumns.error.visible">@{{ job.error }}</td>
                            <td ng-show="mbcc.visibleColumns.user_name.visible">@{{ job.user_name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (mbcc.pagination.current_page - 1) * mbcc.pagination.per_page + 1 }} to @{{ Math.min(mbcc.pagination.current_page * mbcc.pagination.per_page, mbcc.pagination.total) }} of @{{ mbcc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mbcc.changePage(mbcc.pagination.current_page - 1)" 
                            ng-disabled="mbcc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="mbcc.changePage(mbcc.pagination.current_page + 1)" 
                            ng-disabled="mbcc.pagination.current_page === mbcc.pagination.last_page">
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
            var app = angular.module('badlicashApp');
            app.controller('MerchantBulkChargebacksController', ['$http', function($http) {
                var vm = this;
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
                    user_name: { visible: true, label: 'User Name' }
                };

                vm.loadJobs = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key] && vm.filters[key] !== 'all') {
                            params[key] = vm.filters[key];
                        }
                    });
                    
                    $http.get('/merchant/payments/bulk-chargebacks/jobs', { params: params }).then(function(response) {
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
                    if (!vm.selectedFile) {
                        alert('Please select a file');
                        return;
                    }

                    var formData = new FormData();
                    var fileInput = document.getElementById('chargebackFile');
                    if (fileInput.files.length > 0) {
                        formData.append('file', fileInput.files[0]);
                    }

                    vm.uploading = true;
                    $http.post('/merchant/payments/bulk-chargebacks/upload', formData, {
                        headers: { 'Content-Type': undefined },
                        transformRequest: angular.identity
                    }).then(function(response) {
                        vm.uploading = false;
                        alert('File uploaded successfully!');
                        vm.loadJobs();
                        document.getElementById('chargebackFile').value = '';
                        document.getElementById('chargebackFileNameDisplay').value = 'No Files Selected';
                    }, function(error) {
                        vm.uploading = false;
                        alert('Error uploading file: ' + (error.data?.message || 'Unknown error'));
                    });
                };

                vm.downloadTemplate = function() {
                    window.location.href = '/merchant/payments/bulk-chargebacks/template';
                };

                vm.downloadStatusFile = function(job) {
                    if (job.export_files && job.export_files !== '-') {
                        // Note: This route may need to be added if download functionality is required
                        alert('Download functionality is not yet available for this job.');
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

