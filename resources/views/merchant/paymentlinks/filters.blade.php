<div class="stat-card mb-3">
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <label class="form-label">Status</label>
            <select class="form-select" ng-model="plc.filters.status" ng-change="plc.applyFilters()">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label">Per Page</label>
            <select class="form-select" ng-model="plc.perPage" ng-change="plc.applyFilters()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="col-md-12 col-lg-4">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" placeholder="Search by title or token" 
                   ng-model="plc.filters.search" ng-change="plc.applyFilters()">
        </div>
        <div class="col-md-6 col-lg-2 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100" ng-click="plc.clearFilters()">
                <i class="bi bi-x-circle"></i> Clear Filters
            </button>
        </div>
    </div>
</div>
