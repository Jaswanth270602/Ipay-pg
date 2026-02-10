@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') { setTimeout(registerController, 50); return; }
        try {
            var app = angular.module('badlicashApp');
            app.controller('AdminRiskController', ['$http', function($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.stats = { total_rules: 0, total_events: 0, critical_alerts: 0, high_alerts: 0 };
                vm.rules = { data: [] };
                vm.events = { data: [] };
                vm.alerts = { data: [] };
                vm.ruleSearch = '';
                vm.ruleFilters = { status: '', type: '' };
                vm.eventFilters = { merchant_id: '', severity: '', resolved: '' };
                vm.alertFilters = { merchant_id: '', status: '', severity: '' };
                vm.ruleForm = { name: '', type: 'velocity', rule_config_json: '{}', action: 'alert', status: 'active', priority: 0 };
                vm.alertForm = { merchant_id: '', transaction_id: '', alert_type: 'suspicious_pattern', severity: 'medium', description: '', risk_score: 50 };

                vm.loadStats = function() {
                    $http.get('/admin/risk/stats').then(function(resp) {
                        vm.stats = resp.data.data || vm.stats;
                    });
                };

                vm.loadRules = function(page) {
                    var params = { search: vm.ruleSearch || '', status: vm.ruleFilters.status || '', type: vm.ruleFilters.type || '' };
                    if (page) params.page = page;
                    $http.get('/admin/risk/rules/data', { params: params }).then(function(resp) {
                        vm.rules = resp.data.data;
                    });
                };

                vm.createRule = function() {
                    try {
                        vm.ruleForm.rule_config = JSON.parse(vm.ruleForm.rule_config_json || '{}');
                    } catch(e) {
                        alert('Invalid JSON in rule config');
                        return;
                    }
                    $http.post('/admin/risk/rules', vm.ruleForm, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.ruleForm = { name: '', type: 'velocity', rule_config_json: '{}', action: 'alert', status: 'active', priority: 0 };
                        bootstrap.Modal.getInstance(document.getElementById('ruleModal')).hide();
                        vm.loadRules();
                        vm.loadStats();
                    }, function(err) {
                        alert('Failed to create rule');
                        console.error(err);
                    });
                };

                vm.updateRule = function(r) {
                    $http.post('/admin/risk/rules/' + r.id, { status: r.status }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.loadStats();
                    }, function() { alert('Failed to update rule'); });
                };

                vm.deleteRule = function(r) {
                    if (!confirm('Delete this rule?')) return;
                    $http.delete('/admin/risk/rules/' + r.id, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.loadRules();
                        vm.loadStats();
                    }, function(err) { 
                        alert('Failed to delete rule');
                        console.error(err);
                    });
                };

                vm.loadEvents = function(page) {
                    var params = { merchant_id: vm.eventFilters.merchant_id || '', severity: vm.eventFilters.severity || '', resolved: vm.eventFilters.resolved || '' };
                    if (page) params.page = page;
                    $http.get('/admin/risk/events/data', { params: params }).then(function(resp) {
                        vm.events = resp.data.data;
                    });
                };

                vm.resolveEvent = function(e) {
                    $http.post('/admin/risk/events/' + e.id + '/resolve', { resolved: true }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.loadEvents();
                        vm.loadStats();
                    }, function() { alert('Failed to resolve event'); });
                };

                vm.loadAlerts = function(page) {
                    var params = { merchant_id: vm.alertFilters.merchant_id || '', status: vm.alertFilters.status || '', severity: vm.alertFilters.severity || '', alert_type: vm.alertFilters.alert_type || '' };
                    if (page) params.page = page;
                    $http.get('/admin/risk/alerts/data', { params: params }).then(function(resp) {
                        vm.alerts = resp.data.data;
                    });
                };

                vm.createAlert = function() {
                    $http.post('/admin/risk/alerts', vm.alertForm, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.alertForm = { merchant_id: '', transaction_id: '', alert_type: 'suspicious_pattern', severity: 'medium', description: '', risk_score: 50 };
                        bootstrap.Modal.getInstance(document.getElementById('alertModal')).hide();
                        vm.loadAlerts();
                        vm.loadStats();
                    }, function(err) {
                        alert('Failed to create alert');
                        console.error(err);
                    });
                };

                vm.updateAlert = function(a) {
                    $http.post('/admin/risk/alerts/' + a.id, { status: a.status }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.loadStats();
                    }, function() { alert('Failed to update alert'); });
                };

                vm.viewAlert = function(a) {
                    alert('Description: ' + (a.description || 'N/A') + '\nRisk Score: ' + a.risk_score);
                };

                vm.openRuleModal = function() {
                    new bootstrap.Modal(document.getElementById('ruleModal')).show();
                };
                vm.openAlertModal = function() {
                    new bootstrap.Modal(document.getElementById('alertModal')).show();
                };

                vm.loadStats();
                vm.loadRules();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') { registerController(); } else { setTimeout(registerController, 50); }
})();
</script>
@endpush

