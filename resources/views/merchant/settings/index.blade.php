@extends('layouts.app-sidebar')

@section('title', 'Settings - ' . config('app.name'))
@section('page-title','Settings')

@section('content')
<x-breadcrumbs :items="[
    ['label'=>'Dashboard','url'=>route('dashboard')],
    ['label'=>'Settings']
]" />

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@php
    $settings = $settings ?? [];
    $splitModeDefault = old('split_mode');
    if ($splitModeDefault === null) {
        if (empty($settings['split_merchant_vendor_id'] ?? null)) {
            $splitModeDefault = 'none';
        } elseif (isset($settings['split_secondary_amount']) && $settings['split_secondary_amount'] !== null && $settings['split_secondary_amount'] !== '') {
            $splitModeDefault = 'fixed';
        } else {
            $splitModeDefault = 'percentage';
        }
    }
@endphp

<div class="row g-4">
    <div class="col-md-8">
        <div class="stat-card mb-4">
            <h5 class="mb-3">API Keys</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Key</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    @forelse($apiKeys as $k)
                        <tr>
                            <td><code>{{ $k->key }}</code></td>
                            <td><span class="badge {{ $k->status==='active'?'bg-success':'bg-secondary' }}">{{ $k->status }}</span></td>
                            <td>{{ $k->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No keys yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stat-card mb-4">
            <h5 class="mb-2">Payment split (merchant + vendor)</h5>
            <p class="text-muted small">Route part of each successful payment to an <strong>approved</strong> vendor profile (from Admin → Merchant Vendors). Applies to <strong>new</strong> payments after you save. You can override per order using order metadata: <code>split_merchant_vendor_id</code>, <code>split_secondary_percentage</code> or <code>split_secondary_amount</code>.</p>
            <form method="POST" action="{{ route('merchant.settings.update-split') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Vendor (secondary payee)</label>
                    <select name="split_merchant_vendor_id" class="form-select">
                        <option value="">— None (100% to your merchant) —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected(old('split_merchant_vendor_id', $settings['split_merchant_vendor_id'] ?? '') == $v->id)>
                                {{ $v->vendor_name }} ({{ $v->vendor_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('split_merchant_vendor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Split mode</label>
                    <select name="split_mode" class="form-select" id="merchant-split-mode">
                        <option value="none" @selected($splitModeDefault === 'none')>No vendor split</option>
                        <option value="percentage" @selected($splitModeDefault === 'percentage')>Percentage to vendor</option>
                        <option value="fixed" @selected($splitModeDefault === 'fixed')>Fixed amount to vendor</option>
                    </select>
                </div>
                <div class="mb-3" id="split-pct-wrap">
                    <label class="form-label">% of payment to vendor</label>
                    <input type="number" step="0.01" min="0.01" max="100" name="split_secondary_percentage" class="form-control"
                           value="{{ old('split_secondary_percentage', $settings['split_secondary_percentage'] ?? '') }}" placeholder="e.g. 20">
                </div>
                <div class="mb-3" id="split-amt-wrap" style="display:none;">
                    <label class="form-label">Fixed amount to vendor (INR)</label>
                    <input type="number" step="0.01" min="0.01" name="split_secondary_amount" class="form-control"
                           value="{{ old('split_secondary_amount', $settings['split_secondary_amount'] ?? '') }}" placeholder="e.g. 500">
                </div>
                <button type="submit" class="btn btn-primary">Save split settings</button>
            </form>
            <script>
            (function(){
                var mode = document.getElementById('merchant-split-mode');
                var pct = document.getElementById('split-pct-wrap');
                var amt = document.getElementById('split-amt-wrap');
                function sync(){
                    if (!mode || !pct || !amt) return;
                    if (mode.value === 'fixed') { pct.style.display = 'none'; amt.style.display = 'block'; }
                    else if (mode.value === 'percentage') { pct.style.display = 'block'; amt.style.display = 'none'; }
                    else { pct.style.display = 'none'; amt.style.display = 'none'; }
                }
                if (mode) { mode.addEventListener('change', sync); sync(); }
            })();
            </script>
        </div>

        <div class="stat-card">
            <h5 class="mb-3">Webhook</h5>
            <form method="POST" action="{{ route('merchant.settings.update-webhook') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url',$merchant->webhook_url) }}" placeholder="https://example.com/webhooks/ipay">
                </div>
                <button class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <h5 class="mb-3">Account Mode</h5>
            <div class="d-flex gap-2">
                <button class="btn {{ $merchant->test_mode?'btn-warning':'btn-outline-warning' }}" onclick="switchMode('test')">Test</button>
                <button class="btn {{ !$merchant->test_mode?'btn-success':'btn-outline-success' }}" onclick="switchMode('live')">Live</button>
            </div>
            <div class="mt-3">
                <span class="badge {{ $merchant->test_mode?'bg-warning':'bg-success' }}">{{ $merchant->test_mode?'TEST':'LIVE' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

 