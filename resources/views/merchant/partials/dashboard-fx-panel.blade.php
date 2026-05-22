@php
    $legacyCount = (int) ($legacy_fx_transaction_count ?? 0);
    $fxMode = $dashboard_fx_mode ?? 'historical';
    $displayCurrency = $dashboard_display_currency ?? 'KES';
    $showLegacyCallout = $legacyCount > 0 && $fxMode === 'historical';
    $currencies = $fx_options['supported_currencies'] ?? ['USD', 'INR', 'KES'];
@endphp

<style>
    .dashboard-fx-panel {
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .dashboard-fx-panel__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem 1.5rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
        border-bottom: 1px solid #f3f4f6;
    }
    .dashboard-fx-panel__title {
        flex: 1 1 100%;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.15rem;
    }
    .dashboard-fx-panel__title i {
        font-size: 1.15rem;
        color: #E10600;
    }
    .dashboard-fx-panel__title h6 {
        margin: 0;
        font-weight: 700;
        color: #111827;
        font-size: 0.95rem;
    }
    .dashboard-fx-panel__field label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }
    .dashboard-fx-panel__field .form-select {
        min-width: 140px;
        border-radius: 10px;
        border-color: #e5e7eb;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .dashboard-fx-panel__hint {
        flex: 1 1 220px;
        font-size: 0.8rem;
        color: #6b7280;
        line-height: 1.45;
        padding-bottom: 0.15rem;
    }
    .dashboard-fx-panel__hint strong {
        color: #374151;
    }
    .fx-legacy-callout {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 1rem 1.25rem;
        padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 55%, #fde68a 100%);
        border-top: 1px solid #fde68a;
    }
    .fx-legacy-callout__icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.65);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        color: #d97706;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15);
    }
    .fx-legacy-callout__body {
        flex: 1 1 260px;
        min-width: 0;
    }
    .fx-legacy-callout__body h6 {
        margin: 0 0 0.35rem;
        font-weight: 700;
        color: #92400e;
        font-size: 0.95rem;
    }
    .fx-legacy-callout__body p {
        margin: 0;
        font-size: 0.84rem;
        color: #78350f;
        line-height: 1.5;
    }
    .fx-legacy-callout__stat {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.5rem;
        padding: 0.25rem 0.65rem;
        background: rgba(255, 255, 255, 0.55);
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #b45309;
    }
    .fx-legacy-callout__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    .fx-legacy-callout__actions .btn-fix-fx {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fx-legacy-callout__actions .btn-fix-fx:hover:not(:disabled) {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(234, 88, 12, 0.45);
    }
    .fx-legacy-callout__actions .btn-fix-fx:disabled {
        opacity: 0.75;
    }
    .fx-legacy-callout__actions .btn-live-fx {
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #92400e;
        border-color: #fbbf24;
        background: rgba(255, 255, 255, 0.7);
    }
    .fx-legacy-callout__actions .btn-live-fx:hover {
        background: #fff;
        color: #78350f;
        border-color: #f59e0b;
    }
    .fx-legacy-callout__dismiss {
        border: none;
        background: transparent;
        color: #a16207;
        font-size: 1.1rem;
        line-height: 1;
        padding: 0.25rem;
        opacity: 0.7;
        border-radius: 8px;
    }
    .fx-legacy-callout__dismiss:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.4);
    }
    .fx-legacy-callout.is-hidden {
        display: none;
    }
    .fx-legacy-callout.is-working .btn-fix-fx .spinner-border {
        width: 1rem;
        height: 1rem;
        border-width: 2px;
    }
</style>

