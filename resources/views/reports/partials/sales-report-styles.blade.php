{{-- Include once per admin sales report page (inside @section content). --}}
@push('styles')
<style>
    .sales-report-page .sales-report-table-wrap {
        border-color: #e5e7eb !important;
        overflow: hidden;
    }
    .sales-report-page .sales-report-table {
        --sr-table-border: #e8eaed;
    }
    .sales-report-page .sales-report-table thead tr:first-child th {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.055em;
        color: #374151;
        background: linear-gradient(180deg, #fafafa 0%, #f3f4f6 100%);
        border-bottom: 2px solid var(--sr-table-border);
        padding: 0.75rem 0.875rem;
        white-space: nowrap;
        vertical-align: middle;
    }
    .sales-report-page .sales-report-table thead tr.filters-row th {
        background: #f9fafb;
        padding: 0.45rem 0.625rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--sr-table-border);
    }
    .sales-report-page .sales-report-table thead tr.filters-row .form-control-sm {
        font-size: 0.8125rem;
        border-radius: 0.375rem;
        border-color: #d1d5db;
        min-height: calc(1.5em + 0.45rem + 2px);
    }
    .sales-report-page .sales-report-table thead tr.filters-row .form-control-sm:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.15rem rgba(99, 102, 241, 0.18);
    }
    .sales-report-page .sales-report-table tbody td {
        padding: 0.65rem 0.875rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .sales-report-page .sales-report-table tbody tr:nth-child(even) td {
        background-color: rgba(249, 250, 251, 0.92);
    }
    .sales-report-page .sales-report-table tbody tr:hover td {
        background-color: rgba(238, 242, 255, 0.65);
    }
    .sales-report-page .sales-report-table tbody td.text-end {
        font-variant-numeric: tabular-nums;
    }
</style>
@endpush
