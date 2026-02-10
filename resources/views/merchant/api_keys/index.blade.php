@extends('layouts.app-sidebar')

@section('title', 'API Keys - ' . config('app.name'))
@section('page-title', 'API Keys')

@section('content')
<div ng-app="badlicashApp" ng-controller="ApiKeysController as akc">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold">API Keys</h3>
            <p class="text-muted">Manage your API keys for test and live modes</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                <i class="bi bi-plus-circle"></i> Create API Key
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div ng-show="akc.loading" class="loader-overlay">
        <div class="spinner-violet"></div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="key in akc.apiKeys track by $index">
                        <td>@{{ (akc.pagination.current_page - 1) * akc.pagination.per_page + $index + 1 }}</td>
                        <td>
                            <strong>@{{ key.name }}</strong>
                        </td>
                        <td>
                            <code class="text-primary">@{{ key.key }}</code>
                            <button class="btn btn-sm btn-link p-0 ms-2" ng-click="akc.copyToClipboard(key.key)" title="Copy">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </td>
                        <td>
                            <span class="badge" ng-class="key.mode === 'test' ? 'bg-warning' : 'bg-success'">
                                @{{ key.mode.toUpperCase() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge" ng-class="key.status === 'active' ? 'bg-success' : 'bg-secondary'">
                                @{{ key.status }}
                            </span>
                        </td>
                        <td>
                            <span ng-if="key.last_used_at">@{{ key.last_used_at | date:'short' }}</span>
                            <span ng-if="!key.last_used_at" class="text-muted">Never</span>
                        </td>
                        <td>@{{ key.created_at | date:'short' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-danger" ng-click="akc.revokeKey(key.id)" title="Revoke">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <button class="btn btn-outline-primary" ng-click="akc.showSecret(key)" title="View Secret">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr ng-if="akc.apiKeys.length === 0 && !akc.loading">
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                            <p class="mt-2">No API keys found. Create your first API key to get started.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div ng-if="akc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing @{{ akc.pagination.from || 0 }} to @{{ akc.pagination.to || 0 }} of @{{ akc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="akc.pagination.current_page > 1" ng-click="akc.loadPage(akc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in akc.getPaginationPages() track by page" ng-class="{'active': page === akc.pagination.current_page}" ng-click="akc.loadPage(page)">@{{ page }}</a>
                <a href="#" class="page-link" ng-if="akc.pagination.current_page < akc.pagination.last_page" ng-click="akc.loadPage(akc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>

    <!-- Create API Key Modal -->
    <div class="modal fade" id="createApiKeyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form ng-submit="akc.createApiKey()">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" ng-model="akc.newKey.name" placeholder="e.g., Production Key, Test Key" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode</label>
                            <select class="form-select" ng-model="akc.newKey.mode" required>
                                <option value="test">Test Mode</option>
                                <option value="live">Live Mode</option>
                            </select>
                            <small class="text-muted">Select the mode for this API key</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Important:</strong> Make sure to save your secret key securely. You won't be able to see it again!
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="akc.createApiKey()" ng-disabled="akc.creating">
                        <span ng-if="akc.creating" class="spinner-border spinner-border-sm me-2"></span>
                        Create Key
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Secret Display Modal -->
    <div class="modal fade" id="secretModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">API Secret Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Warning:</strong> This is the only time you'll see this secret. Copy it now!
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Secret Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="secretKeyDisplay" readonly value="@{{ akc.currentSecret }}">
                            <button class="btn btn-outline-secondary" type="button" ng-click="akc.copySecret()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('merchant.api_keys.angular.main_controller')
@endsection

