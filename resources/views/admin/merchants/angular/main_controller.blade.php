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
                vm.filters = { status: 'all', search: '', reseller_id: '' };
                vm.resellers = [];
                vm.loading = false;
                vm.selected = {};
                vm.selectedIds = [];
                vm.selectAll = false;
                vm.bulkConfirmTitle = '';
                vm.bulkConfirmMessage = '';
                vm.bulkConfirmButtonLabel = '';
                vm.bulkConfirmBtnClass = 'btn-primary';
                vm.pendingBulkAction = null;

                vm.loadResellers = function() {
                    $http.get('/admin/merchant-accounts/resellers').then(function(res) {
                        if (res.data && res.data.success && res.data.data) {
                            vm.resellers = res.data.data;
                        } else {
                            vm.resellers = [];
                        }
                    }, function() {
                        vm.resellers = [];
                    });
                };

                vm.loadMerchants = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        search: vm.filters.search || ''
                    };
                    if (vm.filters.reseller_id) {
                        params.reseller_id = vm.filters.reseller_id;
                    }

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
                    vm.filters = { status: 'all', search: '', reseller_id: '' };
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

                vm.getSelectedMerchants = function () {
                    return vm.merchants.filter(function (m) {
                        return vm.selectedIds.indexOf(parseInt(m.id, 10)) !== -1;
                    });
                };

                vm._bulkApprove = function () {
                    if (!vm.hasSelection()) { return; }

                    $http.post('/admin/merchants/bulk-approve', { ids: vm.selectedIds }).then(function (response) {
                        if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Selected merchants bulk activated', 'success');
                        }
                        vm.loadMerchants();
                    }, function (error) {
                        console.error('Bulk approve error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to activate merchants', 'error');
                        } else {
                            alert('Failed to activate merchants');
                        }
                    });
                };

                vm._bulkReject = function () {
                    if (!vm.hasSelection()) { return; }

                    $http.post('/admin/merchants/bulk-reject', { ids: vm.selectedIds }).then(function (response) {
                        if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Selected merchants bulk deactivated', 'warning');
                        }
                        vm.loadMerchants();
                    }, function (error) {
                        console.error('Bulk reject error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to deactivate merchants', 'error');
                        } else {
                            alert('Failed to deactivate merchants');
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

                vm._deactivateThenContinueDelete = function () {
                    if (!vm.hasSelection()) { return; }

                    var activeIds = vm.getSelectedMerchants()
                        .filter(function (m) { return (m.status || '').toLowerCase() === 'active'; })
                        .map(function (m) { return parseInt(m.id, 10); });

                    if (!activeIds.length) {
                        vm.openBulkConfirm('delete');
                        return;
                    }

                    $http.post('/admin/merchants/bulk-reject', { ids: activeIds }).then(function () {
                        // Update local rows so next delete confirm can proceed without extra refresh
                        vm.merchants.forEach(function (m) {
                            if (activeIds.indexOf(parseInt(m.id, 10)) !== -1) {
                                m.status = 'inactive';
                            }
                        });

                        if (typeof showToast === 'function') {
                            showToast('Selected active merchants bulk deactivated. Please confirm delete.', 'warning');
                        }

                        setTimeout(function () {
                            vm.openBulkConfirm('delete');
                        }, 150);
                    }, function (error) {
                        console.error('Deactivate before delete error:', error);
                        if (typeof showToast === 'function') {
                            showToast('Failed to deactivate active merchants before delete', 'error');
                        } else {
                            alert('Failed to deactivate active merchants before delete');
                        }
                    });
                };

                vm.openBulkConfirm = function (type) {
                    if (!vm.hasSelection()) {
                        return;
                    }

                    vm.pendingBulkAction = type;

                    if (type === 'activate') {
                        vm.bulkConfirmTitle = 'Activate merchants';
                        vm.bulkConfirmMessage = 'Are you sure you want to activate the selected merchants?';
                        vm.bulkConfirmButtonLabel = 'Yes, Activate';
                        vm.bulkConfirmBtnClass = 'btn-success';
                    } else if (type === 'deactivate') {
                        vm.bulkConfirmTitle = 'Deactivate merchants';
                        vm.bulkConfirmMessage = 'Are you sure you want to deactivate the selected merchants?';
                        vm.bulkConfirmButtonLabel = 'Yes, Deactivate';
                        vm.bulkConfirmBtnClass = 'btn-warning';
                    } else if (type === 'delete') {
                        var hasActiveSelected = vm.getSelectedMerchants().some(function (m) {
                            return (m.status || '').toLowerCase() === 'active';
                        });

                        if (hasActiveSelected) {
                            vm.pendingBulkAction = 'deactivate_then_delete';
                            vm.bulkConfirmTitle = 'Active merchants selected';
                            vm.bulkConfirmMessage = 'Active merchants cannot be deleted. Deactivate selected active merchants first.';
                            vm.bulkConfirmButtonLabel = 'Deactivate & Continue';
                            vm.bulkConfirmBtnClass = 'btn-warning';
                        } else {
                            vm.bulkConfirmTitle = 'Delete merchants';
                            vm.bulkConfirmMessage = 'This action cannot be undone. Do you really want to delete the selected merchants?';
                            vm.bulkConfirmButtonLabel = 'Yes, Delete';
                            vm.bulkConfirmBtnClass = 'btn-danger';
                        }
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

                    if (action === 'activate') {
                        vm._bulkApprove();
                    } else if (action === 'deactivate') {
                        vm._bulkReject();
                    } else if (action === 'deactivate_then_delete') {
                        vm._deactivateThenContinueDelete();
                    } else if (action === 'delete') {
                        vm._bulkDelete();
                    }
                };

                vm.viewMerchant = function(merchant) {
                    vm.merchantDetail = null;
                    var modalEl = document.getElementById('merchantDetailModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    }

                    $http.get('/admin/merchants/' + merchant.id).then(function(response) {
                        if (response.data && response.data.success && response.data.data) {
                            vm.merchantDetail = response.data.data;
                        } else if (typeof showToast === 'function') {
                            showToast(response.data.message || 'Failed to load merchant details', 'error');
                        } else {
                            alert((response.data && response.data.message) || 'Failed to load merchant details');
                        }
                    }, function(error) {
                        if (typeof showToast === 'function') {
                            showToast('Failed to load merchant details', 'error');
                        } else {
                            alert('Failed to load merchant details');
                        }
                        console.error('Error loading merchant details', error);
                    });
                };

                vm.loadResellers();
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

