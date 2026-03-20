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
            app.controller('AdminMerchantsController', ['$http', function($http) {
                var vm = this;
                vm.merchants = [];
                vm.pagination = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
                vm.filters = { status: 'all', search: '' };
                vm.loading = false;
                vm.selected = {};
                vm.selectedIds = [];
                vm.selectAll = false;
                vm.bulkConfirmTitle = '';
                vm.bulkConfirmMessage = '';
                vm.bulkConfirmButtonLabel = '';
                vm.bulkConfirmBtnClass = 'btn-primary';
                vm.pendingBulkAction = null;

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
                        vm.selected = {};
                        vm.selectedIds = [];
                        vm.selectAll = false;
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

                vm.syncSelection = function () {
                    vm.selectedIds = Object.keys(vm.selected)
                        .filter(function (id) { return vm.selected[id]; })
                        .map(function (id) { return parseInt(id, 10); });
                    vm.selectAll = vm.merchants.length > 0 && vm.selectedIds.length === vm.merchants.length;
                };

                vm.toggleSelectAll = function () {
                    vm.selected = {};
                    if (vm.selectAll) {
                        vm.merchants.forEach(function (m) {
                            vm.selected[m.id] = true;
                        });
                    }
                    vm.syncSelection();
                };

                vm.hasSelection = function () {
                    return vm.selectedIds.length > 0;
                };

                vm._bulkApprove = function () {
                    if (!vm.hasSelection()) { return; }

                    $http.post('/admin/merchants/bulk-approve', { ids: vm.selectedIds }).then(function (response) {
                        if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Selected merchants bulk approved', 'success');
                        }
                        vm.loadMerchants();
                    }, function (error) {
                        console.error('Bulk approve error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to approve merchants', 'error');
                        } else {
                            alert('Failed to approve merchants');
                        }
                    });
                };

                vm._bulkReject = function () {
                    if (!vm.hasSelection()) { return; }

                    $http.post('/admin/merchants/bulk-reject', { ids: vm.selectedIds }).then(function (response) {
                        if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Selected merchants bulk rejected', 'warning');
                        }
                        vm.loadMerchants();
                    }, function (error) {
                        console.error('Bulk reject error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to reject merchants', 'error');
                        } else {
                            alert('Failed to reject merchants');
                        }
                    });
                };

                vm._bulkDelete = function () {
                    if (!vm.hasSelection()) { return; }

                    $http.post('/admin/merchants/bulk-delete', { ids: vm.selectedIds }).then(function (response) {
                        if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Selected merchants bulk deleted', 'success');
                        }
                        vm.loadMerchants();
                    }, function (error) {
                        console.error('Bulk delete error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to delete merchants', 'error');
                        } else {
                            alert('Failed to delete merchants');
                        }
                    });
                };

                vm.openBulkConfirm = function (type) {
                    if (!vm.hasSelection()) {
                        return;
                    }

                    vm.pendingBulkAction = type;

                    if (type === 'approve') {
                        vm.bulkConfirmTitle = 'Approve merchants';
                        vm.bulkConfirmMessage = 'Are you sure you want to approve the selected merchants?';
                        vm.bulkConfirmButtonLabel = 'Yes, Approve';
                        vm.bulkConfirmBtnClass = 'btn-success';
                    } else if (type === 'reject') {
                        vm.bulkConfirmTitle = 'Reject merchants';
                        vm.bulkConfirmMessage = 'Are you sure you want to reject the selected merchants?';
                        vm.bulkConfirmButtonLabel = 'Yes, Reject';
                        vm.bulkConfirmBtnClass = 'btn-warning';
                    } else if (type === 'delete') {
                        vm.bulkConfirmTitle = 'Delete merchants';
                        vm.bulkConfirmMessage = 'This action cannot be undone. Do you really want to delete the selected merchants?';
                        vm.bulkConfirmButtonLabel = 'Yes, Delete';
                        vm.bulkConfirmBtnClass = 'btn-danger';
                    } else {
                        vm.bulkConfirmTitle = 'Confirm action';
                        vm.bulkConfirmMessage = 'Are you sure you want to perform this action?';
                        vm.bulkConfirmButtonLabel = 'Confirm';
                        vm.bulkConfirmBtnClass = 'btn-primary';
                    }

                    try {
                        var modalElement = document.getElementById('bulkConfirmModal');
                        if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                            modal.show();
                        }
                    } catch (e) {
                        console.error('Error opening bulk confirm modal', e);
                    }
                };

                vm.confirmBulk = function () {
                    if (!vm.pendingBulkAction) {
                        return;
                    }
                    var action = vm.pendingBulkAction;
                    vm.pendingBulkAction = null;

                    if (action === 'approve') {
                        vm._bulkApprove();
                    } else if (action === 'reject') {
                        vm._bulkReject();
                    } else if (action === 'delete') {
                        vm._bulkDelete();
                    }
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

