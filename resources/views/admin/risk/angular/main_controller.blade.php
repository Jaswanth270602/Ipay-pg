@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') { setTimeout(registerController, 50); return; }
        try {
            var app = angular.module('ipayApp');
            app.controller('AdminRiskController', ['$http', function($http) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.stats = { total_rules: 0, total_events: 0, critical_alerts: 0, high_alerts: 0, fds_review: 0, fds_block: 0 };
                vm.rules = { data: [] };
                vm.events = { data: [] };
                vm.alerts = { data: [] };
                vm.fdsDecisions = { data: [] };
                vm.fdsEvents = { data: [] };
                vm.ruleSearch = '';
                vm.ruleFilters = { status: '', type: '' };
                vm.eventFilters = { merchant_id: '', severity: '', resolved: '' };
                vm.alertFilters = { merchant_id: '', status: '', severity: '' };
                vm.fdsDecisionFilters = { merchant_id: '', decision: '', transaction_id: '' };
                vm.fdsEventFilters = { fraud_transaction_id: '', rule_name: '', triggered: '' };
                vm.ruleForm = { name: '', type: 'velocity', rule_config_json: '{}', action: 'alert', status: 'active', priority: 0 };
                vm.alertForm = { merchant_id: '', transaction_id: '', alert_type: 'suspicious_pattern', severity: 'medium', description: '', risk_score: 50 };
                vm.ruleSubmitting = false;
                vm.ruleTypeTemplates = {
                    velocity: '{\n  "max_transactions": 10,\n  "time_window": "1h"\n}',
                    amount_limit: '{\n  "max_amount": 100000,\n  "currency": "INR"\n}',
                    geo_block: '{\n  "blocked_countries": ["KP", "IR"]\n}',
                    merchant_block: '{\n  "merchant_ids": [101, 205]\n}',
                    ip_block: '{\n  "blocked_ips": ["192.168.1.10"]\n}'
                };

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
                    if (!vm.ruleForm.name || !vm.ruleForm.name.trim()) {
                        if (typeof showToast === 'function') {
                            showToast('Rule name is required.', 'error');
                        } else {
                            alert('Rule name is required.');
                        }
                        return;
                    }

                    try {
                        vm.ruleForm.rule_config = JSON.parse(vm.ruleForm.rule_config_json || '{}');
                    } catch(e) {
                        if (typeof showToast === 'function') {
                            showToast('Invalid JSON in Rule Config.', 'error');
                        } else {
                            alert('Invalid JSON in Rule Config.');
                        }
                        return;
                    }

                    vm.ruleSubmitting = true;
                    $http.post('/admin/risk/rules', vm.ruleForm, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.ruleSubmitting = false;
                        vm.ruleForm = { name: '', type: 'velocity', rule_config_json: '{}', action: 'alert', status: 'active', priority: 0 };
                        bootstrap.Modal.getInstance(document.getElementById('ruleModal')).hide();
                        vm.loadRules();
                        vm.loadStats();
                        if (typeof showToast === 'function') {
                            showToast('Risk rule created successfully.', 'success');
                        }
                    }, function(err) {
                        vm.ruleSubmitting = false;
                        var msg = 'Failed to create rule';
                        if (err && err.data && err.data.errors) {
                            var firstKey = Object.keys(err.data.errors)[0];
                            if (firstKey && err.data.errors[firstKey] && err.data.errors[firstKey][0]) {
                                msg = err.data.errors[firstKey][0];
                            }
                        } else if (err && err.data && err.data.message) {
                            msg = err.data.message;
                        }
                        if (typeof showToast === 'function') {
                            showToast(msg, 'error');
                        } else {
                            alert(msg);
                        }
                        console.error(err);
                    });
                };

                vm.applyRuleTemplate = function() {
                    var t = vm.ruleTypeTemplates[vm.ruleForm.type] || '{}';
                    vm.ruleForm.rule_config_json = t;
                };

                vm.updateRule = function(r) {
                    $http.post('/admin/risk/rules/' + r.id, { status: r.status }, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                        vm.loadStats();
                    }, function() { alert('Failed to update rule'); });
                };

                vm.deleteRule = function(r) {
                    ipayConfirm('Delete this rule?', 'danger', {
                        okText: 'Delete',
                        cancelText: 'Cancel',
                        title: 'Delete rule'
                    }).then(function (ok) {
                        if (!ok) return;
                        $http.delete('/admin/risk/rules/' + r.id, { headers: { 'X-CSRF-TOKEN': csrf } }).then(function() {
                            vm.loadRules();
                            vm.loadStats();
                        }, function(err) {
                            alert('Failed to delete rule');
                            console.error(err);
                        });
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

                vm.loadFdsDecisions = function(page) {
                    var params = {
                        merchant_id: vm.fdsDecisionFilters.merchant_id || '',
                        decision: vm.fdsDecisionFilters.decision || '',
                        transaction_id: vm.fdsDecisionFilters.transaction_id || ''
                    };
                    if (page) params.page = page;
                    $http.get('/admin/risk/fds/decisions/data', { params: params }).then(function(resp) {
                        vm.fdsDecisions = resp.data.data;
                    });
                };

                vm.loadFdsEvents = function(page) {
                    var params = {
                        fraud_transaction_id: vm.fdsEventFilters.fraud_transaction_id || '',
                        rule_name: vm.fdsEventFilters.rule_name || '',
                        triggered: vm.fdsEventFilters.triggered
                    };
                    if (page) params.page = page;
                    $http.get('/admin/risk/fds/events/data', { params: params }).then(function(resp) {
                        vm.fdsEvents = resp.data.data;
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
                    vm.ruleForm = { name: '', type: 'velocity', rule_config_json: vm.ruleTypeTemplates.velocity, action: 'alert', status: 'active', priority: 0 };
                    vm.ruleSubmitting = false;
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

