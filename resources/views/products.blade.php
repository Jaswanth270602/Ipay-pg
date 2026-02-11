@extends('layouts.marketing')

@section('title', 'Products – ' . config('app.name'))

@section('content')
<style>
    /* SECTION 1: Products header */
    .products-hero {
        padding: 48px 0 32px;
    }
    .products-hero-inner {
        background: linear-gradient(180deg, #20242D 0%, #1A1D24 100%);
        border-radius: 24px;
        padding: 32px 32px 28px;
    }
    .products-title {
        font-size: 32px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.02em;
        margin-bottom: 8px;
    }
    .products-subtitle {
        font-size: 15px;
        color: #9CA3AF;
        max-width: 640px;
        margin-bottom: 18px;
    }
    .products-strip {
        display: inline-flex;
        flex-direction: column;
        gap: 2px;
        border-radius: 999px;
        padding: 10px 18px;
        background: rgba(225, 6, 0, 0.08);
        border: 1px solid #E10600;
        font-size: 12px;
        color: #F9FAFB;
    }
    .products-strip-label {
        font-weight: 600;
        font-size: 13px;
        color: #ffffff;
    }

    /* SECTION 2: Products cards grid */
    .products-grid-section {
        background-color: #20242D;
        border-radius: 24px;
        padding: 32px 28px;
        margin-top: 24px;
    }
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
        margin-bottom: 12px;
    }
    .product-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px 20px 16px;
        border: 1px solid #262B36;
        box-shadow: 0 10px 24px rgba(26, 29, 36, 0.3);
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }
    .product-card:hover {
        border-color: #E10600;
        box-shadow: 0 10px 30px rgba(225, 6, 0, 0.35);
        transform: translateY(-4px);
    }
    .product-card-title {
        font-size: 15px;
        font-weight: 600;
        color: #1A1D24;
        margin-bottom: 4px;
    }
    .product-card-text {
        font-size: 13px;
        color: #4b5563;
        margin-bottom: 0;
    }

    @media (max-width: 576px) {
        .products-hero-inner {
            padding: 24px 18px 20px;
            border-radius: 18px;
        }
        .products-grid-section {
            padding: 24px 18px;
            border-radius: 18px;
        }
    }
</style>

<section class="products-hero">
    <div class="products-hero-inner">
        <h1 class="products-title">Products built for the full payments journey.</h1>
        <p class="products-subtitle mb-0">
            Choose the right building blocks for accepting, routing and settling payments—then add payouts, subscriptions
            and analytics as your use‑cases grow.
        </p>
        <div class="mt-3">
            <div class="products-strip">
                <span class="products-strip-label">Everything in one stack.</span>
                <span>Gateway, payouts, subscriptions and analytics working together from day one.</span>
            </div>
        </div>
    </div>
</section>

<section aria-label="Products list" class="products-grid-section">
    <div class="products-grid">
        <div class="product-card">
            <h6 class="product-card-title">Payments Gateway</h6>
            <p class="product-card-text">
                A single API and hosted checkout for cards, UPI, netbanking and wallets with smart routing for higher success.
            </p>
        </div>
        <div class="product-card">
            <h6 class="product-card-title">Payouts &amp; Settlements</h6>
            <p class="product-card-text">
                Flexible settlement cycles, transparent fees and MIS reports built for finance and reconciliation teams.
            </p>
        </div>
        <div class="product-card">
            <h6 class="product-card-title">Subscriptions &amp; Billing</h6>
            <p class="product-card-text">
                Plans, trials and automatic renewals with clear retry logic and webhooks for your backend.
            </p>
        </div>
        <div class="product-card">
            <h6 class="product-card-title">Payment Links</h6>
            <p class="product-card-text">
                Shareable links for invoices, one‑off payments or quick campaigns—no development required.
            </p>
        </div>
        <div class="product-card">
            <h6 class="product-card-title">Risk &amp; Disputes</h6>
            <p class="product-card-text">
                Chargeback workflows, alerts and scoring so you can catch risky behaviour early.
            </p>
        </div>
        <div class="product-card">
            <h6 class="product-card-title">Analytics Studio</h6>
            <p class="product-card-text">
                Real‑time dashboards and exports to understand performance by method, geography and partner.
            </p>
        </div>
    </div>
</section>
@endsection

