@extends('layouts.marketing')

@section('title', 'About – ' . config('app.name'))

@section('content')
<style>
    /* About page – black & red theme */
    .about-shell {
        padding: 40px 0 56px;
    }

    .about-block {
        background: #20242D;
        border-radius: 28px;
        padding: 32px 32px 40px;
        margin-bottom: 32px;
        box-shadow: -18px 26px 40px rgba(26, 29, 36, 0.4);
    }

    .about-block--tinted {
        background: #1A1D24;
    }

    .about-hero-heading {
        font-size: 34px;
        font-weight: 800;
        color: #ffffff;
        text-align: center;
        margin-bottom: 10px;
    }

    .about-hero-heading span {
        color: #E10600;
    }

    .about-hero-text {
        font-size: 15px;
        color: #9CA3AF;
        max-width: 720px;
        margin: 0 auto;
        text-align: center;
    }

    .about-row {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 32px;
        align-items: center;
    }

    .about-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        margin-top: 24px;
    }

    .about-metric {
        padding: 14px 0;
        border-radius: 18px;
        background: #262B36;
        text-align: center;
    }

    .about-metric-value {
        font-size: 20px;
        font-weight: 700;
        color: #E10600;
    }

    .about-metric-label {
        font-size: 12px;
        color:rgb(171, 156, 175);
    }

    .about-story-img {
        display: inline-block;
        width: 100%;
        max-width: 360px;
        height: 220px;
        border-radius: 24px;
        overflow: hidden;
        background-image:
            linear-gradient(135deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.02)),
            url('{{ asset('images/about-story.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .about-card-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .about-card {
        background: #262B36;
        border-radius: 18px;
        padding: 18px 18px 16px;
        border: 1px solid #20242D;
    }

    .about-secure-panel {
        display: inline-block;
        background: #262B36;
        padding: 24px 32px;
        border-radius: 24px;
        box-shadow: -18px 26px 40px rgba(225, 6, 0, 0.4);
    }

    @media (max-width: 992px) {
        .about-row {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="about-shell">
    <div class="about-block about-block--tinted text-center">
        <h1 class="about-hero-heading">
            Simplifying <span>global payments</span>, one transaction at a time.
        </h1>
        <p class="about-hero-text">
            {{ config('app.name') }} makes global transactions feel as simple as getting paid locally,
            so you can focus on building your business instead of chasing wires.
        </p>
        <div class="about-metrics">
            <div class="about-metric">
                <div class="about-metric-value">20,000+</div>
                <div class="about-metric-label">Businesses supported</div>
            </div>
            <div class="about-metric">
                <div class="about-metric-value">$300M+</div>
                <div class="about-metric-label">Processed annually</div>
            </div>
            <div class="about-metric">
                <div class="about-metric-value">150+</div>
                <div class="about-metric-label">Destination countries</div>
            </div>
        </div>
    </div>

    <div class="about-block">
        <div class="about-row">
            <div>
                <h2 style="font-size:26px;font-weight:800;color:#ffffff;" class="mb-2">Our story</h2>
                <p style="font-size:14px;color:#9CA3AF;">
                    {{ config('app.name') }} was started to solve a familiar problem: Indian businesses with
                    global opportunities, but complicated payment journeys.
                </p>
                <p style="font-size:14px;color:#9CA3AF;" class="mb-0">
                    With experience across banking, fintech and cross‑border trade, our team is focused on
                    giving exporters, SaaS companies and freelancers a modern way to get paid from anywhere.
                </p>
            </div>
            <div class="text-end">
                <span class="about-story-img"></span>
            </div>
        </div>
    </div>

    <div class="about-block">
        <h2 style="font-size:26px;font-weight:800;color:#ffffff;" class="mb-3 text-center">
            What we’re building
        </h2>
        <p class="text-center" style="font-size:14px;color:#9CA3AF;margin-bottom:24px;">
            A single place to accept payments, see settlements and stay compliant—without needing ten different tools.
        </p>
        <div class="about-card-row">
            <div class="about-card">
                <h6 class="fw-semibold mb-1" style="color:#ffffff;">For growing companies</h6>
                <p class="mb-0" style="font-size:13px;color:#9CA3AF;">
                    From first invoice to global scale, {{ config('app.name') }} grows with your business.
                </p>
            </div>
            <div class="about-card">
                <h6 class="fw-semibold mb-1" style="color:#ffffff;">For finance teams</h6>
                <p class="mb-0" style="font-size:13px;color:#9CA3AF;">
                    Clear reports, predictable fees and faster reconciliations built into the platform.
                </p>
            </div>
            <div class="about-card">
                <h6 class="fw-semibold mb-1" style="color:#ffffff;">For developers</h6>
                <p class="mb-0" style="font-size:13px;color:#9CA3AF;">
                    Clean APIs, realistic sandboxes and detailed webhooks for every critical event.
                </p>
            </div>
        </div>
    </div>

    <div class="about-block about-block--tinted">
        <div class="about-row">
            <div class="text-center">
                <div class="about-secure-panel">
                    <div style="width:120px;height:120px;border-radius:999px;margin:0 auto 12px;overflow:hidden;background-image:conic-gradient(#111827 0deg 72deg,#ef4444 72deg 144deg,#ffffff 144deg 216deg,#a855f7 216deg 288deg,#3b82f6 288deg 360deg);"></div>
                    <div style="font-size:13px;color:#4b5563;">
                        Operating under<br><strong>bank‑grade compliance standards</strong>
                    </div>
                </div>
            </div>
            <div>
                <h3 style="font-size:20px;font-weight:700;color:#ffffff;" class="mb-2">Securing what matters most</h3>
                <p style="font-size:14px;color:#9CA3AF;">
                    Trusted by exporters and digital businesses across India, we process high‑value payouts every month.
                    Reliability, observability and compliance are built into our stack from day one.
                </p>
                <p style="font-size:14px;color:#9CA3AF;" class="mb-0">
                    From KYC and risk monitoring to strong data protections, {{ config('app.name') }} keeps your
                    funds and your customers’ information safe so you can focus on growth.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

