<script>
angular.module('badlicashApp', [])
    .controller('TransactionsController', ['$http', '$timeout', function($http, $timeout){
        var vm = this;
        vm.transactions = [];
        vm.loading = false;
        vm.perPage = 10;
        vm.pagination = { current_page:1, last_page:1, total:0, from:0, to:0 };
        vm.filters = { status:'', payment_method:'', from_date:'', to_date:'', search:'' };

        vm.load = function(){
            vm.loading = true;
            var params = {
                page: vm.pagination.current_page,
                per_page: vm.perPage,
                status: vm.filters.status,
                payment_method: vm.filters.payment_method,
                from_date: vm.filters.from_date,
                to_date: vm.filters.to_date,
                search: vm.filters.search
            };
            $http.get('/merchant/transactions/data', { params: params }).then(function(res){
                vm.transactions = res.data.data;
                vm.pagination.current_page = res.data.current_page;
                vm.pagination.last_page = res.data.last_page;
                vm.pagination.total = res.data.total;
                vm.pagination.from = res.data.from;
                vm.pagination.to = res.data.to;
                vm.loading = false;
                renderPaginator();
            }, function(){ vm.loading = false; alert('Unable to load transactions'); });
        };

        var t; vm.applyFilters = function(){ if(t) $timeout.cancel(t); t = $timeout(function(){ vm.pagination.current_page=1; vm.load(); },300); };

        function renderPaginator(){
            var el = document.getElementById('paginator-container'); if(!el) return;
            if(vm.pagination.last_page <= 1) { el.innerHTML = ''; return; }
            var html = '<div class="d-flex justify-content-between align-items-center mt-4">'+
                '<div class="text-muted small">Showing '+(vm.pagination.from||0)+' to '+(vm.pagination.to||0)+' of '+(vm.pagination.total||0)+' results</div>'+
                '<div class="pagination">';
            if(vm.pagination.current_page > 1) {
                html += '<a href="#" class="page-link" ng-click="tc.loadPage('+(vm.pagination.current_page-1)+')">Previous</a>';
            }
            var start = Math.max(1, vm.pagination.current_page-2), end = Math.min(vm.pagination.last_page, vm.pagination.current_page+2);
            for(var p=start; p<=end; p++){ 
                html += '<a href="#" class="page-link'+(p===vm.pagination.current_page?' active':'')+'" ng-click="tc.loadPage('+p+')">'+p+'</a>'; 
            }
            if(vm.pagination.current_page < vm.pagination.last_page) {
                html += '<a href="#" class="page-link" ng-click="tc.loadPage('+(vm.pagination.current_page+1)+')">Next</a>';
            }
            html += '</div></div>';
            el.innerHTML = html;
        }

        vm.loadPage = function(page) {
            if(page < 1 || page > vm.pagination.last_page) return;
            vm.pagination.current_page = page;
            vm.load();
        };

        vm.load();
    }]);
</script>

