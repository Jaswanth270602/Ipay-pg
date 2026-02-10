@push('scripts')
<script>
(function() {
    'use strict';
    // Register controller - wait for Angular to be ready
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        
        try {
            var app = angular.module('badlicashApp');
            app.controller('DashboardController', ['$http', function($http) {
                var vm = this;
                vm.loading = false;
                vm.recentTransactions = [];

                vm.loadRecentTransactions = function() {
                    vm.loading = true;
                    $http.get('/merchant/transactions/data', {
                        params: { page: 1, per_page: 5 }
                    }).then(function(response) {
                        if (response.data && response.data.success && response.data.data) {
                            // Map the API response fields to the template fields
                            vm.recentTransactions = response.data.data.map(function(txn) {
                                return {
                                    txn_id: txn.transaction_id || txn.txn_id || '-',
                                    amount: parseFloat(txn.amount_paid_by_customer || txn.amount || 0),
                                    currency: txn.currency_code || txn.currency || 'INR',
                                    payment_method: txn.payment_mode || txn.payment_method || '-',
                                    status: txn.payment_status || txn.status || '-',
                                    created_at: txn.transaction_datetime || txn.created_at || txn.transaction_initiation_time || new Date().toISOString()
                                };
                            });
                        } else {
                            vm.recentTransactions = [];
                        }
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading recent transactions:', error);
                        vm.recentTransactions = [];
                    });
                };

                vm.loadRecentTransactions();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    
    // Register immediately if Angular is ready, otherwise wait
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', registerController);
        } else {
            registerController();
        }
    }
})();
</script>
@endpush

