<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Payment - {{ $paymentLink->title }} - BadliCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-container {
            max-width: 900px;
            width: 100%;
        }

        .payment-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 300px 1fr;
            max-height: 90vh;
        }

        /* LEFT PANEL */
        .left-panel {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: auto;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .merchant-info {
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }

        .merchant-logo {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .merchant-name {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .merchant-desc {
            font-size: 13px;
            opacity: 0.95;
            line-height: 1.4;
        }

        .amount-section {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .amount-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .amount-value {
            font-size: 30px;
            font-weight: 800;
            margin: 0;
            line-height: 1;
        }

        .test-mode-badge {
            position: relative;
            z-index: 1;
            background: rgba(245, 158, 11, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid rgba(245, 158, 11, 0.4);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 18px;
        }

        .test-mode-badge strong {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .test-mode-badge div {
            font-size: 11px;
            line-height: 1.5;
            opacity: 0.95;
        }

        .secured-by {
            position: relative;
            z-index: 1;
            margin-top: auto;
            padding-top: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            opacity: 0.95;
        }

        /* RIGHT PANEL */
        .right-panel {
            padding: 25px 28px;
            overflow-y: auto;
            max-height: 90vh;
        }

        .panel-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 20px;
        }

        .alert strong {
            font-size: 14px;
        }

        .alert div {
            font-size: 13px;
        }

        /* CUSTOMER FORM */
        .customer-section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 12px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
            display: block;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        /* Hide browser autofill warnings and payment method warnings */
        input::-webkit-credentials-auto-fill-button,
        input::-webkit-contacts-auto-fill-button,
        input::-webkit-credit-card-auto-fill-button {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Prevent browser from showing payment warnings */
        #cardForm input {
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }

        /* Suppress browser autofill warnings */
        form[autocomplete="off"] input {
            background-image: none !important;
        }

        /* Hide any browser-generated tooltips */
        input[data-lpignore="true"]::after,
        input[data-lpignore="true"]::before {
            content: none !important;
            display: none !important;
        }

        /* Hide browser payment warnings - ALL tooltips outside our content */
        div[role="tooltip"]:not(.payment-card *):not(.left-panel *):not(.right-panel *):not(.alert):not(.customer-section *):not(.payment-form *) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        }

        .form-control.is-valid {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        #amountError {
            display: block;
            margin-top: 6px;
            padding: 8px 12px;
            background-color: #fee;
            border: 1px solid #fcc;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #dc3545;
        }

        /* PAYMENT METHODS */
        .payment-methods-section {
            margin-bottom: 18px;
        }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .payment-method-btn {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .payment-method-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
            background: white;
        }

        .payment-method-btn.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.08) 100%);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .payment-method-btn i {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 6px;
            display: block;
        }

        .payment-method-btn .method-label {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            display: block;
            margin-bottom: 2px;
        }

        .payment-method-btn .method-desc {
            font-size: 10px;
            color: #64748b;
        }

        .payment-method-btn.active .method-label {
            color: var(--primary);
        }

        /* PAYMENT FORMS */
        .payment-form {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .payment-form.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-preview {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 24px;
            border-radius: 14px;
            margin-bottom: 25px;
        }

        .card-number-display {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            letter-spacing: 3px;
            margin: 15px 0;
        }

        /* PAY BUTTON */
        .pay-button {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .pay-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .pay-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .pay-button:not(:disabled):hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(99, 102, 241, 0.4);
        }

        .pay-button:not(:disabled):active {
            transform: translateY(-1px);
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 992px) {
            .payment-card {
                grid-template-columns: 1fr;
            }
            .left-panel {
                padding: 35px 25px;
                min-height: auto;
            }
            .payment-methods-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <!-- LEFT PANEL -->
            <div class="left-panel">
                <div class="merchant-info">
                    <div class="merchant-logo">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h1 class="merchant-name">{{ $paymentLink->title }}</h1>
                    @if($paymentLink->description)
                        <p class="merchant-desc">{{ $paymentLink->description }}</p>
                    @endif
                </div>

                <div class="amount-section">
                    <div class="amount-label">Total Amount</div>
                    <h2 class="amount-value">{{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}</h2>
                    @if($paymentLink->allow_partial_payment)
                        @if(($paymentLink->amount_paid ?? 0) > 0)
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 6px;">Amount Paid</div>
                                <div style="font-size: 18px; font-weight: 600; color: #10b981;">{{ $paymentLink->currency }} {{ number_format($paymentLink->amount_paid ?? 0, 2) }}</div>
                                <div style="font-size: 12px; opacity: 0.9; margin-top: 8px; margin-bottom: 6px;">Remaining Balance</div>
                                <div style="font-size: 18px; font-weight: 600; color: #fbbf24;">{{ $paymentLink->currency }} {{ number_format($paymentLink->getRemainingBalance(), 2) }}</div>
                            </div>
                        @else
                            <div style="margin-top: 10px; padding: 10px; background: rgba(251, 191, 36, 0.15); border-radius: 8px; border: 1px solid rgba(251, 191, 36, 0.3);">
                                <div style="font-size: 12px; opacity: 0.95; margin-bottom: 4px;">
                                    <i class="bi bi-info-circle"></i> Partial Payment Enabled
                                </div>
                                <div style="font-size: 13px; opacity: 0.9;">
                                    Pay any amount up to {{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                @if($paymentLink->test_mode)
                <div class="test-mode-badge">
                    <strong><i class="bi bi-info-circle"></i> TEST MODE - Simulate Payment</strong>
                    <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                        <button class="btn btn-sm btn-success" id="simulateSuccessBtn" style="width: 100%; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.5); color: white; font-weight: 600;">
                            <i class="bi bi-check-circle"></i> Simulate Success
                        </button>
                        <button class="btn btn-sm btn-danger" id="simulateFailBtn" style="width: 100%; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); color: white; font-weight: 600;">
                            <i class="bi bi-x-circle"></i> Simulate Failure
                        </button>
                    </div>
                </div>
                @endif

                <div class="secured-by">
                    <i class="bi bi-shield-check" style="font-size: 22px;"></i>
                    <span>Secured by <strong>BadliCash</strong></span>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel" id="paymentApp">
                <h2 class="panel-title">Complete Your Payment</h2>

                <!-- Success/Error Messages -->
                <div class="alert alert-success" id="successAlert" style="display: none;">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>Payment Successful!</strong>
                        <div style="font-size: 14px; margin-top: 4px;" id="successMessage"></div>
                    </div>
                </div>

                <div class="alert alert-danger" id="errorAlert" style="display: none;">
                    <i class="bi bi-x-circle-fill"></i>
                    <div>
                        <strong>Payment Failed</strong>
                        <div style="font-size: 14px; margin-top: 4px;" id="errorMessage"></div>
                    </div>
                </div>

                @php
                    // Check allow_partial_payment - the model casts it to boolean, so just check if truthy
                    $isPartialEnabled = (bool)($paymentLink->allow_partial_payment ?? false);
                @endphp
                
                @if($isPartialEnabled)
                <!-- Partial Payment Amount - SHOWN FIRST -->
                <div class="customer-section" id="partialPaymentSection" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 2px solid #667eea; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); position: relative; z-index: 10; display: block !important; visibility: visible !important; opacity: 1 !important;">
                    <h4 class="section-title" style="color: white; margin-bottom: 16px; font-size: 18px; font-weight: 700;">
                        <i class="bi bi-cash-coin" style="margin-right: 10px; font-size: 20px;"></i>Enter Payment Amount
                    </h4>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label" style="font-weight: 600; color: white; font-size: 14px; margin-bottom: 10px;">
                                How much would you like to pay? <span class="text-danger">*</span>
                            </label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <span class="input-group-text" style="background: white; color: #667eea; font-weight: 700; font-size: 16px; border: none;">{{ $paymentLink->currency }}</span>
                                <input type="number" class="form-control form-control-lg" id="customAmount" 
                                       placeholder="Enter amount (max: {{ number_format($paymentLink->amount, 2) }})" 
                                       min="0.01" 
                                       max="{{ $paymentLink->amount }}" 
                                       step="0.01"
                                       value="{{ $paymentLink->getRemainingBalance() }}"
                                       required
                                       style="font-size: 18px; font-weight: 700; padding: 14px; border: none; border-radius: 0 8px 8px 0;">
                            </div>
                            <div id="amountError" class="text-danger mt-2" style="display: none; padding: 10px 14px; background-color: rgba(255, 255, 255, 0.95); border: 2px solid #dc3545; border-radius: 8px; font-size: 14px; font-weight: 600; color: #dc3545;"></div>
                            <small class="d-block mt-2" style="font-size: 13px; color: rgba(255, 255, 255, 0.9);">
                                @if(($paymentLink->amount_paid ?? 0) > 0)
                                    <strong>✓ Already paid:</strong> {{ $paymentLink->currency }} {{ number_format($paymentLink->amount_paid, 2) }} | 
                                    <strong>Remaining:</strong> {{ $paymentLink->currency }} {{ number_format($paymentLink->getRemainingBalance(), 2) }}
                                @else
                                    <strong>Total amount:</strong> {{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}. 
                                    <strong>You can pay any amount up to this total.</strong>
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Customer Details -->
                <div class="customer-section">
                    <h4 class="section-title">Customer Details</h4>
                    <form autocomplete="off" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customerName" name="customerName" autocomplete="off" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="customerEmail" name="customerEmail" autocomplete="off" placeholder="john@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="customerPhone" name="customerPhone" autocomplete="off" placeholder="9876543210" maxlength="10" pattern="[0-9]{10}" required>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Payment Methods -->
                <div class="payment-methods-section">
                    <h4 class="section-title">Select Payment Method</h4>
                    <div class="payment-methods-grid">
                        <div class="payment-method-btn active" data-method="card">
                            <i class="bi bi-credit-card-fill"></i>
                            <div class="method-label">Card</div>
                            <div class="method-desc">Credit/Debit</div>
                        </div>
                        <div class="payment-method-btn" data-method="upi">
                            <i class="bi bi-phone-fill"></i>
                            <div class="method-label">UPI</div>
                            <div class="method-desc">Pay via UPI</div>
                        </div>
                        <div class="payment-method-btn" data-method="netbanking">
                            <i class="bi bi-bank"></i>
                            <div class="method-label">Net Banking</div>
                            <div class="method-desc">Online Banking</div>
                        </div>
                        <div class="payment-method-btn" data-method="wallet">
                            <i class="bi bi-wallet2"></i>
                            <div class="method-label">Wallets</div>
                            <div class="method-desc">Paytm, PhonePe</div>
                        </div>
                    </div>
                </div>

                <!-- CARD FORM -->
                <div class="payment-form active" id="cardForm">
                    <form autocomplete="off" novalidate spellcheck="false" data-form-type="other">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Card Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="cardNumber" name="card_number_field" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" autocomplete="off" spellcheck="false" placeholder="4242 4242 4242 4242" maxlength="19" inputmode="numeric" pattern="[0-9\s]*">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Card Holder Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cardHolder" name="card_holder_field" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" autocomplete="off" spellcheck="false" placeholder="JOHN DOE" style="text-transform: uppercase;">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Month <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="expiryMonth" name="expiry_month_field" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" autocomplete="off" spellcheck="false" placeholder="12" maxlength="2" inputmode="numeric" pattern="[0-9]*">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="expiryYear" name="expiry_year_field" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" autocomplete="off" spellcheck="false" placeholder="2025" maxlength="4" inputmode="numeric" pattern="[0-9]*">
                            </div>
                            <div class="col-4">
                                <label class="form-label">CVV <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="cvv" name="cvv_field" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" autocomplete="off" spellcheck="false" placeholder="123" maxlength="3" inputmode="numeric" pattern="[0-9]*">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- UPI FORM -->
                <div class="payment-form" id="upiForm">
                    <div class="mb-3">
                        <label class="form-label">UPI ID</label>
                        <input type="text" class="form-control" id="upiId" placeholder="yourname@upi">
                        <div style="text-align: center; margin: 20px 0; color: #94a3b8; font-weight: 600;">OR</div>
                        <label class="form-label">Choose UPI App</label>
                        <select class="form-select" id="upiApp">
                            <option value="">Select UPI App</option>
                            <option value="gpay">Google Pay</option>
                            <option value="phonepe">PhonePe</option>
                            <option value="paytm">Paytm</option>
                            <option value="amazonpay">Amazon Pay</option>
                        </select>
                    </div>
                </div>

                <!-- NET BANKING FORM -->
                <div class="payment-form" id="netbankingForm">
                    <div class="mb-3">
                        <label class="form-label">Select Your Bank <span class="text-danger">*</span></label>
                        <select class="form-select" id="bankCode">
                            <option value="">Choose your bank</option>
                            <option value="SBI">State Bank of India</option>
                            <option value="HDFC">HDFC Bank</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="AXIS">Axis Bank</option>
                            <option value="KOTAK">Kotak Mahindra Bank</option>
                            <option value="PNB">Punjab National Bank</option>
                            <option value="BOB">Bank of Baroda</option>
                            <option value="IDBI">IDBI Bank</option>
                            <option value="YES">Yes Bank</option>
                        </select>
                    </div>
                </div>

                <!-- WALLET FORM -->
                <div class="payment-form" id="walletForm">
                    <div class="mb-3">
                        <label class="form-label">Select Wallet <span class="text-danger">*</span></label>
                        <select class="form-select" id="walletProvider">
                            <option value="">Choose wallet</option>
                            <option value="paytm">Paytm</option>
                            <option value="phonepe">PhonePe</option>
                            <option value="mobikwik">MobiKwik</option>
                            <option value="freecharge">Freecharge</option>
                            <option value="amazonpay">Amazon Pay</option>
                        </select>
                    </div>
                </div>


                <!-- PAY BUTTON -->
                <button class="pay-button" id="payButton" disabled>
                    <span id="payButtonText">Enter details to continue</span>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Razorpay Checkout.js -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Cashfree PG v3 SDK -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        const paymentLink = @json($paymentLink);
        let selectedMethod = 'card';
        
        const payButton = document.getElementById('payButton');
        const payButtonText = document.getElementById('payButtonText');
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        // CashFree Checkout Initialization Function
        function initializeCashFreeCheckout(result) {
            try {
                console.log('Initializing CashFree checkout...', result);
                
                // Check if CashFree SDK is loaded
                if (typeof Cashfree === 'undefined') {
                    console.error('CashFree SDK not loaded');
                    errorMessage.textContent = 'Payment SDK failed to load. Please refresh the page.';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    payButton.dataset.processing = '';
                    return;
                }

                // Cashfree SDK mode MUST match where the order was created (backend uses acquirer test/live)
                const cashfreeMode = (result.cashfree_mode === 'production' || result.cashfree_mode === 'sandbox')
                    ? result.cashfree_mode
                    : (paymentLink.test_mode ? 'sandbox' : 'production');
                const cashfree = Cashfree({ mode: cashfreeMode });

                const sessionId = result.payment_session_id;
                if (!sessionId || typeof sessionId !== 'string' || sessionId.trim() === '') {
                    console.error('CashFree: payment_session_id missing or invalid', result);
                    errorMessage.textContent = 'Payment session not created. Please try again.';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    payButton.dataset.processing = '';
                    return;
                }

                // Prepare checkout options as per CashFree PG v3 documentation
                const checkoutOptions = {
                    paymentSessionId: sessionId.trim(),
                    redirectTarget: "_modal", // Modal overlay - NO page redirect
                };

                console.log('CashFree checkout options:', checkoutOptions);
                console.log('Opening CashFree modal (no redirect)...');

                // Open CashFree checkout in modal
                cashfree.checkout(checkoutOptions).then(function(checkoutResult) {
                    console.log('CashFree checkout completed:', checkoutResult);
                    console.log('Checkout result keys:', Object.keys(checkoutResult || {}));
                    console.log('Checkout result full object:', JSON.stringify(checkoutResult, null, 2));
                    console.log('Payment initiation result:', result);
                    
                    // Modal closed - user completed payment flow
                    // CashFree checkout callback fires when modal closes (payment may or may not be complete)
                    // Show success message immediately, then verify in background
                    // Webhooks will handle final status confirmation
                    
                    const orderIdToVerify = result.gateway_order_id;
                    
                    if (!orderIdToVerify) {
                        console.error('No gateway_order_id available for verification!', {
                            'result': result,
                            'checkoutResult': checkoutResult
                        });
                        errorMessage.textContent = 'Payment completed but order ID not found. Please contact support.';
                        errorAlert.style.display = 'flex';
                        payButton.disabled = false;
                        payButton.dataset.processing = '';
                        return;
                    }
                    
                    console.log('Using gateway_order_id for verification:', orderIdToVerify);
                    console.log('Available IDs:', {
                        'result.gateway_order_id': result.gateway_order_id,
                        'checkoutResult.orderId': checkoutResult?.orderId,
                        'checkoutResult.order_id': checkoutResult?.order_id,
                        'checkoutResult.cf_order_id': checkoutResult?.cf_order_id,
                        'using': orderIdToVerify
                    });
                    
                    // Show immediate success message (user completed payment flow)
                    // Verification will happen in background, webhooks will confirm final status
                    successMessage.textContent = 'Payment completed! Verifying payment status...';
                    successAlert.style.display = 'flex';
                    payButtonText.innerHTML = '<span class="spinner"></span> Verifying...';
                    payButton.disabled = true;
                    
                    // Reset retry counter for this verification attempt
                    verifyCashFreePayment.attempts = 0;
                    
                    // Wait 5 seconds before first verification (CashFree needs more time to process the payment)
                    // Increased from 2 seconds to 5 seconds to allow CashFree's system to fully process
                    setTimeout(() => {
                        verifyCashFreePayment(orderIdToVerify, result.transaction_id);
                    }, 5000);
                    
                }).catch(function(error) {
                    console.error('CashFree checkout error:', error);
                    errorMessage.textContent = error.message || 'Payment processing failed';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    payButton.dataset.processing = '';
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                    @endif
                });

            } catch (error) {
                console.error('CashFree checkout initialization error:', error);
                errorMessage.textContent = 'Failed to initialize payment. Please try again.';
                errorAlert.style.display = 'flex';
                payButton.disabled = false;
                payButton.dataset.processing = '';
                @if($paymentLink->allow_partial_payment)
                const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
            }
        }

        // Verify CashFree payment status after modal closes
        async function verifyCashFreePayment(gatewayOrderId, transactionId) {
            try {
                console.log('Verifying CashFree payment status...', { gatewayOrderId, transactionId });
                
                const verifyResponse = await fetch(`/pay/{{ $paymentLink->link_token }}/verify-cashfree`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        gateway_order_id: gatewayOrderId,
                        transaction_id: transactionId,
                    }),
                });

                // Handle non-200 responses
                if (!verifyResponse.ok) {
                    const errorData = await verifyResponse.json().catch(() => ({}));
                    console.warn('CashFree verification returned non-200:', verifyResponse.status, errorData);
                    
                    // Check if it's a permanent error (should not retry)
                    // Note: "Order Reference Id does not exist" might be temporary if CashFree is still processing
                    const isPermanentError = errorData.status === 'failed' || 
                                           (errorData.message && (
                                               errorData.message.toLowerCase().includes('invalid') ||
                                               errorData.message.toLowerCase().includes('acquirer account not found') ||
                                               errorData.message.toLowerCase().includes('authentication failed')
                                           ));
                    
                    // If 400/404, treat as potentially temporary and retry (order might not be ready yet)
                    // CashFree might need time to process the order after checkout completes
                    if (verifyResponse.status === 400 || verifyResponse.status === 404) {
                        let attempts = verifyCashFreePayment.attempts || 0;
                        verifyCashFreePayment.attempts = attempts + 1;
                        
                        // Retry up to 8 times with increasing delays
                        // First few attempts: 3 seconds, then 5 seconds, then 10 seconds
                        const delays = [3000, 3000, 5000, 5000, 10000, 10000, 15000, 15000];
                        const delay = delays[Math.min(attempts - 1, delays.length - 1)] || 15000;
                        
                        if (attempts < 8 && !isPermanentError) {
                            console.log(`Verification returned ${verifyResponse.status} (${errorData.message || 'unknown error'}), retrying in ${delay/1000} seconds... (attempt ${attempts + 1}/8)`);
                            setTimeout(() => {
                                verifyCashFreePayment(gatewayOrderId, transactionId);
                            }, delay);
                            return;
                        } else {
                            // Max attempts reached - CashFree might have propagation delays
                            // Show pending message and rely on webhooks for final status
                            console.warn('CashFree verification: Max retries reached. Order may still be processing. Webhooks will update final status.');
                            successMessage.textContent = 'Payment completed! Your payment is being processed. You will receive a confirmation shortly. If payment was successful, your order will be updated automatically.';
                            successAlert.style.display = 'flex';
                            payButton.disabled = false;
                            payButton.dataset.processing = '';
                            payButtonText.textContent = 'Payment Processing...';
                            payButton.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                            
                            // Hide error alert if it was shown
                            errorAlert.style.display = 'none';
                            return;
                        }
                    }
                    
                    // Other permanent errors - don't retry
                    if (isPermanentError) {
                        errorMessage.textContent = errorData.message || 'Payment verification failed. Please contact support.';
                        errorAlert.style.display = 'flex';
                        payButton.disabled = false;
                        payButton.dataset.processing = '';
                        @if($paymentLink->allow_partial_payment)
                        const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                        payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                        @else
                        payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                        @endif
                        return;
                    }
                    
                    // Other errors - show error message
                    errorMessage.textContent = errorData.message || 'Failed to verify payment status';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    payButton.dataset.processing = '';
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                    @endif
                    return;
                }

                const verifyResult = await verifyResponse.json();
                console.log('CashFree payment verification result:', verifyResult);

                if (verifyResult.success) {
                    if (verifyResult.status === 'success') {
                        // Payment successful
                        const finalTxnId = verifyResult.transaction_id || transactionId;
                        successMessage.textContent = `Payment successful! Transaction ID: ${finalTxnId}`;
                        successAlert.style.display = 'flex';
                        payButtonText.textContent = 'Payment Successful!';
                        payButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                        payButton.disabled = false;
                        payButton.dataset.processing = '';
                        
                        // Redirect to the existing branded success page (public/success-simple.html)
                        // which is the one already designed for merchants.
                        setTimeout(() => {
                            const baseUrl = window.location.origin || '';
                            window.location.href = `${baseUrl}/success-simple.html?transaction_id=${encodeURIComponent(finalTxnId || '')}`;
                        }, 2000);
                    } else if (verifyResult.status === 'failed') {
                        // Payment failed
                        errorMessage.textContent = verifyResult.message || 'Payment processing failed';
                        errorAlert.style.display = 'flex';
                        payButton.disabled = false;
                        payButton.dataset.processing = '';
                        @if($paymentLink->allow_partial_payment)
                        const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                        payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                        @else
                        payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                        @endif
                    } else {
                        // Still pending - wait a bit and check again (max 5 attempts)
                        let attempts = verifyCashFreePayment.attempts || 0;
                        verifyCashFreePayment.attempts = attempts + 1;
                        
                        if (attempts < 5) {
                            console.log(`Payment still pending, checking again in 2 seconds... (attempt ${attempts + 1}/5)`);
                            setTimeout(() => {
                                verifyCashFreePayment(gatewayOrderId, transactionId);
                            }, 2000);
                        } else {
                            // Max attempts reached - show message
                            errorMessage.textContent = 'Payment is being processed. Please check your dashboard for status.';
                            errorAlert.style.display = 'flex';
                            payButton.disabled = false;
                            payButton.dataset.processing = '';
                            @if($paymentLink->allow_partial_payment)
                            const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                            @else
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                            @endif
                        }
                    }
                } else {
                    // Verification failed - show error
                    errorMessage.textContent = verifyResult.message || 'Failed to verify payment status';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    payButton.dataset.processing = '';
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                    @endif
                }
            } catch (error) {
                console.error('Error verifying CashFree payment:', error);
                errorMessage.textContent = 'Failed to verify payment. Please check your dashboard.';
                errorAlert.style.display = 'flex';
                payButton.disabled = false;
                payButton.dataset.processing = '';
                @if($paymentLink->allow_partial_payment)
                const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
            }
        }

        // Payment method switching
        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const method = btn.dataset.method;
                selectedMethod = method;
                document.querySelectorAll('.payment-form').forEach(f => f.classList.remove('active'));
                const formEl = document.getElementById(method + 'Form');
                if (formEl) formEl.classList.add('active');
                
                validateForm();
            });
        });

        // Prevent browser autofill warnings and payment detection - AGGRESSIVE APPROACH
        document.querySelectorAll('#cardForm input').forEach(input => {
            // Set all anti-detection attributes
            input.setAttribute('data-lpignore', 'true');
            input.setAttribute('data-1p-ignore', 'true');
            input.setAttribute('data-bwignore', 'true');
            input.setAttribute('data-form-type', 'other');
            input.setAttribute('autocomplete', 'new-password');
            input.setAttribute('spellcheck', 'false');
            input.setAttribute('data-payment', 'false');
            
            // Remove readonly on first interaction
            const removeReadonly = function(e) {
                if (this.hasAttribute('readonly')) {
                    this.removeAttribute('readonly');
                }
                this.setAttribute('autocomplete', 'new-password');
                e.stopPropagation();
            };
            
            input.addEventListener('focus', removeReadonly, { once: true });
            input.addEventListener('click', removeReadonly, { once: true });
            input.addEventListener('touchstart', removeReadonly, { once: true });
        });

        // Simple warning removal - won't break the page
        (function() {
            function removeWarnings() {
                try {
                    document.querySelectorAll('div').forEach(div => {
                        const text = (div.textContent || '').toLowerCase();
                        if ((text.includes('secure connection') || text.includes('automatic payment')) &&
                            !div.closest('.payment-card') && 
                            !div.closest('.left-panel') && 
                            !div.closest('.right-panel') &&
                            !div.closest('.alert')) {
                            div.style.display = 'none';
                            setTimeout(() => {
                                try { div.remove(); } catch(e) {}
                            }, 100);
                        }
                    });
                } catch(e) {
                    // Ignore errors
                }
            }
            
            // Run after page loads
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(removeWarnings, 100);
                    setInterval(removeWarnings, 500);
                });
            } else {
                setTimeout(removeWarnings, 100);
                setInterval(removeWarnings, 500);
            }
            
            // Watch for new warnings
            try {
                const observer = new MutationObserver(function() {
                    removeWarnings();
                });
                if (document.body) {
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            } catch(e) {}
        })();

        // Card number formatting
        const cardNumberInput = document.getElementById('cardNumber');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\s/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
                validateForm(true); // Silent validation on input
            });
        }

        // Real-time validation (debounced)
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', () => validateForm(true)); // Silent validation on input
            input.addEventListener('change', () => validateForm(false)); // Log on change
        });

        // Update pay button text and validate amount in real-time (for partial payments)
        @if($paymentLink->allow_partial_payment)
        const customAmountInput = document.getElementById('customAmount');
        const amountErrorDiv = document.getElementById('amountError');
        
        if (customAmountInput && amountErrorDiv) {
            const remainingBalanceValue = {{ $paymentLink->getRemainingBalance() }};
            const totalAmountValue = {{ $paymentLink->amount }};
            
            customAmountInput.addEventListener('input', () => {
                const customAmount = parseFloat(customAmountInput.value) || 0;
                const remainingBalance = parseFloat(remainingBalanceValue);
                const totalAmount = parseFloat(totalAmountValue);
                
                // Clear previous error styling
                customAmountInput.classList.remove('is-invalid', 'is-valid');
                amountErrorDiv.style.display = 'none';
                amountErrorDiv.textContent = '';
                
                // Validate amount
                if (customAmountInput.value && customAmountInput.value.trim() !== '') {
                    // First check: Amount cannot exceed total amount
                    if (customAmount > totalAmount) {
                        customAmountInput.classList.add('is-invalid');
                        customAmountInput.classList.remove('is-valid');
                        amountErrorDiv.textContent = `❌ Error: Amount cannot exceed total amount of ${paymentLink.currency} ${totalAmount.toFixed(2)}`;
                        amountErrorDiv.style.display = 'block';
                        amountErrorDiv.style.color = '#dc3545';
                        amountErrorDiv.style.fontWeight = '600';
                        if (payButton) {
                            payButton.disabled = true;
                            payButtonText.textContent = 'Invalid Amount';
                        }
                        return;
                    }
                    
                    // Second check: Amount cannot exceed remaining balance
                    if (customAmount > remainingBalance) {
                        customAmountInput.classList.add('is-invalid');
                        customAmountInput.classList.remove('is-valid');
                        amountErrorDiv.textContent = `❌ Error: Amount cannot exceed remaining balance of ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                        amountErrorDiv.style.display = 'block';
                        amountErrorDiv.style.color = '#dc3545';
                        amountErrorDiv.style.fontWeight = '600';
                        if (payButton) {
                            payButton.disabled = true;
                            payButtonText.textContent = 'Invalid Amount';
                        }
                        return;
                    }
                    
                    // Third check: Amount must be at least 0.01
                    if (customAmount < 0.01) {
                        customAmountInput.classList.add('is-invalid');
                        customAmountInput.classList.remove('is-valid');
                        amountErrorDiv.textContent = '❌ Error: Amount must be at least 0.01';
                        amountErrorDiv.style.display = 'block';
                        amountErrorDiv.style.color = '#dc3545';
                        amountErrorDiv.style.fontWeight = '600';
                        if (payButton) {
                            payButton.disabled = true;
                            payButtonText.textContent = 'Invalid Amount';
                        }
                        return;
                    }
                    
                    // Valid amount - clear errors
                    customAmountInput.classList.remove('is-invalid');
                    customAmountInput.classList.add('is-valid');
                    amountErrorDiv.style.display = 'none';
                    amountErrorDiv.textContent = '';
                    
                    // Update pay button text immediately with custom amount
                    if (payButton && payButtonText) {
                        // Check if form is valid (customer details and payment method)
                        validateForm(true); // Silent validation
                        if (!payButton.disabled) {
                            payButton.disabled = false;
                            // Force update to custom amount (don't let validateForm override it)
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${customAmount.toFixed(2)}`;
                        } else {
                            payButton.disabled = true;
                            payButtonText.textContent = 'Complete payment details';
                        }
                    }
                } else {
                    // Empty input - reset to default
                    customAmountInput.classList.remove('is-invalid', 'is-valid');
                    amountErrorDiv.style.display = 'none';
                    if (payButton) {
                        validateForm(true); // Silent validation
                        if (!payButton.disabled) {
                            payButton.disabled = false;
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalanceValue.toFixed(2)}`;
                        } else {
                            payButton.disabled = true;
                            payButtonText.textContent = 'Complete payment details';
                        }
                    }
                }
            });
        }
        @endif

        // Debounce validation to prevent excessive calls
        let validationTimeout = null;
        
        // Store payment link values at page load to avoid Blade syntax issues in callbacks
        const paymentLinkAmount = {{ $paymentLink->amount }};
        const paymentLinkCurrency = '{{ $paymentLink->currency }}';
        @if($paymentLink->allow_partial_payment)
        const paymentLinkRemainingBalance = {{ $paymentLink->getRemainingBalance() }};
        @endif
        
        function validateForm(silent = false) {
            // Clear any pending validation
            if (validationTimeout) {
                clearTimeout(validationTimeout);
            }
            
            // Debounce validation - only run after 200ms of no changes
            validationTimeout = setTimeout(() => {
                const name = document.getElementById('customerName')?.value.trim() || '';
                const email = document.getElementById('customerEmail')?.value.trim() || '';
                const phone = document.getElementById('customerPhone')?.value.trim() || '';
                
                if (!silent) {
                    console.log('Validating form:', { 
                        hasName: !!name, 
                        hasEmail: !!email, 
                        phoneLength: phone.length,
                        selectedMethod 
                    });
                }
                
                // Always validate customer details first - ALL fields are required
                // CRITICAL: Validate ALL customer details - phone is REQUIRED
                const nameTrimmed = (name || '').trim();
                const emailTrimmed = (email || '').trim();
                const phoneTrimmed = (phone || '').trim();
                
                if (!nameTrimmed || !emailTrimmed || !phoneTrimmed || phoneTrimmed.length !== 10) {
                    // Build list of missing fields
                    let missingFields = [];
                    if (!nameTrimmed) missingFields.push('Name');
                    if (!emailTrimmed) missingFields.push('Email');
                    if (!phoneTrimmed || phoneTrimmed.length !== 10) missingFields.push('Phone (10 digits)');
                    
                    if (payButton) {
                        payButton.disabled = true;
                        if (payButtonText) {
                            payButtonText.textContent = `Enter ${missingFields.join(', ')}`;
                        }
                    }
                    if (!silent) {
                        console.log('Validation failed: Customer details incomplete', {
                            name: nameTrimmed.length,
                            email: emailTrimmed.length,
                            phone: phoneTrimmed.length,
                            missingFields: missingFields
                        });
                    }
                    return;
                }

                let methodValid = false;
                
                if (selectedMethod === 'card') {
                    // For card payments, backend will decide if Razorpay Checkout.js should be used
                    // If Razorpay is configured, we don't need card details here
                    // Button is enabled if customer details are filled (already validated above)
                    methodValid = true;
                    if (!silent) {
                        console.log('✅ Card payment selected - button enabled (backend/Razorpay will handle card validation)');
                    }
                } else if (selectedMethod === 'upi') {
                    const upiId = document.getElementById('upiId')?.value.trim();
                    const upiApp = document.getElementById('upiApp')?.value;
                    methodValid = !!(upiId || upiApp);
                } else if (selectedMethod === 'netbanking') {
                    methodValid = !!document.getElementById('bankCode')?.value;
                } else if (selectedMethod === 'wallet') {
                    methodValid = !!document.getElementById('walletProvider')?.value;
                }

                if (methodValid && payButton && payButtonText) {
                    payButton.disabled = false;
                    @if($paymentLink->allow_partial_payment)
                        // Check if custom amount is entered
                        const customAmountInput = document.getElementById('customAmount');
                        if (customAmountInput && customAmountInput.value && customAmountInput.value.trim() !== '') {
                            const customAmount = parseFloat(customAmountInput.value) || 0;
                            
                            // Only update if amount is valid
                            if (customAmount >= 0.01 && customAmount <= paymentLinkAmount && customAmount <= paymentLinkRemainingBalance) {
                                payButtonText.textContent = `Pay ${paymentLinkCurrency} ${customAmount.toFixed(2)}`;
                            } else {
                                payButtonText.textContent = `Pay ${paymentLinkCurrency} ${paymentLinkRemainingBalance.toFixed(2)}`;
                            }
                        } else {
                            payButtonText.textContent = `Pay ${paymentLinkCurrency} ${paymentLinkRemainingBalance.toFixed(2)}`;
                        }
                    @else
                        payButtonText.textContent = `Pay ${paymentLinkCurrency} ${paymentLinkAmount.toFixed(2)}`;
                    @endif
                } else if (payButton && payButtonText) {
                    payButton.disabled = true;
                    payButtonText.textContent = 'Complete payment details';
                }
            }, 200); // Debounce by 200ms
        }

        // Process payment
        payButton.addEventListener('click', async (event) => {
            // CRITICAL: Prevent default form submission and event bubbling
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            
            if (payButton.disabled) {
                console.log('Pay button is disabled, ignoring click');
                return false;
            }
            
            // Prevent duplicate clicks
            if (payButton.dataset.processing === 'true') {
                console.log('Payment already processing, ignoring duplicate click');
                return false;
            }
            
            // Prevent if Razorpay modal is already opening
            if (payButton.dataset.razorpayOpening === 'true') {
                console.log('Razorpay modal already opening, ignoring click');
                return false;
            }
            
            payButton.dataset.processing = 'true';
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';
            
            payButton.disabled = true;
            payButtonText.innerHTML = '<span class="spinner"></span> Processing...';

            const paymentData = {
                payment_method: selectedMethod,
                customer_details: {
                    name: document.getElementById('customerName').value.trim(),
                    email: document.getElementById('customerEmail').value.trim(),
                    phone: document.getElementById('customerPhone').value.trim(),
                },
                payment_details: {}
            };

            // For card payments, send card details for CashFree and other acquirers
            // For Razorpay, card details are collected via Checkout.js, so we don't send them
            if (selectedMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber')?.value.replace(/\s/g, '') || '';
                const expiryMonth = document.getElementById('expiryMonth')?.value.trim() || '';
                const expiryYear = document.getElementById('expiryYear')?.value.trim() || '';
                const cardCvv = document.getElementById('cvv')?.value.trim() || '';
                const cardHolder = document.getElementById('cardHolder')?.value.trim() || '';
                
                // Format expiry month (ensure 2 digits)
                const formattedMonth = expiryMonth.padStart(2, '0');
                // Format expiry year (ensure 4 digits)
                let formattedYear = expiryYear;
                if (formattedYear.length === 2) {
                    formattedYear = '20' + formattedYear;
                }
                
                // Send card details - backend will determine if Razorpay (uses Checkout.js) or CashFree (needs details)
                // If Razorpay, backend will ignore payment_details and use Checkout.js
                // If CashFree or other acquirer, backend will use the card details
                if (cardNumber && formattedMonth && formattedYear && cardCvv && cardHolder) {
                    paymentData.payment_details = {
                        card_number: cardNumber,
                        expiry_month: formattedMonth,
                        expiry_year: formattedYear,
                        cvv: cardCvv,
                        card_holder: cardHolder,
                    };
                } else {
                    // If card details are incomplete, still send what we have
                    // Backend will validate and return appropriate error
                    paymentData.payment_details = {
                        card_number: cardNumber || '',
                        expiry_month: formattedMonth || '',
                        expiry_year: formattedYear || '',
                        cvv: cardCvv || '',
                        card_holder: cardHolder || '',
                    };
                }
            } else if (selectedMethod === 'upi') {
                paymentData.payment_details = {
                    upi_id: document.getElementById('upiId').value.trim() || null,
                    upi_app: document.getElementById('upiApp').value || null,
                };
            } else if (selectedMethod === 'netbanking') {
                paymentData.payment_details = {
                    bank_code: document.getElementById('bankCode').value,
                };
            } else if (selectedMethod === 'wallet') {
                paymentData.payment_details = {
                    wallet_provider: document.getElementById('walletProvider').value,
                };
            }

            // Add custom amount if partial payment is enabled
            @if($paymentLink->allow_partial_payment)
            const customAmountInput = document.getElementById('customAmount');
            if (customAmountInput && customAmountInput.value) {
                const customAmount = parseFloat(customAmountInput.value);
                const totalAmount = parseFloat({{ $paymentLink->amount }});
                const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                
                // Validate against total amount first
                if (customAmount > totalAmount) {
                    errorAlert.style.display = 'block';
                    errorMessage.textContent = `Error: Amount cannot exceed total amount of ${paymentLink.currency} ${totalAmount.toFixed(2)}`;
                    payButton.disabled = false;
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    return;
                }
                
                // Validate against remaining balance
                if (customAmount > remainingBalance) {
                    errorAlert.style.display = 'block';
                    errorMessage.textContent = `Error: Amount cannot exceed remaining balance of ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    payButton.disabled = false;
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    return;
                }
                
                if (customAmount < 0.01) {
                    errorAlert.style.display = 'block';
                    errorMessage.textContent = 'Error: Payment amount must be at least 0.01';
                    payButton.disabled = false;
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                    return;
                }
                
                paymentData.amount = customAmount;
            }
            @endif

            try {
                const response = await fetch(`/pay/${paymentLink.link_token}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(paymentData),
                });

                // Check if response is ok before parsing JSON
                let result;
                if (!response.ok) {
                    // Try to parse error response
                    try {
                        result = await response.json();
                    } catch (e) {
                        // If not JSON, create error result
                        result = {
                            success: false,
                            message: `Payment failed: ${response.status} ${response.statusText}`,
                        };
                    }
                } else {
                    result = await response.json();
                }
                
                console.log('Payment response:', result);
                console.log('result.success:', result.success);
                console.log('result.gateway:', result.gateway);
                
                // Remove Razorpay-specific console logs when gateway is cashfree
                if (result.gateway !== 'cashfree') {
                    console.log('result.use_razorpay_checkout:', result.use_razorpay_checkout);
                    console.log('result.razorpay_key:', result.razorpay_key);
                    console.log('result.razorpay_order_id:', result.razorpay_order_id);
                }

                if (result.success) {
                    console.log('Payment response success is true, checking payment gateway...');
                    console.log('Gateway type:', result.gateway || (result.use_razorpay_checkout ? 'razorpay' : 'other'));
                    
                    // CASHFREE PAYMENT FLOW
                    if (result.gateway === 'cashfree') {
                        console.log('✅ CashFree payment flow initiated', {
                            payment_session_id: result.payment_session_id,
                            gateway_order_id: result.gateway_order_id,
                            transaction_id: result.transaction_id,
                            status: result.status
                        });
                        
                        // CashFree: Order created, payment_session_id returned
                        // Status is 'pending' (ACTIVE in CashFree) - this is expected
                        // Frontend MUST open CashFree checkout
                        if (result.payment_session_id) {
                            // Reset button state for checkout
                            payButton.dataset.processing = '';
                            payButton.disabled = false;
                            payButtonText.textContent = 'Opening payment...';
                            
                            // Initialize CashFree checkout immediately
                            initializeCashFreeCheckout(result);
                        } else {
                            // Missing payment_session_id - error
                            console.error('CashFree: payment_session_id missing', result);
                            errorMessage.textContent = 'Payment session not created. Please try again.';
                            errorAlert.style.display = 'flex';
                            payButton.disabled = false;
                            payButton.dataset.processing = '';
                            @if($paymentLink->allow_partial_payment)
                            const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                            @else
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                            @endif
                        }
                        return; // Exit early - don't process Razorpay logic
                    }
                    
                    // RAZORPAY PAYMENT FLOW (only if gateway is razorpay)
                    // IMPORTANT: Only use Razorpay Checkout.js if gateway is explicitly 'razorpay'
                    // CashFree and other gateways should NOT trigger Razorpay logic
                    if (result.gateway === 'razorpay' && result.use_razorpay_checkout && result.razorpay_key && result.razorpay_order_id) {
                        console.log('✅ All conditions met - Opening Razorpay Checkout.js', {
                            key: result.razorpay_key,
                            order_id: result.razorpay_order_id,
                            amount: result.amount
                        });
                        
                        // Hide the custom card form since Razorpay Checkout.js will handle it
                        const cardForm = document.getElementById('cardForm');
                        if (cardForm) {
                            cardForm.style.display = 'none';
                            console.log('Card form hidden - Razorpay Checkout.js will handle payment');
                        }
                        
                        // Reset processing flag so modal can open
                        payButton.dataset.processing = '';
                        payButton.disabled = false;
                        payButtonText.textContent = 'Processing Payment...';
                        console.log('Pay button ready for Razorpay Checkout');
                        
                        // Open Razorpay Checkout.js
                        // Store order_id and link_token for handler
                        const orderIdForVerification = result.order_id;
                        const linkTokenForVerification = paymentLink.link_token;
                        
                        const options = {
                            key: result.razorpay_key,
                            amount: result.amount, // Amount in paise
                            currency: result.currency,
                            name: '{{ $paymentLink->merchant->name }}',
                            description: '{{ $paymentLink->title }}',
                            order_id: result.razorpay_order_id,
                            prefill: {
                                name: result.customer_details.name,
                                email: result.customer_details.email,
                                contact: result.customer_details.phone,
                            },
                            // Force card payment method only - explicitly disable other methods
                            method: {
                                card: {},      // Enable only card payments
                                netbanking: false,
                                wallet: false,
                                upi: false,
                                emi: false
                            },
                            // CRITICAL: DO NOT set callback_url - it causes Razorpay to redirect
                            // We handle everything in the handler function
                            // callback_url: undefined, // Explicitly undefined
                            // IMPORTANT: Prevent Razorpay from auto-redirecting
                            // Handler will manage everything - close modal, verify, redirect
                            handler: function(response) {
                                // PREVENT DEFAULT RAZORPAY BEHAVIOR IMMEDIATELY
                                console.log('=== RAZORPAY HANDLER FIRED ===');
                                console.log('🛑 PREVENTING RAZORPAY REDIRECT');
                                console.log('Response:', response);
                                console.log('Payment ID:', response.razorpay_payment_id);
                                console.log('Order ID:', response.razorpay_order_id);
                                console.log('Signature:', response.razorpay_signature ? 'Present' : 'Missing');
                                
                                // CRITICAL: Close modal SYNCHRONOUSLY FIRST - before any async work
                                if (window.razorpayInstance) {
                                    try {
                                        window.razorpayInstance.close();
                                        console.log('✅ Razorpay modal closed immediately');
                                        // Clear instance reference
                                        window.razorpayInstance = null;
                                    } catch(e) {
                                        console.error('❌ Error closing modal:', e);
                                    }
                                }
                                
                                // Store response for async processing
                                const paymentResponse = response;
                                
                                // Update UI immediately - show verification in progress
                                payButtonText.innerHTML = '<span class="spinner"></span> Verifying payment...';
                                payButton.disabled = true;
                                
                                // Hide any existing alerts
                                if (successAlert) {
                                    successAlert.style.display = 'none';
                                }
                                if (errorAlert) {
                                    errorAlert.style.display = 'none';
                                }
                                
                                try {
                                
                                    // IMPORTANT: Execute async verification AFTER closing modal
                                    (async function() {
                                        const response = paymentResponse;
                                        try {
                                            console.log('Razorpay payment successful, verifying...', response);
                                            console.log('Calling verification endpoint...');
                                            console.log('Link token:', linkTokenForVerification);
                                            console.log('Order ID:', orderIdForVerification);
                                            
                                            const verifyResponse = await fetch(`/pay/${linkTokenForVerification}/verify-razorpay`, {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                },
                                                body: JSON.stringify({
                                                    razorpay_payment_id: response.razorpay_payment_id,
                                                    razorpay_order_id: response.razorpay_order_id,
                                                    razorpay_signature: response.razorpay_signature,
                                                    order_id: orderIdForVerification,
                                                }),
                                            });
                                            
                                            console.log('Verification response status:', verifyResponse.status);
                                            
                                            if (!verifyResponse.ok) {
                                                const errorText = await verifyResponse.text();
                                                console.error('Verification failed:', errorText);
                                                throw new Error(`Verification failed: ${verifyResponse.status} - ${errorText}`);
                                            }
                                            
                                            const verifyResult = await verifyResponse.json();
                                            console.log('Verification response:', verifyResult);
                                        
                                            if (verifyResult.success) {
                                                console.log('Payment verified successfully!', verifyResult);
                                                let successMsg = `Order ID: ${verifyResult.order_id} | Transaction ID: ${verifyResult.transaction_id}`;
                                                
                                                @if($paymentLink->allow_partial_payment)
                                                if (verifyResult.payment_link) {
                                                    const paymentLinkInfo = verifyResult.payment_link;
                                                    if (paymentLinkInfo.is_partially_paid) {
                                                        successMsg += `\n\nAmount Paid: ${paymentLink.currency} ${parseFloat(paymentLinkInfo.amount_paid).toFixed(2)}`;
                                                        successMsg += `\nRemaining Balance: ${paymentLink.currency} ${parseFloat(paymentLinkInfo.remaining_balance).toFixed(2)}`;
                                                        if (!paymentLinkInfo.is_fully_paid) {
                                                            successMsg += `\n\nYou can use this same link to pay the remaining balance later.`;
                                                        }
                                                    }
                                                }
                                                @endif
                                                
                                                successMessage.textContent = successMsg;
                                                successAlert.style.display = 'flex';
                                                payButtonText.textContent = 'Payment Successful!';
                                                payButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                                                successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                
                                                // Disable all inputs
                                                document.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
                                                
                                                // Redirect after 2 seconds ONLY if verification succeeded and has transaction_id
                                                if (verifyResult.redirect_url && verifyResult.transaction_id) {
                                                    console.log('Redirecting to:', verifyResult.redirect_url);
                                                    setTimeout(() => {
                                                        window.location.href = verifyResult.redirect_url;
                                                    }, 2000);
                                                } else {
                                                    console.error('Missing redirect_url or transaction_id:', verifyResult);
                                                    // Show error if transaction_id is missing
                                                    errorMessage.textContent = 'Payment completed but transaction ID not received. Please contact support with Order ID: ' + (verifyResult.order_id || orderIdForVerification);
                                                    errorAlert.style.display = 'flex';
                                                }
                                            } else {
                                                errorMessage.textContent = verifyResult.message || 'Payment verification failed';
                                                errorAlert.style.display = 'flex';
                                                payButton.disabled = false;
                                                @if($paymentLink->allow_partial_payment)
                                                const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                                                payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                                                @else
                                                payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                                                @endif
                                            }
                                        } catch (error) {
                                            console.error('Verification error:', error);
                                            console.error('Error details:', {
                                                message: error.message,
                                                stack: error.stack,
                                                response: error.response
                                            });
                                            errorMessage.textContent = 'Payment verification failed. Please contact support.';
                                            errorAlert.style.display = 'flex';
                                            payButton.disabled = false;
                                            @if($paymentLink->allow_partial_payment)
                                            const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                                            @else
                                            payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                                            @endif
                                        }
                                    })();
                                } catch (error) {
                                    console.error('Razorpay handler outer error:', error);
                                    errorMessage.textContent = 'An error occurred. Please try again.';
                                    errorAlert.style.display = 'flex';
                                    payButton.disabled = false;
                                    @if($paymentLink->allow_partial_payment)
                                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                                    @else
                                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                                    @endif
                                }
                                
                                // CRITICAL: Return false and prevent default to stop Razorpay redirect
                                // This prevents Razorpay from following its default redirect behavior
                                if (typeof event !== 'undefined') {
                                    event.preventDefault?.();
                                    event.stopPropagation?.();
                                }
                                return false;
                            },
                            modal: {
                                ondismiss: function() {
                                    console.log('Razorpay modal dismissed by user');
                                    // Reset flag when modal is dismissed
                                    if (payButton) {
                                        payButton.dataset.razorpayOpened = '';
                                    }
                                    // User closed the Razorpay checkout
                                    payButton.disabled = false;
                                    @if($paymentLink->allow_partial_payment)
                                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                                    @else
                                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                                    @endif
                                }
                            }
                        };
                        
                        console.log('🚀 About to create Razorpay instance...');
                        console.log('Razorpay options:', JSON.stringify(options, null, 2));
                        
                        // Close any existing Razorpay instance to prevent duplicates
                        if (window.razorpayInstance) {
                            console.log('Closing existing Razorpay instance...');
                            try {
                                window.razorpayInstance.close();
                            } catch(e) {
                                console.log('No existing instance to close');
                            }
                            window.razorpayInstance = null;
                        }
                        
                        // Prevent duplicate opens - check if already opened
                        if (payButton.dataset.razorpayOpening === 'true') {
                            console.warn('Razorpay already opening, preventing duplicate');
                            return;
                        }
                        
                        payButton.dataset.razorpayOpening = 'true';
                        
                        // CRITICAL: Ensure callback_url is NOT set to prevent Razorpay auto-redirect
                        if (options.callback_url) {
                            delete options.callback_url;
                            console.log('⚠️ Removed callback_url to prevent Razorpay redirect');
                        }
                        
                        const razorpay = new Razorpay(options);
                        window.razorpayInstance = razorpay; // Store globally to prevent duplicates
                        
                        // Add error handler for Razorpay payment failures
                        razorpay.on('payment.failed', function(response) {
                            console.error('❌ Razorpay payment failed:', response);
                            payButton.dataset.razorpayOpening = ''; // Reset flag
                            payButton.dataset.processing = ''; // Reset processing flag
                            
                            // Close modal immediately on failure
                            try {
                                razorpay.close();
                                console.log('Razorpay modal closed after payment failure');
                            } catch(e) {
                                console.log('Error closing modal:', e);
                            }
                            
                            // Show card form again
                            const cardForm = document.getElementById('cardForm');
                            if (cardForm) {
                                cardForm.style.display = '';
                            }
                            
                            errorMessage.textContent = 'Payment failed: ' + (response.error?.description || response.error?.reason || 'Unknown error');
                            errorAlert.style.display = 'flex';
                            payButton.disabled = false;
                            @if($paymentLink->allow_partial_payment)
                            const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                            @else
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                            @endif
                        });
                        
                        // CRITICAL: Open Razorpay Checkout.js IMMEDIATELY
                        // This must happen synchronously to prevent any redirect
                        try {
                            // Open modal immediately - this prevents redirect
                            razorpay.open();
                            console.log('✅ Razorpay Checkout.js modal opened successfully - NO REDIRECT');
                            
                            // The modal is now open - user will enter card details in the modal
                            // Handler will be called when payment completes
                            
                            // Reset flag after modal opens
                            setTimeout(() => {
                                payButton.dataset.razorpayOpening = '';
                            }, 1000);
                            
                            // CRITICAL: Return false to prevent any form submission or redirect
                            return false;
                        } catch (error) {
                            console.error('❌ Error opening Razorpay Checkout:', error);
                            payButton.dataset.razorpayOpening = '';
                            payButton.dataset.processing = '';
                            
                            // Show card form again if error
                            const cardForm = document.getElementById('cardForm');
                            if (cardForm) {
                                cardForm.style.display = '';
                            }
                            
                            errorMessage.textContent = 'Failed to open payment gateway. Please try again.';
                            errorAlert.style.display = 'flex';
                            payButton.disabled = false;
                            @if($paymentLink->allow_partial_payment)
                            const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                            @else
                            payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                            @endif
                            
                            return false;
                        }
                    } else {
                        // CashFree or other server-side gateway flow (NOT Razorpay)
                        const gatewayName = result.gateway || 'payment gateway';
                        console.log(`Payment succeeded through ${gatewayName} (server-side processing)`);
                        console.log('Transaction ID:', result.transaction_id);
                        console.log('Gateway Payment ID:', result.gateway_payment_id);
                        
                        let successMsg = result.message || 'Payment processed successfully!';
                        if (result.transaction_id) {
                            successMsg += `\n\nTransaction ID: ${result.transaction_id}`;
                        }
                        
                        // Show partial payment info if available
                        @if($paymentLink->allow_partial_payment)
                        if (result.payment_link) {
                            const paymentLinkInfo = result.payment_link;
                            if (paymentLinkInfo.is_partially_paid) {
                                successMsg += `\n\nAmount Paid: ${paymentLink.currency} ${parseFloat(paymentLinkInfo.amount_paid).toFixed(2)}`;
                                successMsg += `\nRemaining Balance: ${paymentLink.currency} ${parseFloat(paymentLinkInfo.remaining_balance).toFixed(2)}`;
                                if (!paymentLinkInfo.is_fully_paid) {
                                    successMsg += `\n\nYou can use this same link to pay the remaining balance later.`;
                                }
                            }
                        }
                        @endif
                        
                        successMessage.textContent = successMsg;
                        successAlert.style.display = 'flex';
                        payButtonText.textContent = 'Payment Successful!';
                        payButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                        successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Redirect to success page after 2 seconds
                        if (result.redirect_url) {
                            setTimeout(() => {
                                window.location.href = result.redirect_url;
                            }, 2000);
                        }
                        
                        // Disable all inputs
                        document.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
                    }
                } else {
                    errorMessage.textContent = result.message || 'Payment failed. Please try again.';
                    errorAlert.style.display = 'flex';
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Redirect to merchant test app failure page after 2 seconds
                    if (result.redirect_url) {
                        setTimeout(() => {
                            window.location.href = result.redirect_url;
                        }, 2000);
                    } else {
                        payButton.disabled = false;
                        @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
                    }
                }
            } catch (error) {
                console.error('Payment error:', error);
                
                // Try to get error message from response if available
                let errorMsg = 'Network error. Please check your connection and try again.';
                if (error.response) {
                    try {
                        const errorData = await error.response.json();
                        if (errorData.message) {
                            errorMsg = errorData.message;
                        }
                    } catch (e) {
                        // If response is not JSON, use default message
                    }
                } else if (error.message) {
                    errorMsg = error.message;
                }
                
                errorMessage.textContent = errorMsg;
                errorAlert.style.display = 'flex';
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                payButton.disabled = false;
                payButton.dataset.processing = '';
                @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
            }
        });

        // Run validation on page load (only once, with minimal logging)
        setTimeout(() => {
            validateForm(false); // Log once on initial load
        }, 500);
        
        // Also run validation when customer details are filled (on blur)
        const customerNameInput = document.getElementById('customerName');
        const customerEmailInput = document.getElementById('customerEmail');
        const customerPhoneInput = document.getElementById('customerPhone');
        
        if (customerNameInput) {
            customerNameInput.addEventListener('blur', () => validateForm(false));
        }
        if (customerEmailInput) {
            customerEmailInput.addEventListener('blur', () => validateForm(false));
        }
        if (customerPhoneInput) {
            customerPhoneInput.addEventListener('blur', () => validateForm(false));
        }

        // Test mode simulation buttons
        @if($paymentLink->test_mode)
        const simulateSuccessBtn = document.getElementById('simulateSuccessBtn');
        const simulateFailBtn = document.getElementById('simulateFailBtn');

        if (simulateSuccessBtn) {
            simulateSuccessBtn.addEventListener('click', async () => {
                if (payButton.disabled && !document.getElementById('customerName').value.trim()) {
                    alert('Please fill in customer details first');
                    return;
                }

                successAlert.style.display = 'none';
                errorAlert.style.display = 'none';
                
                payButton.disabled = true;
                payButtonText.innerHTML = '<span class="spinner"></span> Simulating Success...';

                // Simulate successful payment
                const paymentData = {
                    payment_method: selectedMethod || 'card',
                    customer_details: {
                        name: document.getElementById('customerName').value.trim() || 'Test Customer',
                        email: document.getElementById('customerEmail').value.trim() || 'test@example.com',
                        phone: document.getElementById('customerPhone').value.trim() || '9876543210',
                    },
                    payment_details: {
                        simulate: true,
                        simulate_result: 'success'
                    }
                };

                try {
                    const response = await fetch(`/pay/${paymentLink.link_token}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(paymentData),
                    });

                    const result = await response.json();

                    if (result.success) {
                        successMessage.textContent = `Order ID: ${result.order_id} | Transaction ID: ${result.transaction_id}`;
                        successAlert.style.display = 'flex';
                        payButtonText.textContent = 'Payment Successful!';
                        payButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                        successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Redirect after showing success
                        setTimeout(() => {
                            const baseUrl = window.location.origin;
                            window.location.href = result.redirect_url || `${baseUrl}/success-simple.html?transaction_id=${result.transaction_id}`;
                        }, 2000);
                    } else {
                        errorMessage.textContent = result.message || 'Simulation failed';
                        errorAlert.style.display = 'flex';
                        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Redirect to failure page after showing error
                        setTimeout(() => {
                            const baseUrl = window.location.origin;
                            window.location.href = result.redirect_url || `${baseUrl}/failure-simple.html?transaction_id=${result.transaction_id}`;
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Simulation error:', error);
                    errorMessage.textContent = 'Simulation error occurred';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
                }
            });
        }

        if (simulateFailBtn) {
            simulateFailBtn.addEventListener('click', async () => {
                if (payButton.disabled && !document.getElementById('customerName').value.trim()) {
                    alert('Please fill in customer details first');
                    return;
                }

                successAlert.style.display = 'none';
                errorAlert.style.display = 'none';
                
                payButton.disabled = true;
                payButtonText.innerHTML = '<span class="spinner"></span> Simulating Failure...';

                // Simulate failed payment
                const paymentData = {
                    payment_method: selectedMethod || 'card',
                    customer_details: {
                        name: document.getElementById('customerName').value.trim() || 'Test Customer',
                        email: document.getElementById('customerEmail').value.trim() || 'test@example.com',
                        phone: document.getElementById('customerPhone').value.trim() || '9876543210',
                    },
                    payment_details: {
                        simulate: true,
                        simulate_result: 'failed'
                    }
                };

                try {
                    const response = await fetch(`/pay/${paymentLink.link_token}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(paymentData),
                    });

                    const result = await response.json();

                    if (result.success) {
                        errorMessage.textContent = 'Unexpected: Simulation returned success';
                        errorAlert.style.display = 'flex';
                    } else {
                        errorMessage.textContent = result.message || 'Payment failed (simulated)';
                        errorAlert.style.display = 'flex';
                        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Redirect to failure page
                        setTimeout(() => {
                            const baseUrl = window.location.origin;
                            window.location.href = result.redirect_url || `${baseUrl}/failure-simple.html?transaction_id=${result.transaction_id || ''}`;
                        }, 2000);
                    }
                    
                    payButton.disabled = false;
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
                } catch (error) {
                    console.error('Simulation error:', error);
                    errorMessage.textContent = 'Payment failed (simulated)';
                    errorAlert.style.display = 'flex';
                    payButton.disabled = false;
                    @if($paymentLink->allow_partial_payment)
                    const remainingBalance = parseFloat({{ $paymentLink->getRemainingBalance() }});
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${remainingBalance.toFixed(2)}`;
                @else
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${parseFloat(paymentLink.amount).toFixed(2)}`;
                @endif
                }
            });
        }
        @endif
    </script>
</body>
</html>
