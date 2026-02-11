@extends('layouts.marketing')

@section('title', 'Terms & Conditions – ' . config('app.name'))

@section('content')
<div class="py-4 py-md-5">
    <div class="mx-auto" style="max-width: 760px;">
        <h1 class="mb-3" style="font-size:26px;font-weight:800;color:var(--pg-text-main,rgb(49, 170, 12));">
            Terms &amp; Conditions
        </h1>
        <p class="mb-4" style="font-size:14px;color:#ffff;line-height:1.7;">
            These Terms &amp; Conditions (“Terms”) govern your access to and use of {{ config('app.name') }}’s payment
            gateway, merchant dashboard and related services. By using our services you agree to be bound by these
            Terms.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            1. Eligibility and account
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            You must have the authority to bind the entity on whose behalf you are using our services. You are
            responsible for maintaining accurate account information and for keeping your credentials secure.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            2. Use of services
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            You agree to use the services only for lawful business purposes, and in accordance with applicable card
            network rules, banking guidelines and regulations. You may not use the services for prohibited or high‑risk
            activities without our prior written approval.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            3. Fees, settlements and chargebacks
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            Fees, settlement schedules and other commercial terms are communicated to you separately. You are
            responsible for any chargebacks, disputes, refunds or penalties arising from transactions processed
            through your account.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            4. Compliance and data protection
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            You must comply with all applicable laws, including data protection and privacy laws, when using our
            services. Our use of personal data is described in our Privacy Policy, which forms part of these Terms.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            5. Suspension and termination
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We may suspend or terminate your access to the services if we reasonably believe there is a breach of
            these Terms, suspicious activity, or risk to customers, partners or payment networks.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            6. Limitation of liability
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            To the maximum extent permitted by law, {{ config('app.name') }} is not liable for indirect, incidental or
            consequential damages, or for any loss of profits, revenue or data arising from your use of the services.
        </p>

        <h2 class="mt-4 mb-2" style="font-size:18px;font-weight:700;color:#f97373;">
            7. Changes to these Terms
        </h2>
        <p style="font-size:14px;color:#ffff;line-height:1.7;">
            We may update these Terms from time to time. Continued use of the services after changes take effect
            constitutes your acceptance of the updated Terms.
        </p>
    </div>
</div>
@endsection

