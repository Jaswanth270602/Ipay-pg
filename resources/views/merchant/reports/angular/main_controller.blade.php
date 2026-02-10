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
            app.controller('ReportsController', ['$http', function($http) {
        var vm = this;
        vm.filters = { from_date: '', to_date: '' };
        vm.reportData = null;
        vm.generating = false;
        vm.exporting = false;

        vm.generateReport = function() {
            vm.generating = true;
            var params = {
                from_date: vm.filters.from_date || '',
                to_date: vm.filters.to_date || ''
            };
            
            $http.get('/merchant/reports/data', { params: params }).then(function(response) {
                vm.reportData = response.data.data || null;
                vm.generating = false;
            }, function(error) {
                vm.generating = false;
                alert('Unable to generate report. Please try again.');
                console.error('Error generating report:', error);
            });
        };

        vm.exportReport = function() {
            vm.exporting = true;
            var params = new URLSearchParams();
            if (vm.filters.from_date) params.append('from_date', vm.filters.from_date);
            if (vm.filters.to_date) params.append('to_date', vm.filters.to_date);
            
            window.location.href = '/merchant/reports/export?' + params.toString();
            setTimeout(function() {
                vm.exporting = false;
            }, 1000);
        };
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

