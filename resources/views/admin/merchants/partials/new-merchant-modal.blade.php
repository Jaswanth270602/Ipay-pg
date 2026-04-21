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
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isResellerMerchant" ng-model="amac.merchantForm.is_reseller_merchant">
                                    <label class="form-check-label" for="isResellerMerchant">Is Merchant Reseller</label>
                                </div>
                            </div>
                            <div class="col-md-6" ng-show="amac.merchantForm.is_reseller_merchant">
                                <label class="form-label">* Reseller</label>
                                <select class="form-select" ng-model="amac.merchantForm.reseller_id" ng-class="{'is-invalid': amac.formErrors.reseller_id}">
                                    <option value="">Select Reseller</option>
                                    <option ng-repeat="reseller in amac.resellers" value="@{{ reseller.id }}">
                                        @{{ reseller.name }} (@{{ reseller.company_name || reseller.email }})
                                    </option>
                                </select>
                                <div class="invalid-feedback" ng-if="amac.formErrors.reseller_id">
                                    <span ng-repeat="msg in amac.formErrors.reseller_id">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6" ng-show="amac.merchantForm.is_partner_merchant">
                                <label class="form-label">Partner</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.partner_id"
                                        ng-change="amac.onPartnerMerchantPartnerChange()"
                                        ng-class="{'is-invalid': amac.formErrors.partner_id}">
                                    <option value="">Select Partner</option>
                                    <option ng-repeat="p in amac.partners" ng-value="p.id">@{{ p.name }}</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.partner_id">
                                    <span ng-repeat="msg in amac.formErrors.partner_id">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6" ng-show="amac.merchantForm.is_partner_merchant && amac.teams.length">
                                <label class="form-label">* Team</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.team_name"
                                        ng-class="{'is-invalid': amac.formErrors.team_name}">
                                    <option value="">Select Team</option>
                                    <option ng-repeat="t in amac.teams" ng-value="t">@{{ t }}</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.team_name">
                                    <span ng-repeat="msg in amac.formErrors.team_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Merchant Name</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="250"
                                       ng-class="{'is-invalid': amac.formErrors.name}"
                                       ng-model="amac.merchantForm.name"
                                       ng-change="amac.enforceMaxLength('name', 250)"
                                       ng-blur="amac.validateMerchantName()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.name">
                                    <span ng-repeat="msg in amac.formErrors.name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Merchant Legal Name</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="250"
                                       ng-class="{'is-invalid': amac.formErrors.legal_name}"
                                       ng-model="amac.merchantForm.legal_name"
                                       ng-change="amac.enforceMaxLength('legal_name', 250)"
                                       ng-blur="amac.validateLegalName()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.legal_name">
                                    <span ng-repeat="msg in amac.formErrors.legal_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Merchant Email</label>
                                <input type="email"
                                       class="form-control"
                                       maxlength="120"
                                       ng-class="{'is-invalid': amac.formErrors.email}"
                                       ng-model="amac.merchantForm.email"
                                       ng-blur="amac.validateMerchantEmail()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.email">
                                    <span ng-repeat="msg in amac.formErrors.email">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Merchant Phone</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="20"
                                       ng-class="{'is-invalid': amac.formErrors.phone}"
                                       ng-model="amac.merchantForm.phone"
                                       ng-blur="amac.validateMerchantPhone()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.phone">
                                    <span ng-repeat="msg in amac.formErrors.phone">@{{ msg }}<br></span>
                                </div>
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
                                <select class="form-select"
                                        ng-model="amac.merchantForm.merchant_category"
                                        ng-class="{'is-invalid': amac.formErrors.merchant_category}"
                                        required>
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
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.merchant_category">
                                    <span ng-repeat="msg in amac.formErrors.merchant_category">@{{ msg }}<br></span>
                                </div>
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
                                <input type="text"
                                       class="form-control"
                                       maxlength="250"
                                       ng-class="{'is-invalid': amac.formErrors.address_line_1}"
                                       ng-model="amac.merchantForm.address_line_1"
                                       ng-change="amac.enforceMaxLength('address_line_1', 250)"
                                       ng-blur="amac.validateAddress1()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.address_line_1">
                                    <span ng-repeat="msg in amac.formErrors.address_line_1">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" ng-model="amac.merchantForm.address_line_2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">* Country</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.business_country"
                                        ng-change="amac.onCountryChange()"
                                        ng-class="{'is-invalid': amac.formErrors.business_country}"
                                        required>
                                    <option value="">Select Country</option>
                                    <option ng-repeat="country in amac.availableCountries" value="@{{ country }}">@{{ country }}</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.business_country">
                                    <span ng-repeat="msg in amac.formErrors.business_country">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">* State</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.business_state"
                                        ng-change="amac.onStateChange()"
                                        ng-disabled="!amac.availableStates.length"
                                        ng-class="{'is-invalid': amac.formErrors.business_state}"
                                        required>
                                    <option value="">Select State</option>
                                    <option ng-repeat="state in amac.availableStates" value="@{{ state }}">@{{ state }}</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.business_state">
                                    <span ng-repeat="msg in amac.formErrors.business_state">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">* City</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.business_city"
                                        ng-disabled="!amac.availableCities.length"
                                        ng-class="{'is-invalid': amac.formErrors.business_city}"
                                        required>
                                    <option value="">Select City</option>
                                    <option ng-repeat="city in amac.availableCities" value="@{{ city }}">@{{ city }}</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.business_city">
                                    <span ng-repeat="msg in amac.formErrors.business_city">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Zip Code</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="15"
                                       ng-class="{'is-invalid': amac.formErrors.business_postal_code}"
                                       ng-model="amac.merchantForm.business_postal_code"
                                       ng-change="amac.validateZipCode()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.business_postal_code">
                                    <span ng-repeat="msg in amac.formErrors.business_postal_code">@{{ msg }}<br></span>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-primary mb-3">TAX IDENTIFICATION</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">* Merchant PAN Number</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.merchant_pan_number}"
                                       ng-model="amac.merchantForm.merchant_pan_number"
                                       ng-change="amac.validateMerchantPan()"
                                       maxlength="10"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.merchant_pan_number">
                                    <span ng-repeat="msg in amac.formErrors.merchant_pan_number">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Name On PAN Card</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.name_on_pan_card}"
                                       ng-model="amac.merchantForm.name_on_pan_card"
                                       ng-change="amac.validateNameOnPan()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.name_on_pan_card">
                                    <span ng-repeat="msg in amac.formErrors.name_on_pan_card">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">VAT Identification No.</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.gst_identification_no}"
                                       ng-model="amac.merchantForm.gst_identification_no"
                                       ng-change="amac.validateGstin()">
                                <div class="invalid-feedback" ng-if="amac.formErrors.gst_identification_no">
                                    <span ng-repeat="msg in amac.formErrors.gst_identification_no">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">VATIN State</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.gstin_state}"
                                       ng-model="amac.merchantForm.gstin_state"
                                       ng-change="amac.validateGstinState()">
                                <div class="invalid-feedback" ng-if="amac.formErrors.gstin_state">
                                    <span ng-repeat="msg in amac.formErrors.gstin_state">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TAN No</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.tan_no}"
                                       ng-model="amac.merchantForm.tan_no"
                                       ng-change="amac.validateTanNo()">
                                <div class="invalid-feedback" ng-if="amac.formErrors.tan_no">
                                    <span ng-repeat="msg in amac.formErrors.tan_no">@{{ msg }}<br></span>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-primary mb-3">CONTACT INFORMATION</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">* Contact Name</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.contact_name}"
                                       ng-model="amac.merchantForm.contact_name"
                                       ng-change="amac.validateContactName()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.contact_name">
                                    <span ng-repeat="msg in amac.formErrors.contact_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Contact Mobile</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="20"
                                       ng-class="{'is-invalid': amac.formErrors.contact_mobile}"
                                       ng-model="amac.merchantForm.contact_mobile"
                                       ng-change="amac.validateContactMobile()"
                                       ng-blur="amac.validateContactMobile()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.contact_mobile">
                                    <span ng-repeat="msg in amac.formErrors.contact_mobile">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Landline</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="20"
                                       ng-class="{'is-invalid': amac.formErrors.contact_landline}"
                                       ng-model="amac.merchantForm.contact_landline"
                                       ng-change="amac.validateContactLandline()">
                                <div class="invalid-feedback" ng-if="amac.formErrors.contact_landline">
                                    <span ng-repeat="msg in amac.formErrors.contact_landline">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Contact Email</label>
                                <input type="email"
                                       class="form-control"
                                       maxlength="120"
                                       ng-class="{'is-invalid': amac.formErrors.contact_email}"
                                       ng-model="amac.merchantForm.contact_email"
                                       ng-blur="amac.validateContactEmail()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.contact_email">
                                    <span ng-repeat="msg in amac.formErrors.contact_email">@{{ msg }}<br></span>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-primary mb-3">SETTLEMENT BANK ACCOUNT</h6>
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
                                <input type="text"
                                       class="form-control"
                                       autocomplete="off"
                                       ng-class="{'is-invalid': amac.formErrors.bank_account_holder_name}"
                                       ng-model="amac.merchantForm.bank_account_holder_name"
                                       ng-change="amac.syncAccountHolderNameField()"
                                       ng-keyup="amac.syncAccountHolderNameField()"
                                       ng-paste="amac.syncAccountHolderNameField()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.bank_account_holder_name">
                                    <span ng-repeat="msg in amac.formErrors.bank_account_holder_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Bank Account Number</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="34"
                                       ng-class="{'is-invalid': amac.formErrors.bank_account_number}"
                                       ng-model="amac.merchantForm.bank_account_number"
                                       ng-change="amac.enforceAlphaNumericOnly('bank_account_number', 34); amac.validateBankAccountNumber()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.bank_account_number">
                                    <span ng-repeat="msg in amac.formErrors.bank_account_number">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Bank Name</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.bank_name}"
                                       ng-model="amac.merchantForm.bank_name"
                                       ng-change="amac.validateBankName()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.bank_name">
                                    <span ng-repeat="msg in amac.formErrors.bank_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Account Type</label>
                                <select class="form-select"
                                        ng-model="amac.merchantForm.account_type"
                                        ng-class="{'is-invalid': amac.formErrors.account_type}"
                                        required>
                                    <option value="">Select Type</option>
                                    <option value="Savings Account">Savings Account</option>
                                    <option value="Current Account">Current Account</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.account_type">
                                    <span ng-repeat="msg in amac.formErrors.account_type">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* Bank Branch</label>
                                <input type="text"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.bank_branch}"
                                       ng-model="amac.merchantForm.bank_branch"
                                       ng-change="amac.validateBankBranch()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.bank_branch">
                                    <span ng-repeat="msg in amac.formErrors.bank_branch">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">* IFSC Code</label>
                                <input type="text"
                                       class="form-control"
                                       maxlength="20"
                                       ng-class="{'is-invalid': amac.formErrors.bank_ifsc_code}"
                                       ng-model="amac.merchantForm.bank_ifsc_code"
                                       ng-change="amac.validateIfscCode()"
                                       required>
                                <div class="invalid-feedback" ng-if="amac.formErrors.bank_ifsc_code">
                                    <span ng-repeat="msg in amac.formErrors.bank_ifsc_code">@{{ msg }}<br></span>
                                </div>
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

                        <h6 class="text-primary mb-3" ng-show="!amac.editingMerchantId">USER LOGIN CREDENTIALS</h6>
                        <div class="alert alert-info" ng-show="!amac.editingMerchantId">
                            <i class="bi bi-info-circle"></i> A user account will be automatically created for this merchant. The merchant will appear in the Users tab and can only login after admin verifies their email.
                        </div>
                        <div class="row g-3 mb-3" ng-show="!amac.editingMerchantId">
                            <div class="col-md-6">
                                <label class="form-label">Login Email</label>
                                <input type="email"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.login_name}"
                                       ng-model="amac.merchantForm.login_name"
                                       >
                                <small class="text-muted">If left empty, merchant email will be used as login email</small>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.login_name">
                                    <span ng-repeat="msg in amac.formErrors.login_name">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.password}"
                                       ng-model="amac.merchantForm.password">
                                <small class="text-muted">Minimum 12 characters with uppercase, lowercase, number, and special character. Leave empty to auto-generate.</small>
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.password">
                                    <span ng-repeat="msg in amac.formErrors.password">@{{ msg }}<br></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Retype Password</label>
                                <input type="password"
                                       class="form-control"
                                       ng-class="{'is-invalid': amac.formErrors.retype_password}"
                                       ng-model="amac.merchantForm.retype_password">
                                <div class="invalid-feedback d-block" ng-if="amac.formErrors.retype_password">
                                    <span ng-repeat="msg in amac.formErrors.retype_password">@{{ msg }}<br></span>
                                </div>
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
