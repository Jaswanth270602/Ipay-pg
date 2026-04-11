@extends('layouts.app-sidebar')

@section('title', 'Routing attempt #' . $monitor->id . ' - Admin')
@section('page-title', 'Routing trace')

@section('content')
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Acquirer Details'],
        ['label'=>'Routing attempts', 'url'=>route('admin.acquirer.monitoring.index')],
        ['label'=>'#' . $monitor->id]
    ]" />

    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Attempt #{{ $monitor->id }}</h2>
                <p class="text-muted small mb-0">
                    Merchant: <strong>{{ $monitor->merchant?->name ?? '—' }}</strong>
                    @if($monitor->txn_id)
                        · Txn: <code>{{ $monitor->txn_id }}</code>
                    @endif
                </p>
            </div>
            <div>
                <span class="badge @if($monitor->status==='success') bg-success @elseif($monitor->status==='failed') bg-danger @else bg-warning text-dark @endif">
                    {{ strtoupper($monitor->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="stat-card h-100">
                <h6 class="text-primary mb-3">Customer snapshot</h6>
                <dl class="row small mb-0">
                    <dt class="col-4">Name</dt><dd class="col-8">{{ $monitor->customer_name ?? '—' }}</dd>
                    <dt class="col-4">Email</dt><dd class="col-8">{{ $monitor->customer_email ?? '—' }}</dd>
                    <dt class="col-4">Phone</dt><dd class="col-8">{{ $monitor->customer_phone ?? '—' }}</dd>
                    <dt class="col-4">Method</dt><dd class="col-8">{{ $monitor->payment_method ?? '—' }}</dd>
                    <dt class="col-4">Final acquirer</dt><dd class="col-8">{{ $monitor->final_acquirer_name ?? '—' }}</dd>
                    <dt class="col-4">Mode</dt><dd class="col-8">{{ $monitor->test_mode ? 'Test' : 'Live' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card h-100">
                <h6 class="text-primary mb-3">Error</h6>
                <p class="small mb-0 text-break">{{ $monitor->error_message ?? '—' }}</p>
            </div>
        </div>
        <div class="col-12">
            <div class="stat-card">
                <h6 class="text-primary mb-3">Flow trace (acquirer health)</h6>
                <p class="text-muted small mb-2">
                    <strong>Health</strong> reflects a live credential check against each acquirer’s API (method varies by provider—e.g. a lightweight authenticated call where implemented). If the merchant has a fixed acquirer, that account is still used for payment, but you will see <strong>passed</strong> or <strong>failed</strong> here. The badge at the top is the <strong>checkout outcome</strong> after the transaction is linked.
                </p>
                @php
                    $trace = $monitor->flow_trace ?? [];
                @endphp
                @if(empty($trace))
                    <p class="text-muted small mb-0">No trace (e.g. not approved or no candidates).</p>
                @else
                    <ol class="mb-0 small">
                        @foreach($trace as $step)
                            <li class="mb-2">
                                <strong>{{ $step['acquirer'] ?? '?' }}</strong>
                                @if(!empty($step['acquirer_account_id']))
                                    <span class="text-muted">#{{ $step['acquirer_account_id'] }}</span>
                                @endif
                                — health: <strong>{{ $step['health'] ?? '—' }}</strong>
                                @if(!empty($step['message']))
                                    <span class="text-muted">({{ $step['message'] }})</span>
                                @endif
                                @if(!empty($step['reason']))
                                    <span class="badge bg-light text-dark border" title="Routing reason">{{ str_replace('_', ' ', $step['reason']) }}</span>
                                @endif
                                @if(!empty($step['source']))
                                    <span class="badge bg-light text-dark border">{{ $step['source'] }}</span>
                                @endif
                                @if(!empty($step['used_for_payment']))
                                    <span class="badge bg-success">used</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    <details class="mt-3">
                        <summary class="small text-muted">Raw JSON</summary>
                        <pre class="small bg-light p-2 rounded mt-2 mb-0 overflow-auto" style="max-height: 320px;">{{ json_encode($trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('admin.acquirer.monitoring.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>
@endsection
