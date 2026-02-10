<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #fef2f2, #fef9c3, #f9fafb);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card-status {
            max-width: 480px;
            width: 100%;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            background: linear-gradient(145deg, #ffffff, #fefce8);
        }
        .status-icon {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.25);
        }
        .status-icon.failed {
            background: radial-gradient(circle at 30% 30%, #fecaca, #ef4444);
            color: #fef2f2;
        }
        .status-icon.pending {
            background: radial-gradient(circle at 30% 30%, #fee2e2, #f97316);
            color: #fffbeb;
            box-shadow: 0 10px 25px rgba(234, 88, 12, 0.25);
        }
    </style>
</head>
<body>
<div class="container px-3 px-sm-0">
    <div class="card card-status mx-auto p-4 p-md-5 text-center">
        @php
            // If link is partially paid or already paid, show a softer "processing" / "already paid" state
            $isPaid = method_exists($paymentLink, 'isPaid') ? $paymentLink->isPaid() : ($paymentLink->status === 'paid');
            $isPartiallyPaid = method_exists($paymentLink, 'isPartiallyPaid') ? $paymentLink->isPartiallyPaid() : false;
        @endphp

        <div class="d-flex flex-column align-items-center gap-3 mb-3">
            <div class="status-icon {{ $isPaid || $isPartiallyPaid ? 'pending' : 'failed' }}">
                @if($isPaid || $isPartiallyPaid)
                    !
                @else
                    ✕
                @endif
            </div>
            <div>
                @if($isPaid)
                    <h1 class="h4 fw-bold mb-1">Payment Already Completed</h1>
                    <p class="text-muted mb-0">
                        This link has already been marked as paid. No further payment is required.
                    </p>
                @elseif($isPartiallyPaid)
                    <h1 class="h4 fw-bold mb-1">Payment In Progress</h1>
                    <p class="text-muted mb-0">
                        We received a partial payment. Please check your dashboard for the latest status.
                    </p>
                @else
                    <h1 class="h4 fw-bold mb-1">Payment Not Completed</h1>
                    <p class="text-muted mb-0">
                        Your payment could not be confirmed. You can safely retry from the original payment link.
                    </p>
                @endif
            </div>
        </div>

        <p class="text-muted small mb-4">
            If you see the amount debited from your bank/card but this page shows payment not completed,
            please wait a few minutes and refresh your merchant dashboard or contact support with your
            transaction reference.
        </p>

        <a href="{{ url('/pay/'.$paymentLink->link_token) }}" class="btn btn-outline-secondary w-100 mb-2">
            Go back to payment page
        </a>
        <a href="{{ url('/') }}" class="btn btn-secondary w-100">
            Back to home
        </a>
    </div>
</div>
</body>
</html>