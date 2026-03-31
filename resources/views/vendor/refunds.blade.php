@extends('vendor._layout')

@section('title', 'Vendor Refunds')
@section('heading', 'Refunds')

@section('content')
<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Refund ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($refunds as $refund)
                <tr>
                    <td>{{ $refund->refund_id }}</td>
                    <td>INR {{ number_format((float)$refund->amount, 2) }}</td>
                    <td>
                        @php $s = strtolower((string)$refund->status); @endphp
                        <span class="status-badge {{ in_array($s,['completed','approved']) ? 'status-success' : (in_array($s,['failed','rejected']) ? 'status-danger' : 'status-warning') }}">
                            {{ strtoupper($refund->status) }}
                        </span>
                    </td>
                    <td>{{ optional($refund->created_at)->format('d M Y H:i') }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick='vendorShowRowDetails("Refund {{ $refund->refund_id }}", {id: {{ $refund->id }}, refund_id: "{{ $refund->refund_id }}", amount: "{{ $refund->amount }}", status: "{{ $refund->status }}", created_at: "{{ optional($refund->created_at)->format("d M Y H:i") }}"})'>
                            View
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No refunds found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $refunds->links() }}</div>
</div>
@endsection

