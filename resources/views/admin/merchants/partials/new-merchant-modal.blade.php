<!-- New Merchant Modal -->
<div class="modal fade" id="newMerchantModal" tabindex="-1" aria-labelledby="newMerchantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMerchantModalLabel">@{{ amac.editingMerchantId ? 'Edit Merchant' : 'Create new entry' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newMerchantForm" ng-submit="amac.submitMerchant()">
                    <h6 class="text-primary mb-3">MERCHANT DETAILS</h6>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isPartnerMerchant" ng-model="amac.merchantForm.is_partner_merchant">
                                <label class="form-check-label" for="isPartnerMerchant">Is Partner Merchant</label>
                            </div>
                        </div>
                        <div class="col-md-6" ng-show="amac.merchantForm.is_partner_merchant">
                            <label class="form-label">Partners</label>
                            <select class="form-select" ng-model="amac.merchantForm.partner_id" ng-change="amac.loadPartnerTeams()">
                                <option value="">Select Partner</option>
                                <option value="1">Partner 1</option>
                                <option value="2">Partner 2</option>
                            </select>
                        </div>
                        <div class="col-md-6" ng-show="amac.merchantForm.is_partner_merchant">
                            <label class="form-label">* Team</label>
                            <select class="form-select" ng-model="amac.merchantForm.team_id" required>
                                <option value="">Select Team</option>
                                <option ng-repeat="team in amac.teams" value="@{{ team.id }}">@{{ team.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Merchant Name</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Merchant Legal Name</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.legal_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Merchant Email</label>
                            <input type="email" class="form-control" ng-model="amac.merchantForm.email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Merchant Phone</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquirer</label>
                            <select class="form-select" ng-model="amac.merchantForm.acquirer_account_id">
                                <option value="">— No acquirer —</option>
                                <option ng-repeat="acq in amac.acquirers" value="@{{ acq.id }}">@{{ acq.acquirer_name }} @{{ acq.account_id ? '(' + acq.account_id + ')' : '' }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Merchant Category</label>
                            <select class="form-select" ng-model="amac.merchantForm.merchant_category" required>
                                <option value="">Select Category</option>
                                <option value="B2B">B2B</option>
                                <option value="Education">Education</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Utilities">Utilities</option>
                                <option value="E-commerce">E-commerce</option>
                                <option value="Travel & Hospitality">Travel & Hospitality</option>
                                <option value="Telecom">Telecom</option>
                                <option value="High Risk">High Risk</option>
                                <option value="Grocery">Grocery</option>
                                <option value="NBFC">NBFC</option>
                                <option value="Government">Government</option>
                                <option value="Others">Others</option>
                                <option value="Forex">Forex</option>
                                <option value="Real Estate">Real Estate</option>
                                <option value="Housing Society">Housing Society</option>
                                <option value="Housing Board">Housing Board</option>
                                <option value="Govt E-Tendering">Govt E-Tendering</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merchant Category Code</label>
                            <select class="form-select" ng-model="amac.merchantForm.merchant_category_code">
                                <option value="">Select Code</option>
                                <option value="7399">7399 | Business services not elsewhere classified</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ownership Type</label>
                            <select class="form-select" ng-model="amac.merchantForm.ownership_type">
                                <option value="">Select Type</option>
                                <option value="Private Limited">Private Limited</option>
                                <option value="Public Limited">Public Limited</option>
                                <option value="Partnership">Partnership</option>
                                <option value="Sole Proprietorship">Sole Proprietorship</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website Link</label>
                            <input type="url" class="form-control" ng-model="amac.merchantForm.website_link">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">* Address Line 1</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.address_line_1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.address_line_2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">* Country</label>
                            <select class="form-select" ng-model="amac.merchantForm.business_country" required>
                                <option value="">Select Country</option>
                                <option value="India">India</option>
                                <option value="USA">USA</option>
                                <option value="UK">UK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">* State</label>
                            <select class="form-select" ng-model="amac.merchantForm.business_state" required>
                                <option value="">Select State</option>
                                <option ng-repeat="state in amac.states" value="@{{ state }}">@{{ state }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">* City</label>
                            <select class="form-select" ng-model="amac.merchantForm.business_city" required>
                                <option value="">Select City</option>
                                <option ng-repeat="city in amac.cities" value="@{{ city }}">@{{ city }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Zip Code</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.business_postal_code" required>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">TAX IDENTIFICATION</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">* Merchant PAN Number</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.merchant_pan_number" maxlength="10" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Name On PAN Card</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.name_on_pan_card" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Identification No.</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.gst_identification_no" placeholder="Enter GSTIN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GSTIN State</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.gstin_state">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TAN No</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.tan_no">
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">CONTACT INFORMATION</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">* Contact Name</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.contact_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Contact Mobile</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.contact_mobile" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Landline</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.contact_landline">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Contact Email</label>
                            <input type="email" class="form-control" ng-model="amac.merchantForm.contact_email" required>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">SETTLEMENT BANK ACCOUNT</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isDummyAccount" ng-model="amac.merchantForm.is_dummy_account">
                                <label class="form-check-label" for="isDummyAccount">Set Dummy Details</label>
                            </div>
                            <small class="text-muted">No settlements will be done to this merchant until real account is updated, real account can be updated later</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Account Holder Name</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.bank_account_holder_name" placeholder="Enter Bank Account Holder Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Bank Account Number</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.bank_account_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Bank Name</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.bank_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Account Type</label>
                            <select class="form-select" ng-model="amac.merchantForm.account_type" required>
                                <option value="">Select Type</option>
                                <option value="Savings Account">Savings Account</option>
                                <option value="Current Account">Current Account</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* Bank Branch</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.bank_branch" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">* IFSC Code</label>
                            <input type="text" class="form-control" ng-model="amac.merchantForm.bank_ifsc_code" required>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">SETTLEMENT SETTINGS</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Settlement Cycle - Domestic</label>
                            <select class="form-select" ng-model="amac.merchantForm.settlement_cycle_domestic">
                                <option value="1">T+1 (1 day)</option>
                                <option value="2">T+2 (2 days)</option>
                                <option value="3">T+3 (3 days)</option>
                                <option value="4">T+4 (4 days)</option>
                                <option value="5">T+5 (5 days)</option>
                                <option value="6">T+6 (6 days)</option>
                                <option value="7">T+7 (7 days)</option>
                            </select>
                            <small class="text-muted">Default: T+1 for domestic transactions</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement Cycle - International</label>
                            <select class="form-select" ng-model="amac.merchantForm.settlement_cycle_international">
                                <option value="1">T+1 (1 day)</option>
                                <option value="2">T+2 (2 days)</option>
                                <option value="3">T+3 (3 days)</option>
                                <option value="4">T+4 (4 days)</option>
                                <option value="5">T+5 (5 days)</option>
                                <option value="6">T+6 (6 days)</option>
                                <option value="7" selected>T+7 (7 days)</option>
                            </select>
                            <small class="text-muted">Default: T+7 for international transactions</small>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4" ng-show="!amac.editingMerchantId">USER LOGIN CREDENTIALS</h6>
                    <div class="alert alert-info" ng-show="!amac.editingMerchantId">
                        <i class="bi bi-info-circle"></i> A user account will be automatically created for this merchant. The merchant will appear in the Users tab and can only login after admin verifies their email.
                    </div>
                    <div class="row g-3 mb-3" ng-show="!amac.editingMerchantId">
                        <div class="col-md-6">
                            <label class="form-label">Login Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" ng-model="amac.merchantForm.login_name" placeholder="Leave empty to use merchant email" ng-required="false">
                            <small class="text-muted">If left empty, merchant email will be used as login email</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" ng-model="amac.merchantForm.password" required>
                            <small class="text-muted">Password must have minimum 12 characters and should include at least 1 uppercase, 1 lowercase, 1 numeric and 1 special character. If left empty, a random password will be generated.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Retype Password</label>
                            <input type="password" class="form-control" ng-model="amac.merchantForm.retype_password">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" ng-click="amac.submitMerchant()" ng-disabled="amac.submitting">
                    <span ng-if="!amac.submitting">@{{ amac.editingMerchantId ? 'Update' : 'Create' }}</span>
                    <span ng-if="amac.submitting">
                        <span class="spinner-border spinner-border-sm me-2"></span>@{{ amac.editingMerchantId ? 'Updating...' : 'Creating...' }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