<div class="dashboard-fx-panel" id="dashboardFxPanel">
    <form method="GET" action="{{ route('dashboard') }}" class="dashboard-fx-panel__toolbar" id="merchantFxFilterForm"
          onsubmit="document.getElementById('merchantDashboardFxLoader').style.display='flex';">
        <div class="dashboard-fx-panel__title">
            <i class="bi bi-currency-exchange"></i>
            <h6>Dashboard currency &amp; conversion</h6>
        </div>
        <div class="dashboard-fx-panel__field">
            <label for="merchantDisplayCurrency">Display currency</label>
            <select name="display_currency" id="merchantDisplayCurrency" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($currencies as $code)
                    <option value="{{ $code }}" @selected($displayCurrency === $code)>{{ $code }}</option>
                @endforeach
            </select>
        </div>
        <div class="dashboard-fx-panel__field">
            <label for="merchantFxMode">Conversion</label>
            <select name="fx_mode" id="merchantFxMode" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="historical" @selected($fxMode === 'historical')>Historical (per payment)</option>
                <option value="live" @selected($fxMode === 'live')>Live current rate</option>
            </select>
        </div>
        <div class="dashboard-fx-panel__hint">
            @if($fxMode === 'historical')
                Volume totals convert to <strong>{{ $displayCurrency }}</strong> using each payment's stored rate — aligned with checkout when snapshots exist.
            @else
                Volume totals use <strong>today's</strong> rates into {{ $displayCurrency }}. Consistent for all payments, but may differ from receipt amounts.
            @endif
        </div>
    </form>

    @if($showLegacyCallout)
        <div class="fx-legacy-callout" id="fxLegacyCallout" role="status" aria-live="polite"
             data-legacy-count="{{ $legacyCount }}"
             data-backfill-url="{{ route('dashboard.fx-backfill') }}">
            <div class="fx-legacy-callout__icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="fx-legacy-callout__body">
                <h6>Some totals may not match payment amounts</h6>
                <p>
                    <strong id="fxLegacyCount">{{ number_format($legacyCount) }}</strong>
                    successful payment{{ $legacyCount === 1 ? '' : 's' }} in
                    {{ $merchant->test_mode ? 'test' : 'live' }} mode
                    {{ $legacyCount === 1 ? 'is' : 'are' }} missing a stored exchange-rate snapshot.
                    Dashboard volume uses live conversion for those until fixed.
                </p>
                <span class="fx-legacy-callout__stat">
                    <i class="bi bi-graph-up-arrow"></i>
                    Historical rate mode
                </span>
            </div>
            <div class="fx-legacy-callout__actions">
                <button type="button" class="btn btn-fix-fx" id="btnFixFxSnapshots">
                    <span class="btn-label"><i class="bi bi-magic me-1"></i> Fix totals</span>
                </button>
                <button type="button" class="btn btn-outline-warning btn-live-fx" id="btnSwitchLiveFx">
                    Use live rates
                </button>
                <button type="button" class="fx-legacy-callout__dismiss" id="btnDismissFxCallout" title="Dismiss for now" aria-label="Dismiss">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    @endif
</div>

@if($showLegacyCallout)
@push('scripts')
<script>
(function () {
    var callout = document.getElementById('fxLegacyCallout');
    var fixBtn = document.getElementById('btnFixFxSnapshots');
    var liveBtn = document.getElementById('btnSwitchLiveFx');
    var dismissBtn = document.getElementById('btnDismissFxCallout');
    var fxModeSelect = document.getElementById('merchantFxMode');
    var countEl = document.getElementById('fxLegacyCount');
    var loader = document.getElementById('merchantDashboardFxLoader');
    if (!callout || !fixBtn) return;

    var dismissKey = 'ipay_fx_callout_dismiss_{{ $merchant->id }}_{{ $merchant->test_mode ? 'test' : 'live' }}';
    if (sessionStorage.getItem(dismissKey) === '1') {
        callout.classList.add('is-hidden');
    }

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    dismissBtn && dismissBtn.addEventListener('click', function () {
        callout.classList.add('is-hidden');
        sessionStorage.setItem(dismissKey, '1');
    });

    liveBtn && liveBtn.addEventListener('click', function () {
        if (!fxModeSelect) return;
        fxModeSelect.value = 'live';
        document.getElementById('merchantFxFilterForm').submit();
    });

    fixBtn.addEventListener('click', function () {
        if (fixBtn.disabled) return;
        var url = callout.getAttribute('data-backfill-url');
        fixBtn.disabled = true;
        callout.classList.add('is-working');
        fixBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Fixing…';
        if (loader) loader.style.display = 'flex';

        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (typeof window.showToast === 'function') {
                    window.showToast(res.data.message || (res.ok ? 'Done.' : 'Could not update FX snapshots.'), res.ok ? 'success' : 'warning');
                }
                sessionStorage.removeItem(dismissKey);
                if (res.ok && (res.data.legacy_remaining === 0 || res.data.legacy_remaining === '0')) {
                    window.location.reload();
                    return;
                }
                if (countEl && res.data.legacy_remaining != null) {
                    countEl.textContent = Number(res.data.legacy_remaining).toLocaleString();
                }
                if (!res.ok) {
                    fixBtn.disabled = false;
                    callout.classList.remove('is-working');
                    fixBtn.innerHTML = '<span class="btn-label"><i class="bi bi-magic me-1"></i> Fix totals</span>';
                } else {
                    window.location.reload();
                }
            })
            .catch(function () {
                if (typeof window.showToast === 'function') {
                    window.showToast('Could not update FX snapshots. Please try again.', 'error');
                }
                fixBtn.disabled = false;
                callout.classList.remove('is-working');
                fixBtn.innerHTML = '<span class="btn-label"><i class="bi bi-magic me-1"></i> Fix totals</span>';
            })
            .finally(function () {
                if (loader) loader.style.display = 'none';
            });
    });
})();
</script>
@endpush
@endif
