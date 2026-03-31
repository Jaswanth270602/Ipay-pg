@extends('vendor._layout')

@section('title', 'Vendor Dashboard')
@section('heading', 'Vendor Dashboard')

@section('content')
    <div class="stat-card">
        <div class="fw-semibold mb-2">Recent Payment Links</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse($paymentLinks as $link)
                    <tr>
                        <td>{{ $link->title }}</td>
                        <td>INR {{ number_format((float)$link->amount, 2) }}</td>
                        <td>
                            <span class="status-badge status-{{ $link->display_status ?? 'neutral' }}">
                                {{ strtoupper($link->display_status ?? 'pending') }}
                            </span>
                        </td>
                        <td>{{ optional($link->created_at)->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No payment links yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

