@php $alias = $ng ?? 'mcc'; @endphp

<style>
    .cb-create-modal .modal-content { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15); }
    .cb-create-modal__header {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 55%, #4338ca 100%);
        color: #fff; border: none; padding: 1.25rem 1.5rem;
    }
    .cb-create-modal__body { background: #fafafa; padding: 1.25rem 1.5rem; }
    .cb-create-modal__hint {
        background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px;
        padding: 0.75rem 1rem; font-size: 0.875rem; color: #3730a3; margin-bottom: 1rem;
    }
    .cb-create-modal__payment-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1rem; margin-bottom: 1rem;
    }
</style>

<div class="modal fade cb-create-modal" id="chargeback-create-modal-{{ $alias }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header cb-create-modal__header">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="rounded-3 bg-white bg-opacity-25 p-2"><i class="bi bi-shield-exclamation fs-4"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-0 fw-bold">Register chargeback</h5>
                        <p class="mb-0 small opacity-90">Payment gateway flow — link bank dispute to a captured payment</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <form ng-submit="{{ $alias }}.submitCreateChargeback($event)">
                <div class="modal-body cb-create-modal__body">
                    <div class="cb-create-modal__hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Enter <strong>txn_id</strong> or gateway payment ID, then <strong>Look up payment</strong>.
                        Chargeback amount cannot exceed the payment total. A <strong>target date</strong> is set for bank response (default 14 days).
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Payment reference <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="{{ $alias }}.createForm.transaction_id"
                                   placeholder="txn_id or gateway payment id" maxlength="120">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100" ng-click="{{ $alias }}.lookupTransaction()"
                                    ng-disabled="{{ $alias }}.lookupLoading">
                                <span ng-if="!{{ $alias }}.lookupLoading"><i class="bi bi-search me-1"></i> Look up</span>
                                <span ng-if="{{ $alias }}.lookupLoading"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                        </div>
                    </div>

                    <div class="cb-create-modal__payment-card" ng-if="{{ $alias }}.txnPreview">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="small text-muted">Payment</div>
                                <div class="fw-semibold font-monospace" ng-bind="{{ $alias }}.txnPreview.txn_id"></div>
                                <div class="small text-muted">
                                    <span ng-bind="{{ $alias }}.txnPreview.payment_method"></span> ·
                                    <span ng-bind="{{ $alias }}.txnPreview.gateway"></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Captured amount</div>
                                <div class="fw-bold">
                                    <span ng-bind="{{ $alias }}.txnPreview.currency"></span>
                                    <span ng-bind="{{ $alias }}.txnPreview.amount | number:2"></span>
                                </div>
                                <div class="small" ng-class="{{ $alias }}.txnPreview.refunded ? 'text-warning' : 'text-success'">
                                    <span ng-if="{{ $alias }}.txnPreview.refunded">Refund on file</span>
                                    <span ng-if="!{{ $alias }}.txnPreview.refunded">No refund</span>
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted mt-2">
                            Remaining chargeback capacity:
                            <strong ng-bind="{{ $alias }}.txnPreview.remaining_chargeback_capacity | number:2"></strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Chargeback request ID</label>
                            <input type="text" class="form-control" ng-model="{{ $alias }}.createForm.chargeback_request_id"
                                   placeholder="Auto-generated if empty" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Target date (respond by)</label>
                            <input type="date" class="form-control" ng-model="{{ $alias }}.createForm.target_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Chargeback amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" step="0.01" min="0.01"
                                   ng-model="{{ $alias }}.createForm.chargeback_amount" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Initial status</label>
                            <select class="form-select" ng-model="{{ $alias }}.createForm.chargeback_status">
                                <option value="pending">Pending (bank initiated)</option>
                                <option value="processing">Processing</option>
                                <option value="contested">Contested</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Notes</label>
                            <textarea class="form-control" rows="2" maxlength="2000" ng-model="{{ $alias }}.createForm.notes"
                                      placeholder="Bank case reference, reason code, etc."></textarea>
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 mb-0 py-2 small" ng-if="{{ $alias }}.createFormError" role="alert" ng-bind="{{ $alias }}.createFormError"></div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal" ng-disabled="{{ $alias }}.createSubmitting">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" ng-disabled="{{ $alias }}.createSubmitting || !{{ $alias }}.txnPreview">
                        <span ng-if="!{{ $alias }}.createSubmitting"><i class="bi bi-check2 me-1"></i> Register chargeback</span>
                        <span ng-if="{{ $alias }}.createSubmitting"><span class="spinner-border spinner-border-sm me-1"></span> Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
