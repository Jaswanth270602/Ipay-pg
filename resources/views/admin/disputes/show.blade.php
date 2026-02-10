@extends('layouts.app-sidebar')

@section('title', 'Dispute Details - Admin - ' . config('app.name'))
@section('page-title', 'Dispute Details')

@push('styles')
<style>
    .dispute-status-banner {
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        border-left: 4px solid;
    }
    
    .dispute-status-banner.action_required {
        background: #fef3c7;
        border-left-color: #f59e0b;
        color: #92400e;
    }
    
    .dispute-status-banner.under_review {
        background: #dbeafe;
        border-left-color: #3b82f6;
        color: #1e40af;
    }
    
    .dispute-status-banner.insufficient_evidence {
        background: #f3f4f6;
        border-left-color: #6b7280;
        color: #374151;
    }
    
    .dispute-status-banner.won {
        background: #d1fae5;
        border-left-color: #10b981;
        color: #065f46;
    }
    
    .dispute-status-banner.lost {
        background: #fee2e2;
        border-left-color: #ef4444;
        color: #991b1b;
    }
    
    .dispute-status-banner.closed {
        background: #e5e7eb;
        border-left-color: #9ca3af;
        color: #374151;
    }
    
    .detail-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .detail-value {
        font-size: 16px;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .evidence-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: #f9fafb;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .evidence-upload-area:hover {
        border-color: #667eea;
        background: #f3f4f6;
    }
    
    .evidence-upload-area.dragover {
        border-color: #667eea;
        background: #eef2ff;
    }
    
    .evidence-item {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .evidence-item:hover {
        background: #f3f4f6;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 32px;
        padding-bottom: 24px;
        border-left: 2px solid #e5e7eb;
    }
    
    .timeline-item:last-child {
        border-left: none;
    }
    
    .timeline-dot {
        position: absolute;
        left: -6px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #667eea;
    }
    
    .timeline-content {
        background: #f9fafb;
        border-radius: 8px;
        padding: 12px 16px;
    }
    
    .timeline-date {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    
    .timeline-event {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .timeline-notes {
        font-size: 14px;
        color: #4b5563;
    }
</style>
@endpush

@section('content')
<div ng-app="badlicashApp" ng-controller="DisputeDetailController as ddc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Disputes','url'=>route('admin.disputes.index')],
        ['label'=>'Dispute Details']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2>Dispute Details</h2>
                <p class="text-muted mb-0">Dispute ID: <code ng-bind="ddc.dispute.dispute_id || 'Loading...'"></code></p>
            </div>
            <button class="btn btn-outline-secondary" onclick="window.history.back()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <div ng-show="ddc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
        <div class="position-absolute top-50 start-50 translate-middle">
            <div class="spinner-violet"></div>
            <p class="mt-2 text-muted text-center">Loading dispute details...</p>
        </div>
    </div>

    <div ng-hide="ddc.loading">
        <!-- Status Banner -->
        <div class="dispute-status-banner" ng-class="ddc.dispute.status">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-2" ng-bind="ddc.dispute.status_formatted || ddc.dispute.status || ''"></h4>
                    <p class="mb-0" ng-if="ddc.dispute.due_by">
                        <strong>Due by:</strong> @{{ ddc.dispute.due_by_formatted }}
                        <span ng-if="ddc.dispute.is_past_due" class="badge bg-danger ms-2">Overdue</span>
                    </p>
                    <p class="mb-0" ng-if="!ddc.dispute.due_by">No deadline set</p>
                </div>
                <div class="text-end">
                    <div class="h3 mb-0">₹@{{ ddc.dispute.amount | number:2 }}</div>
                    <small ng-bind="ddc.dispute.currency || 'INR'"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Payment Details -->
                <div class="detail-section">
                    <h5 class="mb-3">Payment Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Payment ID</div>
                            <div class="detail-value" ng-bind="ddc.dispute.payment_id || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Order ID</div>
                            <div class="detail-value" ng-bind="ddc.dispute.order_id || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Transaction ID</div>
                            <div class="detail-value" ng-bind="ddc.dispute.transaction_id || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Card Network</div>
                            <div class="detail-value" ng-bind="ddc.dispute.card_network || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹@{{ ddc.dispute.amount | number:2 }} @{{ ddc.dispute.currency || 'INR' }}</div>
                        </div>
                        <div class="col-md-6" ng-if="ddc.dispute.payment_details">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value" ng-bind="ddc.dispute.payment_details.status || '-'"></div>
                        </div>
                    </div>
                </div>

                <!-- Dispute Information -->
                <div class="detail-section">
                    <h5 class="mb-3">Dispute Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Reason</div>
                            <div class="detail-value" ng-bind="ddc.dispute.reason_formatted || ddc.dispute.reason || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Merchant</div>
                            <div class="detail-value" ng-bind="ddc.dispute.merchant_name || '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Created At</div>
                            <div class="detail-value" ng-bind="ddc.dispute.created_at ? (ddc.dispute.created_at | date:'MMM d, y HH:mm') : '-'"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value" ng-bind="ddc.dispute.updated_at ? (ddc.dispute.updated_at | date:'MMM d, y HH:mm') : '-'"></div>
                        </div>
                        <div class="col-md-6" ng-if="ddc.dispute.due_by">
                            <div class="detail-label">Due By</div>
                            <div class="detail-value">
                                @{{ ddc.dispute.due_by_formatted }}
                                <span ng-if="ddc.dispute.is_past_due" class="badge bg-danger ms-2">Overdue</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Frozen Amount</div>
                            <div class="detail-value">₹@{{ ddc.dispute.frozen_amount | number:2 }}</div>
                        </div>
                        <div class="col-md-6" ng-if="ddc.dispute.dispute_fee > 0">
                            <div class="detail-label">Dispute Fee</div>
                            <div class="detail-value">₹@{{ ddc.dispute.dispute_fee | number:2 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Evidence Upload Section -->
                <div class="detail-section" ng-if="ddc.dispute.can_upload_evidence">
                    <h5 class="mb-3">Upload Evidence</h5>
                    <p class="text-muted mb-3">Upload documents to support your case. Accepted formats: PDF, JPG, PNG (Max 5MB per file)</p>
                    
                    <div class="evidence-upload-area" 
                         ng-click="ddc.selectFile()"
                         ng-class="{'dragover': ddc.dragOver}"
                         style="cursor: pointer;">
                        <i class="bi bi-cloud-upload" style="font-size: 48px; color: #9ca3af;"></i>
                        <p class="mt-3 mb-0">Click to upload or drag and drop</p>
                        <small class="text-muted">PDF, JPG, PNG up to 5MB</small>
                    </div>
                    <input type="file" id="evidenceFileInput" style="display: none;" 
                           accept=".pdf,.jpg,.jpeg,.png" 
                           ng-change="ddc.onFileSelect($event)">
                    
                    <div class="mt-3" ng-if="ddc.selectedFile">
                        <label class="form-label">Document Type</label>
                        <select class="form-select" ng-model="ddc.uploadForm.document_type">
                            <option value="">Select Document Type</option>
                            <option value="invoice">Invoice</option>
                            <option value="delivery_proof">Delivery Proof</option>
                            <option value="communication">Communication</option>
                            <option value="refund_proof">Refund Proof</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="mt-3">
                            <button class="btn btn-primary" ng-click="ddc.uploadEvidence()" ng-disabled="ddc.uploading">
                                <span ng-if="ddc.uploading">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Uploading...
                                </span>
                                <span ng-if="!ddc.uploading">
                                    <i class="bi bi-upload me-2"></i>Upload Evidence
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Evidence List -->
                <div class="detail-section" ng-if="ddc.dispute.evidence && ddc.dispute.evidence.length > 0">
                    <h5 class="mb-3">Uploaded Evidence (@{{ ddc.dispute.evidence.length }})</h5>
                    <div ng-repeat="evidence in ddc.dispute.evidence">
                        <div class="evidence-item">
                            <div>
                                <div class="fw-bold" ng-bind="evidence.file_name"></div>
                                <small class="text-muted">
                                    @{{ evidence.document_type_formatted || evidence.document_type }}
                                    • Uploaded @{{ evidence.uploaded_at | date:'MMM d, y HH:mm' }}
                                </small>
                            </div>
                            <div>
                                <a ng-href="@{{ evidence.file_url }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button ng-if="ddc.dispute.can_upload_evidence" 
                                        class="btn btn-sm btn-outline-danger" 
                                        ng-click="ddc.deleteEvidence(evidence.id)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="detail-section" ng-if="ddc.dispute.can_submit">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Review your evidence before submitting. Once submitted, you cannot upload additional evidence.
                    </div>
                    <button class="btn btn-primary btn-lg" ng-click="ddc.submitEvidence()" ng-disabled="ddc.submitting">
                        <span ng-if="ddc.submitting">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Submitting...
                        </span>
                        <span ng-if="!ddc.submitting">
                            <i class="bi bi-send me-2"></i>Submit Evidence
                        </span>
                    </button>
                </div>
            </div>

            <!-- Right Column - Timeline -->
            <div class="col-lg-4">
                <div class="detail-section">
                    <h5 class="mb-3">Timeline</h5>
                    <div ng-if="ddc.dispute.timeline && ddc.dispute.timeline.length > 0">
                        <div class="timeline-item" ng-repeat="event in ddc.dispute.timeline">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-date" ng-bind="event.created_at_formatted"></div>
                                <div class="timeline-event" ng-bind="event.event_formatted || event.event"></div>
                                <div class="timeline-notes" ng-if="event.notes" ng-bind="event.notes"></div>
                            </div>
                        </div>
                    </div>
                    <div ng-if="!ddc.dispute.timeline || ddc.dispute.timeline.length === 0" class="text-center text-muted py-4">
                        No timeline events yet
                    </div>
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
            app.controller('DisputeDetailController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                
                vm.dispute = {};
                vm.loading = true;
                vm.uploading = false;
                vm.submitting = false;
                vm.dragOver = false;
                vm.selectedFile = null;
                vm.uploadForm = {
                    document_type: ''
                };
                
                // Get dispute ID from URL
                var disputeId = window.location.pathname.split('/').pop();
                
                // Load dispute details
                vm.loadDispute = function() {
                    vm.loading = true;
                    $http.get('/admin/disputes/' + disputeId + '/data', {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.dispute = response.data.data;
                        } else {
                            alert('Failed to load dispute: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.loading = false;
                    }, function(error) {
                        console.error('Error loading dispute:', error);
                        alert('Failed to load dispute');
                        vm.loading = false;
                    });
                };
                
                // File selection
                vm.selectFile = function() {
                    document.getElementById('evidenceFileInput').click();
                };
                
                vm.onFileSelect = function(event) {
                    var file = event.target.files[0];
                    if (file) {
                        vm.selectedFile = file;
                        vm.uploadForm.document_type = '';
                        $scope.$apply();
                    }
                };
                
                // Initialize drag/drop handlers after controller loads
                $timeout(function() {
                    var uploadArea = document.querySelector('.evidence-upload-area');
                    if (uploadArea) {
                        uploadArea.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            vm.dragOver = true;
                            $scope.$apply();
                        });
                        uploadArea.addEventListener('dragleave', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            vm.dragOver = false;
                            $scope.$apply();
                        });
                        uploadArea.addEventListener('drop', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            vm.dragOver = false;
                            var files = e.dataTransfer.files;
                            if (files.length > 0) {
                                vm.selectedFile = files[0];
                                vm.uploadForm.document_type = '';
                                $scope.$apply();
                            }
                        });
                    }
                }, 500);
                
                // Upload evidence
                vm.uploadEvidence = function() {
                    if (!vm.selectedFile) {
                        alert('Please select a file');
                        return;
                    }
                    
                    if (!vm.uploadForm.document_type) {
                        alert('Please select a document type');
                        return;
                    }
                    
                    var formData = new FormData();
                    formData.append('file', vm.selectedFile);
                    formData.append('document_type', vm.uploadForm.document_type);
                    
                    vm.uploading = true;
                    
                    $http.post('/admin/disputes/' + disputeId + '/evidence', formData, {
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': undefined
                        },
                        transformRequest: angular.identity
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Evidence uploaded successfully');
                            vm.selectedFile = null;
                            vm.uploadForm.document_type = '';
                            document.getElementById('evidenceFileInput').value = '';
                            vm.loadDispute(); // Reload dispute data
                        } else {
                            alert('Failed to upload evidence: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.uploading = false;
                    }, function(error) {
                        console.error('Error uploading evidence:', error);
                        alert('Failed to upload evidence');
                        vm.uploading = false;
                    });
                };
                
                // Delete evidence
                vm.deleteEvidence = function(evidenceId) {
                    if (!confirm('Are you sure you want to delete this evidence?')) {
                        return;
                    }
                    
                    $http.delete('/admin/disputes/' + disputeId + '/evidence/' + evidenceId, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Evidence deleted successfully');
                            vm.loadDispute(); // Reload dispute data
                        } else {
                            alert('Failed to delete evidence: ' + (response.data.message || 'Unknown error'));
                        }
                    }, function(error) {
                        console.error('Error deleting evidence:', error);
                        alert('Failed to delete evidence');
                    });
                };
                
                // Submit evidence
                vm.submitEvidence = function() {
                    if (!confirm('Are you sure you want to submit evidence? You will not be able to upload additional documents after submission.')) {
                        return;
                    }
                    
                    vm.submitting = true;
                    
                    $http.post('/admin/disputes/' + disputeId + '/submit', {}, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            alert('Evidence submitted successfully');
                            vm.loadDispute(); // Reload dispute data
                        } else {
                            alert('Failed to submit evidence: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.submitting = false;
                    }, function(error) {
                        console.error('Error submitting evidence:', error);
                        alert('Failed to submit evidence');
                        vm.submitting = false;
                    });
                };
                
                // Initialize
                vm.loadDispute();
            }]);
        } catch(e) {
            console.error('Error registering DisputeDetailController:', e);
            setTimeout(registerController, 100);
        }
    }
    registerController();
})();
</script>
@endpush

