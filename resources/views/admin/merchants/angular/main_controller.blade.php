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
            app.controller('AdminMerchantsController', ['$http', function($http) {
                var vm = this;
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
                vm.filters = { status: 'all', search: '' };
                vm.loading = false;

                vm.loadMerchants = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        search: vm.filters.search || ''
                    };
                    
                    $http.get('/admin/merchants/data', { params: params }).then(function(response) {
                        vm.merchants = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading merchants:', error);
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadMerchants();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadMerchants();
                };

                vm.clearFilters = function() {
                    vm.filters = { status: 'all', search: '' };
                    vm.applyFilters();
                };

                vm.getPageNumbers = function() {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                vm.viewMerchant = function(merchant) {
                    // TODO: Implement merchant detail view
                    alert('View merchant: ' + merchant.name);
                };

                vm.loadMerchants();
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

