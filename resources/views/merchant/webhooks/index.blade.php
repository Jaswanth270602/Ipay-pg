@extends('layouts.app-sidebar')

@section('title', 'Webhooks - ' . config('app.name'))
@section('page-title', 'Webhooks')

@section('content')
<div ng-app="badlicashApp" ng-controller="WebhooksController as whc">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold">Webhooks</h3>
            <p class="text-muted">Manage webhook events and configure webhook URL</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total Events</h6>
                <h3>@{{ whc.stats.total || 0 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Successful</h6>
                <h3 class="text-success">@{{ whc.stats.successful || 0 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Failed</h6>
                <h3 class="text-danger">@{{ whc.stats.failed || 0 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Pending</h6>
                <h3 class="text-warning">@{{ whc.stats.pending || 0 }}</h3>
            </div>
        </div>
    </div>

    <!-- Webhook URL Configuration -->
    <div class="stat-card mb-4">
        <h5 class="mb-3"><i class="bi bi-webhook me-2"></i>Webhook Configuration</h5>
        <form ng-submit="whc.updateWebhookUrl()">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <input type="url" class="form-control" ng-model="whc.webhookUrl" placeholder="https://example.com/webhooks/badlicash" required>
                        <small class="text-muted">URL where webhook events will be sent</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" ng-disabled="whc.saving">
                                <span ng-if="whc.saving" class="spinner-border spinner-border-sm me-2"></span>
                                Save URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div ng-if="whc.webhookSecret" class="alert alert-info">
                <strong>Webhook Secret:</strong> <code>@{{ whc.webhookSecret }}</code>
                <button class="btn btn-sm btn-link p-0 ms-2" ng-click="whc.copySecret()">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
                <br><small>Use this secret to verify webhook signatures</small>
            </div>
        </form>
        <div class="mt-3">
            <button class="btn btn-outline-primary" ng-click="whc.testWebhook()" ng-disabled="!whc.webhookUrl || whc.testing">
                <span ng-if="whc.testing" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-send me-2"></i>Send Test Webhook
            </button>
        </div>
    </div>

    <!-- Webhook Events Table -->
    <div class="stat-card">
        <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Webhook Events</h5>
        
        <!-- Loading Overlay -->
        <div ng-show="whc.loading" class="loader-overlay">
            <div class="spinner-violet"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Event Type</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Delivered At</th>
                        <th>Next Retry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="webhook in whc.webhooks track by $index">
                        <td>@{{ (whc.pagination.current_page - 1) * whc.pagination.per_page + $index + 1 }}</td>
                        <td>
                            <code>@{{ webhook.event_type }}</code>
                        </td>
                        <td>
                            <span class="badge" ng-class="{
                                'bg-success': webhook.status === 'success',
                                'bg-danger': webhook.status === 'failed',
                                'bg-warning': webhook.status === 'pending'
                            }">
                                @{{ webhook.status }}
                            </span>
                        </td>
                        <td>@{{ webhook.attempt_count || 0 }}</td>
                        <td>
                            <span ng-if="webhook.delivered_at">@{{ webhook.delivered_at | date:'short' }}</span>
                            <span ng-if="!webhook.delivered_at" class="text-muted">-</span>
                        </td>
                        <td>
                            <span ng-if="webhook.next_retry_at">@{{ webhook.next_retry_at | date:'short' }}</span>
                            <span ng-if="!webhook.next_retry_at" class="text-muted">-</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" ng-click="whc.viewDetails(webhook)" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" ng-if="webhook.status !== 'success'" ng-click="whc.retryWebhook(webhook.id)" title="Retry">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </td>
                    </tr>
                    <tr ng-if="whc.webhooks.length === 0 && !whc.loading">
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                            <p class="mt-2">No webhook events found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div ng-if="whc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing @{{ whc.pagination.from || 0 }} to @{{ whc.pagination.to || 0 }} of @{{ whc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="whc.pagination.current_page > 1" ng-click="whc.loadPage(whc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in whc.getPaginationPages() track by page" ng-class="{'active': page === whc.pagination.current_page}" ng-click="whc.loadPage(page)">@{{ page }}</a>
                <a href="#" class="page-link" ng-if="whc.pagination.current_page < whc.pagination.last_page" ng-click="whc.loadPage(whc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="webhookDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Webhook Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>@{{ whc.selectedWebhook | json }}</code></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('merchant.webhooks.angular.main_controller')
@endsection

