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
                // Matches TransactionsController JSON shape
                vm.transactions = res.data.data;
                vm.pagination.current_page = res.data.current_page;
                vm.pagination.last_page = res.data.last_page;
                vm.pagination.total = res.data.total;
                vm.pagination.from = res.data.from;
                vm.pagination.to = res.data.to;
                vm.loading = false;
                renderPaginator();
            }, function(){
                vm.loading = false;
                alert('Unable to load transactions');
            });
        };

        var t;
        vm.applyFilters = function(){
            if(t) $timeout.cancel(t);
            t = $timeout(function(){ vm.pagination.current_page = 1; vm.load(); }, 300);
        };

        function renderPaginator(){
            var el = document.getElementById('paginator-container');
            if(!el) return;
            var html = ''+
                '<div class="d-flex justify-content-between align-items-center mt-3">'+
                '<div class="text-muted" style="font-size:14px;">Showing '+(vm.pagination.from||0)+' to '+(vm.pagination.to||0)+' of '+(vm.pagination.total||0)+' results</div>'+
                '<nav><ul class="pagination mb-0">'+
                '<li class="page-item '+(vm.pagination.current_page===1?'disabled':'')+'"><a class="page-link" href="#" data-page="'+(vm.pagination.current_page-1)+'">Previous</a></li>';
            var start = Math.max(1, vm.pagination.current_page-2);
            var end = Math.min(vm.pagination.last_page, vm.pagination.current_page+2);
            for(var p=start; p<=end; p++){
                html += '<li class="page-item '+(p===vm.pagination.current_page?'active':'')+'"><a class="page-link" href="#" data-page="'+p+'">'+p+'</a></li>';
            }
            html += '<li class="page-item '+(vm.pagination.current_page===vm.pagination.last_page?'disabled':'')+'"><a class="page-link" href="#" data-page="'+(vm.pagination.current_page+1)+'">Next</a></li>'+
                '</ul></nav></div>';
            el.innerHTML = html;
            Array.prototype.slice.call(el.querySelectorAll('a.page-link')).forEach(function(a){
                a.onclick = function(e){ e.preventDefault(); var pg = parseInt(this.getAttribute('data-page')); if(!isNaN(pg)) { vm.pagination.current_page = pg; vm.load(); } };
            });
        }

        vm.load();
    }]);


