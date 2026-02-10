<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #ecfeff, #eff6ff, #f9fafb);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card-success {
            max-width: 480px;
            width: 100%;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            background: linear-gradient(145deg, #ffffff, #f9fafb);
        }
        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 30%, #a7f3d0, #22c55e);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.35);
            color: #ecfdf3;
            font-size: 2.5rem;
        }
        .amount-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .35rem .85rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.08);
            color: #047857;
            font-size: .85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container px-3 px-sm-0">
    <div class="card card-success mx-auto p-4 p-md-5 text-center">
        <div class="d-flex flex-column align-items-center gap-3 mb-3">
            <div class="success-icon">
                ✓
            </div>
            <div>
                <h1 class="h4 fw-bold mb-1">Payment Successful</h1>
                <p class="text-muted mb-0">
                    {{ $paymentLink->title ?? 'Your payment has been received.' }}
                </p>
            </div>
        </div>

        @php
            $currency = $paymentLink->currency ?? 'INR';
            $amount   = number_format($paymentLink->amount ?? 0, 2);
        @endphp

        <div class="mb-4">
            <span class="amount-pill">
                <span>Paid</span>
                <span>{{ $currency }} {{ $amount }}</span>
            </span>
        </div>

        <p class="text-muted small mb-4">
            You can safely close this window. If you opened this from your merchant
            dashboard, your transaction details will be visible there shortly.
        </p>

        <a href="{{ url('/') }}" class="btn btn-success w-100">
            Back to home
        </a>
    </div>
</div>
</body>
</html>