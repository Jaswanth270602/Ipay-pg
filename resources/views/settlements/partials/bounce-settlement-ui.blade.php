@php
    $alias = $ng ?? 'mssc';
    $modalId = 'bounceSettlementModal-' . $alias;
    $postUrl = $postUrl ?? url('/merchant/settlements/summary/bounce');
@endphp

<style>
    .bounce-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    }
    .bounce-modal__header {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 55%, #c2410c 100%);
        color: #fff;
        padding: 1.35rem 1.5rem;
        border: none;
    }
    .bounce-modal__icon-wrap {
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
    .bounce-modal__body {
        padding: 1.35rem 1.5rem 1rem;
        background: #fafafa;
    }
    .bounce-modal__callout {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        font-size: 0.9rem;
        color: #92400e;
        margin-bottom: 1rem;
    }
    .bounce-modal__callout i {
        color: #d97706;
    }
    .bounce-modal__list {
        max-height: 220px;
        overflow-y: auto;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
    }
    .bounce-modal__item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .bounce-modal__item:last-child {
        border-bottom: none;
    }
    .bounce-modal__item-id {
        font-weight: 600;
        font-size: 0.9rem;
        color: #1f2937;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .bounce-modal__item-meta {
        font-size: 0.8rem;
        color: #6b7280;
    }
    .bounce-modal__amount {
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }
    .bounce-modal__status {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        background: #f3f4f6;
        color: #4b5563;
    }
    .bounce-modal__status--pending { background: #dbeafe; color: #1d4ed8; }
    .bounce-modal__status--processing { background: #e0e7ff; color: #4338ca; }
    .bounce-modal__status--settled { background: #d1fae5; color: #047857; }
    .bounce-modal__status--bounced { background: #fee2e2; color: #b91c1c; }
    .bounce-modal__reason label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #374151;
    }
    .bounce-modal__reason textarea {
        border-radius: 10px;
        border-color: #d1d5db;
        resize: vertical;
        min-height: 88px;
    }
    .bounce-modal__reason textarea:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }
    .bounce-modal__footer {
        background: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }
    .btn-bounce-confirm {
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border-radius: 10px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .btn-bounce-confirm:hover:not(:disabled) {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(234, 88, 12, 0.35);
    }
    .btn-bounce-confirm:disabled {
        opacity: 0.75;
    }
</style>

<div class="modal fade bounce-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bounce-modal__header">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="bounce-modal__icon-wrap">
                        <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1 fw-bold" id="{{ $modalId }}Label">Bounce settlement</h5>
                        <p class="mb-0 small opacity-90">Bank payout failed or was returned — release transactions for a future cycle</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body bounce-modal__body">
                <div class="alert alert-danger d-flex gap-2 align-items-start mb-3 py-2 px-3 rounded-3 border-0"
                     ng-if="{{ $alias }}.bounceHasSettledSelection" role="alert">
                    <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
                    <div class="small">
                        <strong>Cannot bounce settled payouts.</strong>
                        Deselect any row with status <strong>settled</strong> — only <strong>pending</strong> or
                        <strong>processing</strong> batches can be bounced.
                    </div>
                </div>

                <div class="bounce-modal__callout d-flex gap-2 align-items-start" ng-if="!{{ $alias }}.bounceHasSettledSelection">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong class="d-block mb-1">What happens next</strong>
                        Selected batches will be marked <strong>bounced</strong>. All linked transactions return to
                        <strong>pending</strong> and can be included in a new settlement run.
                    </div>
                </div>

                <p class="text-muted small mb-2">
                    <span class="fw-semibold text-dark" ng-bind="{{ $alias }}.bounceSelectedItems.length"></span>
                    settlement<span ng-if="{{ $alias }}.bounceSelectedItems.length !== 1">s</span> selected
                </p>

                <div class="bounce-modal__list mb-3">
                    <div class="bounce-modal__item" ng-repeat="item in {{ $alias }}.bounceSelectedItems track by item.id">
                        <div>
                            <div class="bounce-modal__item-id">@{{ item.settlement_id || ('#' + item.id) }}</div>
                            <div class="bounce-modal__item-meta">
                                @{{ item.transaction_count || 0 }} txn<span ng-if="item.transaction_count !== 1">s</span>
                                <span ng-if="item.settlement_date"> · @{{ item.settlement_date }}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="bounce-modal__amount">
                                @{{ item.currency || 'INR' }} @{{ item.payout_amount | number:2 }}
                            </div>
                            <span class="bounce-modal__status"
                                  ng-class="'bounce-modal__status--' + (item.settlement_status || 'pending')">
                                @{{ item.settlement_status || 'pending' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bounce-modal__reason">
                    <label for="bounceReason-{{ $alias }}" class="form-label">Bounce reason <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="bounceReason-{{ $alias }}"
                              class="form-control"
                              rows="3"
                              maxlength="500"
                              placeholder="e.g. Invalid account, NEFT rejected, insufficient beneficiary details…"
                              ng-model="{{ $alias }}.bounceReason"></textarea>
                    <div class="form-text text-end" ng-bind="(({{ $alias }}.bounceReason || '').length) + ' / 500'"></div>
                </div>
            </div>
            <div class="modal-footer bounce-modal__footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" ng-disabled="{{ $alias }}.bounceSubmitting">
                    Cancel
                </button>
                <button type="button"
                        class="btn btn-bounce-confirm"
                        ng-click="{{ $alias }}.confirmBounceSettlement()"
                        ng-disabled="{{ $alias }}.bounceSubmitting || {{ $alias }}.bounceHasSettledSelection">
                    <span ng-if="!{{ $alias }}.bounceSubmitting">
                        <i class="bi bi-arrow-return-left me-1"></i> Confirm bounce
                    </span>
                    <span ng-if="{{ $alias }}.bounceSubmitting">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Processing…
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

    window.attachBounceSettlementHandlers = function(vm, config) {
        config = config || {};
        var postUrl = config.postUrl;
        var modalId = config.modalId;
        var $http = config.$http;
        var csrf = config.csrf;
        var onDone = config.onDone || function() {};

        vm.bounceReason = '';
        vm.bounceSubmitting = false;
        vm.bounceSelectedItems = [];

        vm.bounceHasSettledSelection = false;

        vm.bounceSettlement = function() {
            vm.bounceSelectedItems = vm.settlements.filter(function(s) { return s.selected; });
            if (vm.bounceSelectedItems.length === 0) return;
            vm.bounceHasSettledSelection = vm.bounceSelectedItems.some(function(s) {
                return String(s.settlement_status || '').toLowerCase() === 'settled';
            });
            vm.bounceReason = '';
            vm.bounceSubmitting = false;
            var el = document.getElementById(modalId);
            if (el && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: !vm.bounceSubmitting }).show();
            }
        };

        vm.closeBounceModal = function() {
            var el = document.getElementById(modalId);
            if (el && typeof bootstrap !== 'undefined') {
                var inst = bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();
            }
        };

        vm.confirmBounceSettlement = function() {
            if (vm.bounceSubmitting || vm.bounceSelectedItems.length === 0 || vm.bounceHasSettledSelection) return;

            var ids = vm.bounceSelectedItems.map(function(s) { return s.id; });
            var reason = (vm.bounceReason || '').trim();

            vm.bounceSubmitting = true;
            $http.post(postUrl, {
                ids: ids,
                bounce_reason: reason || null
            }, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function(response) {
                vm.bounceSubmitting = false;
                vm.closeBounceModal();
                if (response.data && response.data.success) {
                    vm.selectAll = false;
                    if (typeof window.showToast === 'function') {
                        window.showToast(response.data.message || 'Settlements marked as bounced.', 'success');
                    }
                    onDone();
                } else {
                    var warnMsg = (response.data && response.data.message) ? response.data.message : 'Could not bounce settlements.';
                    if (typeof window.showToast === 'function') {
                        window.showToast(warnMsg, 'warning');
                    }
                }
            }, function(error) {
                vm.bounceSubmitting = false;
                var errMsg = (error.data && error.data.message) ? error.data.message : 'Failed to bounce settlements. Please try again.';
                if (typeof window.showToast === 'function') {
                    window.showToast(errMsg, 'error');
                }
            });
        };
    };
})();
</script>
@endpush
