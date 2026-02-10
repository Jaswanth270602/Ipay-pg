@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') { setTimeout(registerController, 50); return; }
        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminSubscriptionsController', ['$http', function($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.planSearch = '';
                vm.plans = { data: [] };
                vm.subscriptions = { data: [] };
                vm.filters = { merchant_id: '', status: '' };
                vm.planForm = { name: '', code: '', amount: '', currency: 'INR', interval: 'month', interval_count: 1, trial_days: 0, status: 'active' };
                vm.subscriptionForm = { merchant_id: '', plan_id: '' };
                vm.allPlans = [];

                vm.loadPlans = function(page) {
                    var params = { search: vm.planSearch || '' };
                    if (page) params.page = page;
                    $http.get('/admin/plans/data', { params: params }).then(function(resp) {
                        vm.plans = resp.data.data;
                        vm.allPlans = (vm.plans.data || []);
                    });
                };

                vm.createPlan = function() {
                    $http.post('/admin/plans', vm.planForm, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.planForm = { name: '', code: '', amount: '', currency: 'INR', interval: 'month', interval_count: 1, trial_days: 0, status: 'active' };
                        bootstrap.Modal.getInstance(document.getElementById('planModal')).hide();
                        vm.loadPlans();
                    }, function(err) {
                        alert('Failed to create plan');
                        console.error(err);
                    });
                };

                vm.updatePlan = function(p) {
                    $http.post('/admin/plans/' + p.id, { status: p.status }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {}, function() { alert('Failed to update plan'); });
                };

                vm.loadSubscriptions = function(page) {
                    var params = { merchant_id: vm.filters.merchant_id || '', status: vm.filters.status || '' };
                    if (page) params.page = page;
                    $http.get('/admin/subscriptions/data', { params: params }).then(function(resp) {
                        vm.subscriptions = resp.data.data;
                    });
                };

                vm.createSubscription = function() {
                    $http.post('/admin/subscriptions', vm.subscriptionForm, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.subscriptionForm = { merchant_id: '', plan_id: '' };
                        bootstrap.Modal.getInstance(document.getElementById('subscriptionModal')).hide();
                        vm.loadSubscriptions();
                    }, function(err) {
                        alert('Failed to create subscription');
                        console.error(err);
                    });
                };

                vm.updateSubscription = function(s) {
                    $http.post('/admin/subscriptions/' + s.id, { status: s.status, cancel_at_period_end: s.cancel_at_period_end }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {}, function() { alert('Failed to update subscription'); });
                };

                vm.openPlanModal = function() {
                    new bootstrap.Modal(document.getElementById('planModal')).show();
                };
                vm.openSubscriptionModal = function() {
                    if (!vm.allPlans.length) vm.loadPlans();
                    new bootstrap.Modal(document.getElementById('subscriptionModal')).show();
                };

                vm.loadPlans();
                vm.loadSubscriptions();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') { registerController(); } else { setTimeout(registerController, 50); }
})();
</script>
@endpush


