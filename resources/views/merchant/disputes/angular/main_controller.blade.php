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
            var app = angular.module('badlicashApp');
            app.controller('MerchantDisputesController', ['$http', function($http) {
                var vm = this;
                vm.filters = { status: '' };
                vm.items = { data: [] };
                vm.form = { 
                    transaction_id: '', 
                    order_id: '',
                    reason: '', 
                    amount: '', 
                    currency: 'INR',
                    card_network: '',
                    internal_notes: '' 
                };
                vm.creating = false;

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
                                currency: 'INR',
                                card_network: '',
                                internal_notes: '' 
                            };
                            var modalEl = document.getElementById('newDisputeModal');
                            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            vm.load();
                            alert('Dispute created successfully!');
                        } else {
                            alert('Failed to create dispute: ' + (response.data.message || 'Unknown error'));
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
                        alert(errorMsg);
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


