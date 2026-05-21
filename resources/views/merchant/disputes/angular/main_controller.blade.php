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
            app.controller('MerchantDisputesController', ['$http', function($http) {
                var vm = this;
                vm.filters = { status: '' };
                vm.items = { data: [] };

                vm.load = function(page) {
                    var params = { status: vm.filters.status || '' };
                    if (page) params.page = page;
                    $http.get('/merchant/disputes/data', { params: params }).then(function(resp) {
                        vm.items = resp.data.data;
                    });
                };

                vm.load();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        setTimeout(registerController, 50);
    }
})();
</script>
@endpush
