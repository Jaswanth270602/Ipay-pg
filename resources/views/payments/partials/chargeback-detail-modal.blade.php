{{--
  Bootstrap modal with read-only form fields for chargeback details.
  Expects Angular controller alias passed as $ng (e.g. 'acc', 'mcc').
  Requires vm.selectedChargeback set before opening modal.
--}}
<div class="modal fade" id="chargeback-detail-modal-{{ $ng }}" tabindex="-1" aria-labelledby="chargeback-detail-modal-label-{{ $ng }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chargeback-detail-modal-label-{{ $ng }}">Chargeback details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" ng-if="{{ $ng }}.selectedChargeback">
                <div class="row g-3">
                    @if (!empty($showMerchant))
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Merchant</label>
                            <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.merchant_name">
                            <small class="text-muted">ID: <span ng-bind="{{ $ng }}.selectedChargeback.merchant_id"></span></small>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Chargeback request ID</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.chargeback_request_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Transaction ID</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.transaction_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Status</label>
                        <input type="text" class="form-control text-uppercase" readonly ng-value="{{ $ng }}.selectedChargeback.chargeback_status">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Amount</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.chargeback_amount">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Refunded</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.refunded_or_not">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Decision in favour of</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.decision_in_favour_of">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Contested</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.contested">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Debit settlement ID</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.debit_settlement_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Account ID</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.account_id">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-1">Account description</label>
                        <textarea class="form-control" rows="2" readonly ng-bind="{{ $ng }}.selectedChargeback.account_id_descript"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Merchant debit date</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.merchant_debit_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Merchant credit date</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.merchant_credit_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Bank debit date</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.bank_debit_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Bank credit date</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.bank_credit_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Target date</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.target_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Debit merchant</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.debit_merchant">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Dispute</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.is_dispute">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Second chargeback</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.second_chargeback">
                    </div>
                    <div class="col-md-6" ng-if="{{ $ng }}.selectedChargeback.test_mode">
                        <label class="form-label text-muted small mb-1">Test mode</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.test_mode">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Created</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.created_at">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-1">Updated</label>
                        <input type="text" class="form-control" readonly ng-value="{{ $ng }}.selectedChargeback.updated_at">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-1">Notes</label>
                        <textarea class="form-control" rows="4" readonly ng-bind="{{ $ng }}.selectedChargeback.notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
