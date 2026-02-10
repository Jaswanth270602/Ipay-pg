<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' - Payment Gateway')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-violet: #6366f1;
            --primary-violet-dark: #4f46e5;
            --primary-violet-light: #818cf8;
            --gradient-start: #0f172a;
            --gradient-end: #1e293b;
            --sidebar-bg: #6366f1;
            --sidebar-hover: rgba(99, 102, 241, 0.2);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1f2937;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: var(--sidebar-bg);
            color: #fff;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease, width 0.3s ease;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .sidebar.collapsed {
            width: 70px !important;
        }
        
        .sidebar.collapsed .sidebar-brand .brand-name,
        .sidebar.collapsed .sidebar-menu-item span,
        .sidebar.collapsed .mode-badge {
            display: none !important;
        }
        
        .sidebar.collapsed .sidebar-menu-item {
            justify-content: center !important;
            padding: 12px 20px !important;
        }
        
        .sidebar.collapsed .sidebar-menu-item i {
            margin-right: 0 !important;
        }

        .sidebar.collapsed .sidebar-brand .brand-name,
        .sidebar.collapsed .sidebar-menu-item span,
        .sidebar.collapsed .mode-badge {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu-item {
            justify-content: center;
            padding: 12px 20px;
        }

        .sidebar.collapsed .sidebar-menu-item i {
            margin-right: 0;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.1) !important;
            border: none !important;
            color: #fff !important;
            width: 36px !important;
            height: 36px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            position: absolute !important;
            top: 16px !important;
            right: 16px !important;
            z-index: 1001 !important;
            font-size: 18px !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar.collapsed .sidebar-toggle {
            margin-left: 0;
            right: 19px;
        }

        .sidebar-header-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .sidebar-brand {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .sidebar-brand img {
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .sidebar-brand .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed .sidebar-brand {
            justify-content: center;
            margin-bottom: 8px;
        }
        
        .sidebar.collapsed .sidebar-brand .brand-name {
            display: none;
        }

        .sidebar-brand i {
            background: linear-gradient(135deg, var(--primary-violet-light) 0%, var(--primary-violet) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .mode-badge {
            margin-top: 8px;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            padding: 12px 0;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .sidebar-menu-item i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu-item:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #fff;
            border-left: 3px solid rgba(129, 140, 248, 0.5);
            padding-left: 24px;
            transition: all 0.2s ease;
        }

        .sidebar-menu-item.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0.15) 100%);
            color: #fff;
            border-left: 3px solid var(--primary-violet);
            font-weight: 600;
            box-shadow: inset 0 0 20px rgba(99, 102, 241, 0.1);
        }

        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 12px 20px;
        }

        .sidebar-menu-dropdown {
            cursor: pointer;
        }

        .sidebar-submenu {
            background: rgba(15, 23, 42, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .sidebar-submenu-item {
            font-size: 14px;
            padding: 10px 20px 10px 50px !important;
        }

        .sidebar-submenu-item:hover {
            background: rgba(99, 102, 241, 0.2);
        }

        .sidebar-menu-dropdown.active .bi-chevron-down {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
        }

        /* Top Bar */
        .topbar {
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hamburger-menu {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: none;
        }

        .hamburger-menu:hover {
            background: #f3f4f6;
            color: var(--primary-violet);
        }

        @media (max-width: 768px) {
            .hamburger-menu {
                display: block !important;
            }
            
            .sidebar-toggle {
                display: flex !important;
            }
        }
        
        @media (min-width: 769px) {
            .sidebar-toggle {
                display: flex !important;
                visibility: visible !important;
            }
            
            .hamburger-menu {
                display: none !important;
            }
            
            .sidebar {
                transform: translateX(0) !important;
                left: 0 !important;
            }
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Mode Toggle */
        .mode-toggle {
            display: flex;
            background: #f3f4f6;
            border-radius: 8px;
            padding: 4px;
            gap: 4px;
        }

        .mode-toggle-btn {
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            color: #6b7280;
        }

        .mode-toggle-btn.active.test {
            background: #fef3c7;
            color: #d97706;
        }

        .mode-toggle-btn.active.live {
            background: #d1fae5;
            color: #059669;
        }

        /* Content Area */
        .content-wrapper {
            padding: 32px;
        }

        /* Cards */
        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: var(--card-shadow-lg);
            transform: translateY(-2px);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-violet) 0%, var(--primary-violet-dark) 100%);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-violet-dark) 0%, var(--primary-violet) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(118, 75, 162, 0.4);
        }

        /* Loader */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner-violet {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top-color: var(--primary-violet);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 24px;
        }

        .page-link {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            background: var(--primary-violet);
            color: #fff;
            border-color: var(--primary-violet);
        }

        .page-link.active {
            background: var(--primary-violet);
            color: #fff;
            border-color: var(--primary-violet);
        }

        /* Tables */
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #6b7280;
            border: none;
            padding: 16px;
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 9999;
            }
            
            .sidebar.show {
                transform: translateX(0);
                z-index: 9999;
            }

            .main-content {
                margin-left: 0;
            }
            
            /* Overlay when sidebar is open on mobile */
            .sidebar.show::after {
                content: '';
                position: fixed;
                top: 0;
                left: 260px;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
            }

            .topbar {
                padding: 0 16px;
            }
            
            .topbar-title {
                font-size: 18px;
            }

            .content-wrapper {
                padding: 16px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .row.g-4 {
                --bs-gutter-y: 1rem;
            }
            
            /* Mobile menu toggle */
            .mobile-menu-toggle {
                display: block;
                background: none;
                border: none;
                color: #fff;
                font-size: 24px;
                cursor: pointer;
                padding: 8px;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu-toggle {
                display: none;
            }
        }
        
        /* Responsive tables */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 14px;
            }
            
            .table thead th,
            .table tbody td {
                padding: 8px 4px;
            }
        }

        /* Toast Notifications */
        .toast-container {
            z-index: 1055;
        }

        .toast {
            border-radius: 12px;
            box-shadow: var(--card-shadow-lg);
        }

        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
        }

        .profile-dropdown-toggle {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            transition: all 0.2s ease;
        }

        .profile-dropdown-toggle:hover {
            background: #f9fafb;
            border-color: var(--primary-violet-light);
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-violet) 0%, var(--primary-violet-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .profile-name {
            font-weight: 500;
            font-size: 14px;
            color: #1f2937;
        }

        .profile-dropdown-menu {
            min-width: 280px;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            margin-top: 8px;
            overflow: hidden;
        }

        .profile-dropdown-header {
            padding: 20px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-avatar-large {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-violet) 0%, var(--primary-violet-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }

        .merchant-id-badge {
            margin-top: 12px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            font-size: 13px;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .merchant-id-badge i {
            color: var(--primary-violet);
            margin-right: 6px;
        }

        .merchant-id-badge strong {
            color: #1f2937;
            font-weight: 600;
        }

        .profile-menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #374151;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .profile-menu-item i {
            width: 20px;
            text-align: center;
            color: #6b7280;
        }

        .profile-menu-item:hover {
            background: #f9fafb;
            color: var(--primary-violet);
        }

        .profile-menu-item:hover i {
            color: var(--primary-violet);
        }

        .logout-item {
            color: #dc2626 !important;
        }

        .logout-item:hover {
            background: #fef2f2 !important;
            color: #dc2626 !important;
        }

        .logout-item i {
            color: #dc2626 !important;
        }
    </style>
    @stack('styles')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.3/angular.min.js"></script>
    <script>
    // Initialize Angular module immediately after Angular loads
    (function() {
        // Wait for Angular to load
        function initModule() {
            if (typeof angular !== 'undefined') {
                // Create module if it doesn't exist
                try {
                    angular.module('badlicashApp');
                } catch(e) {
                    angular.module('badlicashApp', []);
                }
            } else {
                setTimeout(initModule, 10);
            }
        }
        initModule();
    })();
    </script>
</head>
<body>
@auth
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-header-content">
        <div class="sidebar-brand">
            <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}" style="height:42px; width:auto;">
            <span class="brand-name">{{ config('app.name') }}</span>
        </div>
            @if(auth()->user()->merchant)
                <div class="mode-badge {{ auth()->user()->merchant->test_mode ? 'bg-warning text-dark' : 'bg-success' }}">
                    {{ auth()->user()->merchant->test_mode ? 'TEST MODE' : 'LIVE MODE' }}
                </div>
            @elseif(auth()->user()->isAdmin())
                <div class="mode-badge {{ session('admin_view_mode', 'test') === 'test' ? 'bg-warning text-dark' : 'bg-success' }}" id="adminModeBadge">
                    {{ session('admin_view_mode', 'test') === 'test' ? 'TEST MODE' : 'LIVE MODE' }}
                </div>
            @endif
        </div>
       
        <button type="button" class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar" style="cursor: pointer;">
            <i class="bi bi-list" id="sidebarToggleIcon"></i>
        </button>
    </div>
    
    <nav class="sidebar-menu">
        @if(auth()->user()->isMerchant())
        <a href="{{ route('dashboard') }}" class="sidebar-menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>
        @endif
        
        @if(auth()->user()->isMerchant())
        <!-- Payments Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('merchant.payments.*') || request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.refunds.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-wallet2"></i>
            <span>Payments</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('merchant.payments.*') || request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.refunds.*') ? 'block' : 'none' }};">
            <a href="{{ route('merchant.transactions.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.transactions.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-credit-card"></i>
                <span>Transactions</span>
            </a>
            <a href="{{ route('merchant.refunds.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.refunds.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Refunds</span>
            </a>
            <a href="{{ route('merchant.payments.bulk-refund-update') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.payments.bulk-refund-update*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-upload"></i>
                <span>Bulk Update Refund Status</span>
            </a>
            <a href="{{ route('merchant.payments.chargebacks') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.payments.chargebacks*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Chargebacks Upload</span>
            </a>
            <a href="{{ route('merchant.payments.bulk-chargebacks') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.payments.bulk-chargebacks*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-cloud-upload"></i>
                <span>Bulk Chargebacks Upload</span>
            </a>
            <a href="{{ route('merchant.payments.split-transactions') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.payments.split-transactions*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-diagram-3"></i>
                <span>Split Transactions</span>
            </a>
            <a href="{{ route('merchant.payments.federal-vpa') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.payments.federal-vpa*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-bank"></i>
                <span>Federal Direct VPA Payments</span>
            </a>
        </div>
        
        <a href="{{ route('merchant.orders.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.orders.*') ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Orders</span>
        </a>
        <a href="{{ route('merchant.payment_links.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.payment_links.*') ? 'active' : '' }}">
            <i class="bi bi-link-45deg"></i>
            <span>Payment Links</span>
        </a>
        <a href="{{ route('merchant.subscriptions.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.subscriptions.*') || request()->routeIs('merchant.plans.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i>
            <span>Subscriptions</span>
        </a>
        <!-- Settlements Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('merchant.settlements.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-file-earmark-text"></i>
            <span>Settlements</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('merchant.settlements.*') ? 'block' : 'none' }};">
            <a href="{{ route('merchant.settlements.summary') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.settlements.summary*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-ul"></i>
                <span>Settlement Summary</span>
            </a>
            <a href="{{ route('merchant.settlements.details') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('merchant.settlements.details*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-check"></i>
                <span>Settlement Details</span>
            </a>
        </div>
        <div class="sidebar-divider"></div>
        <a href="{{ route('merchant.api_keys.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.api_keys.*') ? 'active' : '' }}">
            <i class="bi bi-key"></i>
            <span>API Keys</span>
        </a>
        <a href="{{ route('merchant.integration.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.integration.*') ? 'active' : '' }}">
            <i class="bi bi-code-square"></i>
            <span>Integration</span>
        </a>
        <a href="{{ route('merchant.webhooks.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.webhooks.*') ? 'active' : '' }}">
            <i class="bi bi-webhook"></i>
            <span>Webhooks</span>
        </a>
        <a href="{{ route('merchant.reports.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.reports.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line"></i>
            <span>Reports</span>
        </a>
        <a href="{{ route('merchant.disputes.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.disputes.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-octagon"></i>
            <span>Disputes</span>
        </a>
        <div class="sidebar-divider"></div>
        <a href="{{ route('merchant.settings.index') }}" class="sidebar-menu-item {{ request()->routeIs('merchant.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <div class="sidebar-divider"></div>
        <a href="{{ route('admin.dashboard') }}" class="sidebar-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i>
            <span>Admin Dashboard</span>
        </a>
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.merchants.*') || request()->routeIs('admin.merchant-accounts.*') || request()->routeIs('admin.merchant-registration-keys.*') || request()->routeIs('admin.merchant-vendors.*') || request()->routeIs('admin.base-rates.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-building"></i>
            <span>Merchants</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.merchants.*') || request()->routeIs('admin.merchant-accounts.*') || request()->routeIs('admin.merchant-registration-keys.*') || request()->routeIs('admin.merchant-vendors.*') || request()->routeIs('admin.base-rates.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.merchant-accounts.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.merchant-accounts.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-person-badge"></i>
                <span>Merchant Accounts</span>
            </a>
            <a href="{{ route('admin.merchant-registration-keys.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.merchant-registration-keys.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-key"></i>
                <span>Merchant Registration Keys</span>
            </a>
            <a href="{{ route('admin.merchant-vendors.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.merchant-vendors.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-people"></i>
                <span>Merchant Vendors</span>
            </a>
            <a href="{{ route('admin.base-rates.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.base-rates.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-percent"></i>
                <span>Base Rates</span>
            </a>
        </div>
        <!-- Partners Management Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-people"></i>
            <span>Partners Management</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.partners.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.partners.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.partners.index') || request()->routeIs('admin.partners.data') || request()->routeIs('admin.partners.store') || request()->routeIs('admin.partners.update') || request()->routeIs('admin.partners.destroy') || request()->routeIs('admin.partners.show') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-ul"></i>
                <span>Partner Details</span>
            </a>
            <a href="{{ route('admin.partners.tdr') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.partners.tdr*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-file-earmark-text"></i>
                <span>Partner TDR Details</span>
            </a>
        </div>
        <!-- Partner Settlements Dropdown - Separate Module -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.partner-settlements.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-handshake"></i>
            <span>Partner Settlements</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.partner-settlements.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.partner-settlements.summary') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.partner-settlements.summary') || request()->routeIs('admin.partner-settlements.data') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-ul"></i>
                <span>Partner Settlement Summary</span>
            </a>
            <a href="{{ route('admin.partner-settlements.details') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.partner-settlements.details') || request()->routeIs('admin.partner-settlements.details.data') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-file-earmark-text"></i>
                <span>Partner Settlement Details</span>
            </a>
        </div>
        <!-- Payments Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-wallet2"></i>
            <span>Payments</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.payments.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.payments.transactions') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.transactions*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-credit-card"></i>
                <span>Transactions</span>
            </a>
            <a href="{{ route('admin.payments.refunds') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.refunds*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Refunds</span>
            </a>
            <a href="{{ route('admin.payments.bulk-refund-update') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.bulk-refund-update*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-upload"></i>
                <span>Bulk Update Refund Status</span>
            </a>
            <a href="{{ route('admin.payments.chargebacks') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.chargebacks*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Chargebacks Upload</span>
            </a>
            <a href="{{ route('admin.payments.bulk-chargebacks') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.bulk-chargebacks*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-cloud-upload"></i>
                <span>Bulk Chargebacks Upload</span>
            </a>
            <a href="{{ route('admin.payments.split-transactions') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.split-transactions*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-diagram-3"></i>
                <span>Split Transactions</span>
            </a>
            <a href="{{ route('admin.payments.federal-vpa') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.payments.federal-vpa*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-bank"></i>
                <span>Federal Direct VPA Payments</span>
            </a>
        </div>

        <!-- Settlements Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.settlements.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-file-earmark-text"></i>
            <span>Settlements</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.settlements.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.settlements.summary') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.settlements.summary*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-ul"></i>
                <span>Settlement Summary</span>
            </a>
            <a href="{{ route('admin.settlements.details') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.settlements.details*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list-check"></i>
                <span>Settlement Details</span>
            </a>
            <a href="{{ route('admin.settlements.fund-transfer') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.settlements.fund-transfer*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-arrow-left-right"></i>
                <span>Fund Transfer</span>
            </a>
        </div>

        <!-- Manage Settlements Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.manage-settlements.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-gear"></i>
            <span>Manage Settlements</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.manage-settlements.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.manage-settlements.pending') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.manage-settlements.pending*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-clock-history"></i>
                <span>Pending Settlement</span>
            </a>
            <a href="{{ route('admin.manage-settlements.mis-report') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.manage-settlements.mis-report*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-download"></i>
                <span>Download MIS Report</span>
            </a>
        </div>

        <a href="{{ route('admin.orders.index') }}" class="sidebar-menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
            <i class="bi bi-cart-check"></i>
            <span>All Orders</span>
        </a>
        <!-- Reports Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Reports</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.reports.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.index') && !request()->routeIs('admin.reports.gst-invoices.*') && !request()->routeIs('admin.reports.success-rate.*') && !request()->routeIs('admin.reports.profitability.*') && !request()->routeIs('admin.reports.sales.*') && !request()->routeIs('admin.reports.datatable-exports.*') && !request()->routeIs('admin.reports.miscellaneous.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-bar-chart"></i>
                <span>Reports</span>
            </a>
            <!-- GST Invoices Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.gst-invoices.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-receipt"></i>
                <span>Gst Invoices</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.gst-invoices.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.gst-invoices.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.gst-invoices.*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Gst Invoices Report</span>
                </a>
            </div>
            <!-- Success Rate Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.success-rate.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-graph-up"></i>
                <span>Success Rate</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.success-rate.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.success-rate.bankcode-wise') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.success-rate.bankcode-wise*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-bank"></i>
                    <span>Bankcode-wise</span>
                </a>
            </div>
            <!-- Profitability Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.profitability.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-bar-chart"></i>
                <span>Profitability</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.profitability.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.profitability.partner-team-profit') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.profitability.partner-team-profit*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-people"></i>
                    <span>Partner Team Profit</span>
                </a>
            </div>
            <!-- Sales Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.sales.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-bar-chart-line"></i>
                <span>Sales</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.sales.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.sales.date-and-merchant') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.date-and-merchant*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Date And Merchant</span>
                </a>
                <a href="{{ route('admin.reports.sales.date-and-acquirer') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.date-and-acquirer*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Date And Acquirer</span>
                </a>
                <a href="{{ route('admin.reports.sales.date-and-tid') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.date-and-tid*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Date And Tid</span>
                </a>
                <a href="{{ route('admin.reports.sales.month-and-merchant') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.month-and-merchant*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Month And Merchant</span>
                </a>
                <a href="{{ route('admin.reports.sales.month-and-acquirer') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.month-and-acquirer*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Month And Acquirer</span>
                </a>
                <a href="{{ route('admin.reports.sales.month-and-tid') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.sales.month-and-tid*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-square"></i>
                    <span>Month And Tid</span>
                </a>
            </div>
            <!-- Datatable Exports Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.datatable-exports.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-cloud-download"></i>
                <span>Datatable Exports</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.datatable-exports.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.datatable-exports.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.datatable-exports.*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Datatable Export List</span>
                </a>
            </div>
            <!-- Miscellaneous Submenu -->
            <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.reports.miscellaneous.*') ? 'active' : '' }}" onclick="event.stopPropagation(); toggleDropdown(this);" style="padding-left: 50px;">
                <i class="bi bi-clock"></i>
                <span>Miscellaneous</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
            </div>
            <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.reports.miscellaneous.*') ? 'block' : 'none' }}; padding-left: 50px;">
                <a href="{{ route('admin.reports.miscellaneous.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.reports.miscellaneous.*') ? 'active' : '' }}" style="padding-left: 50px;">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Adhoc Reports</span>
                </a>
            </div>
        </div>
        <a href="{{ route('admin.subscriptions.index') }}" class="sidebar-menu-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i>
            <span>Subscriptions</span>
        </a>
        <a href="{{ route('admin.disputes.index') }}" class="sidebar-menu-item {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-octagon"></i>
            <span>Disputes</span>
        </a>
        <a href="{{ route('admin.risk.index') }}" class="sidebar-menu-item {{ request()->routeIs('admin.risk.*') ? 'active' : '' }}">
            <i class="bi bi-shield-exclamation"></i>
            <span>Risk Management</span>
        </a>

        <!-- Technical Diagnostics Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.s2s-callback-logs.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-tools"></i>
            <span>Technical Diagnostics</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.s2s-callback-logs.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.s2s-callback-logs.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.s2s-callback-logs.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list"></i>
                <span>S2S Callback Logs</span>
            </a>
        </div>

        <!-- Approvals Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-check-circle"></i>
            <span>Approvals</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.approvals.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.approvals.merchant-tdr') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.approvals.merchant-tdr*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list"></i>
                <span>Merchant TDR</span>
            </a>
            <a href="{{ route('admin.approvals.pg-refunds') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.approvals.pg-refunds*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-list"></i>
                <span>Pg Refunds</span>
            </a>
        </div>

        <!-- Acquirer Details Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.acquirer.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-credit-card-2-front"></i>
            <span>Acquirer Details</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.acquirer.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.acquirer.accounts.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.acquirer.accounts.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-bank"></i>
                <span>Acquirer Accounts</span>
            </a>
            <a href="{{ route('admin.acquirer.detail-upload.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.acquirer.detail-upload.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-upload"></i>
                <span>Acquirer Accounts Detail Upload</span>
            </a>
            <a href="{{ route('admin.acquirer.rates.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.acquirer.rates.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-percent"></i>
                <span>Acquirer Rates</span>
            </a>
        </div>

        <!-- User Settings Dropdown -->
        <div class="sidebar-menu-item sidebar-menu-dropdown {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}" onclick="toggleDropdown(this)">
            <i class="bi bi-people"></i>
            <span>User Settings</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size: 12px;"></i>
        </div>
        <div class="sidebar-submenu" style="display: {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'block' : 'none' }};">
            <a href="{{ route('admin.users.index') }}" class="sidebar-menu-item sidebar-submenu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" style="padding-left: 50px;">
                <i class="bi bi-person"></i>
                <span>Users</span>
            </a>
            <a href="#" class="sidebar-menu-item sidebar-submenu-item" style="padding-left: 50px;">
                <i class="bi bi-person-badge"></i>
                <span>Roles</span>
            </a>
            <a href="#" class="sidebar-menu-item sidebar-submenu-item" style="padding-left: 50px;">
                <i class="bi bi-shield-check"></i>
                <span>Permissions</span>
            </a>
        </div>
        @endif
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button type="button" class="hamburger-menu" title="Toggle Sidebar" style="cursor: pointer;">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        </div>
        <div class="topbar-actions">
            @if(auth()->user()->merchant)
            <div class="mode-toggle">
                <button class="mode-toggle-btn {{ auth()->user()->merchant->test_mode ? 'active test' : '' }}" onclick="switchMode('test')">
                    <i class="bi bi-flask"></i> Test
                </button>
                <button class="mode-toggle-btn {{ !auth()->user()->merchant->test_mode ? 'active live' : '' }}" onclick="switchMode('live')">
                    <i class="bi bi-check-circle"></i> Live
                </button>
            </div>
            @elseif(auth()->user()->isAdmin())
            <div class="mode-toggle">
                <button class="mode-toggle-btn {{ session('admin_view_mode', 'test') === 'test' ? 'active test' : '' }}" onclick="switchAdminMode('test')">
                    <i class="bi bi-flask"></i> Test
                </button>
                <button class="mode-toggle-btn {{ session('admin_view_mode', 'test') === 'live' ? 'active live' : '' }}" onclick="switchAdminMode('live')">
                    <i class="bi bi-check-circle"></i> Live
                </button>
            </div>
            @endif
            
            @if(auth()->user()->merchant)
            <!-- Merchant Profile Dropdown -->
            <div class="dropdown profile-dropdown">
                <button class="btn btn-link text-decoration-none d-flex align-items-center gap-2 profile-dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #1f2937; padding: 8px 12px;">
                    <div class="profile-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="profile-name">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down" style="font-size: 12px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end profile-dropdown-menu" aria-labelledby="profileDropdown">
                    <li class="profile-dropdown-header">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="profile-avatar-large">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ auth()->user()->name }}</div>
                                <div class="small text-muted">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <div class="merchant-id-badge">
                            <i class="bi bi-building"></i> Merchant ID: <strong>{{ auth()->user()->merchant->id }}</strong>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item profile-menu-item" href="{{ route('merchant.profile.index') }}">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                            @csrf
                            <button type="submit" class="dropdown-item profile-menu-item logout-item">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @elseif(auth()->user()->isAdmin())
            <!-- Admin Profile Dropdown -->
            <div class="dropdown profile-dropdown">
                <button class="btn btn-link text-decoration-none d-flex align-items-center gap-2 profile-dropdown-toggle" type="button" id="adminProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #1f2937; padding: 8px 12px;">
                    <div class="profile-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="profile-name">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down" style="font-size: 12px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end profile-dropdown-menu" aria-labelledby="adminProfileDropdown">
                    <li class="profile-dropdown-header">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="profile-avatar-large">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ auth()->user()->name }}</div>
                                <div class="small text-muted">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <div class="merchant-id-badge">
                            <i class="bi bi-shield-check"></i> Admin User
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item profile-menu-item" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                            @csrf
                            <button type="submit" class="dropdown-item profile-menu-item logout-item">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </div>

    <div class="content-wrapper">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>
