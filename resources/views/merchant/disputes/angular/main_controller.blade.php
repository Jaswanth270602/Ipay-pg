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
            var app = angular.module('ipayApp');
            app.controller('MerchantDisputesController', ['$http', function($http) {
                var vm = this;
                vm.filters = { status: '' };
                vm.items = { data: [] };
                vm.form = { 
                    transaction_id: '', 
                    order_id: '',
                    reason: '', 
                    amount: '', 
                    currency: 'USD',
                    card_network: '',
                    internal_notes: '' 
                };
                vm.creating = false;
                vm.updating = false;
                vm.updateForm = {
                    id: null,
                    status: '',
                    notes: '',
                    transaction_label: ''
                };

                vm.load = function(page) {
                    var params = { status: vm.filters.status || '' };
                    if (page) params.page = page;
                    $http.get('/merchant/disputes/data', { params: params }).then(function(resp) {
                        vm.items = resp.data.data;
                    });
                };

                vm.create = function() {
                    // Validation
                    if (!vm.form.reason) {
                        alert('Please select a reason');
                        return;
                    }
                    if (!vm.form.amount || vm.form.amount <= 0) {
                        alert('Please enter a valid amount');
                        return;
                    }

                    vm.creating = true;
                    var csrf = document.querySelector('meta[name="csrf-token"]').content;
                    $http.post('/merchant/disputes', vm.form, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.creating = false;
                        if (response.data.success) {
                            // Reset form
                            vm.form = { 
                                transaction_id: '', 
                                order_id: '',
                                reason: '', 
                                amount: '', 
                                currency: 'USD',
                                card_network: '',
                                internal_notes: '' 
                            };
                            var modalEl = document.getElementById('newDisputeModal');
                            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            vm.load();
                            if (typeof showToast === 'function') {
                                showToast('Dispute created successfully!', 'success');
                            }
                        } else {
                            var msg = 'Failed to create dispute: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(msg, 'error');
                            } else {
                                console.error(msg);
                            }
                        }
                    }, function(error) {
                        vm.creating = false;
                        var errorMsg = 'Failed to create dispute';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = [];
                            for (var field in error.data.errors) {
                                errors.push(error.data.errors[field].join(', '));
                            }
                            errorMsg = errors.join('\n');
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            console.error(errorMsg);
                        }
                    });
                };

                vm.openUpdateStatus = function(dispute) {
                    vm.updateForm.id = dispute.id;
                    vm.updateForm.status = dispute.status;
                    vm.updateForm.notes = '';
                    vm.updateForm.transaction_label = dispute.transaction_id || dispute.order_id || ('Dispute #' + dispute.id);

                    var modalEl = document.getElementById('updateDisputeStatusModal');
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                };

                vm.updateStatus = function() {
                    if (!vm.updateForm.id || !vm.updateForm.status) {
                        if (typeof showToast === 'function') {
                            showToast('Please select a status.', 'error');
                        }
                        return;
                    }

                    vm.updating = true;
                    var csrf = document.querySelector('meta[name="csrf-token"]').content;
                    $http.post('/merchant/disputes/' + vm.updateForm.id + '/status', {
                        status: vm.updateForm.status,
                        notes: vm.updateForm.notes
                    }, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.updating = false;
                        if (response.data && response.data.success) {
                            var modalEl = document.getElementById('updateDisputeStatusModal');
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            vm.load();
                            if (typeof showToast === 'function') {
                                showToast('Dispute status updated successfully.', 'success');
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast('Failed to update status.', 'error');
                            } else {
                                console.error('Failed to update status.');
                            }
                        }
                    }, function(error) {
                        vm.updating = false;
                        var msg = 'Failed to update status';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            console.error(msg);
                        }
                    });
                };

                vm.load();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        setTimeout(registerController, 50);
    }
})();
</script>
@endpush


