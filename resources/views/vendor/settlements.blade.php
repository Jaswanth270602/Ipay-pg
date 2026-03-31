@extends('vendor._layout')

@section('title', 'Vendor Settlements')
@section('heading', 'Settlements')

@section('content')
<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Settlement Status</th>
                <th>Settled At</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($transactions as $tx)
                @php
                    $displayTxnId = $tx->txn_id ?: ($tx->transaction_id ?: '-');
                @endphp
                <tr>
                    <td>{{ $displayTxnId }}</td>
                    <td>INR {{ number_format((float)$tx->amount, 2) }}</td>
                    <td>
                        @php $s = strtolower((string)($tx->settlement_status ?? 'na')); @endphp
                        <span class="status-badge {{ in_array($s,['settled','completed']) ? 'status-success' : (in_array($s,['failed','rejected']) ? 'status-danger' : 'status-info') }}">
                            {{ strtoupper($tx->settlement_status ?? 'NA') }}
                        </span>
                    </td>
                    <td>{{ optional($tx->settled_at)->format('d M Y H:i') ?: '-' }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick='vendorShowRowDetails("Settlement {{ $displayTxnId }}", {id: {{ $tx->id }}, transaction_id: "{{ $displayTxnId }}", amount: "{{ $tx->amount }}", settlement_status: "{{ $tx->settlement_status }}", settled_at: "{{ optional($tx->settled_at)->format("d M Y H:i") }}"})'>
                            View
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No settlements found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $transactions->links() }}</div>
</div>
@endsection