</div>
@else
<div class="container py-4">
    @yield('content')
</div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDropdown(element) {
    const submenu = element.nextElementSibling;
    if (submenu && submenu.classList.contains('sidebar-submenu')) {
        const isVisible = submenu.style.display === 'block';
        submenu.style.display = isVisible ? 'none' : 'block';
        element.classList.toggle('active');
    }
}

function switchMode(mode) {
    const overlay = document.createElement('div');
    overlay.className = 'loader-overlay';
    overlay.innerHTML = '<div class="spinner-violet"></div>';
    document.body.appendChild(overlay);

    fetch("{{ route('merchant.settings.switch-mode') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({mode})
    })
    .then(response => response.json())
    .then(data => {
        document.body.removeChild(overlay);
        if (data.success) {
            location.reload();
        } else {
            if (typeof showToast === 'function') {
                showToast('Failed to switch mode', 'error');
            } else {
                if (typeof showToast === 'function') {
            showToast('Failed to switch mode', 'error');
        } else {
            alert('Failed to switch mode');
        }
            }
        }
    })
    .catch(error => {
        document.body.removeChild(overlay);
        if (typeof showToast === 'function') {
            showToast('Failed to switch mode', 'error');
        } else {
            alert('Failed to switch mode');
        }
        console.error('Error:', error);
    });
}

