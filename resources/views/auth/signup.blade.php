<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign up – BadliCash Merchant Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* New compact signup UI – distinct from previous form and login */
        :root {
            --su-primary: #E10600;
            --su-primary-soft: #fef2f2;
            --su-accent-dark: #1A1D24;
            --su-border-dark: #262B36;
            --su-text-main: #111827;
            --su-text-muted: #6b7280;
        }

        body {
            background: linear-gradient(160deg, #1A1D24 0%, #20242D 50%, #262B36 100%);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--su-text-main);
        }

        .su-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .su-card {
            width: 100%;
            max-width: 800px;
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: -8px 12px 28px rgba(225, 6, 0, 0.12), 0 0 0 1px rgba(26, 29, 36, 0.06);
        }

        .su-logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .su-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .su-subtitle {
            font-size: 12px;
            color: var(--su-text-muted);
            margin-bottom: 10px;
        }

        .su-step-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 4px 10px;
            background: var(--su-primary-soft);
            font-size: 11px;
            color: var(--su-text-muted);
            margin-bottom: 12px;
        }

        .su-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            justify-content: center;
        }

        .su-tab {
            flex: 1;
            max-width: 180px;
            border-radius: 999px;
            border: 1px solid var(--su-border-dark);
            background: #f3f4f6;
            font-size: 12px;
            padding: 10px 16px;
            color: var(--su-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }

        .su-tab-active {
            background: var(--su-primary);
            border-color: var(--su-primary);
            color: #ffffff;
        }

        .su-step-badge {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid var(--su-border-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
        }

        .su-tab-active .su-step-badge {
            border-color: transparent;
            background: rgba(255,255,255,0.3);
            color: #ffffff;
        }

        .su-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--su-text-main);
            margin-bottom: 8px;
            display: block;
        }

        .su-input,
        .su-select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--su-border-dark);
            padding: 10px 14px;
            font-size: 14px;
            color: var(--su-text-main);
            background:#ffffff;
        }

        .su-input:focus,
        .su-select:focus {
            outline: none;
            border-color: var(--su-primary);
            box-shadow: 0 0 0 2px rgba(225, 6, 0, 0.2);
        }

        .su-error {
            font-size: 11px;
            color:#b91c1c;
            margin-top:4px;
        }

        .su-bottom-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:28px;
            padding-top:20px;
            border-top:1px solid var(--su-border-dark);
        }

        .su-back-btn {
            border:none;
            background:transparent;
            font-size:12px;
            color:var(--su-text-muted);
        }

        .su-primary-btn {
            border:none;
            border-radius:999px;
            padding:10px 24px;
            font-size:14px;
            font-weight:600;
            background:linear-gradient(135deg,#4f46e5,#6366f1);
            color:#ffffff;
            transition:all 0.2s ease;
        }

        .su-primary-btn:hover {
            transform:translateY(-1px);
            box-shadow:0 4px 12px rgba(79,70,229,0.3);
        }

        .su-helper {
            font-size:11px;
            color:var(--su-text-muted);
            margin-top:10px;
        }

        .su-link {
            color: var(--su-primary);
            text-decoration:none;
            font-weight:600;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: 12px;
            color: #b91c1c;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size:12px;
        }

        .su-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        @media (max-width: 767px) {
            .su-form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .su-tabs {
                flex-wrap: wrap;
            }
            .su-tab {
                flex: 0 0 calc(50% - 6px);
                max-width: none;
            }
        }

        @media (max-width: 480px) {
            .su-card {
                padding:32px 24px;
            }
        }
    </style>
</head>
<body>
<div class="su-shell">
    <div class="su-card">
        <div class="su-logo-row">
            <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}" style="height: 32px; width: auto;">
            <div>
                <div class="su-title">Create your merchant account</div>
                <div class="su-subtitle">4 short steps to a sandbox‑ready account.</div>
            </div>
        </div>
        <div class="su-step-pill">
            Guided flow • Sandbox first • Live after review
        </div>

        @if(session('error'))
            <div class="alert-danger">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert-danger">
                Please fix the highlighted fields.
            </div>
        @endif

        <div class="su-tabs" id="signupTabs">
            <button type="button" class="su-tab su-tab-active" id="tab-business">
                <span class="su-step-badge">1</span> Business
            </button>
            <button type="button" class="su-tab" id="tab-tax">
                <span class="su-step-badge">2</span> Compliance
            </button>
            <button type="button" class="su-tab" id="tab-bank">
                <span class="su-step-badge">3</span> Bank
            </button>
            <button type="button" class="su-tab" id="tab-login">
                <span class="su-step-badge">4</span> Login
            </button>
        </div>

        <form method="POST" action="{{ route('signup.post') }}" id="signupForm" novalidate>
            @csrf
            <div class="tab-content">
                        <!-- Step 1: Business -->
                        <div class="tab-pane fade show active" id="pane-business" role="tabpanel">
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Business / Brand Name <span class="text-danger">*</span></label>
                                    <input type="text" name="business_name" value="{{ old('business_name') }}" class="su-input @error('business_name') is-invalid @enderror" required>
                                    @error('business_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Legal Name (as per PAN) <span class="text-danger">*</span></label>
                                    <input type="text" name="legal_name" value="{{ old('legal_name') }}" class="su-input @error('legal_name') is-invalid @enderror" required>
                                    @error('legal_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Business Email <span class="text-danger">*</span></label>
                                    <input type="email" name="business_email" value="{{ old('business_email') }}" class="su-input @error('business_email') is-invalid @enderror" required>
                                    @error('business_email')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Business Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="business_phone" value="{{ old('business_phone') }}" class="su-input @error('business_phone') is-invalid @enderror" pattern="[0-9]{10,15}" maxlength="15" required>
                                    @error('business_phone')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Website (optional)</label>
                                    <input type="url" name="website_link" value="{{ old('website_link') }}" class="su-input @error('website_link') is-invalid @enderror" placeholder="https://">
                                    @error('website_link')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Business Category <span class="text-danger">*</span></label>
                                    <select name="merchant_category" class="su-select @error('merchant_category') is-invalid @enderror" required>
                                        <option value="">Select category</option>
                                        @foreach(['B2B','Education','Insurance','Utilities','E-commerce','Travel & Hospitality','Telecom','High Risk','Grocery','NBFC','Government','Others'] as $cat)
                                            <option value="{{ $cat }}" @selected(old('merchant_category') === $cat)>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                    @error('merchant_category')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Country <span class="text-danger">*</span></label>
                                    <select name="business_country" class="su-select @error('business_country') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                        <option value="India" @selected(old('business_country') === 'India')>India</option>
                                        <option value="USA" @selected(old('business_country') === 'USA')>USA</option>
                                        <option value="UK" @selected(old('business_country') === 'UK')>UK</option>
                                    </select>
                                    @error('business_country')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">State <span class="text-danger">*</span></label>
                                    <input type="text" name="business_state" value="{{ old('business_state') }}" class="su-input @error('business_state') is-invalid @enderror" required>
                                    @error('business_state')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">City <span class="text-danger">*</span></label>
                                    <input type="text" name="business_city" value="{{ old('business_city') }}" class="su-input @error('business_city') is-invalid @enderror" required>
                                    @error('business_city')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Pin / ZIP Code <span class="text-danger">*</span></label>
                                    <input type="text" name="business_postal_code" value="{{ old('business_postal_code') }}" class="su-input @error('business_postal_code') is-invalid @enderror" maxlength="10" required>
                                    @error('business_postal_code')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <label class="su-label">Address Line 1 <span class="text-danger">*</span></label>
                                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" class="su-input @error('address_line_1') is-invalid @enderror" required>
                                    @error('address_line_1')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <label class="su-label">Address Line 2 (optional)</label>
                                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" class="su-input @error('address_line_2') is-invalid @enderror">
                                    @error('address_line_2')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Compliance -->
                        <div class="tab-pane fade" id="pane-tax" role="tabpanel">
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">PAN Number <span class="text-danger">*</span></label>
                                    <input type="text" name="merchant_pan_number" value="{{ old('merchant_pan_number') }}" class="su-input text-uppercase @error('merchant_pan_number') is-invalid @enderror" maxlength="10" required>
                                    @error('merchant_pan_number')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Name on PAN <span class="text-danger">*</span></label>
                                    <input type="text" name="name_on_pan_card" value="{{ old('name_on_pan_card') }}" class="su-input @error('name_on_pan_card') is-invalid @enderror" required>
                                    @error('name_on_pan_card')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">GSTIN (optional)</label>
                                    <input type="text" name="gst_identification_no" value="{{ old('gst_identification_no') }}" class="su-input @error('gst_identification_no') is-invalid @enderror">
                                    @error('gst_identification_no')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">GSTIN State</label>
                                    <input type="text" name="gstin_state" value="{{ old('gstin_state') }}" class="su-input @error('gstin_state') is-invalid @enderror">
                                    @error('gstin_state')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">TAN (optional)</label>
                                    <input type="text" name="tan_no" value="{{ old('tan_no') }}" class="su-input @error('tan_no') is-invalid @enderror">
                                    @error('tan_no')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <p style="font-size:12px;color:var(--su-text-muted);margin-top:8px;margin-bottom:0;">
                                        We use these details to prepare your onboarding for live payouts. During sandbox testing
                                        no real charges or settlements are triggered.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Bank -->
                        <div class="tab-pane fade" id="pane-bank" role="tabpanel">
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Account Holder Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_account_holder_name" value="{{ old('bank_account_holder_name') }}" class="su-input @error('bank_account_holder_name') is-invalid @enderror" required>
                                    @error('bank_account_holder_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Bank Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="su-input @error('bank_account_number') is-invalid @enderror" minlength="8" maxlength="25" required>
                                    @error('bank_account_number')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="su-input @error('bank_name') is-invalid @enderror" required>
                                    @error('bank_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Account Type <span class="text-danger">*</span></label>
                                    <select name="account_type" class="su-select @error('account_type') is-invalid @enderror" required>
                                        <option value="">Select type</option>
                                        <option value="Savings Account" @selected(old('account_type') === 'Savings Account')>Savings Account</option>
                                        <option value="Current Account" @selected(old('account_type') === 'Current Account')>Current Account</option>
                                    </select>
                                    @error('account_type')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Branch <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" class="su-input @error('bank_branch') is-invalid @enderror" required>
                                    @error('bank_branch')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">IFSC Code <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}" class="su-input text-uppercase @error('bank_ifsc_code') is-invalid @enderror" maxlength="11" required>
                                    @error('bank_ifsc_code')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <p style="font-size:12px;color:var(--su-text-muted);margin-top:8px;margin-bottom:0;">
                                        In sandbox, we never hit real banks while you are testing. Once you are approved for live mode,
                                        these same details will be used for actual settlements.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Login -->
                        <div class="tab-pane fade" id="pane-login" role="tabpanel">
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Primary Contact Name <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="su-input @error('contact_name') is-invalid @enderror" required>
                                    @error('contact_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Contact Mobile <span class="text-danger">*</span></label>
                                    <input type="tel" name="contact_mobile" value="{{ old('contact_mobile') }}" class="su-input @error('contact_mobile') is-invalid @enderror" pattern="[0-9]{10,15}" maxlength="15" required>
                                    @error('contact_mobile')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Contact Email <span class="text-danger">*</span></label>
                                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="su-input @error('contact_email') is-invalid @enderror" required>
                                    @error('contact_email')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Login Email (for BadliCash) <span class="text-danger">*</span></label>
                                    <input type="email" name="login_name" value="{{ old('login_name') }}" class="su-input @error('login_name') is-invalid @enderror" required>
                                    @error('login_name')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="su-input @error('password') is-invalid @enderror" minlength="12" required>
                                    @error('password')<div class="su-error">{{ $message }}</div>@enderror
                                    <div style="font-size:11px;color:var(--su-text-muted);margin-top:6px;">
                                        Min 12 characters with uppercase, lowercase, number & symbol.
                                    </div>
                                </div>
                                <div>
                                    <label class="su-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="su-input @error('password_confirmation') is-invalid @enderror" minlength="12" required>
                                    @error('password_confirmation')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <p style="font-size:12px;color:var(--su-text-muted);margin-top:8px;margin-bottom:0;">
                                        By signing up, you’ll start in the BadliCash sandbox. Our team will review your details and
                                        enable live money flow once compliance checks are complete.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="su-bottom-row">
                        <button type="button" class="su-back-btn" id="prevStepBtn" disabled>
                            ← Back
                        </button>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:12px;color:var(--su-text-muted);display:none;" class="d-md-inline">Step <span id="stepIndicator">1</span> of 4</span>
                            <button type="button" class="su-primary-btn" id="nextStepBtn">
                                Next →
                            </button>
                            <button type="submit" class="su-primary-btn d-none" id="submitBtn">
                                Create my account
                            </button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const tabs = ['business','tax','bank','login'];
        let currentIndex = 0;
        const prevBtn = document.getElementById('prevStepBtn');
        const nextBtn = document.getElementById('nextStepBtn');
        const submitBtn = document.getElementById('submitBtn');
        const stepIndicator = document.getElementById('stepIndicator');

        function updateStep(delta) {
            currentIndex = Math.min(Math.max(currentIndex + delta, 0), tabs.length - 1);
            const targetId = 'tab-' + tabs[currentIndex];
            const tabTriggerEl = document.querySelector('#' + targetId);
            if (tabTriggerEl) {
                new bootstrap.Tab(tabTriggerEl).show();
            }
            prevBtn.disabled = currentIndex === 0;
            nextBtn.classList.toggle('d-none', currentIndex === tabs.length - 1);
            submitBtn.classList.toggle('d-none', currentIndex !== tabs.length - 1);
            stepIndicator.textContent = (currentIndex + 1).toString();
        }

        prevBtn.addEventListener('click', function () {
            updateStep(-1);
        });

        nextBtn.addEventListener('click', function () {
            updateStep(1);
        });
    })();
</script>
</body>
</html>


