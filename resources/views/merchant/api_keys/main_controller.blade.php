<script>
(function() {
    'use strict';
    angular.module('badlicashApp').controller('ApiKeysController', ['$http', '$window', function($http, $window) {
        var vm = this;
        vm.apiKeys = [];
        vm.pagination = {current_page: 1, per_page: 20, total: 0, last_page: 1};
        vm.loading = false;
        vm.creating = false;
        vm.newKey = {name: '', mode: 'test'};
        vm.currentSecret = '';

        vm.loadApiKeys = function() {
            vm.loading = true;
            $http.get('/merchant/api-keys', {
                params: {page: vm.pagination.current_page, per_page: vm.pagination.per_page}
            }).then(function(response) {
                vm.apiKeys = response.data.data || [];
                vm.pagination = response.data.pagination || vm.pagination;
                vm.loading = false;
            }, function() {
                vm.loading = false;
                showToast('Failed to load API keys', 'error');
            });
        };

        vm.createApiKey = function() {
            if (!vm.newKey.name || !vm.newKey.mode) {
                showToast('Please fill in all fields', 'error');
                return;
            }

            vm.creating = true;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/api-keys', vm.newKey, {
                headers: {'X-CSRF-TOKEN': csrf}
            }).then(function(response) {
                vm.creating = false;
                var modal = bootstrap.Modal.getInstance(document.getElementById('createApiKeyModal'));
                if (modal) modal.hide();

                if (response.data.success && response.data.api_key) {
                    vm.currentSecret = response.data.api_key.secret;
                    var secretModal = new bootstrap.Modal(document.getElementById('secretModal'));
                    secretModal.show();
                    vm.newKey = {name: '', mode: 'test'};
                    vm.loadApiKeys();
                }
            }, function(error) {
                vm.creating = false;
                showToast(error.data?.message || 'Failed to create API key', 'error');
            });
        };

        vm.revokeKey = function(id) {
            if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
                return;
            }

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.delete('/merchant/api-keys/' + id, {
                headers: {'X-CSRF-TOKEN': csrf}
            }).then(function() {
                showToast('API key revoked successfully', 'success');
                vm.loadApiKeys();
            }, function() {
                showToast('Failed to revoke API key', 'error');
            });
        };

        vm.showSecret = function(key) {
            // Note: Secrets are not stored in the frontend for security
            showToast('Secret key is only shown once when created', 'info');
        };

        vm.copyToClipboard = function(text) {
            var t = document.createElement('textarea');
            t.value = text;
            t.style.position = 'fixed';
            t.style.opacity = '0';
            document.body.appendChild(t);
            t.select();
            try {
                document.execCommand('copy');
                showToast('Copied to clipboard', 'success');
            } catch(e) {
                showToast('Copy failed', 'error');
            }
            document.body.removeChild(t);
        };

        vm.copySecret = function() {
            vm.copyToClipboard(vm.currentSecret);
        };

        vm.loadPage = function(page) {
            if (page < 1 || page > vm.pagination.last_page) return;
            vm.pagination.current_page = page;
            vm.loadApiKeys();
        };

        vm.getPages = function() {
            var pages = [];
            var start = Math.max(1, vm.pagination.current_page - 2);
            var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
            for (var i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        };

        function showToast(msg, type) {
            var toast = document.getElementById('toast');
            if (toast) {
                var toastInstance = new bootstrap.Toast(toast);
                toastInstance.show();
            } else {
                alert(msg);
            }
        }

        vm.loadApiKeys();
    }]);
})();
</script>