function switchAdminMode(mode) {
    const overlay = document.createElement('div');
    overlay.className = 'loader-overlay';
    overlay.innerHTML = '<div class="spinner-violet"></div>';
    document.body.appendChild(overlay);

    fetch("{{ route('admin.settings.switch-mode') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({mode})
    })
    .then(response => response.json())
    .then(data => {
        document.body.removeChild(overlay);
        if (data.success) {
            location.reload();
        } else {
            if (typeof showToast === 'function') {
                showToast('Failed to switch admin viewing mode', 'error');
            } else {
                if (typeof showToast === 'function') {
            showToast('Failed to switch admin viewing mode', 'error');
        } else {
            alert('Failed to switch admin viewing mode');
        }
            }
        }
    })
    .catch(error => {
        document.body.removeChild(overlay);
        if (typeof showToast === 'function') {
            showToast('Failed to switch admin viewing mode', 'error');
        } else {
            alert('Failed to switch admin viewing mode');
        }
        console.error('Error:', error);
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = document.getElementById('sidebarToggleIcon');
    
    if (!sidebar) {
        console.error('Sidebar element not found');
        return;
    }
    
    if (window.innerWidth <= 768) {
        // Mobile: show/hide sidebar
        const isShowing = sidebar.classList.contains('show');
        if (isShowing) {
            sidebar.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    } else {
        // Desktop: collapse/expand sidebar
        sidebar.classList.toggle('collapsed');
        if (toggleIcon) {
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.className = 'bi bi-justify';
            } else {
                toggleIcon.className = 'bi bi-list';
            }
        }
        // Save preference to localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

// Make function globally accessible
window.toggleSidebar = toggleSidebar;

// Restore sidebar state on page load and setup event listeners
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth > 768) {
        const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (collapsed) {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Add event listeners as backup to onclick handlers
    const sidebarToggle = document.getElementById('sidebarToggle');
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.toggleSidebar();
        }, true);
    }
    
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.toggleSidebar();
        }, true);
    }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.querySelector('.hamburger-menu');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
        if (!sidebar.contains(event.target) && 
            !hamburger?.contains(event.target) && 
            !sidebarToggle?.contains(event.target)) {
            sidebar.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            // Restore collapsed state
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (collapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        } else {
            sidebar.classList.remove('collapsed');
        }
    }
});

