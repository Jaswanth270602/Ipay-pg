@extends('layouts.app-sidebar')

@section('title', 'Settlement schedule & cron - ' . config('app.name'))
@section('page-title', 'Cron & schedule')

@section('content')
<div class="merchant-settlement-cron">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('dashboard')],
        ['label'=>'Cron & schedule']
    ]" />

    @include('merchant.settlements._nav-tabs')

    <div class="row mb-3">
        <div class="col-md-12">
            <h2 class="h4 mb-1">Settlement schedule</h2>
            <p class="text-muted mb-0">Run batches from the dashboard and optionally trigger the platform scheduler over HTTP so you do not need SSH every day.</p>
        </div>
    </div>

    <div class="stat-card mb-4">
        <h3 class="h6 mb-3">Your settlement cycles</h3>
        <dl class="row mb-0 small">
            <dt class="col-sm-4">Domestic (INR)</dt>
            <dd class="col-sm-8">T+{{ (int) ($merchant->settlement_cycle_domestic ?? 1) }}</dd>
            <dt class="col-sm-4">International (non-INR)</dt>
            <dd class="col-sm-8">T+{{ (int) ($merchant->settlement_cycle_international ?? 7) }}</dd>
            <dt class="col-sm-4">Platform batch time</dt>
            <dd class="col-sm-8">Daily at <strong>23:00</strong> (<strong>Asia/Kolkata</strong>) when the scheduler is active</dd>
        </dl>
    </div>

    <div class="stat-card mb-4">
        <h3 class="h6 mb-3">Run settlement batch for your account</h3>
        <p class="text-muted small">Uses the same rules as the automated job: successful, pending transactions that are past the T+N cutoff for the processing date.</p>

        <form id="settlement-cron-run-form" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="cron-date">Processing date</label>
                <input type="date" class="form-control" id="cron-date" name="date"
                       value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cron-mode">Mode</label>
                <select class="form-select" id="cron-mode" name="mode">
                    <option value="all">All (test + live)</option>
                    <option value="test">Test only</option>
                    <option value="live">Live only</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="cron-dry" name="dry_run" value="1">
                    <label class="form-check-label" for="cron-dry">Dry run (no DB changes)</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" id="cron-run-btn">
                    <i class="bi bi-play-fill"></i> Run now
                </button>
            </div>
        </form>

        <div id="cron-run-alert" class="alert mt-3 d-none" role="alert"></div>
    </div>

    <div class="stat-card mb-4">
        <h3 class="h6 mb-3">HTTP cron (no SSH)</h3>
        <p class="text-muted small mb-2">
            Add <code>SCHEDULER_CRON_TOKEN</code> to your <code>.env</code>, then register this URL with your hosting cron or a service such as
            <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> (recommended: every minute).
            The token is never shown here—copy it from your environment file.
        </p>
        <div class="bg-light border rounded p-3 font-monospace small user-select-all">
            {{ $cronPingBaseUrl }}?token=<span class="text-muted">&lt;SCHEDULER_CRON_TOKEN&gt;</span>
        </div>
        @if (empty(config('ipay.scheduler_cron_token')))
            <p class="text-warning small mt-2 mb-0">
                <i class="bi bi-exclamation-triangle"></i> Token is not set yet; configure <code>SCHEDULER_CRON_TOKEN</code> in <code>.env</code> for this URL to work.
            </p>
        @else
            <p class="text-success small mt-2 mb-0">
                <i class="bi bi-check-circle"></i> Token is configured. Keep it secret.
            </p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('settlement-cron-run-form');
    var alertEl = document.getElementById('cron-run-alert');
    var btn = document.getElementById('cron-run-btn');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    function showAlert(type, message) {
        alertEl.className = 'alert mt-3 alert-' + type;
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        btn.disabled = true;
        alertEl.classList.add('d-none');

        var fd = new FormData(form);
        var body = {
            date: fd.get('date') || null,
            mode: fd.get('mode'),
            dry_run: document.getElementById('cron-dry').checked
        };

        fetch('{{ route('merchant.settlements.cron.run') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            if (res.ok && res.data.success) {
                var msg = res.data.message;
                if (res.data.data && res.data.data.settlement_id) {
                    msg += ' Settlement ID: ' + res.data.data.settlement_id;
                }
                showAlert('success', msg);
            } else {
                showAlert('danger', (res.data && res.data.message) ? res.data.message : 'Request failed.');
            }
        })
        .catch(function () {
            showAlert('danger', 'Network error.');
        })
        .finally(function () {
            btn.disabled = false;
        });
    });
})();
</script>
@endpush
