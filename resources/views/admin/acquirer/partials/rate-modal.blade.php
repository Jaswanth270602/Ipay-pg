<!-- Create/Edit Acquirer Rate Modal -->
<div class="modal fade" id="acquirerRateModal" tabindex="-1" aria-labelledby="acquirerRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="acquirerRateModalLabel">@{{ arc.modalTitle }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="acquirerRateForm" ng-submit="arc.submitRate()">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Acquirer: <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="arc.rateForm.acquirer_account_id" ng-change="arc.onAcquirerChange()" required>
                                <option value="">Select Acquirer</option>
                                <option ng-repeat="account in arc.acquirerAccounts" value="@{{ account.id }}">@{{ account.display }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Id: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="arc.rateForm.account_id" readonly>
                            <small class="text-muted">Auto-filled from selected acquirer</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode: <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="arc.rateForm.payment_mode" required>
                                <option value="">Select Payment Mode</option>
                                <option ng-repeat="mode in arc.paymentModes" value="@{{ mode }}">@{{ mode }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Bank:</label>
                            <select class="form-select" ng-model="arc.rateForm.bank_code">
                                <option value="">Select Bank</option>
                                <option ng-repeat="bank in arc.banks" value="@{{ bank.code }}">@{{ bank.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Description:</label>
                            <input type="text" class="form-control" ng-model="arc.rateForm.bank_description">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sector:</label>
                            <select class="form-select" ng-model="arc.rateForm.sector">
                                <option value="">Select Sector</option>
                                <option value="B2B">B2B</option>
                                <option value="Education">Education</option>
                                <option value="E-commerce">E-commerce</option>
                                <option value="Travel & Hospitality">Travel & Hospitality</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Telecom">Telecom</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Time Frame: <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="arc.rateForm.settlement_time_frame" required>
                                <option value="t+0">t+0</option>
                                <option value="t+1">t+1</option>
                                <option value="t+2">t+2</option>
                                <option value="t+3">t+3</option>
                                <option value="t+7">t+7</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Time Of Day: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="arc.rateForm.settlement_time_of_day" placeholder="e.g., 09:00, EOD" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fixed Fee TDR: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.fixed_fee_mdr" step="0.0001" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Percentage TDR: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.percentage_mdr" step="0.0001" min="0" max="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service Tax Rates:</label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.service_tax_rates" step="0.0001" min="0" max="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Min Amount: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.min_amount" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Max Amount: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.max_amount" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Min Transaction Amount: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.min_transaction_charge" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TDR Max Transaction Amount: <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" ng-model="arc.rateForm.max_transaction_charge" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Is Enable:</label>
                            <select class="form-select" ng-model="arc.rateForm.is_enabled">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Part Paid Id:</label>
                            <input type="text" class="form-control" ng-model="arc.rateForm.part_paid_id">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" ng-disabled="arc.submitting">
                            <span ng-if="arc.submitting">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...
                            </span>
                            <span ng-if="!arc.submitting">Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

