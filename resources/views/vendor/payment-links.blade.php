@extends('vendor._layout')

@section('title', 'Vendor Payment Links')
@section('heading', 'Payment Links')

@section('content')
<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Title</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                    <th>Action</th>
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
                        <td>
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick='vendorShowRowDetails("Payment Link #{{ $link->id }}", {id: {{ $link->id }}, title: "{{ $link->title }}", amount: "{{ $link->amount }}", status: "{{ strtoupper($link->display_status ?? "pending") }}", created_at: "{{ optional($link->created_at)->format("d M Y H:i") }}"})'>
                                View
                            </button>
                        </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No payment links found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $paymentLinks->links() }}</div>
</div>
@endsection

