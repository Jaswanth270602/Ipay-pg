@extends('layouts.marketing')

@section('title', config('app.name') . ' – Modern Payment Gateway')

@section('content')
<style>
    :root {
        --pg-primary: #E10600;
        --pg-primary-dark: #B00500;
        --pg-accent-yellow: #E10600;
        --pg-accent-green: #E10600;
        --pg-surface: #20242D;
        --pg-bg: #1A1D24;
        --pg-surface-card: #262B36;
        --pg-text-main: #FFFFFF;
        --pg-text-muted: #9CA3AF;
        --pg-radius-xl: 26px;
    }

    body {
        background: var(--pg-bg);
        color: var(--pg-text-main);
    }

    .lp-hero {
        padding: 32px 0 64px;
    }

    .lp-hero-row {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
        gap: 40px;
        align-items: center;
    }

    .lp-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 12px 4px 6px;
        border-radius: 999px;
        background: rgba(225, 6, 0, 0.14);
        border: 1px solid rgba(225, 6, 0, 0.6);
        font-size: 11px;
        color: #fef2f2;
    }

    .lp-pill-dot {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #E10600;
        font-size: 10px;
    }

    .lp-hero-title {
        font-size: clamp(2.6rem, 3.8vw, 3.2rem);
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.05;
        color: var(--pg-text-main);
        margin-bottom: 14px;
    }

    .lp-hero-title span.lp-highlight {
        background: linear-gradient(135deg, #ffffff, #E10600);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .lp-hero-subtitle {
        font-size: 15px;
        color: var(--pg-text-muted);
        max-width: 520px;
    }

    .lp-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
    }

    .lp-btn-primary {
        background: linear-gradient(135deg, var(--pg-primary), var(--pg-primary-dark));
        border-radius: 999px;
        padding: 11px 26px;
        border: none;
        color: #ffffff;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 16px 32px rgba(225, 6, 0, 0.45);
        text-decoration: none;
        font-size: 14px;
    }

    .lp-btn-primary:hover {
        filter: brightness(1.02);
        transform: translateY(-1px);
    }

    .lp-btn-ghost {
        border-radius: 999px;
        padding: 10px 22px;
        border: 1px solid rgba(148, 163, 184, 0.6);
        background: transparent;
        color: #d11717;
        font-weight: 500;
        text-decoration: none;
        font-size: 14px;
    }

    .lp-hero-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 20px;
        font-size: 12px;
    }

    .lp-hero-metric-label {
        color: var(--pg-text-muted);
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .lp-hero-metric-value {
        font-weight: 700;
        color: var(--pg-text-main);
        font-size: 18px;
    }

    /* Hero visual – stacked payment cards (Unlimit-style) */
    .lp-hero-visual {
        position: relative;
        width: 420px;
        margin-left: auto;
    }

    .lp-card-stack {
        position: relative;
        width: 100%;
        max-width: 360px;
        margin: 0 auto;
    }

    .lp-card-bg {
        position: absolute;
        inset: 16px -10px -16px;
        border-radius: 28px;
        background: linear-gradient(140deg, #E10600, #7F0300);
        opacity: 0.9;
        transform: rotate(8deg);
    }

    .lp-card-main {
        position: relative;
        border-radius: 24px;
        background: #ffffff;
        padding: 18px 20px 16px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.45);
    }

    .lp-card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 11px;
        color: #6b7280;
    }

    .lp-card-amount {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
    }

    .lp-card-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        color: #6b7280;
        margin-top: 4px;
    }

    .lp-card-input {
        border-radius: 10px;
        background: #f3f4f6;
        padding: 6px 8px;
        font-size: 11px;
        color: #111827;
        margin-top: 4px;
    }

    .lp-card-pay-btn {
        margin-top: 12px;
        border-radius: 10px;
        padding: 10px 0;
        text-align: center;
        background: #E10600;
        font-weight: 600;
        font-size: 13px;
        color: #ffffff;
    }

    /* Additional sections */
    .lp-section {
        padding: 56px 0 64px;
    }

    .lp-section-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .lp-section-title {
        font-size: 26px;
        font-weight: 700;
        color:rgb(207, 17, 11);
        margin-bottom: 6px;
    }

    .lp-section-subtitle {
        font-size: 14px;
        color: var(--pg-text-muted);
        max-width: 620px;
        margin: 0 auto;
    }

    .lp-three-column {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 22px;
    }

    .lp-card-simple {
        background: #262B36;
        border-radius: 18px;
        padding: 18px 18px 16px;
        border: 1px solid #20242D;
        box-shadow: 0 14px 30px rgba(26, 29, 36, 0.35);
    }

    .lp-card-icon {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(225, 6, 0, 0.16);
        color: #E10600;
        margin-bottom: 8px;
    }

    .lp-stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        text-align: center;
        margin-top: 26px;
    }

    .lp-stat-label {
        font-size: 13px;
        color: var(--pg-text-muted);
    }

    .lp-stat-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--pg-text-main);
    }

    .lp-cta-strip {
        text-align: center;
        padding: 40px 24px;
        border-radius: 20px;
        background: #20242D;
        border: 1px solid #E10600;
    }

    .lp-split-row {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 40px;
        align-items: center;
    }

    .lp-logo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 16px;
    }

    .lp-logo-cell {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid rgba(209, 213, 219, 0.9);
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        color: #111827;
    }

    @media (max-width: 992px) {
        .lp-hero-row {
            grid-template-columns: minmax(0, 1fr);
        }
        .lp-hero-visual {
            max-width: 420px;
            margin: 24px auto 0;
        }
    }
