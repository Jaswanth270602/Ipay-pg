<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            --light-bg: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .payment-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 380px 1fr;
            min-height: 600px;
        }

        .left-panel {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
        }

        .merchant-info {
            margin-bottom: 40px;
        }

        .merchant-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .merchant-name {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .amount-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .amount-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 36px;
            font-weight: 700;
            margin: 0;
        }

        .secured-by {
            margin-top: auto;
            padding-top: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            opacity: 0.9;
        }

        .right-panel {
            padding: 40px;
            overflow-y: auto;
        }

        .panel-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 15px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 30px;
        }

        .payment-method-btn {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .payment-method-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .payment-method-btn.active {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .payment-method-btn i {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .payment-method-btn .label {
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        .payment-method-btn.active .label {
            color: var(--primary);
            font-weight: 600;
        }

        .payment-form {
            display: none;
        }

        .payment-form.active {
            display: block;
        }

        .pay-button {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .pay-button:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .success-message, .error-message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message {
            background: #d1fae5;
            border: 2px solid var(--success);
            color: #065f46;
        }

        .error-message {
            background: #fee2e2;
            border: 2px solid var(--danger);
            color: #991b1b;
        }

        .success-message.show, .error-message.show {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .test-cards-info {
            background: #fef3c7;
            border: 2px solid var(--warning);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .test-cards-info strong {
            color: #92400e;
        }

        @media (max-width: 768px) {
            .payment-card {
                grid-template-columns: 1fr;
            }

            .left-panel {
                padding: 30px 20px;
            }

            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <!-- Left Panel - Payment Summary -->
            <div class="left-panel">
                <div class="merchant-info">
                    <div class="merchant-logo">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3 class="merchant-name">{{ $paymentLink->title }}</h3>
                    @if($paymentLink->description)
                        <p style="font-size: 14px; opacity: 0.9; margin-top: 8px;">{{ $paymentLink->description }}</p>
                    @endif
                </div>

                <div class="amount-section">
                    <div class="amount-label">Amount to Pay</div>
                    <h2 class="amount-value">{{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}</h2>
                </div>

                @if($paymentLink->test_mode)
                <div class="test-cards-info">
                    <strong><i class="bi bi-info-circle"></i> TEST MODE</strong>
                    <div style="margin-top: 8px;">
                        <div><strong>Success Cards:</strong></div>
                        <div>4242 4242 4242 4242</div>
                        <div>5555 5555 5555 4444</div>
                        <div style="margin-top: 8px;"><strong>Failure Cards:</strong></div>
                        <div>4000 0000 0000 0002</div>
                        <div style="margin-top: 8px; opacity: 0.8;">CVV: Any 3 digits | Expiry: Any future date</div>
                    </div>
                </div>
                @endif

                <div class="secured-by">
                    <i class="bi bi-shield-check" style="font-size: 20px;"></i>
                    <span>Secured by <strong>BadliCash</strong></span>
                </div>
            </div>

            <!-- Right Panel - Payment Form -->
            <div class="right-panel">
                <h2 class="panel-title">Complete Your Payment</h2>

                <!-- Success Message -->
                <div class="success-message" id="successMessage">
                    <i class="bi bi-check-circle-fill" style="font-size: 24px;"></i>
                    <div>
                        <strong>Payment Successful!</strong>
                        <p style="margin: 4px 0 0 0; font-size: 13px;">Your payment has been processed successfully.</p>
                    </div>
                </div>

                <!-- Error Message -->
                <div class="error-message" id="errorMessage">
                    <i class="bi bi-exclamation-circle-fill" style="font-size: 24px;"></i>
                    <div>
                        <strong>Payment Failed</strong>
                        <p style="margin: 4px 0 0 0; font-size: 13px;" id="errorText"></p>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="form-section">
                    <h4 class="section-title">Customer Details</h4>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="customerName" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" id="customerEmail" placeholder="john@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="customerPhone" placeholder="9876543210" maxlength="10" required>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="form-section">
                    <h4 class="section-title">Select Payment Method</h4>
                    <div class="payment-methods">
                        <div class="payment-method-btn active" data-method="card">
                            <i class="bi bi-credit-card"></i>
                            <div class="label">Card</div>
                        </div>
                        <div class="payment-method-btn" data-method="upi">
                            <i class="bi bi-phone"></i>
                            <div class="label">UPI</div>
                        </div>
                        <div class="payment-method-btn" data-method="netbanking">
                            <i class="bi bi-bank"></i>
                            <div class="label">Net Banking</div>
                        </div>
                        <div class="payment-method-btn" data-method="wallet">
                            <i class="bi bi-wallet"></i>
                            <div class="label">Wallets</div>
                        </div>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="payment-form active" id="cardForm">
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">Card Number *</label>
                            <input type="text" class="form-control" id="cardNumber" placeholder="4242 4242 4242 4242" maxlength="19">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Card Holder Name *</label>
                            <input type="text" class="form-control" id="cardHolder" placeholder="JOHN DOE">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Month *</label>
                            <input type="text" class="form-control" id="expiryMonth" placeholder="12" maxlength="2">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Year *</label>
                            <input type="text" class="form-control" id="expiryYear" placeholder="2025" maxlength="4">
                        </div>
                        <div class="col-4">
                            <label class="form-label">CVV *</label>
                            <input type="password" class="form-control" id="cvv" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>

                <!-- UPI Form -->
                <div class="payment-form" id="upiForm">
                    <div class="mb-3">
                        <label class="form-label">UPI ID *</label>
                        <input type="text" class="form-control" id="upiId" placeholder="yourname@upi">
                        <div style="text-align: center; margin: 15px 0; color: #94a3b8;">OR</div>
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

                <!-- Net Banking Form -->
                <div class="payment-form" id="netbankingForm">
                    <div class="mb-3">
                        <label class="form-label">Select Your Bank *</label>
                        <select class="form-select" id="bankCode">
                            <option value="">Choose your bank</option>
                            <option value="SBI">State Bank of India</option>
                            <option value="HDFC">HDFC Bank</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="AXIS">Axis Bank</option>
                            <option value="KOTAK">Kotak Mahindra Bank</option>
                            <option value="PNB">Punjab National Bank</option>
                            <option value="BOB">Bank of Baroda</option>
                        </select>
                    </div>
                </div>

                <!-- Wallet Form -->
                <div class="payment-form" id="walletForm">
                    <div class="mb-3">
                        <label class="form-label">Select Wallet *</label>
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

                <!-- Pay Button -->
                <button class="pay-button" id="payButton" disabled>
                    <span id="payButtonText">Enter details to continue</span>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment link data
        const paymentLink = @json($paymentLink);
        let selectedMethod = 'card';
        
        // Elements
        const payButton = document.getElementById('payButton');
        const payButtonText = document.getElementById('payButtonText');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        // Payment method switching
        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active states
                document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Show corresponding form
                const method = btn.dataset.method;
                selectedMethod = method;
                document.querySelectorAll('.payment-form').forEach(f => f.classList.remove('active'));
                document.getElementById(method + 'Form').classList.add('active');
                
                // Validate form
                validateForm();
            });
        });

        // Card number formatting
        document.getElementById('cardNumber')?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
            validateForm();
        });

        // Real-time validation for all inputs
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', validateForm);
            input.addEventListener('change', validateForm);
        });

        // Form validation
        function validateForm() {
            const name = document.getElementById('customerName').value.trim();
            const email = document.getElementById('customerEmail').value.trim();
            const phone = document.getElementById('customerPhone').value.trim();
            
            if (!name || !email || !phone || phone.length !== 10) {
                payButton.disabled = true;
                payButtonText.textContent = 'Enter customer details';
                return false;
            }

            let methodValid = false;
            
            if (selectedMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const cardHolder = document.getElementById('cardHolder').value.trim();
                const month = document.getElementById('expiryMonth').value.trim();
                const year = document.getElementById('expiryYear').value.trim();
                const cvv = document.getElementById('cvv').value.trim();
                
                methodValid = cardNumber.length >= 15 && cardHolder && 
                             month && year && cvv.length === 3;
            } else if (selectedMethod === 'upi') {
                const upiId = document.getElementById('upiId').value.trim();
                const upiApp = document.getElementById('upiApp').value;
                methodValid = upiId || upiApp;
            } else if (selectedMethod === 'netbanking') {
                methodValid = document.getElementById('bankCode').value;
            } else if (selectedMethod === 'wallet') {
                methodValid = document.getElementById('walletProvider').value;
            }

            if (methodValid) {
                payButton.disabled = false;
                payButtonText.textContent = `Pay ${paymentLink.currency} ${paymentLink.amount}`;
            } else {
                payButton.disabled = true;
                payButtonText.textContent = 'Complete payment details';
            }
            
            return methodValid;
        }

        // Process payment
        payButton.addEventListener('click', async () => {
            if (payButton.disabled) return;
            
            // Hide messages
            successMessage.classList.remove('show');
            errorMessage.classList.remove('show');
            
            // Disable button and show loading
            payButton.disabled = true;
            payButtonText.innerHTML = '<span class="spinner"></span> Processing...';

            // Prepare payment data
            const paymentData = {
                payment_method: selectedMethod,
                customer_details: {
                    name: document.getElementById('customerName').value.trim(),
                    email: document.getElementById('customerEmail').value.trim(),
                    phone: document.getElementById('customerPhone').value.trim(),
                },
                payment_details: {}
            };

            // Add method-specific details
            if (selectedMethod === 'card') {
                paymentData.payment_details = {
                    card_number: document.getElementById('cardNumber').value.replace(/\s/g, ''),
                    card_holder: document.getElementById('cardHolder').value.trim(),
                    expiry_month: document.getElementById('expiryMonth').value.trim(),
                    expiry_year: document.getElementById('expiryYear').value.trim(),
                    cvv: document.getElementById('cvv').value.trim(),
                };
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

            try {
                const response = await fetch(`/pay/${paymentLink.link_token}/process`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(paymentData),
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    successMessage.classList.add('show');
                    payButtonText.textContent = 'Payment Successful!';
                    payButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    
                    // Scroll to success message
                    successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Show error message
                    errorText.textContent = result.message || 'Payment failed. Please try again.';
                    errorMessage.classList.add('show');
                    payButton.disabled = false;
                    payButtonText.textContent = `Pay ${paymentLink.currency} ${paymentLink.amount}`;
                    
                    // Scroll to error message
                    errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } catch (error) {
                console.error('Payment error:', error);
                errorText.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.add('show');
                payButton.disabled = false;
                payButtonText.textContent = `Pay ${paymentLink.currency} ${paymentLink.amount}`;
            }
        });

        // Initialize validation
        validateForm();
    </script>
</body>
</html>