// Close profile dropdown when clicking outside
document.addEventListener('click', function(event) {
    const profileDropdown = document.getElementById('profileDropdown');
    const dropdownMenu = document.querySelector('.profile-dropdown-menu');
    
    if (profileDropdown && dropdownMenu) {
        const isClickInside = profileDropdown.contains(event.target) || dropdownMenu.contains(event.target);
        if (!isClickInside && dropdownMenu.classList.contains('show')) {
            const bsDropdown = bootstrap.Dropdown.getInstance(profileDropdown);
            if (bsDropdown) {
                bsDropdown.hide();
            }
        }
    }
});
</script>

<!-- Global Toast Notification Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; margin-top: 80px;">
    <div id="globalToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 350px; max-width: 450px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); border: none;">
        <div class="toast-header d-flex align-items-center" 
             style="border-bottom: none; padding: 12px 16px; font-weight: 600; background-color: #10b981; color: white;">
            <i class="bi bi-check-circle-fill me-2" style="font-size: 18px;"></i>
            <strong class="me-auto" id="globalToastTitle">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close" style="opacity: 0.9;"></button>
        </div>
        <div class="toast-body" 
             style="padding: 14px 16px; font-weight: 500; font-size: 15px;">
            <!-- Content will be dynamically inserted by JavaScript -->
        </div>
    </div>
