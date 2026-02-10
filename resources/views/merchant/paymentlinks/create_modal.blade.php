<!-- Version: <?php echo time(); ?> -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-labelledby="createLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLinkModalLabel">Create Payment Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" ng-disabled="plc.creating"></button>
            </div>
            <div class="modal-body">
                <form id="createLinkForm">
                    <div class="mb-3">
                        <label for="linkTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="linkTitle"
                               ng-model="plc.newLink.title" 
                               placeholder="e.g., Invoice Payment"
                               required
                               ng-disabled="plc.creating">
                    </div>

                    <div class="mb-3">
                        <label for="linkDescription" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="linkDescription"
                                  ng-model="plc.newLink.description" 
                                  rows="3"
                                  placeholder="Optional description for this payment link"
                                  ng-disabled="plc.creating"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="linkAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01"
                                       class="form-control" 
                                       id="linkAmount"
                                       ng-model="plc.newLink.amount" 
                                       placeholder="0.00"
                                       required
                                       ng-disabled="plc.creating">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="linkCurrency" class="form-label">Currency</label>
                                <select class="form-select" 
                                        id="linkCurrency"
                                        ng-model="plc.newLink.currency"
                                        ng-disabled="plc.creating">
                                    <option value="INR">INR - Indian Rupee</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="allowPartialPayment"
                                   ng-model="plc.newLink.allow_partial_payment"
                                   ng-true-value="true"
                                   ng-false-value="false"
                                   ng-disabled="plc.creating">
                            <label class="form-check-label" for="allowPartialPayment">
                                <strong>Allow Partial Payment</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Enable this to allow customers to pay any amount less than or equal to the total. They can use the same link multiple times until fully paid.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="linkExpires" class="form-label">Expires In (hours)</label>
                        <input type="number" 
                               class="form-control" 
                               id="linkExpires"
                               ng-model="plc.newLink.expires_in_hours" 
                               min="1"
                               max="720"
                               value="24"
                               ng-init="plc.newLink.expires_in_hours = 24"
                               ng-disabled="plc.creating">
                        <small class="text-muted">Link will expire after specified hours (default: 24 hours, max: 720 hours / 30 days)</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-secondary" 
                        data-bs-dismiss="modal" 
                        ng-disabled="plc.creating">
                    Cancel
                </button>
                <button type="button" 
                        id="createLinkButton"
                        class="btn btn-primary" 
                        ng-click="plc.createPaymentLink($event)"
                        ng-disabled="plc.creating">
                    <span ng-if="plc.creating">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Creating...
                    </span>
                    <span ng-if="!plc.creating">
                        <i class="bi bi-plus-circle me-2"></i>
                        Create Link
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
