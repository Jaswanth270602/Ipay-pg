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
            app.controller('ApiKeysController', ['$http', '$window', function($http, $window) {
        var vm = this;
        vm.apiKeys = [];
        vm.pagination = { current_page: 1, per_page: 20, total: 0, last_page: 1, from: 0, to: 0 };
        vm.loading = false;
        vm.creating = false;
        vm.newKey = { name: '', mode: 'test' };
        vm.currentSecret = '';

        vm.loadApiKeys = function() {
            vm.loading = true;
            $http.get('/merchant/api-keys/data', {
                params: { page: vm.pagination.current_page, per_page: vm.pagination.per_page }
            }).then(function(response) {
                vm.apiKeys = response.data.data || [];
                vm.pagination = {
                    current_page: response.data.pagination.current_page,
                    last_page: response.data.pagination.last_page,
                    total: response.data.pagination.total,
                    from: response.data.pagination.from || 0,
                    to: response.data.pagination.to || 0,
                    per_page: response.data.pagination.per_page
                };
                vm.loading = false;
            }, function(error) {
                vm.loading = false;
                vm.showToast('Failed to load API keys', 'error');
            });
        };

        vm.createApiKey = function() {
            if (!vm.newKey.name || !vm.newKey.mode) {
                vm.showToast('Please fill in all fields', 'error');
                return;
            }

            vm.creating = true;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/api-keys', vm.newKey, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function(response) {
                vm.creating = false;
                var modal = bootstrap.Modal.getInstance(document.getElementById('createApiKeyModal'));
                if (modal) modal.hide();

                if (response.data.success && response.data.api_key) {
                    vm.currentSecret = response.data.api_key.secret;
                    var secretModal = new bootstrap.Modal(document.getElementById('secretModal'));
                    secretModal.show();
                    vm.newKey = { name: '', mode: 'test' };
                    vm.loadApiKeys();
                }
            }, function(error) {
                vm.creating = false;
                vm.showToast(error.data?.message || 'Failed to create API key', 'error');
            });
        };

        vm.revokeKey = function(id) {
            if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
                return;
            }

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.delete('/merchant/api-keys/' + id, {
                headers: { 'X-CSRF-TOKEN': csrf }
            }).then(function() {
                vm.showToast('API key revoked successfully', 'success');
                vm.loadApiKeys();
            }, function() {
                vm.showToast('Failed to revoke API key', 'error');
            });
        };

        vm.showSecret = function(key) {
            vm.showToast('Secret key is only shown once when created', 'info');
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
                vm.showToast('Copied to clipboard', 'success');
            } catch(e) {
                vm.showToast('Copy failed', 'error');
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

        vm.getPaginationPages = function() {
            var pages = [];
            var start = Math.max(1, vm.pagination.current_page - 2);
            var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
            for (var i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        };

        vm.showToast = function(msg, type) {
            alert(msg);
        };

        vm.loadApiKeys();
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

