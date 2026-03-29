

<?php $__env->startSection('title', 'Orders - ' . config('app.name')); ?>
<?php $__env->startSection('page-title','Orders'); ?>

<?php $__env->startSection('content'); ?>
<div ng-app="ipayApp" ng-controller="OrdersController as oc">
    <div class="stat-card mb-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Status</label>
                <select class="form-select" ng-model="oc.filters.status" ng-change="oc.applyFilters()">
                    <option value="">All</option>
                    <option value="created">Created</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" ng-model="oc.filters.from_date" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" ng-model="oc.filters.to_date" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Per Page</label>
                <select class="form-select" ng-model="oc.perPage" ng-change="oc.applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-6">
                <label class="form-label">Search</label>
                <input class="form-control" placeholder="Search by order ID or description" ng-model="oc.filters.search" ng-change="oc.applyFilters()">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end gap-2">
                <button class="btn btn-success" ng-click="oc.exportCSV()">
                    <i class="bi bi-download"></i> Download CSV
                </button>
                <button class="btn btn-outline-secondary" ng-click="oc.clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div ng-show="oc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading orders...</p>
            </div>
        </div>
        <div ng-hide="oc.loading" class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="order in oc.orders track by $index">
                    <td>{{ (oc.pagination.current_page - 1) * oc.pagination.per_page + $index + 1 }}</td>
                    <td><code>{{ order.order_id }}</code></td>
                    <td>{{ order.description || 'N/A' }}</td>
                    <td><strong>{{ order.amount | number:2 }}</strong></td>
                    <td>{{ order.currency || 'INR' }}</td>
                    <td>
                        <span class="badge" ng-class="{'bg-success': order.status==='completed', 'bg-danger': order.status==='failed', 'bg-warning': order.status==='pending', 'bg-info': order.status==='created', 'bg-secondary': order.status==='cancelled'}">{{ order.status | uppercase }}</span>
                    </td>
                    <td>{{ order.created_at | date:'MMM d, y HH:mm' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                ng-click="oc.viewOrder(order)"
                                title="View order details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr ng-if="oc.orders.length===0 && !oc.loading">
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-2">No orders found</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div ng-if="oc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted small">Showing {{ oc.pagination.from || 0 }} to {{ oc.pagination.to || 0 }} of {{ oc.pagination.total || 0 }} results</div>
            <div class="pagination">
                <a href="#" class="page-link" ng-if="oc.pagination.current_page > 1" ng-click="oc.loadPage(oc.pagination.current_page - 1)">Previous</a>
                <a href="#" class="page-link" ng-repeat="page in oc.getPaginationPages() track by page" ng-class="{'active': page === oc.pagination.current_page}" ng-click="oc.loadPage(page)">{{ page }}</a>
                <a href="#" class="page-link" ng-if="oc.pagination.current_page < oc.pagination.last_page" ng-click="oc.loadPage(oc.pagination.current_page + 1)">Next</a>
            </div>
        </div>
    </div>

    <!-- Order details modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true" ng-if="oc.selectedOrder">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">
                        Order Details – {{ oc.selectedOrder.order_id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <div class="text-uppercase text-muted small">Status</div>
                            <span class="badge" ng-class="{
                                'bg-success': oc.selectedOrder.status==='completed',
                                'bg-danger': oc.selectedOrder.status==='failed',
                                'bg-warning text-dark': oc.selectedOrder.status==='pending',
                                'bg-info': oc.selectedOrder.status==='processing',
                                'bg-secondary': oc.selectedOrder.status==='created'
                            }">
                                {{ oc.selectedOrder.status | uppercase }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="text-uppercase text-muted small">Amount</div>
                            <div class="fw-semibold">
                                {{ oc.selectedOrder.currency || 'INR' }} {{ oc.selectedOrder.amount | number:2 }}
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="text-uppercase text-muted small">Description</div>
                            <div>{{ oc.selectedOrder.description || '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-uppercase text-muted small">Created At</div>
                            <div>{{ oc.selectedOrder.created_at }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-uppercase text-muted small">Updated At</div>
                            <div>{{ oc.selectedOrder.updated_at }}</div>
                        </div>
                    </div>

                    <div class="row mb-1" ng-if="oc.selectedOrder.customer_details">
                        <div class="col-md-12">
                            <div class="text-uppercase text-muted small">Customer</div>
                            <div class="fw-semibold">{{ oc.selectedOrder.customer_details.name || '-' }}</div>
                        </div>
                    </div>
                    <div class="row" ng-if="oc.selectedOrder.customer_details">
                        <div class="col-md-6">
                            <div class="text-uppercase text-muted small">Email</div>
                            <div class="text-muted">{{ oc.selectedOrder.customer_details.email || '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-uppercase text-muted small">Phone</div>
                            <div class="text-muted">{{ oc.selectedOrder.customer_details.phone || '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('merchant.orders.angular.main_controller', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>


<?php echo $__env->make('layouts.app-sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\dell\Downloads\Ipay-pg\resources\views/merchant/orders/index.blade.php ENDPATH**/ ?>