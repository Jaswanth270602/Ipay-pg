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
            app.controller('TransactionsController', ['$http', '$timeout', function($http, $timeout) {
        var vm = this;
        vm.transactions = [];
        vm.loading = false;
        vm.perPage = 10;
        vm.pagination = { current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 10 };
        vm.filters = { status: '', payment_method: '', from_date: '', to_date: '', search: '' };

        vm.loadTransactions = function() {
            vm.loading = true;
            var params = {
                page: vm.pagination.current_page,
                per_page: vm.perPage,
                status: vm.filters.status || '',
                payment_method: vm.filters.payment_method || '',
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || '',
                search: vm.filters.search || ''
            };
            
            $http.get('/merchant/transactions/data', { params: params }).then(function(response) {
                vm.transactions = response.data.data || [];
                vm.pagination = {
                    current_page: response.data.pagination.current_page,
                    last_page: response.data.pagination.last_page,
                    total: response.data.pagination.total,
                    from: response.data.pagination.from,
                    to: response.data.pagination.to,
                    per_page: response.data.pagination.per_page
                };
                vm.loading = false;
            }, function(error) {
                vm.loading = false;
                if (typeof showToast === 'function') {
                    showToast('Unable to load transactions. Please try again.', 'error');
                } else {
                    alert('Unable to load transactions. Please try again.');
                }
                console.error('Error loading transactions:', error);
            });
        };

        var filterTimeout;
        vm.applyFilters = function() {
            if (filterTimeout) $timeout.cancel(filterTimeout);
            filterTimeout = $timeout(function() {
                vm.pagination.current_page = 1;
                vm.loadTransactions();
            }, 300);
        };

        vm.clearFilters = function() {
            vm.filters = { status: '', payment_method: '', from_date: '', to_date: '', search: '' };
            vm.pagination.current_page = 1;
            vm.loadTransactions();
        };

        vm.loadPage = function(page) {
            if (page < 1 || page > vm.pagination.last_page) return;
            vm.pagination.current_page = page;
            vm.loadTransactions();
        };

        vm.getPaginationPages = function() {
            var pages = [];
            var start = Math.max(1, vm.pagination.current_page - 2);
            var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
            for (var i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        };

        // View transaction details
        vm.selectedTransaction = null;
        vm.viewDetails = function(transaction) {
            vm.selectedTransaction = transaction;
            var modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
            modal.show();
        };

        vm.closeModal = function() {
            vm.selectedTransaction = null;
            var modalEl = document.getElementById('transactionDetailsModal');
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        };

        vm.exportCSV = function() {
            var params = {
                status: vm.filters.status || '',
                payment_method: vm.filters.payment_method || '',
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || '',
                search: vm.filters.search || ''
            };

            var queryString = Object.keys(params).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }).join('&');

            window.location.href = '/merchant/transactions/export?' + queryString;
        };

        // Initialize
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

