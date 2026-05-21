@extends('layouts.app-sidebar')

@section('title', 'Integration - ' . config('app.name'))
@section('page-title', 'Integration')

@section('content')
<div ng-cloak ng-app="ipayApp" ng-controller="IntegrationController as ic">
    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="fw-bold">Integration Guide</h3>
            <p class="text-muted">Integrate Ipay payment gateway into your application</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="integrationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="integration-tab" data-bs-toggle="tab" data-bs-target="#integration-panel" type="button" role="tab" aria-controls="integration-panel" aria-selected="true">
                <i class="bi bi-code-square me-1"></i> Integration
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="api-docs-tab" data-bs-toggle="tab" data-bs-target="#api-docs-panel" type="button" role="tab" aria-controls="api-docs-panel" aria-selected="false">
                <i class="bi bi-journal-code me-1"></i> API Docs
            </button>
        </li>
    </ul>

    <div class="tab-content" id="integrationTabContent">
        <div class="tab-pane fade show active" id="integration-panel" role="tabpanel" aria-labelledby="integration-tab">
            <div class="row g-4">
                <!-- Integration Options -->
                <div class="col-md-12">
                    <div class="stat-card">
                        <h5 class="mb-3"><i class="bi bi-code-square me-2"></i>Choose Integration Method</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6><i class="bi bi-layout-text-window me-2"></i>Payment Widget</h6>
                                        <p class="text-muted small">Embed a ready-to-use payment widget in your website</p>
                                        <button class="btn btn-primary btn-sm" ng-click="ic.getCode('widget')">
                                            Get Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6><i class="bi bi-window me-2"></i>iFrame</h6>
                                        <p class="text-muted small">Embed payment page in an iframe</p>
                                        <button class="btn btn-primary btn-sm" ng-click="ic.getCode('iframe')">
                                            Get Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6><i class="bi bi-arrow-right-circle me-2"></i>Redirect</h6>
                                        <p class="text-muted small">Redirect customers to our payment page</p>
                                        <button class="btn btn-primary btn-sm" ng-click="ic.getCode('redirect')">
                                            Get Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6><i class="bi bi-webhook me-2"></i>Webhook Handler</h6>
                                        <p class="text-muted small">Receive payment notifications via webhooks</p>
                                        <button class="btn btn-primary btn-sm" ng-click="ic.getCode('webhook')">
                                            Get Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Key Selection -->
                <div class="col-md-12">
                    <div class="stat-card">
                        <h5 class="mb-3"><i class="bi bi-key me-2"></i>Select API Key</h5>
                        <div class="mb-3">
                            <select class="form-select" ng-model="ic.selectedApiKey" ng-change="ic.onApiKeyChange()">
                                <option value="">-- Select API Key --</option>
                                <option ng-repeat="key in ic.apiKeys" ng-value="key.id">
                                    @{{ key.name }} (@{{ key.mode.toUpperCase() }})
                                </option>
                            </select>
                            <small class="text-muted">Choose which API key to use for this integration</small>
                        </div>
                    </div>
                </div>

                <!-- Code Display -->
                <div class="col-md-12" ng-if="ic.code">
                    <div class="stat-card">
                        <h5 class="mb-3"><i class="bi bi-file-code me-2"></i>Integration Code</h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Copy the code below</small>
                                <button class="btn btn-sm btn-outline-primary" ng-click="ic.copyCode()">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>@{{ ic.code }}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Documentation -->
                <div class="col-md-12">
                    <div class="stat-card">
                        <h5 class="mb-3"><i class="bi bi-book me-2"></i>Documentation</h5>
                        <div class="list-group">
                            <a href="{{ route('merchant.integration.index') }}#api-docs-tab" class="list-group-item list-group-item-action">
                                <i class="bi bi-file-earmark-text me-2"></i>API Documentation
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-github me-2"></i>GitHub Examples
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-question-circle me-2"></i>FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="api-docs-panel" role="tabpanel" aria-labelledby="api-docs-tab">
            <div class="stat-card p-0 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h5 class="mb-0"><i class="bi bi-journal-code me-2"></i>API Documentation</h5>
                    <a href="{{ route('docs.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        Open Fullscreen
                    </a>
                </div>
                <iframe src="{{ route('docs.index') }}" title="Ipay API Docs" style="width: 100%; min-height: 80vh; border: 0;"></iframe>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div ng-show="ic.loading" class="loader-overlay">
        <div class="spinner-violet"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#api-docs-tab') {
            var trigger = document.getElementById('api-docs-tab');
            if (trigger && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            } else if (trigger) {
                trigger.click();
            }
        }
    });
</script>

@include('merchant.integration.angular.main_controller')
@endsection

