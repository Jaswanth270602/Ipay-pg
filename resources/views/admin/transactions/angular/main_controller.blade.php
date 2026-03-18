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
            app.controller('AdminTransactionsController', ['$http', function($http) {
                var vm = this;
                vm.transactions = [];
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1 };

                vm.filters = {
                    status: 'all',
                    merchant_id: '',
                    search: '',
                    // column filters (match backend expectations)
                    filter_transaction_id: '',
                    filter_order_id: '',
                    filter_payment_status: 'all',
                    filter_amount_paid: '',
                    filter_payment_mode: '',
                    filter_transaction_datetime: '',
                    filter_transaction_initiation_time: '',
                    filter_transaction_sequence_id: ''
                };
                vm.loading = false;

                vm.loadTransactions = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        merchant_id: vm.filters.merchant_id || '',
                        search: vm.filters.search || '',
                        filter_transaction_id: vm.filters.filter_transaction_id || '',
                        filter_order_id: vm.filters.filter_order_id || '',
                        filter_payment_status: vm.filters.filter_payment_status || '',
                        filter_amount_paid: vm.filters.filter_amount_paid || '',
                        filter_payment_mode: vm.filters.filter_payment_mode || '',
                        filter_transaction_datetime: vm.filters.filter_transaction_datetime || '',
                        filter_transaction_initiation_time: vm.filters.filter_transaction_initiation_time || '',
                        filter_transaction_sequence_id: vm.filters.filter_transaction_sequence_id || ''
                    };
                    
                    $http.get('/admin/transactions/data', { params: params }).then(function(response) {
                        vm.transactions = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading transactions:', error);
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadTransactions();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadTransactions();
                };

                vm.clearFilters = function() {
                    vm.filters = {
                        status: 'all',
                        merchant_id: '',
                        search: '',
                        filter_transaction_id: '',
                        filter_order_id: '',
                        filter_payment_status: 'all',
                        filter_amount_paid: '',
                        filter_payment_mode: '',
                        filter_transaction_datetime: '',
                        filter_transaction_initiation_time: '',
                        filter_transaction_sequence_id: ''
                    };
                    vm.applyFilters();
                };

                vm.getPageNumbers = function() {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                vm.loadTransactions();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
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

