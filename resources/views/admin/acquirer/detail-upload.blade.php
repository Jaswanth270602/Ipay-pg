@extends('layouts.app-sidebar')

@section('title', 'Acquirer Accounts Detail Upload - Admin - ' . config('app.name'))
@section('page-title', 'Acquirer Accounts Detail Upload')

@section('content')
<div ng-app="badlicashApp" ng-controller="AcquirerAccountUploadController as aauc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Acquirer Details'],
        ['label'=>'Detail Upload']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Acquirer Account Details Upload</h2>
            <p class="text-muted">Upload acquirer account details via CSV file</p>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="stat-card mb-4">
        <h5 class="mb-3">Upload Configuration</h5>
        <form id="uploadForm" ng-submit="aauc.uploadFile()">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Select Payment Mode:</label>
                    <select class="form-select" ng-model="aauc.uploadForm.payment_mode" ng-change="aauc.onPaymentModeChange()">
                        <option value="">Select Payment Mode</option>
                        <option ng-repeat="mode in aauc.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Select Bank Code:</label>
                    <div class="position-relative">
                        <div class="form-control" style="min-height: 38px; max-height: 200px; overflow-y: auto; cursor: pointer;" 
                             ng-click="aauc.toggleBankDropdown()" 
                             ng-class="{'is-invalid': aauc.uploadForm.bank_codes.length === 0 && aauc.showBankError}">
                            <div ng-if="aauc.uploadForm.bank_codes.length === 0" class="text-muted">Select banks...</div>
                            <div ng-if="aauc.uploadForm.bank_codes.length > 0" class="d-flex flex-wrap gap-1">
                                <span ng-repeat="code in aauc.uploadForm.bank_codes" class="badge bg-primary d-inline-flex align-items-center">
                                    @{{ aauc.getBankName(code) }}
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 10px;" 
                                            ng-click="aauc.removeBankCode(code); $event.stopPropagation();"></button>
                                </span>
                            </div>
                        </div>
                        <div class="dropdown-menu" ng-class="{'show': aauc.showBankDropdown}" 
                             style="max-height: 300px; overflow-y: auto; width: 100%; position: absolute; z-index: 1000;"
                             ng-click="$event.stopPropagation();">
                            <div class="p-2">
                                <input type="text" class="form-control form-control-sm mb-2" 
                                       placeholder="Search banks..." 
                                       ng-model="aauc.bankSearch"
                                       ng-click="$event.stopPropagation();">
                            </div>
                            <div class="px-2 pb-2">
                                <button type="button" class="btn btn-sm btn-link p-0" ng-click="aauc.selectAllBanks()">Select All</button>
                                <button type="button" class="btn btn-sm btn-link p-0 ms-2" ng-click="aauc.deselectAllBanks()">Deselect All</button>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div ng-repeat="bank in aauc.filteredBanks" class="dropdown-item-text">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           ng-checked="aauc.isBankSelected(bank.code)"
                                           ng-click="aauc.toggleBankCode(bank.code); $event.stopPropagation();"
                                           id="bank_@{{ bank.code }}">
                                    <label class="form-check-label" for="bank_@{{ bank.code }}" style="cursor: pointer;" ng-click="aauc.toggleBankCode(bank.code); $event.stopPropagation();">
                                        @{{ bank.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Selected: @{{ aauc.uploadForm.bank_codes.length }} bank(s)</small>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Select Acquirer Account File:</label>
                    <input type="file" class="form-control" 
                           accept=".csv,.xlsx,.xls" 
                           ng-model="aauc.uploadForm.file"
                           file-model="aauc.uploadForm.file"
                           required>
                    <small class="text-muted">Supported formats: CSV, XLSX, XLS (Max: 10MB)</small>
                </div>
                <div class="col-md-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" ng-disabled="aauc.uploading">
                            <span ng-if="aauc.uploading">
                                <span class="spinner-border spinner-border-sm" role="status"></span> Uploading...
                            </span>
                            <span ng-if="!aauc.uploading">
                                <i class="bi bi-upload"></i> Upload File
                            </span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" ng-click="aauc.downloadTemplate()">
                            <i class="bi bi-download"></i> Download Template
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Jobs List -->
    <div class="stat-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>List of Acquirer Account Details Upload Jobs</h5>
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="aauc.pagination.per_page" ng-change="aauc.loadJobs()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
        </div>

        <!-- Filters -->
        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="text" class="form-control form-control-sm" placeholder="Job Id" ng-model="aauc.filters.filter_job_id" ng-change="aauc.applyFilters()">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control form-control-sm" placeholder="Job Name" ng-model="aauc.filters.filter_job_name" ng-change="aauc.applyFilters()">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" ng-model="aauc.filters.filter_status" ng-change="aauc.applyFilters()">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" placeholder="Started From" ng-model="aauc.filters.filter_started_from" ng-change="aauc.applyFilters()">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" placeholder="Started To" ng-model="aauc.filters.filter_started_to" ng-change="aauc.applyFilters()">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-secondary w-100" ng-click="aauc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div ng-show="aauc.loading" class="text-center py-4">
            <div class="spinner-violet mx-auto"></div>
            <p class="mt-2 text-muted">Loading jobs...</p>
        </div>

        <div ng-hide="aauc.loading">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Job Id</th>
                            <th>Job Name</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Download Status File</th>
                            <th>Started At</th>
                            <th>Finished At</th>
                            <th>Error</th>
                            <th>Status Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="job in aauc.jobs">
                            <td>@{{ job.job_id }}</td>
                            <td>@{{ job.job_name }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" 
                                         ng-class="{'bg-success': job.status === 'completed', 'bg-warning': job.status === 'processing', 'bg-danger': job.status === 'failed'}"
                                         role="progressbar" 
                                         style="width: @{{ job.progress }}%">
                                        @{{ job.progress }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" 
                                      ng-class="{'bg-secondary': job.status === 'pending', 'bg-warning': job.status === 'processing', 'bg-success': job.status === 'completed', 'bg-danger': job.status === 'failed'}">
                                    @{{ job.status }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="aauc.downloadStatusFile(job.id)" ng-disabled="job.status === 'pending'">
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </td>
                            <td>@{{ job.started_at }}</td>
                            <td>@{{ job.finished_at }}</td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="@{{ job.error }}">@{{ job.error }}</td>
                            <td>@{{ job.status_info }}</td>
                        </tr>
                        <tr ng-if="aauc.jobs.length === 0">
                            <td colspan="9" class="text-center py-4 text-muted">
                                No data available in table
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ ((aauc.pagination.current_page - 1) * aauc.pagination.per_page) + 1 }} to @{{ Math.min(aauc.pagination.current_page * aauc.pagination.per_page, aauc.pagination.total) }} of @{{ aauc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="aauc.loadJobs(aauc.pagination.current_page - 1)" ng-disabled="aauc.pagination.current_page === 1">
                        Previous
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="aauc.loadJobs(aauc.pagination.current_page + 1)" ng-disabled="aauc.pagination.current_page >= aauc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.acquirer.angular.upload_controller')
@endsection
