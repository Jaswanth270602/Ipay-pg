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
            app.controller('TransactionsController', ['$http', '$timeout', function($http, $timeout) {
        var vm = this;
        vm.transactions = [];
        vm.loading = false;
        vm.perPage = 10;
        vm.pagination = { current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 10 };
        vm.filters = {
            status: '',
            payment_method: '',
            from_date: '',
            to_date: '',
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

        vm.loadTransactions = function() {
            vm.loading = true;
            var params = {
                page: vm.pagination.current_page,
                per_page: vm.perPage,
                status: vm.filters.status || '',
                payment_method: vm.filters.payment_method || '',
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || '',
                search: vm.filters.search || '',
                filter_transaction_id: vm.filters.filter_transaction_id || '',
                filter_order_id: vm.filters.filter_order_id || '',
                filter_payment_status: vm.filters.filter_payment_status || '',
                filter_amount_paid: vm.filters.filter_amount_paid || '',
                filter_payment_mode: vm.filters.filter_payment_mode || '',
                // Let backend ignore date; handle on client so it always works
                filter_transaction_datetime: '',
                filter_transaction_initiation_time: '',
                filter_transaction_sequence_id: vm.filters.filter_transaction_sequence_id || ''
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

        // Client-side date filtering so UI always matches what user types/picks
        vm.dateMatches = function(transaction) {
            var dtFilter = vm.filters.filter_transaction_datetime;
            var initFilter = vm.filters.filter_transaction_initiation_time;

            // No date filters at all
            if (!dtFilter && !initFilter) return true;

            var dtValue = (transaction.transaction_datetime || '').toString();           // e.g. "17-03-2026 08:05:00"
            var initValue = (transaction.transaction_initiation_time || '').toString(); // e.g. "17-03-2026 08:05:00"

            var dtOk = true;
            var initOk = true;

            // date input model can be a Date object or "yyyy-mm-dd" string,
            // while we DISPLAY "dd-mm-yyyy ...". Normalize filter to "dd-mm-yyyy".
            function normalizeFilterDate(val) {
                if (!val) return '';

                // If it's a Date object from the date picker
                if (Object.prototype.toString.call(val) === '[object Date]' && !isNaN(val.getTime())) {
                    var y = val.getFullYear();
                    var m = ('0' + (val.getMonth() + 1)).slice(-2);
                    var d = ('0' + val.getDate()).slice(-2);
                    // we display dd-mm-yyyy
                    return d + '-' + m + '-' + y;
                }

                var s = val.toString().trim();
                // Already dd-mm-yyyy
                if (/^\d{2}-\d{2}-\d{4}$/.test(s)) return s;
                // yyyy-mm-dd
                var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (m) return m[3] + '-' + m[2] + '-' + m[1];
                return s;
            }

            var dtFilterNorm = normalizeFilterDate(dtFilter);
            var initFilterNorm = normalizeFilterDate(initFilter);

            if (dtFilterNorm) {
                dtOk = dtValue.indexOf(dtFilterNorm) === 0;
            }
            if (initFilterNorm) {
                initOk = initValue.indexOf(initFilterNorm) === 0;
            }
            return dtOk && initOk;
        };

        vm.clearFilters = function() {
            vm.filters = {
                status: '',
                payment_method: '',
                from_date: '',
                to_date: '',
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

