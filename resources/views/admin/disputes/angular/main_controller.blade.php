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
            app.controller('AdminDisputesController', ['$http', '$scope', '$timeout', function($http, $scope, $timeout) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                
                // Initialize
                vm.activeTab = 'action_required'; // Default tab
                vm.disputes = [];
                vm.pagination = {
                    current_page: 1,
                    per_page: 20,
                    total: 0,
                    last_page: 1,
                    from: 0,
                    to: 0
                };
                vm.summary = {
                    due_today_count: 0,
                    due_today_amount: 0,
                    due_tomorrow_count: 0,
                    due_tomorrow_amount: 0,
                    insufficient_evidence_count: 0,
                    insufficient_evidence_amount: 0
                };
                vm.loading = false;
                vm.creating = false;
                vm.form = {
                    merchant_id: '',
                    transaction_id: '',
                    order_id: '',
                    reason: '',
                    amount: '',
                    currency: 'INR',
                    card_network: '',
                    internal_notes: ''
                };
                vm.filters = {
                    status: 'action_required',
                    date_range: '',
                    search: '',
                    merchant_id: '',
                    from_date: '',
                    to_date: ''
                };

                // Set active tab
                vm.setTab = function(tab) {
                    vm.activeTab = tab;
                    vm.pagination.current_page = 1;
                    
                    // Map tab to status filter
                    if (tab === 'action_required') {
                        vm.filters.status = 'action_required';
                    } else if (tab === 'under_review') {
                        vm.filters.status = 'under_review';
                    } else if (tab === 'closed') {
                        vm.filters.status = 'closed';
                    } else {
                        vm.filters.status = 'all';
                    }
                    
                    vm.loadDisputes();
                    
                    // Load summary for action_required tab
                    if (tab === 'action_required') {
                        vm.loadSummary();
                    }
                };

                // Apply date range filter
                vm.applyDateRange = function() {
                    var days = parseInt(vm.filters.date_range);
                    if (days) {
                        var toDate = new Date();
                        var fromDate = new Date();
                        fromDate.setDate(fromDate.getDate() - days);
                        
                        vm.filters.from_date = fromDate.toISOString().split('T')[0];
                        vm.filters.to_date = toDate.toISOString().split('T')[0];
                    } else {
                        vm.filters.from_date = '';
                        vm.filters.to_date = '';
                    }
                    vm.loadDisputes();
                };

                // Load disputes
                vm.loadDisputes = function(page) {
                    if (page) {
                        vm.pagination.current_page = page;
                    }
                    
                    vm.loading = true;
                    
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        search: vm.filters.search,
                        merchant_id: vm.filters.merchant_id
                    };
                    
                    if (vm.filters.from_date && vm.filters.to_date) {
                        params.from_date = vm.filters.from_date;
                        params.to_date = vm.filters.to_date;
                    }
                    
                    $http.get('/admin/disputes/data', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.disputes = response.data.data;
                            vm.pagination = response.data.pagination;
                        } else {
                            alert('Failed to load disputes: ' + (response.data.message || 'Unknown error'));
                        }
                        vm.loading = false;
                    }, function(error) {
                        console.error('Error loading disputes:', error);
                        alert('Failed to load disputes');
                        vm.loading = false;
                    });
                };

                // Load summary
                vm.loadSummary = function() {
                    var params = {};
                    if (vm.filters.merchant_id) {
                        params.merchant_id = vm.filters.merchant_id;
                    }
                    
                    $http.get('/admin/disputes/summary', {
                        params: params,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            vm.summary = response.data.data;
                        }
                    }, function(error) {
                        console.error('Error loading summary:', error);
                    });
                };

                // View dispute detail
                vm.viewDispute = function(disputeId) {
                    window.location.href = '/admin/disputes/' + disputeId;
                };

                // Clear filters
                vm.clearFilters = function() {
                    vm.filters = {
                        status: vm.activeTab === 'action_required' ? 'action_required' : (vm.activeTab === 'under_review' ? 'under_review' : (vm.activeTab === 'closed' ? 'closed' : 'all')),
                        date_range: '',
                        search: '',
                        merchant_id: '',
                        from_date: '',
                        to_date: ''
                    };
                    vm.pagination.current_page = 1;
                    vm.loadDisputes();
                    if (vm.activeTab === 'action_required') {
                        vm.loadSummary();
                    }
                };

                vm.createDispute = function() {
                    if (!vm.form.merchant_id || !vm.form.reason || !vm.form.amount || vm.form.amount <= 0) {
                        alert('Please fill merchant, reason, and amount.');
                        return;
                    }

                    vm.creating = true;
                    $http.post('/admin/disputes', vm.form, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.creating = false;
                        if (response.data && response.data.success) {
                            vm.form = {
                                merchant_id: '',
                                transaction_id: '',
                                order_id: '',
                                reason: '',
                                amount: '',
                                currency: 'INR',
                                card_network: '',
                                internal_notes: ''
                            };
                            var modalEl = document.getElementById('adminNewDisputeModal');
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            vm.loadDisputes();
                            vm.loadSummary();
                            alert('Dispute created successfully.');
                        } else {
                            alert(response.data.message || 'Failed to create dispute.');
                        }
                    }, function(error) {
                        vm.creating = false;
                        var msg = 'Failed to create dispute';
                        if (error.data && error.data.message) {
                            msg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            msg = Object.values(error.data.errors).flat().join('\n');
                        }
                        alert(msg);
                    });
                };

                // Export CSV
                vm.exportCSV = function() {
                    var params = {
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        search: vm.filters.search,
                        merchant_id: vm.filters.merchant_id
                    };
                    
                    if (vm.filters.from_date && vm.filters.to_date) {
                        params.from_date = vm.filters.from_date;
                        params.to_date = vm.filters.to_date;
                    }
                    
                    var queryString = Object.keys(params)
                        .filter(key => params[key])
                        .map(key => key + '=' + encodeURIComponent(params[key]))
                        .join('&');
                    
                    window.location.href = '/admin/disputes/export/csv?' + queryString;
                };

                // Get page numbers for pagination
                vm.getPageNumbers = function() {
                    var pages = [];
                    var current = vm.pagination.current_page;
                    var last = vm.pagination.last_page;
                    var start = Math.max(1, current - 2);
                    var end = Math.min(last, current + 2);
                    
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    
                    return pages;
                };

                // Initialize
                vm.loadDisputes();
                vm.loadSummary();
            }]);
        } catch(e) {
            console.error('Error registering AdminDisputesController:', e);
            setTimeout(registerController, 100);
        }
    }
    registerController();
})();
</script>
@endpush
