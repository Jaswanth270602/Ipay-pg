<div ng-if="tc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
    <div class="text-muted small">
        Showing @{{ tc.pagination.from || 0 }} to @{{ tc.pagination.to || 0 }} of @{{ tc.pagination.total || 0 }} results
    </div>
    <div class="pagination">
        <a href="#" class="page-link" ng-if="tc.pagination.current_page > 1" ng-click="tc.loadPage(tc.pagination.current_page - 1)">Previous</a>
        <a href="#" class="page-link" ng-repeat="page in tc.getPaginationPages() track by page" ng-class="{'active': page === tc.pagination.current_page}" ng-click="tc.loadPage(page)">@{{ page }}</a>
        <a href="#" class="page-link" ng-if="tc.pagination.current_page < tc.pagination.last_page" ng-click="tc.loadPage(tc.pagination.current_page + 1)">Next</a>
    </div>
</div>
