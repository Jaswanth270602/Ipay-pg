# Pagination Fix Summary

The pagination component needs to use the correct controller scope. Each view should have its own pagination inline using the correct controller alias.

## Quick Fix Needed

For each view file, replace `<x-pagination />` with the inline pagination HTML using the correct controller:

### For Transactions (uses `tc`):
```blade
<div ng-if="tc.pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
    <div class="text-muted small">Showing @{{ tc.pagination.from || 0 }} to @{{ tc.pagination.to || 0 }} of @{{ tc.pagination.total || 0 }} results</div>
    <div class="pagination">
        <a href="#" class="page-link" ng-if="tc.pagination.current_page > 1" ng-click="tc.loadPage(tc.pagination.current_page - 1)">Previous</a>
        <a href="#" class="page-link" ng-repeat="page in tc.getPaginationPages() track by page" ng-class="{'active': page === tc.pagination.current_page}" ng-click="tc.loadPage(page)">@{{ page }}</a>
        <a href="#" class="page-link" ng-if="tc.pagination.current_page < tc.pagination.last_page" ng-click="tc.loadPage(tc.pagination.current_page + 1)">Next</a>
    </div>
</div>
```

### For Orders (uses `oc`):
Replace `tc` with `oc`

### For Refunds (uses `rc`):
Replace `tc` with `rc`

### For Settlements (uses `sc`):
Replace `tc` with `sc`

## Status

✅ All controllers now have:
- `getPaginationPages()` method
- Proper filter handling with debounce
- `clearFilters()` method
- Serial numbers in grids
- INR as default currency
- Proper @{{}} Angular syntax
- All controllers in `angular/main_controller.blade.php`

## Remaining Tasks

1. Update pagination in orders, refunds, settlements views
2. Add getPaginationPages() to all other controllers (api_keys, webhooks, etc.)
3. Update payment links view with new structure
4. Update seeder to use INR
5. Make all views responsive

