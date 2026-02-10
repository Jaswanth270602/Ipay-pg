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
            app.controller('AdminReportsController', ['$http', function($http) {
                var vm = this;
                vm.filters = { 
                    merchant_id: '', 
                    from_date: '', 
                    to_date: '',
                    status: 'all',
                    payment_method: 'all'
                };
                vm.reportData = null;
                vm.generating = false;
                vm.exporting = false;
                vm.loading = false;
                vm.pagination = { per_page: 25 };

                vm.generateReport = function() {
                    // Validate dates
                    if (vm.filters.from_date && vm.filters.to_date) {
                        if (new Date(vm.filters.from_date) > new Date(vm.filters.to_date)) {
                            alert('From date must be before or equal to To date');
                            return;
                        }
                    }

                    vm.generating = true;
                    vm.loading = true;
                    var params = {
                        merchant_id: vm.filters.merchant_id || '',
                        from_date: vm.filters.from_date || '',
                        to_date: vm.filters.to_date || '',
                        status: vm.filters.status || 'all',
                        payment_method: vm.filters.payment_method || 'all',
                        page: 1,
                        per_page: vm.pagination.per_page
                    };

                    $http.get('/admin/reports/data', { params: params }).then(function(response) {
                        if (response.data.success) {
                            vm.reportData = response.data.data || null;
                            // Store the filters used for this report so export can use them
                            if (vm.reportData) {
                                vm.reportData._filters = {
                                    merchant_id: params.merchant_id,
                                    from_date: params.from_date,
                                    to_date: params.to_date,
                                    status: params.status,
                                    payment_method: params.payment_method
                                };
                            }
                        } else {
                            alert(response.data.message || 'Unable to generate report. Please try again.');
                        }
                        vm.generating = false;
                        vm.loading = false;
                    }, function(error) {
                        vm.generating = false;
                        vm.loading = false;
                        var errorMsg = error.data && error.data.message ? error.data.message : 'Unable to generate report. Please try again.';
                        alert(errorMsg);
                        console.error('Error generating admin report:', error);
                    });
                };

                vm.exportReport = function() {
                    if (!vm.reportData) {
                        alert('Please generate a report first');
                        return;
                    }

                    vm.exporting = true;
                    var params = new URLSearchParams();
                    
                    // Helper function to format date as YYYY-MM-DD - FIXED to handle all cases
                    var formatDate = function(dateValue) {
                        if (!dateValue) return '';
                        
                        // Convert to string if not already
                        var dateString = String(dateValue);
                        
                        // If it's already in YYYY-MM-DD format, return as is
                        if (dateString.match && dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            return dateString;
                        }
                        
                        // Try to parse as date
                        var date = new Date(dateString);
                        if (isNaN(date.getTime())) {
                            // If parsing fails, try to extract date parts from DD-MM-YYYY format
                            var parts = dateString.split(/[-\/]/);
                            if (parts.length === 3) {
                                // Assume DD-MM-YYYY or DD/MM/YYYY
                                return parts[2] + '-' + parts[1] + '-' + parts[0];
                            }
                            return '';
                        }
                        
                        var year = date.getFullYear();
                        var month = String(date.getMonth() + 1).padStart(2, '0');
                        var day = String(date.getDate()).padStart(2, '0');
                        return year + '-' + month + '-' + day;
                    };
                    
                    // CRITICAL: Use the filters from the report data (stored when report was generated)
                    // This ensures we export the exact same data that was shown in the report
                    var filtersToUse = vm.reportData._filters || vm.filters;
                    
                    // If still no filters, alert user
                    if (!filtersToUse || (!filtersToUse.from_date && !filtersToUse.to_date)) {
                        console.warn('No filters found! Using current filters:', vm.filters);
                        filtersToUse = vm.filters;
                    }
                    
                    // Debug: Log what filters we're using
                    console.log('Export filters to use:', filtersToUse);
                    console.log('Report data filters:', vm.reportData._filters);
                    console.log('Current filters:', vm.filters);
                    
                    // Always include all filter parameters - MUST include dates even if empty
                    if (filtersToUse.merchant_id) {
                        params.append('merchant_id', filtersToUse.merchant_id);
                    }
                    
                    var fromDate = formatDate(filtersToUse.from_date);
                    var toDate = formatDate(filtersToUse.to_date);
                    
                    // CRITICAL: Always append dates if they exist
                    if (fromDate) {
                        params.append('from_date', fromDate);
                    }
                    if (toDate) {
                        params.append('to_date', toDate);
                    }
                    if (filtersToUse.status && filtersToUse.status !== 'all') {
                        params.append('status', filtersToUse.status);
                    }
                    if (filtersToUse.payment_method && filtersToUse.payment_method !== 'all') {
                        params.append('payment_method', filtersToUse.payment_method);
                    }

                    // Build URL with ALL parameters
                    var exportUrl = '/admin/reports/export?' + params.toString();
                    
                    // Add timestamp to prevent caching
                    exportUrl += '&_t=' + Date.now();
                    
                    // Debug: Log the final URL
                    console.log('Export URL:', exportUrl);
                    
                    // Use direct link click for immediate download (most reliable)
                    var link = document.createElement('a');
                    link.href = exportUrl;
                    link.download = 'transactions_report.csv';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Reset exporting state quickly
                    setTimeout(function() {
                        vm.exporting = false;
                    }, 1000);
                };

                vm.clearFilters = function() {
                    vm.filters = { 
                        merchant_id: '', 
                        from_date: '', 
                        to_date: '',
                        status: 'all',
                        payment_method: 'all'
                    };
                    vm.reportData = null;
                };

                vm.changePage = function(page) {
                    if (!vm.reportData || !vm.reportData.pagination) return;
                    if (page < 1 || page > vm.reportData.pagination.last_page) return;

                    vm.loading = true;
                    var params = {
                        merchant_id: vm.filters.merchant_id || '',
                        from_date: vm.filters.from_date || '',
                        to_date: vm.filters.to_date || '',
                        status: vm.filters.status || 'all',
                        payment_method: vm.filters.payment_method || 'all',
                        page: page,
                        per_page: vm.pagination.per_page
                    };

                    $http.get('/admin/reports/data', { params: params }).then(function(response) {
                        if (response.data.success) {
                            vm.reportData = response.data.data || null;
                        }
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading page:', error);
                    });
                };

                vm.changePerPage = function() {
                    if (vm.reportData) {
                        vm.generateReport();
                    }
                };
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
