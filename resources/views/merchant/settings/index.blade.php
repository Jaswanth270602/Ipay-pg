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

        <div class="stat-card">
            <h5 class="mb-3">Webhook</h5>
            <form method="POST" action="{{ route('merchant.settings.update-webhook') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url',$merchant->webhook_url) }}" placeholder="https://example.com/webhooks/badlicash">
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

 