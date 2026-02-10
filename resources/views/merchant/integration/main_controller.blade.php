<script>
(function() {
    'use strict';
    angular.module('badlicashApp').controller('IntegrationController', ['$http', function($http) {
        var vm = this;
        vm.apiKeys = [];
        vm.selectedApiKey = '';
        vm.code = '';
        vm.loading = false;

        vm.loadApiKeys = function() {
            $http.get('/merchant/api-keys').then(function(response) {
                vm.apiKeys = (response.data.data || []).filter(function(key) {
                    return key.status === 'active';
                });
                if (vm.apiKeys.length > 0) {
                    vm.selectedApiKey = vm.apiKeys[0].id;
                }
            });
        };

        vm.getCode = function(type) {
            if (!vm.selectedApiKey) {
                alert('Please select an API key first');
                return;
            }

            vm.loading = true;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            $http.post('/merchant/integration/code', {
                type: type,
                api_key_id: vm.selectedApiKey
            }, {
                headers: {'X-CSRF-TOKEN': csrf}
            }).then(function(response) {
                vm.loading = false;
                if (response.data.success) {
                    vm.code = response.data.code;
                } else {
                    alert('Failed to get integration code');
                }
            }, function() {
                vm.loading = false;
                alert('Failed to get integration code');
            });
        };

        vm.copyCode = function() {
            var textarea = document.createElement('textarea');
            textarea.value = vm.code;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert('Code copied to clipboard');
            } catch(e) {
                alert('Copy failed');
            }
            document.body.removeChild(textarea);
        };

        vm.onApiKeyChange = function() {
            vm.code = '';
        };

        vm.loadApiKeys();
    }]);
})();
</script>

