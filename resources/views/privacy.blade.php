@extends('layouts.marketing')

@section('title', 'Privacy Policy – ' . config('app.name'))

@section('content')
<div class="py-4 py-md-5">
    <div class="mx-auto" style="max-width: 760px;">
        <h1 class="mb-3" style="font-size:26px;font-weight:800;color:var(--pg-text-main,rgb(15, 129, 5));">
            Privacy Policy
        </h1>
        <p class="mb-4" style="font-size:14px;color:#ffff;line-height:1.7;">
            This Privacy Policy explains how {{ config('app.name') }} (“we”, “our”, “us”) collects, uses and protects
            information when you use our payment gateway, merchant dashboard and related services.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            1. Information we collect
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We collect information that you provide directly to us (such as account registration details, contact
            information and support queries) as well as transaction data we receive from your use of our products.
            This may include payer details, payment method information, device and log data, and other data required
            to process payments securely.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            2. How we use information
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We use your information to operate and improve our services, process and reconcile payments, prevent fraud
            and abuse, comply with legal and regulatory requirements, and communicate with you about your account,
            product updates and support.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            3. Sharing and disclosure
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We may share information with payment networks, banks, acquiring partners, service providers and other
            entities as required to provide our services, meet compliance obligations or respond to lawful requests.
            We do not sell personal data.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            4. Data security and retention
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We apply industry-standard security controls to protect information, including encryption, access controls
            and monitoring. We retain data only for as long as necessary for the purposes described in this Policy or
            as required by law and network rules.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            5. Your rights and choices
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            Depending on your jurisdiction, you may have rights to access, correct or delete certain information we
            hold about you. You can contact us using the details on the Contact page for any privacy-related queries.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            6. Updates to this Policy
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We may update this Privacy Policy periodically to reflect changes to our products or legal requirements.
            When we make material changes, we will notify you through the dashboard or via email where appropriate.
        </p>
    </div>
</div>
@endsection

