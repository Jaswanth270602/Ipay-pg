<!-- Create/Edit Acquirer Account Modal -->
<div class="modal fade" id="acquirerAccountModal" tabindex="-1" aria-labelledby="acquirerAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="acquirerAccountModalLabel" ng-bind="aac.modalTitle || 'Acquirer Account'">Acquirer Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="acquirerAccountForm" ng-submit="aac.submitAccount($event)">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Team:</label>
                            <select class="form-select" ng-model="aac.accountForm.team">
                                <option value="">Select Team</option>
                                <option value="Team A">Team A</option>
                                <option value="Team B">Team B</option>
                                <option value="Team C">Team C</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquirer: <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="aac.accountForm.acquirer_name" required>
                                <option value="">Select Acquirer</option>
                                <option ng-repeat="name in aac.acquirerNames track by $index" ng-value="name" ng-bind="name"></option>
                            </select>
                            <small class="text-muted" ng-if="aac.acquirerNames && aac.acquirerNames.length > 0">
                                <span ng-bind="aac.acquirerNames.length"></span> acquirers available
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Id: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.account_id" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secret Key:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.secret_key">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salt:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.salt">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Key 1:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.additional_key_1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Key 2:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.additional_key_2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Key 3:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.additional_key_3">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Additional Key Data:</label>
                            <textarea class="form-control" rows="3" ng-model="aac.accountForm.additional_key_data"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description:</label>
                            <textarea class="form-control" rows="2" ng-model="aac.accountForm.description"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Whitelist URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.whitelist_url">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mode: <span class="text-danger">*</span></label>
                            <select class="form-select" ng-model="aac.accountForm.mode" required>
                                <option value="TEST">TEST</option>
                                <option value="LIVE">LIVE</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sector:</label>
                            <select class="form-select" ng-model="aac.accountForm.sector">
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
                            <label class="form-label">Hdfc Me Code:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.hdfc_me_code">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nodal Account:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.nodal_account">
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">API URLs</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Live Request URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.live_request_url">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Live Query URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.live_query_url">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Live Refund URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.live_refund_url">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test Request URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.test_request_url">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test Query URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.test_query_url">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test Refund URL:</label>
                            <input type="url" class="form-control" ng-model="aac.accountForm.test_refund_url">
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">SETTINGS</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Settlement Account Name:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.settlement_account_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Copy Rates From:</label>
                            <select class="form-select" ng-model="aac.accountForm.copy_rates_from">
                                <option value="">None</option>
                                <option ng-repeat="account in aac.accounts" value="@{{ account.id }}">@{{ account.account_id }} - @{{ account.acquirer_name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Refund Allowed:</label>
                            <select class="form-select" ng-model="aac.accountForm.refund_allowed">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlements Allowed:</label>
                            <select class="form-select" ng-model="aac.accountForm.settlements_to_be_created">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mask Pii:</label>
                            <select class="form-select" ng-model="aac.accountForm.mask_pii">
                                <option value="false">No</option>
                                <option value="true">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Is Active:</label>
                            <select class="form-select" ng-model="aac.accountForm.is_active">
                                <option ng-value="true">Yes</option>
                                <option ng-value="false">No</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Email Ids:</label>
                            <input type="text" class="form-control" ng-model="aac.accountForm.email_ids" placeholder="Comma-separated emails">
                        </div>
                        <div class="col-md-12">
                            <small class="text-muted">Merchants are assigned from the Merchants module (each merchant has one acquirer).</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" ng-disabled="aac.submitting">
                            <span ng-if="aac.submitting">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span ng-bind="aac.isEditMode ? 'Saving...' : 'Creating...'"></span>
                            </span>
                            <span ng-if="!aac.submitting" ng-bind="aac.isEditMode ? 'Save' : 'Create'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

