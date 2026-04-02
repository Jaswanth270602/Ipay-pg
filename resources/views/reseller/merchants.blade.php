@extends('layouts.app-sidebar')

@section('title', 'Merchants - Reseller - ' . config('app.name'))
@section('page-title', 'Merchants')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <h2>Your merchants</h2>
        <p class="text-muted mb-0">Merchants assigned to your reseller account.</p>
    </div>
</div>

<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Merchant ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Approval</th>
                </tr>
            </thead>
            <tbody>
                @forelse($merchants as $m)
                <tr>
                    <td>{{ $m->merchant_unique_id ?? $m->id }}</td>
                    <td>{{ $m->name }}</td>
                    <td>{{ $m->email }}</td>
                    <td><span class="badge bg-secondary">{{ $m->status }}</span></td>
                    <td>{{ $m->approval_status ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-muted text-center py-4">No merchants assigned yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