</style>

<div class="lp-shell">
    <section class="lp-hero">
        <div class="lp-hero-row">
            <div>
                <h1 class="lp-hero-title">
                    Process card payments with a single integration.
                </h1>
                <p class="lp-hero-subtitle mb-3">
                    Seamlessly accept debit and credit card payments with {{ config('app.name') }}, while leveraging 1000+ alternative payment methods for global customers.
                </p>
                <div class="lp-hero-actions">
                    <a href="{{ route('signup') }}" class="lp-btn-primary">
                        Start free sandbox
                        <i class="bi bi-arrow-right-short"></i>
                    </a>
                    <a href="{{ route('products.page') }}" class="lp-btn-ghost">
                        Explore products
                    </a>
                </div>
                <div class="lp-hero-metrics">
                    <div>
                        <div class="lp-hero-metric-label">Currencies supported</div>
                        <div class="lp-hero-metric-value">70+</div>
                    </div>
                    <div>
                        <div class="lp-hero-metric-label">Countries covered</div>
                        <div class="lp-hero-metric-value">190+</div>
                    </div>
                    <div>
                        <div class="lp-hero-metric-label">Availability</div>
                        <div class="lp-hero-metric-value">24 / 7</div>
                    </div>
                </div>
            </div>

            <div class="lp-hero-visual">
                <div class="lp-card-stack">
                    <div class="lp-card-bg"></div>
                    <div class="lp-card-main">
                        <div class="lp-card-header-row">
                            <span>Demo Store</span>
                            <span class="badge bg-light text-dark border border-light-subtle" style="font-size:10px;">VISA</span>
                        </div>
                        <div class="lp-card-amount">₹ 2,999</div>
                        <div class="lp-card-line">
                            <span>Order number</span>
                            <span>9812416901</span>
                        </div>
                        <div class="mt-2">
                            <div class="lp-card-line">
                                <span>Card number</span>
                                <span class="text-muted" style="font-size:10px;">•••• •••• •••• 0101</span>
                            </div>
                            <div class="lp-card-input">4761 7390 0101 0101</div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <div style="flex:1;">
                                <div class="lp-card-line">
                                    <span>Exp. date</span>
                                </div>
                                <div class="lp-card-input">04/36</div>
                            </div>
                            <div style="flex:1;">
                                <div class="lp-card-line">
                                    <span>CVV/CVC</span>
                                </div>
                                <div class="lp-card-input">•••</div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="lp-card-line">
                                <span>Cardholder name</span>
                            </div>
                            <div class="lp-card-input">LUCAS WRIGHT</div>
                        </div>
                        <div class="lp-card-pay-btn">Pay</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 1: Why teams choose -->
    <section class="lp-section">
        <div class="lp-section-header">
            <div class="lp-section-title">Why teams choose {{ config('app.name') }}</div>
            <p class="lp-section-subtitle">A single platform for payouts, collections and global expansion—without the complexity.</p>
        </div>
        <div class="lp-three-column">
            <div class="lp-card-simple">
                <div class="lp-card-icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h6 class="fw-semibold mb-1">One platform, all rails</h6>
                <p class="mb-0" style="font-size:13px; color: var(--pg-text-muted);">
                    Cards, UPI, netbanking and wallets under one unified API and dashboard.
                </p>
            </div>
            <div class="lp-card-simple">
                <div class="lp-card-icon" style="background:rgba(34,197,94,0.12);color:#15803d;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h6 class="fw-semibold mb-1">Better conversion</h6>
                <p class="mb-0" style="font-size:13px; color: var(--pg-text-muted);">
                    Optimised routing and local payment methods help more customers complete checkout.
                </p>
            </div>
            <div class="lp-card-simple">
                <div class="lp-card-icon" style="background:rgba(250,204,21,0.16);color:#a16207;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h6 class="fw-semibold mb-1">Bank‑grade security</h6>
                <p class="mb-0" style="font-size:13px; color: var(--pg-text-muted);">
                    Enterprise security, PCI compliance and observability built into every flow.
                </p>
            </div>
        </div>
    </section>

    <!-- Section 2: We take care of payments (text + image) -->
    <section class="lp-section">
        <div style="background:#ffffff;border-radius:24px;box-shadow:0 18px 40px rgba(15,23,42,0.16);padding:40px 40px;">
        <div class="lp-split-row">
            <div>
                <h2 class="lp-section-title" style="text-align:left;font-size:30px;margin-bottom:12px;">
                    We take care of payments,<br>so you can take care of your customers <span style="color:#16a34a;">anywhere</span>.
                </h2>
                <p class="lp-section-subtitle" style="text-align:left;margin-left:0;margin-bottom:18px;">
                    Seamless integration of card processing and local methods like UPI from a single API integration,
                    no matter where your customers are.
                </p>
                <a href="{{ route('products.page') }}" class="lp-btn-primary mt-1">
                    Find out more <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="text-end">
                <div style="display:inline-block;border-radius:24px;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,0.25);width:100%;max-width:360px;height:220px;background:#f3f4f6;">
                    <img src="{{ asset('images/landing-payments.png') }}" alt="Payments and customers worldwide" class="w-100 h-100" style="object-fit:cover;">
                </div>
            </div>
        </div>
        </div>
    </section>

    <!-- Section 3: Stats strip -->
    <section class="lp-section" style="padding-top:16px;">
        <div class="lp-section-header">
            <div class="lp-section-title">Do global business, like a local</div>
            <p class="lp-section-subtitle">Join the businesses that rely on {{ config('app.name') }} to move money confidently across borders.</p>
        </div>
        <div class="lp-stats-row">
            <div>
                <div class="lp-stat-value">70+</div>
                <div class="lp-stat-label">currencies supported</div>
            </div>
            <div>
                <div class="lp-stat-value">190+</div>
                <div class="lp-stat-label">countries & territories</div>
            </div>
            <div>
                <div class="lp-stat-value">20+</div>
                <div class="lp-stat-label">languages supported</div>
            </div>
        </div>
    </section>

    <!-- Section 4: Payment methods grid + copy -->
    <section class="lp-section" style="padding-top:24px;">
        <div style="background:rgba(255,255,255,0.92);border-radius:24px;box-shadow:0 20px 45px rgba(15,23,42,0.18);padding:36px 40px;">
            <div class="lp-split-row">
                <div>
                    <div class="lp-logo-grid">
                        @foreach(['VISA','Mastercard','UPI','Netbanking','Alipay','PIX','M-Pesa','SEPA','KakaoPay','iDEAL','OXXO','POLi'] as $logo)
                            <div class="lp-logo-cell">{{ $logo }}</div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h2 class="lp-section-title" style="text-align:left;font-size:24px;margin-bottom:10px;">Reach 4 billion customers</h2>
                    <p class="lp-section-subtitle" style="text-align:left;margin-left:0;margin-bottom:14px;">
                        {{ config('app.name') }} partners with leading local payment methods so you can accept
                        cards, wallets, bank transfers and more in key regions around the world.
                    </p>
                    <a href="{{ route('products.page') }}" class="lp-btn-ghost mt-1">
                        See all payment methods
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Final CTA -->
    <section class="lp-section" style="padding-top:16px;padding-bottom:72px;">
        <div class="lp-cta-strip">
            <h3 style="font-size:22px;font-weight:700;color:var(--pg-text-main);margin-bottom:6px;">
                Get your free {{ config('app.name') }} account now
            </h3>
            <p style="font-size:13px;color:var(--pg-text-muted);max-width:480px;margin:0 auto 14px;">
                Create a sandbox account in minutes and start testing payments, webhooks and settlements without risk.
            </p>
            <a href="{{ route('signup') }}" class="lp-btn-primary">
                Sign up – it’s free
                <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
    </section>
</div>
@endsection

