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
            app.controller('WebhooksController', ['$http', function($http) {
        var vm = this;
        vm.webhooks = [];
        vm.stats = { total: 0, successful: 0, failed: 0, pending: 0 };
        vm.pagination = { current_page: 1, per_page: 50, total: 0, last_page: 1, from: 0, to: 0 };
        vm.loading = false;
        vm.saving = false;
        vm.testing = false;
        vm.webhookUrl = '';
        vm.webhookSecret = '';
        vm.selectedWebhook = null;

        vm.loadWebhooks = function() {
            vm.loading = true;
            $http.get('/merchant/webhooks/data', {
                params: { page: vm.pagination.current_page, per_page: vm.pagination.per_page }
            }).then(function(response) {
                vm.webhooks = response.data.data?.webhooks || [];
                vm.stats = response.data.data?.stats || vm.stats;
                vm.pagination = {
                    current_page: response.data.data?.pagination?.current_page || 1,
                    last_page: response.data.data?.pagination?.last_page || 1,
                    total: response.data.data?.pagination?.total || 0,
                    from: response.data.data?.pagination?.from || 0,
                    to: response.data.data?.pagination?.to || 0,
                    per_page: response.data.data?.pagination?.per_page || 50
                };
                vm.webhookUrl = response.data.data?.merchant?.webhook_url || '';
                vm.webhookSecret = response.data.data?.merchant?.webhook_secret || '';
                vm.loading = false;
            }, function(error) {
                vm.loading = false;
                alert('Failed to load webhooks');
                console.error('Error:', error);
            });
        };

        vm.updateWebhookUrl = function() {
            if (!vm.webhookUrl) {
                alert('Please enter a webhook URL');
                return;
            }

            vm.saving = true;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/webhooks/update-url', {
                webhook_url: vm.webhookUrl
            }, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function(response) {
                vm.saving = false;
                if (response.data.success) {
                    vm.webhookSecret = response.data.webhook_secret;
                    alert('Webhook URL updated successfully');
                }
            }, function(error) {
                vm.saving = false;
                alert('Failed to update webhook URL');
            });
        };

        vm.testWebhook = function() {
            if (!vm.webhookUrl) {
                alert('Please configure webhook URL first');
                return;
            }

            vm.testing = true;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/webhooks/test', {}, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function(response) {
                vm.testing = false;
                if (response.data.success) {
                    alert('Test webhook sent successfully');
                    vm.loadWebhooks();
                } else {
                    alert(response.data.message || 'Failed to send test webhook');
                }
            }, function(error) {
                vm.testing = false;
                alert('Failed to send test webhook');
            });
        };

        vm.retryWebhook = function(id) {
            if (!confirm('Retry sending this webhook?')) return;

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/webhooks/' + id + '/retry', {}, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function(response) {
                if (response.data.success) {
                    alert('Webhook retry scheduled');
                    vm.loadWebhooks();
                }
            }, function(error) {
                alert('Failed to retry webhook');
            });
        };

        vm.viewDetails = function(webhook) {
            vm.selectedWebhook = webhook;
            var modal = new bootstrap.Modal(document.getElementById('webhookDetailsModal'));
            modal.show();
        };

        vm.copySecret = function() {
            var textarea = document.createElement('textarea');
            textarea.value = vm.webhookSecret;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert('Secret copied to clipboard');
            } catch(e) {
                alert('Copy failed');
            }
            document.body.removeChild(textarea);
        };

        vm.loadPage = function(page) {
            if (page < 1 || page > vm.pagination.last_page) return;
            vm.pagination.current_page = page;
            vm.loadWebhooks();
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

        vm.loadWebhooks();
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

