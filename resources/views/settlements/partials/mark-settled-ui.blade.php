@php
    $alias = $ng ?? 'mssc';
    $modalId = 'markSettledModal-' . $alias;
@endphp

<style>
    .settled-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    }
    .settled-modal__header {
        background: linear-gradient(135deg, #10b981 0%, #059669 55%, #047857 100%);
        color: #fff;
        padding: 1.35rem 1.5rem;
        border: none;
    }
    .settled-modal__icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.65rem;
        flex-shrink: 0;
    }
    .settled-modal__body {
        padding: 1.35rem 1.5rem 1rem;
        background: #fafafa;
    }
    .settled-modal__callout {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid #a7f3d0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        font-size: 0.9rem;
        color: #065f46;
        margin-bottom: 1rem;
    }
    .settled-modal__list {
        max-height: 200px;
        overflow-y: auto;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
    }
    .settled-modal__item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .settled-modal__item:last-child { border-bottom: none; }
    .settled-modal__item-id {
        font-weight: 600;
        font-size: 0.9rem;
        color: #1f2937;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .settled-modal__footer {
        background: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }
    .btn-settled-confirm {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
    }
    .btn-settled-confirm:hover:not(:disabled) {
        color: #fff;
        box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
    }
</style>

<div class="modal fade settled-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header settled-modal__header">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="settled-modal__icon-wrap">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1 fw-bold">Mark as settled</h5>
                        <p class="mb-0 small opacity-90">Confirm bank payout succeeded for the selected batch(es)</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body settled-modal__body">
                <div class="settled-modal__callout d-flex gap-2 align-items-start">
                    <i class="bi bi-bank2 fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong class="d-block mb-1">Payout recorded</strong>
                        Selected batches and their transactions will be marked <strong>settled</strong>.
                        Add a bank reference if you have a UTR/NEFT reference number.
                    </div>
                </div>

                <p class="text-muted small mb-2">
                    <span class="fw-semibold text-dark" ng-bind="{{ $alias }}.settledSelectedItems.length"></span>
                    settlement<span ng-if="{{ $alias }}.settledSelectedItems.length !== 1">s</span> selected
                </p>

                <div class="settled-modal__list mb-3">
                    <div class="settled-modal__item" ng-repeat="item in {{ $alias }}.settledSelectedItems track by item.id">
                        <div>
                            <div class="settled-modal__item-id">@{{ item.settlement_id || ('#' + item.id) }}</div>
                            <div class="small text-muted">@{{ item.currency || 'INR' }} @{{ item.payout_amount | number:2 }}</div>
                        </div>
                        <span class="badge bg-secondary text-uppercase">@{{ item.settlement_status || 'pending' }}</span>
                    </div>
                </div>

                <div class="mb-0">
                    <label for="bankRef-{{ $alias }}" class="form-label fw-semibold small">Bank reference <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" id="bankRef-{{ $alias }}" class="form-control"
                           placeholder="UTR / NEFT reference number"
                           maxlength="120"
                           ng-model="{{ $alias }}.settledBankReference">
                </div>
            </div>
            <div class="modal-footer settled-modal__footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" ng-disabled="{{ $alias }}.settledSubmitting">Cancel</button>
                <button type="button" class="btn btn-settled-confirm"
                        ng-click="{{ $alias }}.confirmMarkAsSettled()"
                        ng-disabled="{{ $alias }}.settledSubmitting">
                    <span ng-if="!{{ $alias }}.settledSubmitting"><i class="bi bi-check2 me-1"></i> Confirm settled</span>
                    <span ng-if="{{ $alias }}.settledSubmitting">
                        <span class="spinner-border spinner-border-sm me-1"></span> Saving…
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    window.attachMarkSettledHandlers = function(vm, config) {
        config = config || {};
        var postUrl = config.postUrl;
        var modalId = config.modalId;
        var $http = config.$http;
        var csrf = config.csrf;
        var onDone = config.onDone || function() {};

        vm.settledBankReference = '';
        vm.settledSubmitting = false;
        vm.settledSelectedItems = [];

        vm.markAsSettled = function() {
            vm.settledSelectedItems = vm.settlements.filter(function(s) { return s.selected; });
            if (vm.settledSelectedItems.length === 0) return;
            vm.settledBankReference = '';
            vm.settledSubmitting = false;
            var el = document.getElementById(modalId);
            if (el && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static' }).show();
            }
        };

        vm.closeSettledModal = function() {
            var el = document.getElementById(modalId);
            if (el && typeof bootstrap !== 'undefined') {
                var inst = bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();
            }
        };

        vm.confirmMarkAsSettled = function() {
            if (vm.settledSubmitting || vm.settledSelectedItems.length === 0) return;
            var ids = vm.settledSelectedItems.map(function(s) { return s.id; });
            var ref = (vm.settledBankReference || '').trim();
            vm.settledSubmitting = true;
            $http.post(postUrl, {
                ids: ids,
                bank_reference: ref || null
            }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function(response) {
                vm.settledSubmitting = false;
                vm.closeSettledModal();
                if (response.data && response.data.success) {
                    vm.selectAll = false;
                    if (typeof window.showToast === 'function') {
                        window.showToast(response.data.message || 'Settlements marked as settled.', 'success');
                    }
                    onDone();
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Could not update settlements.';
                    if (typeof window.showToast === 'function') window.showToast(msg, 'warning');
                }
            }, function(error) {
                vm.settledSubmitting = false;
                var errMsg = (error.data && error.data.message) ? error.data.message : 'Failed to mark settlements as settled.';
                if (typeof window.showToast === 'function') window.showToast(errMsg, 'error');
            });
        };
    };
})();
</script>
@endpush
