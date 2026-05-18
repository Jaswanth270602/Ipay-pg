{{-- Shared analytics UI; pass $dataUrl, $exportUrl, $isAdmin (bool), $breadcrumbs --}}
<div ng-app="ipayApp" ng-controller="PaymentAnalyticsController as pa">
    <x-breadcrumbs :items="$breadcrumbs" />

    <div class="stat-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Report type</label>
                <select class="form-select" ng-model="pa.filters.report_type" ng-change="pa.onReportTypeChange()">
                    @foreach($reportTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(!empty($isAdmin))
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Merchant ID</label>
                <input type="number" class="form-control" ng-model="pa.filters.merchant_id" placeholder="All">
            </div>
            @endif
            <div class="col-md-6 col-lg-2">
                <label class="form-label">From date</label>
                <input type="date" class="form-control" ng-model="pa.filters.from_date">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">To date</label>
                <input type="date" class="form-control" ng-model="pa.filters.to_date">
            </div>
            <div class="col-md-6 col-lg-2">
                <button class="btn btn-primary w-100" ng-click="pa.loadReport()" ng-disabled="pa.loading">
                    <span ng-if="pa.loading" class="spinner-border spinner-border-sm me-1"></span>
                    Generate
                </button>
            </div>
            <div class="col-md-6 col-lg-2">
                <button class="btn btn-outline-primary w-100" ng-click="pa.exportReport()" ng-disabled="pa.exporting || !pa.payload">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="mt-2" ng-if="pa.meta.environment">
            <span class="badge" ng-class="pa.meta.environment === 'TEST' ? 'bg-warning text-dark' : 'bg-success'">
                @{{ pa.meta.environment }} mode
            </span>
        </div>
    </div>

    <div ng-show="pa.loading" class="stat-card text-center py-5">
        <div class="spinner-border text-primary"></div>
        <p class="text-muted mt-2 mb-0">Building report…</p>
    </div>

    <div ng-if="pa.payload && !pa.loading">
        <div class="stat-card mb-3" ng-if="pa.summaryKeys.length">
            <h5 class="mb-3">Summary</h5>
            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-3" ng-repeat="key in pa.summaryKeys">
                    <div class="p-3 bg-light rounded">
                        <div class="text-muted small text-capitalize">@{{ pa.formatLabel(key) }}</div>
                        <div class="h5 mb-0">@{{ pa.payload.summary[key] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card" ng-if="pa.payload.rows && pa.payload.rows.length">
            <h5 class="mb-3">Breakdown</h5>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th ng-repeat="col in pa.rowColumns">@{{ pa.formatLabel(col) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="row in pa.payload.rows">
                            <td ng-repeat="col in pa.rowColumns">@{{ row[col] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stat-card text-center py-4 text-muted" ng-if="!pa.payload.rows || !pa.payload.rows.length">
            <i class="bi bi-check-circle"></i> Summary-only report (no row breakdown).
        </div>
    </div>

    <div class="stat-card text-center py-5 text-muted" ng-if="!pa.payload && !pa.loading">
        <i class="bi bi-bar-chart-line" style="font-size:2.5rem;"></i>
        <p class="mt-2 mb-0">Choose a report type and date range, then click Generate.</p>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function registerController() {
        if (typeof angular === 'undefined') { setTimeout(registerController, 50); return; }
        try {
            angular.module('ipayApp').controller('PaymentAnalyticsController', ['$http', function ($http) {
                var vm = this;
                vm.filters = {
                    report_type: '{{ array_key_first($reportTypes) }}',
                    from_date: '',
                    to_date: '',
                    merchant_id: ''
                };
                vm.payload = null;
                vm.meta = {};
                vm.loading = false;
                vm.exporting = false;
                vm.summaryKeys = [];
                vm.rowColumns = [];

                var today = new Date();
                var prior = new Date();
                prior.setDate(today.getDate() - 30);
                vm.filters.to_date = today.toISOString().slice(0, 10);
                vm.filters.from_date = prior.toISOString().slice(0, 10);

                vm.formatLabel = function (key) {
                    return String(key).replace(/_/g, ' ');
                };

                vm.applyPayload = function (data, meta) {
                    vm.payload = data;
                    vm.meta = meta || {};
                    vm.summaryKeys = data && data.summary ? Object.keys(data.summary) : [];
                    vm.rowColumns = (data && data.rows && data.rows[0]) ? Object.keys(data.rows[0]) : [];
                };

                vm.buildParams = function () {
                    var p = {
                        report_type: vm.filters.report_type,
                        from_date: vm.filters.from_date || '',
                        to_date: vm.filters.to_date || ''
                    };
                    if (vm.filters.merchant_id) {
                        p.merchant_id = vm.filters.merchant_id;
                    }
                    return p;
                };

                vm.loadReport = function () {
                    vm.loading = true;
                    $http.get(@json($dataUrl), { params: vm.buildParams() }).then(function (res) {
                        if (res.data.success) {
                            vm.applyPayload(res.data.data, res.data.meta);
                        } else {
                            alert(res.data.message || 'Failed to load report');
                        }
                        vm.loading = false;
                    }, function (err) {
                        vm.loading = false;
                        alert((err.data && err.data.message) ? err.data.message : 'Failed to load report');
                    });
                };

                vm.exportReport = function () {
                    var q = new URLSearchParams(vm.buildParams()).toString();
                    window.location.href = @json($exportUrl) + '?' + q;
                };

                vm.onReportTypeChange = function () {
                    if (vm.payload) { vm.loadReport(); }
                };
            }]);
        } catch (e) { setTimeout(registerController, 50); }
    }
    registerController();
})();
</script>
@endpush
