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
            app.controller('RefundsController', ['$http', '$timeout', function($http, $timeout) {
        var vm = this;
        vm.refunds = [];
        vm.loading = false;
        vm.creating = false;
        vm.perPage = 10;
        vm.pagination = { current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 10 };
        vm.filters = { status: '', from_date: '', to_date: '', search: '' };
        vm.newRefund = { transaction_id: '', amount: '', reason: '' };
        vm.selectedRefund = null;

        vm.loadRefunds = function() {
            vm.loading = true;
            var params = {
                page: vm.pagination.current_page,
                per_page: vm.perPage,
                status: vm.filters.status || '',
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || '',
                search: vm.filters.search || ''
            };
            
            $http.get('/merchant/refunds/data', { params: params }).then(function(response) {
                vm.refunds = response.data.data || [];
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
                    showToast('Unable to load refunds. Please try again.', 'error');
                } else {
                    alert('Unable to load refunds. Please try again.');
                }
                console.error('Error loading refunds:', error);
            });
        };

        vm.createRefund = function() {
            // Prevent double submission
            if (vm.creating) {
                return;
            }

            // Validate required fields
            if (!vm.newRefund.transaction_id || !vm.newRefund.transaction_id.trim()) {
                if (typeof showToast === 'function') {
                    showToast('Please enter a Transaction ID', 'error');
                } else {
                    alert('Please enter a Transaction ID');
                }
                return;
            }

            if (!vm.newRefund.amount || parseFloat(vm.newRefund.amount) <= 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter a valid amount greater than 0', 'error');
                } else {
                    alert('Please enter a valid amount greater than 0');
                }
                return;
            }

            vm.creating = true;
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (!csrf) {
                vm.creating = false;
                if (typeof showToast === 'function') {
                    showToast('CSRF token not found. Please refresh the page.', 'error');
                } else {
                    alert('CSRF token not found. Please refresh the page.');
                }
                return;
            }

            var requestData = {
                transaction_id: vm.newRefund.transaction_id.trim(),
                amount: parseFloat(vm.newRefund.amount),
                reason: (vm.newRefund.reason || '').trim()
            };

            console.log('Creating refund with data:', requestData);

            $http({
                method: 'POST',
                url: '/merchant/refunds',
                data: requestData,
                headers: {
                    'X-CSRF-TOKEN': csrf.content,
                    'Content-Type': 'application/json'
                }
            }).then(function(response) {
                console.log('Refund creation response:', response);
                vm.creating = false;
                
                if (response.data && response.data.success) {
                    // Close modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('createRefundModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reset form
                    vm.newRefund = { transaction_id: '', amount: '', reason: '' };
                    
                    // Reload refunds list
                    vm.loadRefunds();
                    
                    // Show success message
                    var refundData = response.data.data;
                    var successMsg = 'Refund Created Successfully! Refund ID: ' + refundData.refund_id + ', Amount: ' + refundData.currency + ' ' + parseFloat(refundData.amount).toFixed(2);
                    if (typeof showToast === 'function') {
                        showToast(successMsg, 'success');
                    } else {
                        var alertMsg = 'Refund Created Successfully!\n\n';
                        alertMsg += 'Refund ID: ' + refundData.refund_id + '\n';
                        alertMsg += 'Amount: ' + refundData.currency + ' ' + parseFloat(refundData.amount).toFixed(2) + '\n';
                        alertMsg += 'Status: ' + refundData.status.toUpperCase() + '\n';
                        alertMsg += (refundData.is_partial ? 'Type: Partial Refund' : 'Type: Full Refund');
                        alert(alertMsg);
                    }
                } else {
                    var errorMsg = 'Failed to create refund: ' + (response.data && response.data.message ? response.data.message : 'Unknown error');
                    if (typeof showToast === 'function') {
                        showToast(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                }
            }, function(error) {
                console.error('Refund creation error:', error);
                vm.creating = false;
                
                var errorMsg = 'Failed to create refund.\n\n';
                if (error.data) {
                    if (error.data.message) {
                        errorMsg += error.data.message;
                    } else if (error.data.errors) {
                        var errors = error.data.errors;
                        var firstKey = Object.keys(errors)[0];
                        if (firstKey && errors[firstKey]) {
                            errorMsg += Array.isArray(errors[firstKey]) ? errors[firstKey][0] : String(errors[firstKey]);
                        }
                    }
                } else if (error.status === 404) {
                    errorMsg += 'Transaction not found. Please check the Transaction ID and ensure you are in the correct mode (TEST/LIVE).';
                } else if (error.status === 400) {
                    errorMsg += 'Invalid request. Please check your input.';
                } else if (error.status === 403) {
                    errorMsg += 'Live mode is not configured. Please configure your live API credentials.';
                } else if (error.status === 0 || error.status === -1) {
                    errorMsg += 'Network error. Please check your connection and try again.';
                } else {
                    errorMsg += 'An unexpected error occurred. Please try again.';
                }
                if (typeof showToast === 'function') {
                    showToast(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            });
        };

        var filterTimeout;
        vm.applyFilters = function() {
            if (filterTimeout) $timeout.cancel(filterTimeout);
            filterTimeout = $timeout(function() {
                vm.pagination.current_page = 1;
                vm.loadRefunds();
            }, 300);
        };

        vm.clearFilters = function() {
            vm.filters = { status: '', from_date: '', to_date: '', search: '' };
            vm.pagination.current_page = 1;
            vm.loadRefunds();
        };

        vm.loadPage = function(page) {
            if (page < 1 || page > vm.pagination.last_page) return;
            vm.pagination.current_page = page;
            vm.loadRefunds();
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

        vm.viewRefund = function(refund) {
            vm.selectedRefund = refund;
            var modal = new bootstrap.Modal(document.getElementById('refundDetailsModal'));
            modal.show();
        };

        vm.exportCSV = function() {
            var params = {
                status: vm.filters.status || '',
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || '',
                search: vm.filters.search || ''
            };

            var queryString = Object.keys(params).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }).join('&');

            window.location.href = '/merchant/refunds/export?' + queryString;
        };

        vm.loadRefunds();
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

