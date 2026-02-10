<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Link Already Used - BadliCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .message-card {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .icon {
            font-size: 80px;
            color: #10b981;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }
        p {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="message-card">
        <i class="bi bi-check-circle-fill icon"></i>
        <h1>Payment Already Completed</h1>
        <p>This payment link has already been used and cannot be used again.</p>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-top: 30px;">
            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Payment Link</div>
            <div style="font-weight: 600; color: #334155;">{{ $paymentLink->title }}</div>
            <div style="margin-top: 12px; font-size: 20px; font-weight: 700; color: #10b981;">
                {{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}
            </div>
            <div style="margin-top: 12px; font-size: 13px; color: #94a3b8;">
                Paid on {{ $paymentLink->paid_at->format('M d, Y \a\t H:i') }}
            </div>
        </div>
        <div style="margin-top: 30px;">
            <i class="bi bi-shield-check" style="color: #6366f1; margin-right: 8px;"></i>
            <span style="color: #64748b; font-size: 14px;">Secured by <strong>BadliCash</strong></span>
        </div>
    </div>
</body>
</html>



