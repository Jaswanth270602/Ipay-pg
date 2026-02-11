<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' – Payment Gateway')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    @stack('styles')
    <style>
        :root {
            --site-bg: #1A1D24;
            --site-bg-alt: #20242D;
            --site-surface: #262B36;
            --site-red: #E10600;
            --site-text-main: #FFFFFF;
            --site-text-muted: #9CA3AF;
        }
        body {
            background: var(--site-bg);
            color: var(--site-text-main);
        }
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            min-height: 110px;
            background: linear-gradient(90deg, #ffffff 0%, #fef9f9 25%, #fef2f2 50%, #fce8e8 75%, #fad5d5 100%);
            border-bottom: 1px solid rgba(225, 6, 0, 0.12);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            transition: box-shadow 0.3s ease;
        }
        .site-header.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .site-header .site-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
            width: 100%;
            min-height: 110px;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .site-header-brand-title {
            font-weight: 700;
            font-size: 18px;
            color: #1A1D24;
        }
        .site-header-brand-sub {
            font-size: 11px;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: .14em;
        }
        .site-nav a {
            color: #1A1D24;
            text-decoration: none;
            font-weight: 500;
        }
        .site-nav a:hover {
            color: var(--site-red);
        }
        .site-btn-login {
            border-radius: 999px;
            border: 1px solid var(--site-red);
            color: var(--site-red);
            background: transparent;
            font-size: 13px;
            padding-inline: 14px;
            font-weight: 500;
        }
        .site-btn-login:hover {
            background: rgba(225, 6, 0, 0.08);
            color: #b00500;
            border-color: #b00500;
        }
        .site-btn-signup {
            border-radius: 999px;
            border: none;
            background: var(--site-red);
            color: #ffffff;
            font-size: 13px;
            padding-inline: 16px;
            font-weight: 600;
        }
        .site-btn-signup:hover {
            filter: brightness(1.08);
            color: #ffffff;
        }
        .site-main {
            padding-top: 130px;
            padding-bottom: 48px;
            min-height: calc(100vh - 300px);
        }
        .site-footer {
            background: linear-gradient(180deg, #ffffff 0%, #fee2e2 35%, #fecaca 65%, #f87171 100%);
            border-top: 1px solid rgba(225, 6, 0, 0.18);
            color: #374151;
            padding-top: 0;
            margin-top: 64px;
        }
        .site-footer .site-footer-body {
            padding-top: 56px;
            padding-bottom: 40px;
        }
        .site-footer .site-footer-body a {
            color: #4b5563;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .site-footer .site-footer-body a:hover {
            color: var(--site-red);
        }
        .site-footer .site-footer-body .footer-heading {
            color: #1A1D24;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 14px;
            letter-spacing: 0.02em;
        }
        .site-footer .site-footer-body .footer-brand {
            color: #1A1D24;
            font-weight: 600;
        }
        .site-footer .site-footer-body ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .site-footer .site-footer-body ul li {
            margin-bottom: 10px;
            font-size: 13px;
            color: #4b5563;
        }
        .site-footer .site-footer-body .footer-address {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.7;
        }
        .site-footer .site-footer-bottom {
            background: transparent;
            border-top: 1px solid rgba(225, 6, 0, 0.15);
            color: #4b5563;
            padding: 24px 0 28px;
            margin-top: 0;
            border-radius: 0;
        }
        .site-footer .site-footer-bottom a {
            color: #4b5563;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .site-footer .site-footer-bottom a:hover {
            color: var(--site-red);
        }
        .site-footer-btn {
            border-radius: 8px;
            border: 1px solid rgba(225, 6, 0, 0.5);
            color: var(--site-red);
            background: transparent;
            font-size: 12px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .site-footer-btn:hover {
            background: rgba(225, 6, 0, 0.08);
            border-color: var(--site-red);
            color: #b00500;
        }
    </style>
</head>
<body>
    <header class="border-bottom site-header">
        <div class="container site-header-inner">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}" style="height: 34px; width:auto;">
                <div class="d-flex flex-column">
                    <span class="site-header-brand-title">{{ config('app.name') }}</span>
                    <span class="site-header-brand-sub">Modern payment gateway</span>
                </div>
            </div>
            <nav class="d-flex align-items-center gap-3 site-nav" style="font-size:13px;">
                <a href="{{ route('landing') }}">Home</a>
                <a href="{{ route('about.page') }}">About</a>
                <a href="{{ route('products.page') }}">Products</a>
                <a href="{{ route('contact.page') }}">Contact</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-sm site-btn-login">
                    <i class="bi bi-person-circle me-1"></i> Login
                </a>
                <a href="{{ route('signup') }}" class="btn btn-sm site-btn-signup">
                    Create account
                </a>
            </div>
        </div>
    </header>

    <main class="container site-main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container site-footer-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}" style="height: 28px; width:auto;">
                        <strong class="footer-brand">{{ config('app.name') }}</strong>
                    </div>
                    <div class="footer-address">
                        Bengaluru, Karnataka<br>
                        India
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="footer-heading">Compare</div>
                    <ul>
                        <li><a href="#">vs Legacy gateways</a></li>
                        <li><a href="#">vs Banks</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <div class="footer-heading">Company</div>
                    <ul>
                        <li><a href="{{ route('about.page') }}">About us</a></li>
                        <li><a href="{{ route('contact.page') }}">Contact us</a></li>
                        <li><a href="#">Blogs</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <div class="footer-heading">Follow us</div>
                    <ul>
                        <li><a href="#">LinkedIn</a></li>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">YouTube</a></li>
                        <li><a href="#">X / Twitter</a></li>
                        <li><a href="#">Reddit</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <div class="footer-heading">Community</div>
                    <p style="font-size:13px;color:var(--site-text-muted);margin-bottom:12px;line-height:1.5;">
                        Expert advice and stories, all in one place.
                    </p>
                    <button class="site-footer-btn" type="button">
                        Join Slack
                    </button>
                </div>
            </div>
        </div>
        <div class="site-footer-bottom">
            <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div style="font-size:12px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
                <div style="font-size:12px;" class="d-flex gap-3">
                    <a href="{{ route('privacy.page') }}">Privacy</a>
                    <a href="{{ route('terms.page') }}">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sticky header with scroll shadow
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.site-header');
            if (window.scrollY > 10) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>

