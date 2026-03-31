<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor Dashboard') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin: 0; background: #f5f6fa; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #1c2333; color: #cfd6e6; padding: 16px 12px; }
        .brand { display: flex; align-items: center; gap: 8px; color: #fff; font-weight: 700; margin-bottom: 14px; }
        .mode-pill { background: #f0b90b; color: #1c2333; font-weight: 700; border-radius: 999px; padding: 4px 10px; font-size: 11px; display: inline-block; margin-bottom: 16px; }
        .menu-item { color: #cfd6e6; padding: 10px 12px; border-radius: 8px; display: block; text-decoration: none; margin-bottom: 6px; }
        .menu-item.active, .menu-item:hover { background: #2a3348; color: #fff; }
        .main { flex: 1; }
        .topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 10px 18px; display: flex; justify-content: space-between; align-items: center; }
        .top-title { font-size: 22px; font-weight: 700; color: #be1616; }
        .stat-card { background: #fff; border: 1px solid #e7e8ee; border-radius: 12px; padding: 14px; }
        .metric-card { min-height: 118px; padding: 18px 18px 16px 18px; box-shadow: 0 4px 18px rgba(16, 24, 40, 0.08); border-top: 3px solid #be1616; }
        .metric-label { font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.2px; }
        .metric-value { font-size: 26px; line-height: 1; font-weight: 700; color: #111827; }
        .metric-value-amount { font-size: 20px; line-height: 1.1; font-weight: 700; color: #111827; }
        .status-badge { border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.2px; text-transform: uppercase; }
        .status-pending { background: #fff7d6; color: #7a5a00; border: 1px solid #f3d77a; }
        .status-paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-failed { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-neutral { background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }
        .status-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-warning { background: #fff7d6; color: #7a5a00; border: 1px solid #f3d77a; }
        .status-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .bell-btn { border: 1px solid #e5e7eb; background: #fff; border-radius: 10px; padding: 6px 10px; position: relative; }
        .bell-dot { position: absolute; top: -4px; right: -4px; background: #ef4444; color: #fff; font-size: 10px; line-height: 1; border-radius: 999px; padding: 4px 5px; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset(logo_path()) }}" alt="logo" style="height:20px;">
            <span>{{ config('app.name') }}</span>
        </div>
        <div class="mode-pill">VENDOR PORTAL</div>
        <a class="menu-item {{ ($activeTab ?? '') === 'dashboard' ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</a>
        <a class="menu-item {{ ($activeTab ?? '') === 'payment-links' ? 'active' : '' }}" href="{{ route('vendor.payment-links') }}"><i class="bi bi-link-45deg me-2"></i>Payment Links</a>
        <a class="menu-item {{ ($activeTab ?? '') === 'orders' ? 'active' : '' }}" href="{{ route('vendor.orders') }}"><i class="bi bi-receipt me-2"></i>Orders</a>
        <a class="menu-item {{ ($activeTab ?? '') === 'refunds' ? 'active' : '' }}" href="{{ route('vendor.refunds') }}"><i class="bi bi-arrow-counterclockwise me-2"></i>Refunds</a>
        <a class="menu-item {{ ($activeTab ?? '') === 'settlements' ? 'active' : '' }}" href="{{ route('vendor.settlements') }}"><i class="bi bi-bank me-2"></i>Settlements</a>
    </aside>
    <main class="main">
        <div class="topbar">
            <div class="top-title">@yield('heading', 'Vendor Dashboard')</div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="bell-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        @if(!empty($vendorNotifications) && count($vendorNotifications) > 0)
                            <span class="bell-dot">{{ count($vendorNotifications) }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px;">
                        @forelse(($vendorNotifications ?? []) as $n)
                            <li class="px-3 py-2 small">
                                <span class="status-badge {{ $n['type'] === 'danger' ? 'status-danger' : ($n['type'] === 'warning' ? 'status-warning' : ($n['type'] === 'success' ? 'status-success' : 'status-info')) }}">
                                    {{ strtoupper($n['type']) }}
                                </span>
                                <div class="mt-1">{{ $n['text'] }}</div>
                            </li>
                        @empty
                            <li class="px-3 py-2 small text-muted">No notifications</li>
                        @endforelse
                    </ul>
                </div>
                <span class="small text-muted">{{ $vendor->vendor_name }} ({{ $vendor->vendor_code }})</span>
                <form method="POST" action="{{ route('vendor.logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Logout</button>
                </form>
            </div>
        </div>
        <div class="p-3 p-md-4">
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Payment Links</div><div class="metric-value">{{ $paymentLinksCount ?? 0 }}</div></div></div>
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Orders</div><div class="metric-value">{{ $ordersCount ?? 0 }}</div></div></div>
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Refunds</div><div class="metric-value">{{ $refundsCount ?? 0 }}</div></div></div>
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Collected</div><div class="metric-value-amount">INR {{ number_format((float)($collectedAmount ?? 0), 2) }}</div></div></div>
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Refund Amount</div><div class="metric-value-amount">INR {{ number_format((float)($refundAmount ?? 0), 2) }}</div></div></div>
                <div class="col-md-6 col-lg-2"><div class="stat-card metric-card"><div class="metric-label">Balance</div><div class="metric-value-amount">INR {{ number_format((float)($balanceAmount ?? 0), 2) }}</div></div></div>
            </div>
            @yield('content')
        </div>
    </main>
</div>

<div class="modal fade" id="vendorRowViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorRowViewTitle">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="vendorRowViewBody"></div>
            </div>
        </div>
    </div>
</div>
<script>
    function vendorShowRowDetails(title, dataObj) {
        var titleEl = document.getElementById('vendorRowViewTitle');
        var bodyEl = document.getElementById('vendorRowViewBody');
        if (titleEl) titleEl.textContent = title || 'Details';
        if (bodyEl) {
            var rows = '';
            var obj = dataObj || {};
            Object.keys(obj).forEach(function (key) {
                var label = key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                var value = (obj[key] === null || obj[key] === undefined || obj[key] === '') ? '-' : String(obj[key]);
                rows += '<tr><th class="text-muted" style="width:35%;">' + label + '</th><td>' + value + '</td></tr>';
            });
            bodyEl.innerHTML = '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>' + rows + '</tbody></table></div>';
        }
        var modalEl = document.getElementById('vendorRowViewModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

