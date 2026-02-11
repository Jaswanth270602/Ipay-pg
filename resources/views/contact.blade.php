@extends('layouts.marketing')

@section('title', 'Contact – ' . config('app.name'))

@section('content')
<style>
    .contact-hero {
        padding: 40px 0 24px;
    }
    .contact-title {
        font-size: 30px;
        font-weight: 800;
        color: #ffffff;
    }
    .contact-subtitle {
        font-size: 15px;
        color: #9CA3AF;
        max-width: 640px;
    }
    .contact-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        gap: 26px;
        margin-top: 24px;
    }
    .contact-panel,
    .contact-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 18px 18px 16px;
        border: 1px solid rgba(32, 36, 45, 0.4);
        box-shadow: 0 18px 40px rgba(26, 29, 36, 0.35);
    }
    .contact-bg {
        background: #20242D;
        border-radius: 24px;
        padding: 32px 32px 40px;
        margin-top: 16px;
    }
    .contact-bg .form-control:focus,
    .contact-bg .form-select:focus {
        border-color: #E10600;
        box-shadow: 0 0 0 2px rgba(225, 6, 0, 0.35);
    }
    .contact-bg .btn-primary {
        background: #E10600;
        border-color: #E10600;
        border-radius: 999px;
        padding-inline: 18px;
    }
    .contact-bg .btn-primary:hover {
        filter: brightness(1.05);
    }
    @media (max-width: 992px) {
        .contact-layout {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<section class="contact-hero">
    <h1 class="contact-title mb-2">Talk to our team.</h1>
    <p class="contact-subtitle mb-0">
        Share a few details and we’ll help you choose the right setup for your business and integration.
    </p>
</section>

<section class="contact-bg">
<div class="contact-layout">
    <div class="contact-panel">
        <h6 class="fw-semibold mb-3">Send us a message</h6>
        <form>
            <div class="mb-2">
                <label class="form-label small text-muted">Full name</label>
                <input type="text" class="form-control form-control-sm" placeholder="Jane Doe">
            </div>
            <div class="mb-2">
                <label class="form-label small text-muted">Work email</label>
                <input type="email" class="form-control form-control-sm" placeholder="you@company.com">
            </div>
            <div class="mb-2">
                <label class="form-label small text-muted">Company</label>
                <input type="text" class="form-control form-control-sm" placeholder="Company name">
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted">How can we help?</label>
                <textarea class="form-control form-control-sm" rows="3" placeholder="Describe your use‑case or question"></textarea>
            </div>
            <button type="button" class="btn btn-sm btn-primary">
                Submit enquiry
            </button>
        </form>
    </div>

    <div class="contact-card">
        <h6 class="fw-semibold mb-2">Other ways to reach us</h6>
        <p class="mb-2" style="font-size:13px;color:#4b5563;">
            Prefer not to use a form? Reach out using any of these channels.
        </p>
        <ul class="list-unstyled mb-3" style="font-size:13px;color:#4b5563;">
            <li class="mb-1"><i class="bi bi-envelope-fill text-primary me-2"></i>support@example.com</li>
            <li class="mb-1"><i class="bi bi-telephone-fill text-primary me-2"></i>+91‑00000‑00000</li>
            <li><i class="bi bi-clock-history text-primary me-2"></i>Mon–Fri · 9:00 am – 7:00 pm IST</li>
        </ul>
        <hr>
        <p class="mb-1" style="font-size:13px;color:#4b5563;">
            Already using {{ config('app.name') }}?
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">
                Login to dashboard
            </a>
            <a href="{{ route('signup') }}" class="btn btn-sm btn-primary">
                Create new merchant account
            </a>
        </div>
    </div>
</div>
</section>
@endsection

