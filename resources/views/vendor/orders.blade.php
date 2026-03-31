@extends('vendor._layout')

@section('title', 'Vendor Orders')
@section('heading', 'Orders')

@section('content')
<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_id }}</td>
                    <td>INR {{ number_format((float)$order->amount, 2) }}</td>
                    <td>
                        @php $s = strtolower((string)$order->status); @endphp
                        <span class="status-badge {{ in_array($s,['completed','paid','success']) ? 'status-success' : (in_array($s,['failed','cancelled']) ? 'status-danger' : 'status-warning') }}">
                            {{ strtoupper($order->status) }}
                        </span>
                    </td>
                    <td>{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick='vendorShowRowDetails("Order {{ $order->order_id }}", {id: {{ $order->id }}, order_id: "{{ $order->order_id }}", amount: "{{ $order->amount }}", status: "{{ $order->status }}", created_at: "{{ optional($order->created_at)->format("d M Y H:i") }}"})'>
                            View
                        </button>
                    </td>
                </tr>
            @empty
                @forelse(($linksWithoutOrders ?? []) as $plink)
                    <tr>
                        <td>PL-{{ $plink->id }}</td>
                        <td>INR {{ number_format((float)$plink->amount, 2) }}</td>
                        <td><span class="status-badge status-pending">PENDING</span></td>
                        <td>{{ optional($plink->created_at)->format('d M Y H:i') }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick='vendorShowRowDetails("Pending Order Track (Link #{{ $plink->id }})", {payment_link_id: {{ $plink->id }}, title: "{{ $plink->title }}", amount: "{{ $plink->amount }}", status: "PENDING", note: "Link created but payment order not generated yet."})'>
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No orders found</td></tr>
                @endforelse
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection

