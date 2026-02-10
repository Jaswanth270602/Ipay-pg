@extends('layouts.app')

@section('title', config('app.name') . ' – The Safer, Smarter Payment Gateway for Modern Businesses')

@section('content')
<style>
    :root {
        --bc-primary: #6366f1;
        --bc-primary-dark: #4f46e5;
        --bc-primary-soft: #eef2ff;
        --bc-bg: #020617;
    }

    body {
        background: radial-gradient(circle at top left, #1d213b 0, #020617 40%, #020617 100%);
        color: #e5e7eb;
    }

    .landing-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .landing-nav {
        padding: 20px 0;
    }

    .landing-badge {
        background: rgba(148, 163, 184, 0.25);
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: #cbd5f5;
    }

    .gradient-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 4px 10px 4px 4px;
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(129, 140, 248, 0.4));
        border: 1px solid rgba(129, 140, 248, 0.7);
        font-size: 12px;
        color: #e0f2fe;
    }

    .hero-title {
        font-size: clamp(2.6rem, 4vw, 3.4rem);
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -.04em;
        color: #f9fafb;
    }

    .hero-gradient-text {
        background: linear-gradient(135deg, #a855f7, #6366f1, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 16px;
        color: #9ca3af;
        max-width: 520px;
    }

    .hero-metrics {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-top: 24px;
    }

    .hero-metric {
        min-width: 120px;
    }

    .hero-metric-value {
        font-size: 20px;
        font-weight: 700;
        color: #e5e7eb;
    }

    .hero-metric-label {
        font-size: 12px;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .btn-primary-hero {
        background: linear-gradient(135deg, var(--bc-primary) 0%, var(--bc-primary-dark) 100%);
        border: none;
        padding: 12px 26px;
        border-radius: 999px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 18px 45px rgba(79, 70, 229, 0.5);
    }

    .btn-primary-hero:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 55px rgba(79, 70, 229, 0.7);
    }

    .btn-outline-hero {
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.5);
        color: #e5e7eb;
        padding: 11px 22px;
        font-weight: 500;
    }

    .btn-outline-hero:hover {
        background: rgba(15, 23, 42, 0.85);
    }

    .hero-card {
        background: radial-gradient(circle at top, rgba(248, 250, 252, 0.06), rgba(15, 23, 42, 0.95));
        border-radius: 24px;
        padding: 22px 22px 18px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.7);
    }

    .hero-logos {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        align-items: center;
        margin-top: 14px;
    }

    .logo-pill {
        border-radius: 999px;
        padding: 6px 12px;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(148, 163, 184, 0.5);
        font-size: 11px;
        color: #cbd5f5;
    }

    .features-grid {
        margin-top: 64px;
    }

    .feature-card {
        background: rgba(15, 23, 42, 0.9);
        border-radius: 18px;
        padding: 18px 18px 16px;
        border: 1px solid rgba(55, 65, 81, 0.9);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.8);
        height: 100%;
    }

    .feature-icon {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, 0.12);
        color: #60a5fa;
        margin-bottom: 8px;
    }

    .testimonials-strip {
        margin-top: 50px;
        border-radius: 18px;
        padding: 14px 18px;
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(55, 65, 81, 0.9);
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .testimonial-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #38bdf8, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #0f172a;
        font-weight: 700;
    }

    .testimonial-quote {
        font-size: 13px;
        color: #e5e7eb;
    }

    .floating-badge {
        position: absolute;
        top: -14px;
        right: 24px;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(34, 197, 94, 0.15);
        border: 1px solid rgba(34, 197, 94, 0.6);
        font-size: 10px;
        color: #bbf7d0;
        text-transform: uppercase;
        letter-spacing: .1em;
    }

    .section-divider {
        margin: 80px 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.3), transparent);
    }

    .section-title {
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        font-weight: 800;
        color: #f9fafb;
        margin-bottom: 16px;
    }

    .section-subtitle {
        font-size: 16px;
        color: #9ca3af;
        max-width: 600px;
        margin: 0 auto;
    }

    .how-it-works-card {
        background: rgba(15, 23, 42, 0.85);
        border-radius: 20px;
        padding: 28px;
        border: 1px solid rgba(55, 65, 81, 0.9);
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .how-it-works-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.3);
        border-color: rgba(99, 102, 241, 0.6);
    }

    .svg-illustration {
        width: 100%;
        max-width: 320px;
        height: auto;
        margin: 0 auto 20px;
        opacity: 0.95;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 16px;
    }

    .security-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 40px;
    }

    .security-card {
        background: rgba(15, 23, 42, 0.9);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid rgba(55, 65, 81, 0.9);
        text-align: center;
    }

    .security-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: rgba(34, 197, 94, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        color: #6ee7b7;
        font-size: 24px;
    }

    .business-boost-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }

    .boost-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(79, 70, 229, 0.05));
        border-radius: 18px;
        padding: 24px;
        border: 1px solid rgba(99, 102, 241, 0.3);
        position: relative;
        overflow: hidden;
    }

    .boost-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #6366f1, #a855f7, #22c55e);
    }

    .use-case-comparison {
        background: rgba(15, 23, 42, 0.95);
        border-radius: 24px;
        padding: 40px;
        border: 1px solid rgba(55, 65, 81, 0.9);
        margin-top: 40px;
    }

    .comparison-item {
        display: flex;
        align-items: start;
        gap: 20px;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 16px;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(55, 65, 81, 0.6);
    }

    .comparison-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        margin: 60px 0;
    }

    .stat-card {
        text-align: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.7);
        border-radius: 16px;
        border: 1px solid rgba(55, 65, 81, 0.6);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #a855f7, #6366f1, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
    }

    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }

    .testimonial-card {
        background: rgba(15, 23, 42, 0.9);
        border-radius: 18px;
        padding: 24px;
        border: 1px solid rgba(55, 65, 81, 0.9);
    }

    .payment-illustration-wrapper {
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    @media (max-width: 992px) {
        .hero-card {
            margin-top: 32px;
        }
        .section-divider {
            margin: 60px 0;
        }
        .payment-illustration-wrapper {
            margin-top: 40px;
        }
    }
</style>

<div class="landing-wrapper">
    <header class="landing-nav">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary bg-opacity-20 d-flex align-items-center justify-content-center" style="width:px;height:34px;">
                    <!-- <i class="bi bi-wallet2 text-primary"></i> -->
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}" style="height: 72px; width: auto;">
                        <div class="fw-bold text-white" style="font-size: 28px; font-weight: 700;">{{ config('app.name') }}</div>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;">Safer payments for ambitious teams</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-hero">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
                <a href="{{ route('signup') }}" class="btn btn-sm btn-primary-hero">
                    Start accepting payments
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </header>

    <section class="py-4 py-md-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="mb-3">
                    <span class="gradient-pill">
                        <span class="badge bg-dark border border-info border-opacity-50 rounded-pill me-1">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        PCI-ready sandbox · Go live in days, not weeks
                    </span>
                </div>
                <h1 class="hero-title mb-3">
                    The payment gateway
                    <span class="hero-gradient-text">teams actually love</span>.
                </h1>
                <p class="hero-subtitle mb-3">
                    {{ config('app.name') }} is a developer-first, bank-grade payment gateway designed to feel as smooth as Razorpay,
                    but with obsessive focus on observability, sandbox–production parity, and merchant UX.
                </p>
                <ul class="list-unstyled mb-4" style="font-size:13px;color:#9ca3af;">
                    <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Unified APIs for payments, refunds, disputes & settlements</li>
                    <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Realistic sandbox flows with webhooks that mirror production</li>
                    <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> SDK checkout widget that drops into your app in minutes</li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <a href="{{ route('signup') }}" class="btn btn-primary-hero">
                        Create free test account
                        <i class="bi bi-arrow-right-short"></i>
                    </a>
                    <a href="#features" class="btn btn-outline-hero">
                        View features
                    </a>
                </div>

                <div class="hero-metrics">
                    <div class="hero-metric">
                        <div class="hero-metric-value">99.95%</div>
                        <div class="hero-metric-label">Uptime Simulated</div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-value">&lt; 60 sec</div>
                        <div class="hero-metric-label">Webhook Latency</div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-value">0</div>
                        <div class="hero-metric-label">Bank Dependencies Today</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 position-relative d-flex align-items-center justify-content-center">
                <div class="payment-illustration-wrapper" style="width: 100%; max-width: 600px; position: relative;">
                    <!-- Payment Gateway Illustration -->
                    <svg viewBox="0 0 600 500" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: auto; filter: drop-shadow(0 20px 40px rgba(99, 102, 241, 0.3));">
                        <!-- Background gradient circles -->
                        <circle cx="300" cy="250" r="200" fill="url(#gradient1)" opacity="0.3"/>
                        <circle cx="300" cy="250" r="150" fill="url(#gradient2)" opacity="0.2"/>
                        
                        <!-- Gradient Definitions -->
                        <defs>
                            <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#a855f7;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="gradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#22c55e;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#16a34a;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="cardGradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:0.9" />
                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.9" />
                            </linearGradient>
                            <linearGradient id="cardGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#22c55e;stop-opacity:0.9" />
                                <stop offset="100%" style="stop-color:#16a34a;stop-opacity:0.9" />
                            </linearGradient>
                            <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                <polygon points="0 0, 10 3, 0 6" fill="rgba(34, 197, 94, 0.8)" />
                            </marker>
                        </defs>
                        
                        <!-- Credit Card 1 -->
                        <g transform="translate(100, 150) rotate(-10)">
                            <rect x="0" y="0" width="180" height="110" rx="12" fill="url(#cardGradient1)"/>
                            <rect x="0" y="0" width="180" height="110" rx="12" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>
                            <rect x="20" y="20" width="60" height="40" rx="6" fill="rgba(255,255,255,0.2)"/>
                            <rect x="20" y="70" width="140" height="3" rx="1.5" fill="rgba(255,255,255,0.4)"/>
                            <rect x="20" y="80" width="100" height="3" rx="1.5" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="150" cy="30" r="8" fill="rgba(255,255,255,0.3)"/>
                            <circle cx="165" cy="30" r="8" fill="rgba(255,255,255,0.3)"/>
                        </g>
                        
                        <!-- Credit Card 2 -->
                        <g transform="translate(320, 180) rotate(10)">
                            <rect x="0" y="0" width="180" height="110" rx="12" fill="url(#cardGradient2)"/>
                            <rect x="0" y="0" width="180" height="110" rx="12" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>
                            <rect x="20" y="20" width="60" height="40" rx="6" fill="rgba(255,255,255,0.2)"/>
                            <rect x="20" y="70" width="140" height="3" rx="1.5" fill="rgba(255,255,255,0.4)"/>
                            <rect x="20" y="80" width="100" height="3" rx="1.5" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="150" cy="30" r="8" fill="rgba(255,255,255,0.3)"/>
                            <circle cx="165" cy="30" r="8" fill="rgba(255,255,255,0.3)"/>
                        </g>
                        
                        <!-- Payment Processing Center -->
                        <g transform="translate(240, 200)">
                            <circle cx="60" cy="60" r="50" fill="rgba(99, 102, 241, 0.2)" stroke="rgba(99, 102, 241, 0.6)" stroke-width="3"/>
                            <circle cx="60" cy="60" r="35" fill="rgba(99, 102, 241, 0.3)"/>
                            <path d="M 45 60 L 55 70 L 75 50" stroke="#6366f1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </g>
                        
                        <!-- Payment Flow Arrows -->
                        <path d="M 190 205 Q 240 180, 260 230" stroke="rgba(34, 197, 94, 0.6)" stroke-width="3" fill="none" stroke-linecap="round" marker-end="url(#arrowhead)"/>
                        <path d="M 410 235 Q 360 210, 340 260" stroke="rgba(34, 197, 94, 0.6)" stroke-width="3" fill="none" stroke-linecap="round" marker-end="url(#arrowhead)"/>
                        
                        <!-- UPI/Phone Icon -->
                        <g transform="translate(80, 320)">
                            <rect x="0" y="0" width="80" height="120" rx="20" fill="rgba(168, 85, 247, 0.2)" stroke="rgba(168, 85, 247, 0.6)" stroke-width="2"/>
                            <rect x="15" y="20" width="50" height="70" rx="8" fill="rgba(168, 85, 247, 0.3)"/>
                            <circle cx="40" cy="100" r="8" fill="rgba(168, 85, 247, 0.5)"/>
                        </g>
                        
                        <!-- Bank/Netbanking Icon -->
                        <g transform="translate(440, 320)">
                            <rect x="0" y="0" width="100" height="80" rx="12" fill="rgba(251, 146, 60, 0.2)" stroke="rgba(251, 146, 60, 0.6)" stroke-width="2"/>
                            <rect x="20" y="20" width="60" height="40" rx="6" fill="rgba(251, 146, 60, 0.3)"/>
                            <rect x="25" y="25" width="15" height="10" rx="2" fill="rgba(251, 146, 60, 0.5)"/>
                            <rect x="45" y="25" width="30" height="10" rx="2" fill="rgba(251, 146, 60, 0.5)"/>
                            <rect x="25" y="40" width="50" height="10" rx="2" fill="rgba(251, 146, 60, 0.5)"/>
                        </g>
                        
                        <!-- Payment Methods Icons (small) -->
                        <circle cx="150" cy="380" r="30" fill="rgba(99, 102, 241, 0.15)" stroke="rgba(99, 102, 241, 0.5)" stroke-width="2"/>
                        <path d="M 135 380 L 145 390 L 165 370" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        
                        <circle cx="450" cy="380" r="30" fill="rgba(34, 197, 94, 0.15)" stroke="rgba(34, 197, 94, 0.5)" stroke-width="2"/>
                        <path d="M 435 380 L 445 390 L 465 370" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        
                    </svg>
                </div>
            </div>
            
        </div>

        <div id="features" class="features-grid mt-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-columns-gap"></i>
                        </div>
                        <div class="fw-semibold mb-1">Merchant-first dashboards</div>
                        <div style="font-size:13px;color:#9ca3af;">
                            Rich dashboards for merchants and admins, including test/live toggles, settlement insights,
                            disputes, and detailed transaction timelines.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(16,185,129,.12);color:#6ee7b7;">
                            <i class="bi bi-webhook"></i>
                        </div>
                        <div class="fw-semibold mb-1">Webhooks that just work</div>
                        <div style="font-size:13px;color:#9ca3af;">
                            Payment, refund, subscription and payment-link webhooks with retries, signatures,
                            event toggles and test/live headers out of the box.
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(244,114,182,.12);color:#f9a8d4;">
                            <i class="bi bi-code-slash"></i>
                        </div>
                        <div class="fw-semibold mb-1">Drop-in checkout widget</div>
                        <div style="font-size:13px;color:#9ca3af;">
                            A lightweight JS SDK that opens a beautiful hosted checkout in a modal, while your app receives
                            simple success/failure callbacks.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- How It Works Section -->
        <div class="text-center mb-5">
            <div class="landing-badge mb-3">Simple integration</div>
            <h2 class="section-title">How {{ config('app.name') }} works</h2>
            <p class="section-subtitle">Get up and running in minutes. Our streamlined process makes payment integration effortless.</p>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-md-4">
                <div class="how-it-works-card text-center">
                    <div class="step-number mx-auto">1</div>
                    <svg class="svg-illustration" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="20" y="60" width="160" height="80" rx="8" fill="rgba(99, 102, 241, 0.2)" stroke="rgba(99, 102, 241, 0.6)" stroke-width="2"/>
                        <path d="M60 100 L90 100 L100 90 L110 100 L140 100" stroke="rgba(34, 197, 94, 0.8)" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <circle cx="100" cy="40" r="20" fill="rgba(99, 102, 241, 0.4)" stroke="rgba(99, 102, 241, 0.8)" stroke-width="2"/>
                        <text x="100" y="45" text-anchor="middle" fill="#6366f1" font-size="14" font-weight="bold">API</text>
                    </svg>
                    <h4 class="fw-semibold mb-2" style="color:#e5e7eb;">Sign up & get API keys</h4>
                    <p style="font-size:13px;color:#9ca3af;">Create your merchant account in under 2 minutes. Receive sandbox and live API keys instantly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-it-works-card text-center">
                    <div class="step-number mx-auto">2</div>
                    <svg class="svg-illustration" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="30" y="40" width="140" height="100" rx="10" fill="rgba(168, 85, 247, 0.2)" stroke="rgba(168, 85, 247, 0.6)" stroke-width="2"/>
                        <rect x="50" y="60" width="100" height="20" rx="4" fill="rgba(168, 85, 247, 0.4)"/>
                        <rect x="50" y="90" width="80" height="15" rx="4" fill="rgba(168, 85, 247, 0.3)"/>
                        <circle cx="70" cy="125" r="8" fill="rgba(34, 197, 94, 0.6)"/>
                        <circle cx="100" cy="125" r="8" fill="rgba(34, 197, 94, 0.6)"/>
                        <circle cx="130" cy="125" r="8" fill="rgba(34, 197, 94, 0.6)"/>
                    </svg>
                    <h4 class="fw-semibold mb-2" style="color:#e5e7eb;">Integrate our SDK</h4>
                    <p style="font-size:13px;color:#9ca3af;">Drop our lightweight JavaScript SDK into your app. Just 3 lines of code to get started.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-it-works-card text-center">
                    <div class="step-number mx-auto">3</div>
                    <svg class="svg-illustration" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M50 80 Q100 40, 150 80" stroke="rgba(34, 197, 94, 0.8)" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <circle cx="50" cy="80" r="12" fill="rgba(99, 102, 241, 0.6)"/>
                        <circle cx="100" cy="60" r="10" fill="rgba(168, 85, 247, 0.6)"/>
                        <circle cx="150" cy="80" r="12" fill="rgba(34, 197, 94, 0.6)"/>
                        <path d="M70 100 L90 100 L100 90 L110 100 L130 100" stroke="rgba(34, 197, 94, 0.8)" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <text x="100" y="135" text-anchor="middle" fill="#22c55e" font-size="12" font-weight="bold">Payment Complete</text>
                    </svg>
                    <h4 class="fw-semibold mb-2" style="color:#e5e7eb;">Accept payments instantly</h4>
                    <p style="font-size:13px;color:#9ca3af;">Customers pay seamlessly. You receive real-time webhooks and detailed transaction analytics.</p>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Security & Safety Section -->
        <div class="text-center mb-5">
            <div class="landing-badge mb-3">Enterprise-grade security</div>
            <h2 class="section-title">Bank-level security, built-in</h2>
            <p class="section-subtitle">Your customers' payment data is protected with industry-leading encryption and compliance standards.</p>
        </div>

        <div class="security-grid">
            <div class="security-card">
                <div class="security-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">PCI DSS Compliant</h5>
                <p style="font-size:13px;color:#9ca3af;">Fully compliant with Payment Card Industry Data Security Standards. We never store your card details.</p>
            </div>
            <div class="security-card">
                <div class="security-icon" style="background:rgba(37,99,235,0.15);color:#60a5fa;">
                    <i class="bi bi-encryption"></i>
                </div>
                <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">End-to-end encryption</h5>
                <p style="font-size:13px;color:#9ca3af;">All payment data is encrypted in transit using TLS 1.3 and at rest with AES-256 encryption.</p>
            </div>
            <div class="security-card">
                <div class="security-icon" style="background:rgba(244,114,182,0.15);color:#f9a8d4;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">Fraud protection</h5>
                <p style="font-size:13px;color:#9ca3af;">Advanced fraud detection algorithms and risk scoring protect your business from chargebacks.</p>
            </div>
            <div class="security-card">
                <div class="security-icon" style="background:rgba(251,146,60,0.15);color:#fb923c;">
                    <i class="bi bi-key"></i>
                </div>
                <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">Secure API keys</h5>
                <p style="font-size:13px;color:#9ca3af;">Separate test and live API keys with granular permissions. Rotate keys anytime from your dashboard.</p>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Boost Your Business Section -->
        <div class="text-center mb-5">
            <div class="landing-badge mb-3">Growth tools</div>
            <h2 class="section-title">Boost your business with {{ config('app.name') }}</h2>
            <p class="section-subtitle">Everything you need to grow your revenue and delight your customers.</p>
        </div>

        <div class="business-boost-grid">
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-20 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="bi bi-graph-up-arrow text-primary" style="font-size:20px;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Increase conversion rates</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Faster checkout flows and optimized payment methods reduce cart abandonment by up to 30%.</p>
            </div>
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(34,197,94,0.15);">
                        <i class="bi bi-clock-history" style="font-size:20px;color:#22c55e;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Faster settlements</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Get paid faster with flexible settlement cycles. Automatic reconciliation and detailed reports included.</p>
            </div>
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(168,85,247,0.15);">
                        <i class="bi bi-people" style="font-size:20px;color:#a855f7;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Better customer experience</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Smooth, mobile-first checkout experience with support for cards, UPI, netbanking, and wallets.</p>
            </div>
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(251,146,60,0.15);">
                        <i class="bi bi-bar-chart" style="font-size:20px;color:#fb923c;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Real-time analytics</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Comprehensive dashboards with transaction insights, revenue trends, and customer payment patterns.</p>
            </div>
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(236,72,153,0.15);">
                        <i class="bi bi-recycle" style="font-size:20px;color:#ec4899;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Automated refunds</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Process refunds instantly from your dashboard. Full or partial refunds with automatic reconciliation.</p>
            </div>
            <div class="boost-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(59,130,246,0.15);">
                        <i class="bi bi-link-45deg" style="font-size:20px;color:#3b82f6;"></i>
                    </div>
                    <h5 class="fw-semibold mb-0 ms-3" style="color:#e5e7eb;">Payment links</h5>
                </div>
                <p style="font-size:13px;color:#9ca3af;">Create shareable payment links in seconds. Perfect for invoices, subscriptions, and one-off payments.</p>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Why Developers Love Us Section -->
        <div class="use-case-comparison">
            <div class="text-center mb-4">
                <div class="landing-badge mb-3">Developer-first</div>
                <h2 class="section-title">Built for everyone, loved by students</h2>
                <p class="section-subtitle">While enterprise teams struggle with complex integrations, students build payment systems in hours.</p>
            </div>

            <div class="comparison-item">
                <div class="comparison-icon" style="background:rgba(148,163,184,0.2);color:#94a3b8;">
                    <i class="bi bi-building" style="font-size:24px;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">Enterprise teams</h5>
                    <p style="font-size:13px;color:#9ca3af;margin-bottom:8px;">Heavy payment gateways require weeks of integration, complex documentation, and lengthy approval processes. Teams spend months configuring webhooks and handling edge cases.</p>
                    <div style="font-size:12px;color:#64748b;">
                        <i class="bi bi-clock me-1"></i> 2-4 weeks integration time
                    </div>
                </div>
            </div>

            <div class="comparison-item" style="background:rgba(99,102,241,0.1);border-color:rgba(99,102,241,0.4);">
                <div class="comparison-icon" style="background:rgba(99,102,241,0.3);color:#818cf8;">
                    <i class="bi bi-mortarboard" style="font-size:24px;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-semibold mb-2" style="color:#e5e7eb;">Students & developers</h5>
                    <p style="font-size:13px;color:#9ca3af;margin-bottom:8px;">{{ config('app.name') }}'s intuitive APIs and comprehensive documentation let students integrate payments into college projects in hours. Perfect for hackathons, portfolio projects, and learning payment systems.</p>
                    <div style="font-size:12px;color:#818cf8;">
                        <i class="bi bi-lightning-charge-fill me-1"></i> 2-3 hours to first payment
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-3" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(34,197,94,0.2);">
                        <i class="bi bi-code-square text-success"></i>
                    </div>
                    <div>
                        <div class="fw-semibold mb-1" style="color:#e5e7eb;">Easy integration for any skill level</div>
                        <p style="font-size:13px;color:#9ca3af;margin:0;">Our sandbox environment lets you test everything risk-free. Realistic webhooks and test data make your development environment production-ready from day one.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Stats Section -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value">&lt; 3 min</div>
                <div style="font-size:13px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;">Setup Time</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">5 lines</div>
                <div style="font-size:13px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;">Of Code</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">99.9%</div>
                <div style="font-size:13px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;">Uptime</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">24/7</div>
                <div style="font-size:13px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.1em;">Support</div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- More Testimonials -->
        <div class="text-center mb-5">
            <div class="landing-badge mb-3">Real stories</div>
            <h2 class="section-title">Loved by developers worldwide</h2>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="testimonial-avatar">R</div>
                    <div class="ms-3">
                        <div class="fw-semibold" style="color:#e5e7eb;">Rajesh Kumar</div>
                        <div style="font-size:11px;color:#9ca3af;">CS Student, IIT Delhi</div>
                    </div>
                    <div class="ms-auto" style="color:#fbbf24;">★★★★★</div>
                </div>
                <p style="font-size:13px;color:#9ca3af;line-height:1.6;">
                    "Used {{ config('app.name') }} for my final year project. The documentation is so clear that I had payments working in my e-commerce app within a day. My professor was impressed!"
                </p>
            </div>
            <div class="testimonial-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="testimonial-avatar" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">P</div>
                    <div class="ms-3">
                        <div class="fw-semibold" style="color:#e5e7eb;">Priya Sharma</div>
                        <div style="font-size:11px;color:#9ca3af;">Founder, TechStartup</div>
                    </div>
                    <div class="ms-auto" style="color:#fbbf24;">★★★★★</div>
                </div>
                <p style="font-size:13px;color:#9ca3af;line-height:1.6;">
                    "We switched from Razorpay to {{ config('app.name') }} because the webhook system is so much more reliable. Zero missed webhooks in 6 months. Highly recommended!"
                </p>
            </div>
            <div class="testimonial-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="testimonial-avatar" style="background:linear-gradient(135deg,#8b5cf6,#ec4899);">A</div>
                    <div class="ms-3">
                        <div class="fw-semibold" style="color:#e5e7eb;">Amit Patel</div>
                        <div style="font-size:11px;color:#9ca3af;">Full Stack Developer</div>
                    </div>
                    <div class="ms-auto" style="color:#fbbf24;">★★★★★</div>
                </div>
                <p style="font-size:13px;color:#9ca3af;line-height:1.6;">
                    "Best payment gateway for hackathons! The sandbox mode is perfect for demos, and the API is straightforward. Won first place thanks to seamless payments."
                </p>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Final CTA -->
        <div class="text-center">
            <div class="landing-badge mb-2">Get started in minutes</div>
            <h2 style="font-size:22px;font-weight:700;color:#e5e7eb;">Open your {{ config('app.name') }} merchant account today</h2>
            <p style="font-size:13px;color:#9ca3af;max-width:480px;margin:6px auto 16px;">
                No calls. No PDFs. Fill a modern, guided signup and start integrating with {{ config('app.name') }}'s sandbox and live-ready APIs.
            </p>
            <a href="{{ route('signup') }}" class="btn btn-primary-hero">
                Sign up – free developer account
                <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
    </section>
</div>
@endsection


