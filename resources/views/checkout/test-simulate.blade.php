<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Test simulation — {{ $paymentLink->title }} — Ipay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(130deg, #2563eb 0%, #7c3aed 40%, #db2777 100%);
        }
        .sim-card {
            max-width: 440px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,.18);
            overflow: hidden;
        }
        .sim-head {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            padding: 20px 22px;
        }
        .sim-body { padding: 22px; }
        .badge-test {
            background: rgba(255,255,255,.2);
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .btn-sim-ok {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 12px;
        }
        .btn-sim-fail {
            background: linear-gradient(135deg, #f87171, #dc2626);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 12px;
        }
        .btn-sim-pending {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 12px;
        }
        .upi-qr-masked-wrap { text-align: center; margin-bottom: 12px; }
        .upi-qr-masked {
            width: 140px; height: 140px; margin: 0 auto; border-radius: 12px; position: relative;
            overflow: hidden;
            background: conic-gradient(from 0deg at 50% 50%, #0f172a 0 25%, #1e293b 0 50%, #0f172a 0 75%, #334155 0 100%);
            background-size: 12px 12px;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,.06), 0 8px 24px rgba(15,23,42,.25);
        }
        .upi-qr-masked::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(145deg, rgba(15,23,42,.9) 0%, transparent 45%),
                linear-gradient(320deg, rgba(51,65,85,.8) 0%, transparent 50%);
        }
        .upi-qr-masked::after {
            content: 'UPI'; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            font-size: 10px; font-weight: 800; letter-spacing: 0.2em; color: rgba(255,255,255,.22);
        }
        .upi-test-vpa {
            font-family: ui-monospace, monospace; font-size: 12px; padding: 8px 10px; border-radius: 8px;
            background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25);
            margin-bottom: 8px; text-align: left;
        }
        .upi-test-vpa strong { color: #4338ca; }
    </style>
</head>
<body>
    <div class="sim-card">
        <div class="sim-head">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="badge-test mb-2">TEST MODE</div>
                    <h1 class="h5 mb-0 fw-bold">Payment simulation</h1>
                    <div class="small opacity-90 mt-1">{{ $paymentLink->merchant->name ?? 'Merchant' }}</div>
                </div>
                <i class="bi bi-flask fs-3 opacity-75"></i>
            </div>
        </div>
        <div class="sim-body">
            <p class="text-muted small mb-3">
                Dedicated <strong>simulation</strong> step — not the live gateway. Choose whether this test payment should succeed or fail.
            </p>
            @php
                $simDisplayAmount = isset($payload['amount']) ? (float) $payload['amount'] : (float) $paymentLink->getRemainingBalance();
            @endphp
            <ul class="list-unstyled small mb-3">
                <li class="mb-2"><strong>Amount:</strong> {{ $paymentLink->currency }} {{ number_format($simDisplayAmount, 2) }}</li>
                <li class="mb-2"><strong>Method:</strong> <span id="methodLabel"></span></li>
                @if(($payload['payment_method'] ?? '') === 'netbanking')
                <li class="mb-2"><strong>Bank:</strong> {{ data_get($payload, 'payment_details.bank_label', data_get($payload, 'payment_details.bank_code', '—')) }}</li>
                @endif
                @if(($payload['payment_method'] ?? '') === 'wallet')
                <li class="mb-2"><strong>Wallet:</strong> {{ data_get($payload, 'payment_details.wallet_provider', '—') }}</li>
                <li class="mb-2 text-muted small">Or set link amount to <strong>101</strong> (success), <strong>102</strong> (fail), <strong>103</strong> (pending) before Pay.</li>
                @endif
                <li class="mb-0"><strong>Customer:</strong> {{ data_get($payload, 'customer_details.name', '—') }}</li>
            </ul>

            @if(($payload['payment_method'] ?? '') === 'upi')
            <div class="border rounded-3 p-3 mb-3 bg-light">
                <div class="small text-muted mb-2">Masked QR (demo only — not scannable)</div>
                <div class="upi-qr-masked-wrap">
                    <div class="upi-qr-masked" aria-hidden="true"></div>
                </div>
                <p class="small text-muted mb-2">Optional: enter a test VPA on checkout next time. Here, pick outcome only:</p>
                <div class="upi-test-vpa"><strong>Success VPA</strong><br>testsuccess@gocash</div>
                <div class="upi-test-vpa"><strong>Failure VPA</strong><br>testfailure@gocash</div>
            </div>
            @endif

            <div class="d-grid gap-2 mb-3">
                <button type="button" class="btn btn-sim-ok rounded-pill" id="simSuccessBtn">
                    <i class="bi bi-check-circle"></i> Simulate success
                </button>
                <button type="button" class="btn btn-sim-fail rounded-pill" id="simFailBtn">
                    <i class="bi bi-x-circle"></i> Simulate failure
                </button>
                @if(($payload['payment_method'] ?? '') === 'wallet')
                <button type="button" class="btn btn-sim-pending rounded-pill" id="simPendingBtn">
                    <i class="bi bi-hourglass-split"></i> Simulate pending
                </button>
                @endif
            </div>

            <a href="{{ route('payment.checkout', ['token' => $paymentLink->link_token]) }}" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                <i class="bi bi-arrow-left"></i> Back to checkout
            </a>

            <div class="alert alert-danger mt-3 mb-0 py-2 small" id="simError" style="display:none;"></div>
        </div>
    </div>

    <script>
        const initialPayload = @json($payload);
        const linkToken = @json($paymentLink->link_token);

        const methodLabels = {
            card: 'Card',
            upi: 'UPI',
            netbanking: 'Net banking',
            wallet: 'Wallet',
        };
        document.getElementById('methodLabel').textContent =
            methodLabels[initialPayload.payment_method] || initialPayload.payment_method || '—';

        async function postSimulate(outcome) {
            const errEl = document.getElementById('simError');
            errEl.style.display = 'none';

            let simulateResult = 'failed';
            if (outcome === 'success') {
                simulateResult = 'success';
            } else if (outcome === 'pending') {
                simulateResult = 'pending';
            }
            const paymentDetails = Object.assign({}, initialPayload.payment_details || {}, {
                simulate: true,
                simulate_result: simulateResult,
            });

            const body = {
                payment_method: initialPayload.payment_method,
                customer_details: initialPayload.customer_details,
                payment_details: paymentDetails,
            };
            if (initialPayload.amount != null && initialPayload.amount !== '') {
                body.amount = initialPayload.amount;
            }

            try {
                const res = await fetch(`/pay/${linkToken}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                const result = await res.json().catch(() => ({}));

                if (result.pending || outcome === 'pending') {
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                        return;
                    }
                    errEl.textContent = result.message || 'Payment is pending.';
                    errEl.className = 'alert alert-warning mt-3 mb-0 py-2 small';
                    errEl.style.display = 'block';
                    return;
                }
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                    return;
                }
                const baseUrl = window.location.origin;
                if (result.success && outcome === 'success' && result.transaction_id) {
                    window.location.href = `${baseUrl}/success-simple.html?transaction_id=${encodeURIComponent(result.transaction_id)}`;
                    return;
                }
                if (!result.success && outcome === 'failure') {
                    window.location.href = `${baseUrl}/failure-simple.html?transaction_id=${encodeURIComponent(result.transaction_id || '')}`;
                    return;
                }
                errEl.textContent = result.message || 'Simulation request failed.';
                errEl.style.display = 'block';
            } catch (e) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            }
        }

        document.getElementById('simSuccessBtn').addEventListener('click', () => postSimulate('success'));
        document.getElementById('simFailBtn').addEventListener('click', () => postSimulate('failure'));
        const simPendingBtn = document.getElementById('simPendingBtn');
        if (simPendingBtn) {
            simPendingBtn.addEventListener('click', () => postSimulate('pending'));
        }
    </script>
</body>
</html>