</div>

<!-- Global Toast Service -->
<script>
/**
 * Global Toast Notification Service
 * Usage: showToast('Message here', 'success'|'error'|'warning'|'info')
 */
(function() {
    'use strict';
    
    // Global toast function - accessible everywhere
    window.showToast = function(message, type) {
        type = type || 'success';
        
        var toastElement = document.getElementById('globalToast');
        if (!toastElement || !message) {
            // Fallback to alert if toast element doesn't exist
            alert(message);
            return;
        }
        
        // Hide any existing toast first
        var existingToast = bootstrap.Toast.getInstance(toastElement);
        if (existingToast) {
            existingToast.hide();
        }
        
        // Update toast header
        var toastHeader = toastElement.querySelector('.toast-header');
        var toastTitle = document.getElementById('globalToastTitle');
        var toastIcon = toastHeader ? toastHeader.querySelector('i') : null;
        
        if (toastHeader) {
            var config = {
                success: {
                    bgColor: '#10b981',
                    title: 'Success',
                    icon: 'bi-check-circle-fill'
                },
                error: {
                    bgColor: '#ef4444',
                    title: 'Error',
                    icon: 'bi-x-circle-fill'
                },
                warning: {
                    bgColor: '#f59e0b',
                    title: 'Warning',
                    icon: 'bi-exclamation-triangle-fill'
                },
                info: {
                    bgColor: '#3b82f6',
                    title: 'Info',
                    icon: 'bi-info-circle-fill'
                }
            };
            
            var toastConfig = config[type] || config.success;
            
            toastHeader.style.backgroundColor = toastConfig.bgColor;
            toastHeader.style.color = 'white';
            if (toastTitle) toastTitle.textContent = toastConfig.title;
            if (toastIcon) {
                toastIcon.className = 'bi ' + toastConfig.icon + ' me-2';
            }
        }
        
        // Update toast body
        var toastBody = toastElement.querySelector('.toast-body');
        if (toastBody) {
            toastBody.innerHTML = '';
            
            // Create icon
            var icon = document.createElement('i');
            var bodyIconClass = {
                success: 'bi-check-circle',
                error: 'bi-x-circle',
                warning: 'bi-exclamation-triangle',
                info: 'bi-info-circle'
            };
            icon.className = 'bi ' + (bodyIconClass[type] || bodyIconClass.success) + ' me-2';
            toastBody.appendChild(icon);
            
            // Add message
            var messageSpan = document.createElement('span');
            messageSpan.textContent = message;
            toastBody.appendChild(messageSpan);
            
            // Update body colors
            var bodyColors = {
                success: { bg: '#d1fae5', text: '#065f46' },
                error: { bg: '#fee2e2', text: '#991b1b' },
                warning: { bg: '#fef3c7', text: '#92400e' },
                info: { bg: '#dbeafe', text: '#1e40af' }
            };
            var bodyColor = bodyColors[type] || bodyColors.success;
            toastBody.style.backgroundColor = bodyColor.bg;
            toastBody.style.color = bodyColor.text;
        }
        
        // Create and show toast instance
        var toastInstance = bootstrap.Toast.getInstance(toastElement);
        if (!toastInstance) {
            toastInstance = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: type === 'error' ? 5000 : 4000
            });
        }
        
        toastInstance.show();
    };
    
    // Also make it available as a global function for AngularJS
    if (typeof angular !== 'undefined') {
        angular.module('badlicashApp').service('ToastService', function() {
            return {
                show: window.showToast
            };
        });
    }
})();
</script>

@stack('scripts')
</body>
</html>
 