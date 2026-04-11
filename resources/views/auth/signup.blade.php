<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign up – Ipay Merchant Account</title>
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

        .su-input.is-invalid,
        .su-select.is-invalid {
            border-color: #b91c1c !important;
            box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.25);
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
                            <div id="businessClientErrors" class="alert-danger d-none mb-3" role="alert" style="font-size:12px;"></div>
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Business / Brand Name <span class="text-danger">*</span></label>
                                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" class="su-input @error('business_name') is-invalid @enderror" maxlength="255" pattern="[A-Za-z0-9 ]+" title="Letters, numbers, and spaces only" required autocomplete="organization" data-validate="business_name">
                                    @error('business_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_business_name" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Legal Name (as per PAN) <span class="text-danger">*</span></label>
                                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}" class="su-input @error('legal_name') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces only" required autocomplete="off" data-validate="legal_name">
                                    @error('legal_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_legal_name" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Business Email <span class="text-danger">*</span></label>
                                    <input type="email" name="business_email" id="business_email" value="{{ old('business_email') }}" class="su-input @error('business_email') is-invalid @enderror" maxlength="255" required autocomplete="email" inputmode="email" data-validate="business_email">
                                    @error('business_email')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_business_email" role="alert"></div>
                                </div>
                                <div>
                                    <label class="su-label">Business Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="business_phone" id="business_phone" value="{{ old('business_phone') }}" class="su-input @error('business_phone') is-invalid @enderror" pattern="[+0-9]+" minlength="10" maxlength="20" title="Digits only, optional + prefix" required autocomplete="tel" data-validate="business_phone">
                                    @error('business_phone')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_business_phone" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Website (optional)</label>
                                    <input type="url" name="website_link" id="website_link" value="{{ old('website_link') }}" class="su-input @error('website_link') is-invalid @enderror" placeholder="https://example.com" maxlength="255" title="Valid URL starting with http:// or https://" data-validate="website_link">
                                    @error('website_link')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_website_link" role="status"></div>
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
                                    <select id="business_country" name="business_country" class="su-select @error('business_country') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                    </select>
                                    @error('business_country')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">State <span class="text-danger">*</span></label>
                                    <select id="business_state" name="business_state" class="su-select @error('business_state') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                    </select>
                                    @error('business_state')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">City <span class="text-danger">*</span></label>
                                    <select id="business_city" name="business_city" class="su-select @error('business_city') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                    </select>
                                    @error('business_city')<div class="su-error">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="su-label">Pin / ZIP Code <span class="text-danger">*</span></label>
                                    <input type="text" name="business_postal_code" id="business_postal_code" value="{{ old('business_postal_code') }}" class="su-input @error('business_postal_code') is-invalid @enderror" pattern="[0-9]+" inputmode="numeric" maxlength="15" title="Numbers only" required data-validate="business_postal_code">
                                    @error('business_postal_code')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_business_postal_code" role="status"></div>
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
                            <div id="complianceClientErrors" class="alert-danger d-none mb-3" role="alert" style="font-size:12px;"></div>
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">PAN Number <span class="text-danger">*</span></label>
                                    <input type="text" name="merchant_pan_number" id="merchant_pan_number" value="{{ old('merchant_pan_number') }}" class="su-input text-uppercase @error('merchant_pan_number') is-invalid @enderror" maxlength="10" pattern="[A-Za-z0-9]{10}" title="Exactly 10 letters or numbers" required autocomplete="off" data-validate="merchant_pan_number">
                                    @error('merchant_pan_number')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_merchant_pan_number" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Name on PAN <span class="text-danger">*</span></label>
                                    <input type="text" name="name_on_pan_card" id="name_on_pan_card" value="{{ old('name_on_pan_card') }}" class="su-input @error('name_on_pan_card') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces; include at least one letter" required autocomplete="off" data-validate="name_on_pan_card">
                                    @error('name_on_pan_card')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_name_on_pan_card" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">GSTIN (optional)</label>
                                    <input type="text" name="gst_identification_no" id="gst_identification_no" value="{{ old('gst_identification_no') }}" class="su-input @error('gst_identification_no') is-invalid @enderror" maxlength="20" pattern="[A-Za-z0-9]*" title="Letters and numbers only" autocomplete="off" data-validate="gst_identification_no">
                                    @error('gst_identification_no')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_gst_identification_no" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">GSTIN State</label>
                                    <input type="text" name="gstin_state" id="gstin_state" value="{{ old('gstin_state') }}" class="su-input @error('gstin_state') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]*" title="Letters and spaces only" autocomplete="off" data-validate="gstin_state">
                                    @error('gstin_state')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_gstin_state" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">TAN (optional)</label>
                                    <input type="text" name="tan_no" id="tan_no" value="{{ old('tan_no') }}" class="su-input @error('tan_no') is-invalid @enderror" maxlength="50" pattern="[A-Za-z0-9]*" title="Letters and numbers only" autocomplete="off" data-validate="tan_no">
                                    @error('tan_no')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_tan_no" role="status"></div>
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
                            <div id="bankClientErrors" class="alert-danger d-none mb-3" role="alert" style="font-size:12px;"></div>
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Account Holder Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_account_holder_name" id="bank_account_holder_name" value="{{ old('bank_account_holder_name') }}" class="su-input @error('bank_account_holder_name') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces; include at least one letter" required autocomplete="off" data-validate="bank_account_holder_name">
                                    @error('bank_account_holder_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_bank_account_holder_name" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Bank Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number') }}" class="su-input @error('bank_account_number') is-invalid @enderror" minlength="8" maxlength="34" pattern="[A-Za-z0-9]+" title="Letters and numbers only (8–34 characters)" required inputmode="text" autocomplete="off" data-validate="bank_account_number">
                                    @error('bank_account_number')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_bank_account_number" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" class="su-input @error('bank_name') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces; include at least one letter" required autocomplete="off" data-validate="bank_name">
                                    @error('bank_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_bank_name" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Account Type <span class="text-danger">*</span></label>
                                    <select name="account_type" id="account_type" class="su-select @error('account_type') is-invalid @enderror" required data-validate="account_type">
                                        <option value="">Select type</option>
                                        <option value="Savings Account" @selected(old('account_type') === 'Savings Account')>Savings Account</option>
                                        <option value="Current Account" @selected(old('account_type') === 'Current Account')>Current Account</option>
                                    </select>
                                    @error('account_type')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_account_type" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Branch <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_branch" id="bank_branch" value="{{ old('bank_branch') }}" class="su-input @error('bank_branch') is-invalid @enderror" maxlength="255" pattern="[A-Za-z0-9 ]+" title="Letters, numbers, and spaces" required autocomplete="off" data-validate="bank_branch">
                                    @error('bank_branch')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_bank_branch" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">IFSC Code <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_ifsc_code" id="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}" class="su-input text-uppercase @error('bank_ifsc_code') is-invalid @enderror" maxlength="11" pattern="[A-Za-z0-9]+" title="Letters and numbers only" required autocomplete="off" data-validate="bank_ifsc_code">
                                    @error('bank_ifsc_code')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_bank_ifsc_code" role="status"></div>
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
                            <div id="loginClientErrors" class="alert-danger d-none mb-3" role="alert" style="font-size:12px;"></div>
                            <div class="su-form-grid">
                                <div>
                                    <label class="su-label">Primary Contact Name <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}" class="su-input @error('contact_name') is-invalid @enderror" maxlength="255" pattern="[A-Za-z ]+" title="Letters and spaces; include at least one letter" required autocomplete="name" data-validate="contact_name">
                                    @error('contact_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_contact_name" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Contact Mobile <span class="text-danger">*</span></label>
                                    <input type="tel" name="contact_mobile" id="contact_mobile" value="{{ old('contact_mobile') }}" class="su-input @error('contact_mobile') is-invalid @enderror" maxlength="20" title="Digits only; optional + at start" required autocomplete="tel" inputmode="tel" data-validate="contact_mobile">
                                    @error('contact_mobile')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_contact_mobile" role="status"></div>
                                </div>
                                <div>
                                    <label class="su-label">Contact Email <span class="text-danger">*</span></label>
                                    <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email') }}" class="su-input @error('contact_email') is-invalid @enderror" maxlength="255" required autocomplete="email" inputmode="email" data-validate="contact_email">
                                    @error('contact_email')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_contact_email" role="alert"></div>
                                </div>
                                <div>
                                    <label class="su-label">Login Email (for Ipay) <span class="text-danger">*</span></label>
                                    <input type="email" name="login_name" id="login_name" value="{{ old('login_name') }}" class="su-input @error('login_name') is-invalid @enderror" maxlength="255" required autocomplete="email" inputmode="email" data-validate="login_name">
                                    @error('login_name')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_login_name" role="alert"></div>
                                </div>
                                <div>
                                    <label class="su-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" id="password" class="su-input @error('password') is-invalid @enderror" minlength="12" autocomplete="new-password" required data-validate="password">
                                    @error('password')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_password" role="status"></div>
                                    <div style="font-size:11px;color:var(--su-text-muted);margin-top:6px;">
                                        Min 12 characters with uppercase, lowercase, number &amp; symbol.
                                    </div>
                                </div>
                                <div>
                                    <label class="su-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="su-input @error('password_confirmation') is-invalid @enderror" minlength="12" autocomplete="new-password" required data-validate="password_confirmation">
                                    @error('password_confirmation')<div class="su-error">{{ $message }}</div>@enderror
                                    <div class="su-error d-none" id="js_err_password_confirmation" role="status"></div>
                                </div>
                                <div style="grid-column: 1 / -1;">
                                    <p style="font-size:12px;color:var(--su-text-muted);margin-top:8px;margin-bottom:0;">
                                        By signing up, you’ll start in the Ipay sandbox. Our team will review your details and
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
        const oldCountry = @json(old('business_country'));
        const oldState = @json(old('business_state'));
        const oldCity = @json(old('business_city'));
        const countrySelect = document.getElementById('business_country');
        const stateSelect = document.getElementById('business_state');
        const citySelect = document.getElementById('business_city');
        let locationMap = {};

        function fillOptions(selectEl, values, placeholder, selected) {
            if (!selectEl) return;
            selectEl.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = placeholder;
            selectEl.appendChild(defaultOpt);

            values.forEach(function (value) {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = value;
                if (selected && selected === value) {
                    opt.selected = true;
                }
                selectEl.appendChild(opt);
            });
        }

        function onCountryChange(resetState) {
            const country = countrySelect ? countrySelect.value : '';
            const states = (country && locationMap[country]) ? Object.keys(locationMap[country]) : [];
            fillOptions(stateSelect, states, 'Select', resetState ? '' : oldState);
            stateSelect.disabled = states.length === 0;
            onStateChange(true);
        }

        function onStateChange(resetCity) {
            const country = countrySelect ? countrySelect.value : '';
            const state = stateSelect ? stateSelect.value : '';
            const cities = (country && state && locationMap[country] && locationMap[country][state]) ? locationMap[country][state] : [];
            fillOptions(citySelect, cities, 'Select', resetCity ? '' : oldCity);
            citySelect.disabled = cities.length === 0;
        }

        if (countrySelect && stateSelect && citySelect) {
            stateSelect.disabled = true;
            citySelect.disabled = true;

            fetch('/signup/locations')
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    if (!payload || !payload.success || !payload.data) return;
                    locationMap = payload.data;
                    const countries = Object.keys(locationMap);
                    fillOptions(countrySelect, countries, 'Select', oldCountry || 'India');
                    onCountryChange(false);
                })
                .catch(function () {
                    // Keep empty dropdowns on fetch failure.
                });

            countrySelect.addEventListener('change', function () {
                onCountryChange(true);
            });
            stateSelect.addEventListener('change', function () {
                onStateChange(true);
            });
        }

        // Strip invalid characters as user types (form uses novalidate; pattern alone does not block typing)
        (function attachSignupSanitizers() {
            var bn = document.getElementById('business_name');
            if (bn) {
                bn.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9 ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var ln = document.getElementById('legal_name');
            if (ln) {
                ln.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var ph = document.getElementById('business_phone');
            if (ph) {
                ph.addEventListener('input', function () {
                    var lead = this.value.charAt(0) === '+';
                    var digits = this.value.replace(/[^0-9]/g, '');
                    var v = lead ? ('+' + digits) : digits;
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var pin = document.getElementById('business_postal_code');
            if (pin) {
                pin.addEventListener('input', function () {
                    var v = this.value.replace(/[^0-9]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var pan = document.getElementById('merchant_pan_number');
            if (pan) {
                pan.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 10);
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var nop = document.getElementById('name_on_pan_card');
            if (nop) {
                nop.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var gst = document.getElementById('gst_identification_no');
            if (gst) {
                gst.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var gstSt = document.getElementById('gstin_state');
            if (gstSt) {
                gstSt.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var tan = document.getElementById('tan_no');
            if (tan) {
                tan.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var bah = document.getElementById('bank_account_holder_name');
            if (bah) {
                bah.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var ban = document.getElementById('bank_account_number');
            if (ban) {
                ban.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var bnk = document.getElementById('bank_name');
            if (bnk) {
                bnk.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var br = document.getElementById('bank_branch');
            if (br) {
                br.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9 ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var ifsc = document.getElementById('bank_ifsc_code');
            if (ifsc) {
                ifsc.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 11);
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var cname = document.getElementById('contact_name');
            if (cname) {
                cname.addEventListener('input', function () {
                    var v = this.value.replace(/[^A-Za-z ]/g, '');
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
            var cmob = document.getElementById('contact_mobile');
            if (cmob) {
                cmob.addEventListener('input', function () {
                    var lead = this.value.charAt(0) === '+';
                    var digits = this.value.replace(/[^0-9]/g, '');
                    var v = lead ? ('+' + digits) : digits;
                    if (this.value !== v) {
                        this.value = v;
                    }
                });
            }
        })();

        // —— Inline validation (Business step): messages under fields + email checks while typing
        (function attachBusinessInlineValidation() {
            function isValidEmailStr(s) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(s).trim());
            }

            function setInlineError(inputId, errId, msg) {
                var inp = document.getElementById(inputId);
                var err = document.getElementById(errId);
                if (!inp || !err) {
                    return;
                }
                if (msg) {
                    err.textContent = msg;
                    err.classList.remove('d-none');
                    inp.classList.add('is-invalid');
                } else {
                    err.textContent = '';
                    err.classList.add('d-none');
                    inp.classList.remove('is-invalid');
                }
            }

            function msgBusinessName() {
                var bn = (document.getElementById('business_name') && document.getElementById('business_name').value.trim()) || '';
                if (!bn) {
                    return 'Business / brand name is required.';
                }
                if (!/^(?=.*[A-Za-z0-9])[A-Za-z0-9 ]+$/.test(bn)) {
                    return 'Use only letters, numbers, and spaces.';
                }
                return '';
            }

            function msgLegalName() {
                var ln = (document.getElementById('legal_name') && document.getElementById('legal_name').value.trim()) || '';
                if (!ln) {
                    return 'Legal name is required.';
                }
                if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(ln)) {
                    return 'Use only letters and spaces.';
                }
                return '';
            }

            function msgPhone() {
                var ph = (document.getElementById('business_phone') && document.getElementById('business_phone').value) || '';
                if (!ph.trim()) {
                    return 'Business phone is required.';
                }
                if (!/^[+0-9]+$/.test(ph) || ph.replace(/[^0-9]/g, '').length < 10) {
                    return 'Use digits only (optional + at start). At least 10 digits.';
                }
                return '';
            }

            function msgWebsite() {
                var web = (document.getElementById('website_link') && document.getElementById('website_link').value.trim()) || '';
                if (web === '') {
                    return '';
                }
                try {
                    var u = new URL(web);
                    if (u.protocol !== 'http:' && u.protocol !== 'https:') {
                        return 'URL must start with http:// or https://';
                    }
                } catch (e) {
                    return 'Enter a valid website URL (e.g. https://example.com).';
                }
                return '';
            }

            function msgPin() {
                var pin = (document.getElementById('business_postal_code') && document.getElementById('business_postal_code').value.trim()) || '';
                if (!pin) {
                    return 'Pin / ZIP code is required.';
                }
                if (!/^[0-9]+$/.test(pin)) {
                    return 'Use numbers only.';
                }
                return '';
            }

            var emailDebounce = null;
            var emailEl = document.getElementById('business_email');
            if (emailEl) {
                emailEl.addEventListener('input', function () {
                    clearTimeout(emailDebounce);
                    var self = this;
                    emailDebounce = setTimeout(function () {
                        var v = (self.value && self.value.trim()) || '';
                        if (v === '') {
                            setInlineError('business_email', 'js_err_business_email', '');
                            return;
                        }
                        if (!isValidEmailStr(v)) {
                            setInlineError('business_email', 'js_err_business_email', 'Enter a valid email address.');
                        } else {
                            setInlineError('business_email', 'js_err_business_email', '');
                        }
                    }, 200);
                });
                emailEl.addEventListener('blur', function () {
                    clearTimeout(emailDebounce);
                    var v = (this.value && this.value.trim()) || '';
                    if (v === '') {
                        setInlineError('business_email', 'js_err_business_email', 'Business email is required.');
                        return;
                    }
                    if (!isValidEmailStr(v)) {
                        setInlineError('business_email', 'js_err_business_email', 'Enter a valid email address.');
                    } else {
                        setInlineError('business_email', 'js_err_business_email', '');
                    }
                });
            }

            function wireBlur(inputId, errId, getter) {
                var el = document.getElementById(inputId);
                if (!el) {
                    return;
                }
                el.addEventListener('blur', function () {
                    setInlineError(inputId, errId, getter());
                });
            }

            wireBlur('business_name', 'js_err_business_name', msgBusinessName);
            wireBlur('legal_name', 'js_err_legal_name', msgLegalName);
            wireBlur('business_phone', 'js_err_business_phone', msgPhone);
            wireBlur('website_link', 'js_err_website_link', msgWebsite);
            wireBlur('business_postal_code', 'js_err_business_postal_code', msgPin);

            ['business_name', 'legal_name', 'business_phone', 'website_link', 'business_postal_code'].forEach(function (fid) {
                var el = document.getElementById(fid);
                if (!el) {
                    return;
                }
                var errMap = {
                    business_name: ['js_err_business_name', msgBusinessName],
                    legal_name: ['js_err_legal_name', msgLegalName],
                    business_phone: ['js_err_business_phone', msgPhone],
                    website_link: ['js_err_website_link', msgWebsite],
                    business_postal_code: ['js_err_business_postal_code', msgPin]
                };
                var pair = errMap[fid];
                el.addEventListener('input', function () {
                    var m = pair[1]();
                    if (!m) {
                        setInlineError(fid, pair[0], '');
                    }
                });
            });
        })();

        // —— Inline validation (Compliance step)
        (function attachComplianceInlineValidation() {
            function setInlineError(inputId, errId, msg) {
                var inp = document.getElementById(inputId);
                var err = document.getElementById(errId);
                if (!inp || !err) {
                    return;
                }
                if (msg) {
                    err.textContent = msg;
                    err.classList.remove('d-none');
                    inp.classList.add('is-invalid');
                } else {
                    err.textContent = '';
                    err.classList.add('d-none');
                    inp.classList.remove('is-invalid');
                }
            }

            function msgPan() {
                var p = (document.getElementById('merchant_pan_number') && document.getElementById('merchant_pan_number').value) || '';
                p = String(p).trim();
                if (!p) {
                    return 'PAN number is required.';
                }
                if (p.length !== 10 || !/^[A-Za-z0-9]{10}$/.test(p)) {
                    return 'Enter exactly 10 letters or numbers.';
                }
                return '';
            }

            function msgNameOnPan() {
                var n = (document.getElementById('name_on_pan_card') && document.getElementById('name_on_pan_card').value.trim()) || '';
                if (!n) {
                    return 'Name on PAN is required.';
                }
                if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(n)) {
                    return 'Use only letters and spaces (include at least one letter).';
                }
                return '';
            }

            function msgGstin() {
                var g = (document.getElementById('gst_identification_no') && document.getElementById('gst_identification_no').value.trim()) || '';
                if (g === '') {
                    return '';
                }
                if (!/^[A-Za-z0-9]+$/.test(g)) {
                    return 'Use only letters and numbers.';
                }
                return '';
            }

            function msgGstinState() {
                var s = (document.getElementById('gstin_state') && document.getElementById('gstin_state').value.trim()) || '';
                if (s === '') {
                    return '';
                }
                if (!/^[A-Za-z ]+$/.test(s)) {
                    return 'Use only letters and spaces.';
                }
                return '';
            }

            function msgTan() {
                var t = (document.getElementById('tan_no') && document.getElementById('tan_no').value.trim()) || '';
                if (t === '') {
                    return '';
                }
                if (!/^[A-Za-z0-9]+$/.test(t)) {
                    return 'Use only letters and numbers.';
                }
                return '';
            }

            function wireBlur(inputId, errId, getter) {
                var el = document.getElementById(inputId);
                if (!el) {
                    return;
                }
                el.addEventListener('blur', function () {
                    setInlineError(inputId, errId, getter());
                });
            }

            wireBlur('merchant_pan_number', 'js_err_merchant_pan_number', msgPan);
            wireBlur('name_on_pan_card', 'js_err_name_on_pan_card', msgNameOnPan);
            wireBlur('gst_identification_no', 'js_err_gst_identification_no', msgGstin);
            wireBlur('gstin_state', 'js_err_gstin_state', msgGstinState);
            wireBlur('tan_no', 'js_err_tan_no', msgTan);

            ['merchant_pan_number', 'name_on_pan_card', 'gst_identification_no', 'gstin_state', 'tan_no'].forEach(function (fid) {
                var el = document.getElementById(fid);
                if (!el) {
                    return;
                }
                var errMap = {
                    merchant_pan_number: ['js_err_merchant_pan_number', msgPan],
                    name_on_pan_card: ['js_err_name_on_pan_card', msgNameOnPan],
                    gst_identification_no: ['js_err_gst_identification_no', msgGstin],
                    gstin_state: ['js_err_gstin_state', msgGstinState],
                    tan_no: ['js_err_tan_no', msgTan]
                };
                var pair = errMap[fid];
                el.addEventListener('input', function () {
                    var m = pair[1]();
                    if (!m) {
                        setInlineError(fid, pair[0], '');
                    }
                });
            });
        })();

        // —— Inline validation (Bank step)
        (function attachBankInlineValidation() {
            function setInlineError(inputId, errId, msg) {
                var inp = document.getElementById(inputId);
                var err = document.getElementById(errId);
                if (!inp || !err) {
                    return;
                }
                if (msg) {
                    err.textContent = msg;
                    err.classList.remove('d-none');
                    inp.classList.add('is-invalid');
                } else {
                    err.textContent = '';
                    err.classList.add('d-none');
                    inp.classList.remove('is-invalid');
                }
            }

            function msgHolder() {
                var v = (document.getElementById('bank_account_holder_name') && document.getElementById('bank_account_holder_name').value.trim()) || '';
                if (!v) {
                    return 'Account holder name is required.';
                }
                if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(v)) {
                    return 'Use only letters and spaces (include at least one letter).';
                }
                return '';
            }

            function msgAcctNum() {
                var v = (document.getElementById('bank_account_number') && document.getElementById('bank_account_number').value.trim()) || '';
                if (!v) {
                    return 'Bank account number is required.';
                }
                if (v.length < 8 || v.length > 34) {
                    return 'Use 8 to 34 characters.';
                }
                if (!/^[A-Za-z0-9]+$/.test(v)) {
                    return 'Use only letters and numbers.';
                }
                return '';
            }

            function msgBankName() {
                var v = (document.getElementById('bank_name') && document.getElementById('bank_name').value.trim()) || '';
                if (!v) {
                    return 'Bank name is required.';
                }
                if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(v)) {
                    return 'Use only letters and spaces (include at least one letter).';
                }
                return '';
            }

            function msgAccountType() {
                var s = document.getElementById('account_type');
                if (!s || !s.value) {
                    return 'Select an account type.';
                }
                return '';
            }

            function msgBranch() {
                var v = (document.getElementById('bank_branch') && document.getElementById('bank_branch').value.trim()) || '';
                if (!v) {
                    return 'Branch is required.';
                }
                if (!/^(?=.*[A-Za-z0-9])[A-Za-z0-9 ]+$/.test(v)) {
                    return 'Use only letters, numbers, and spaces (include at least one letter or digit).';
                }
                return '';
            }

            function msgIfsc() {
                var v = (document.getElementById('bank_ifsc_code') && document.getElementById('bank_ifsc_code').value.trim()) || '';
                if (!v) {
                    return 'IFSC code is required.';
                }
                if (!/^[A-Za-z0-9]+$/.test(v)) {
                    return 'Use only letters and numbers.';
                }
                return '';
            }

            function wireBlur(inputId, errId, getter) {
                var el = document.getElementById(inputId);
                if (!el) {
                    return;
                }
                el.addEventListener('blur', function () {
                    setInlineError(inputId, errId, getter());
                });
            }

            wireBlur('bank_account_holder_name', 'js_err_bank_account_holder_name', msgHolder);
            wireBlur('bank_account_number', 'js_err_bank_account_number', msgAcctNum);
            wireBlur('bank_name', 'js_err_bank_name', msgBankName);
            wireBlur('bank_branch', 'js_err_bank_branch', msgBranch);
            wireBlur('bank_ifsc_code', 'js_err_bank_ifsc_code', msgIfsc);

            var accountTypeEl = document.getElementById('account_type');
            if (accountTypeEl) {
                function syncAccountType() {
                    setInlineError('account_type', 'js_err_account_type', msgAccountType());
                }
                accountTypeEl.addEventListener('change', syncAccountType);
                accountTypeEl.addEventListener('blur', syncAccountType);
            }

            [
                ['bank_account_holder_name', 'js_err_bank_account_holder_name', msgHolder],
                ['bank_account_number', 'js_err_bank_account_number', msgAcctNum],
                ['bank_name', 'js_err_bank_name', msgBankName],
                ['bank_branch', 'js_err_bank_branch', msgBranch],
                ['bank_ifsc_code', 'js_err_bank_ifsc_code', msgIfsc]
            ].forEach(function (triple) {
                var el = document.getElementById(triple[0]);
                if (!el) {
                    return;
                }
                el.addEventListener('input', function () {
                    setInlineError(triple[0], triple[1], triple[2]());
                });
            });
        })();

        // —— Inline validation (Login step)
        (function attachLoginInlineValidation() {
            function isValidEmailStr(s) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(s).trim());
            }

            function setInlineError(inputId, errId, msg) {
                var inp = document.getElementById(inputId);
                var err = document.getElementById(errId);
                if (!inp || !err) {
                    return;
                }
                if (msg) {
                    err.textContent = msg;
                    err.classList.remove('d-none');
                    inp.classList.add('is-invalid');
                } else {
                    err.textContent = '';
                    err.classList.add('d-none');
                    inp.classList.remove('is-invalid');
                }
            }

            function msgContactName() {
                var v = (document.getElementById('contact_name') && document.getElementById('contact_name').value.trim()) || '';
                if (!v) {
                    return 'Primary contact name is required.';
                }
                if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(v)) {
                    return 'Use only letters and spaces (include at least one letter).';
                }
                return '';
            }

            function msgContactMobile() {
                var ph = (document.getElementById('contact_mobile') && document.getElementById('contact_mobile').value) || '';
                if (!ph.trim()) {
                    return 'Contact mobile is required.';
                }
                if (!/^\+?[0-9]{10,19}$/.test(ph)) {
                    return 'Use optional + at the start, then 10–19 digits.';
                }
                return '';
            }

            function msgContactEmail() {
                var v = (document.getElementById('contact_email') && document.getElementById('contact_email').value.trim()) || '';
                if (!v) {
                    return 'Contact email is required.';
                }
                if (!isValidEmailStr(v)) {
                    return 'Enter a valid email address.';
                }
                return '';
            }

            function msgLoginEmail() {
                var v = (document.getElementById('login_name') && document.getElementById('login_name').value.trim()) || '';
                if (!v) {
                    return 'Login email is required.';
                }
                if (!isValidEmailStr(v)) {
                    return 'Enter a valid email address.';
                }
                return '';
            }

            function msgPassword() {
                var p = (document.getElementById('password') && document.getElementById('password').value) || '';
                if (!p) {
                    return 'Password is required.';
                }
                if (p.length < 12) {
                    return 'Use at least 12 characters.';
                }
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/.test(p)) {
                    return 'Include upper & lower case, a number, and a symbol (@$!%*?&).';
                }
                return '';
            }

            function msgPasswordConfirm() {
                var p = (document.getElementById('password') && document.getElementById('password').value) || '';
                var c = (document.getElementById('password_confirmation') && document.getElementById('password_confirmation').value) || '';
                if (!c) {
                    return 'Confirm your password.';
                }
                if (p !== c) {
                    return 'Passwords do not match.';
                }
                return '';
            }

            function wireBlur(inputId, errId, getter) {
                var el = document.getElementById(inputId);
                if (!el) {
                    return;
                }
                el.addEventListener('blur', function () {
                    setInlineError(inputId, errId, getter());
                });
            }

            wireBlur('contact_name', 'js_err_contact_name', msgContactName);
            wireBlur('contact_mobile', 'js_err_contact_mobile', msgContactMobile);
            wireBlur('password', 'js_err_password', msgPassword);
            wireBlur('password_confirmation', 'js_err_password_confirmation', msgPasswordConfirm);

            var debounceContactEmail = null;
            var elContactEmail = document.getElementById('contact_email');
            if (elContactEmail) {
                elContactEmail.addEventListener('input', function () {
                    clearTimeout(debounceContactEmail);
                    debounceContactEmail = setTimeout(function () {
                        setInlineError('contact_email', 'js_err_contact_email', msgContactEmail());
                    }, 200);
                });
                elContactEmail.addEventListener('blur', function () {
                    clearTimeout(debounceContactEmail);
                    setInlineError('contact_email', 'js_err_contact_email', msgContactEmail());
                });
            }

            var debounceLoginEmail = null;
            var elLoginName = document.getElementById('login_name');
            if (elLoginName) {
                elLoginName.addEventListener('input', function () {
                    clearTimeout(debounceLoginEmail);
                    debounceLoginEmail = setTimeout(function () {
                        setInlineError('login_name', 'js_err_login_name', msgLoginEmail());
                    }, 200);
                });
                elLoginName.addEventListener('blur', function () {
                    clearTimeout(debounceLoginEmail);
                    setInlineError('login_name', 'js_err_login_name', msgLoginEmail());
                });
            }

            var elContactName = document.getElementById('contact_name');
            if (elContactName) {
                elContactName.addEventListener('input', function () {
                    setInlineError('contact_name', 'js_err_contact_name', msgContactName());
                });
            }
            var elContactMobile = document.getElementById('contact_mobile');
            if (elContactMobile) {
                elContactMobile.addEventListener('input', function () {
                    setInlineError('contact_mobile', 'js_err_contact_mobile', msgContactMobile());
                });
            }

            var elPassword = document.getElementById('password');
            if (elPassword) {
                elPassword.addEventListener('input', function () {
                    setInlineError('password', 'js_err_password', msgPassword());
                    var conf = document.getElementById('password_confirmation');
                    if (conf && conf.value) {
                        setInlineError('password_confirmation', 'js_err_password_confirmation', msgPasswordConfirm());
                    }
                });
            }
            var elPasswordConf = document.getElementById('password_confirmation');
            if (elPasswordConf) {
                elPasswordConf.addEventListener('input', function () {
                    setInlineError('password_confirmation', 'js_err_password_confirmation', msgPasswordConfirm());
                });
            }
        })();

        const tabs = ['business','tax','bank','login'];
        const paneIds = ['pane-business', 'pane-tax', 'pane-bank', 'pane-login'];
        const tabBtnIds = ['tab-business', 'tab-tax', 'tab-bank', 'tab-login'];
        let currentIndex = 0;
        const prevBtn = document.getElementById('prevStepBtn');
        const nextBtn = document.getElementById('nextStepBtn');
        const submitBtn = document.getElementById('submitBtn');
        const stepIndicator = document.getElementById('stepIndicator');

        /**
         * Custom step wizard — do not use bootstrap.Tab (triggers lack data-bs-toggle / data-bs-target,
         * which causes "Illegal invocation" inside Bootstrap’s tab plugin).
         */
        function updateStep(delta) {
            currentIndex = Math.min(Math.max(currentIndex + delta, 0), tabs.length - 1);

            paneIds.forEach(function (pid, i) {
                var pane = document.getElementById(pid);
                if (!pane) {
                    return;
                }
                if (i === currentIndex) {
                    pane.classList.add('show', 'active');
                } else {
                    pane.classList.remove('show', 'active');
                }
            });

            tabBtnIds.forEach(function (tid, i) {
                var btn = document.getElementById(tid);
                if (!btn) {
                    return;
                }
                if (i === currentIndex) {
                    btn.classList.add('su-tab-active');
                } else {
                    btn.classList.remove('su-tab-active');
                }
            });

            prevBtn.disabled = currentIndex === 0;
            nextBtn.classList.toggle('d-none', currentIndex === tabs.length - 1);
            submitBtn.classList.toggle('d-none', currentIndex !== tabs.length - 1);
            stepIndicator.textContent = (currentIndex + 1).toString();
        }

        prevBtn.addEventListener('click', function () {
            updateStep(-1);
        });

        function clearBusinessClientErrors() {
            var box = document.getElementById('businessClientErrors');
            if (box) {
                box.textContent = '';
                box.classList.add('d-none');
            }
        }

        function showBusinessClientError(msg) {
            var box = document.getElementById('businessClientErrors');
            if (box) {
                box.textContent = msg;
                box.classList.remove('d-none');
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function clearComplianceClientErrors() {
            var box = document.getElementById('complianceClientErrors');
            if (box) {
                box.textContent = '';
                box.classList.add('d-none');
            }
        }

        function showComplianceClientError(msg) {
            var box = document.getElementById('complianceClientErrors');
            if (box) {
                box.textContent = msg;
                box.classList.remove('d-none');
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function clearBankClientErrors() {
            var box = document.getElementById('bankClientErrors');
            if (box) {
                box.textContent = '';
                box.classList.add('d-none');
            }
        }

        function showBankClientError(msg) {
            var box = document.getElementById('bankClientErrors');
            if (box) {
                box.textContent = msg;
                box.classList.remove('d-none');
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function clearLoginClientErrors() {
            var box = document.getElementById('loginClientErrors');
            if (box) {
                box.textContent = '';
                box.classList.add('d-none');
            }
        }

        function showLoginClientError(msg) {
            var box = document.getElementById('loginClientErrors');
            if (box) {
                box.textContent = msg;
                box.classList.remove('d-none');
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        /**
         * Client-side checks aligned with RegistrationController (step 1 only).
         */
        function validateBusinessStep() {
            var bn = (document.getElementById('business_name') && document.getElementById('business_name').value) || '';
            var ln = (document.getElementById('legal_name') && document.getElementById('legal_name').value) || '';
            var em = (document.getElementById('business_email') && document.getElementById('business_email').value) || '';
            var ph = (document.getElementById('business_phone') && document.getElementById('business_phone').value) || '';
            var web = (document.getElementById('website_link') && document.getElementById('website_link').value) || '';
            var pin = (document.getElementById('business_postal_code') && document.getElementById('business_postal_code').value) || '';

            if (!/^(?=.*[A-Za-z0-9])[A-Za-z0-9 ]+$/.test(bn.trim())) {
                return 'Business / Brand Name: use only letters, numbers, and spaces (not empty or spaces only).';
            }
            if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(ln.trim())) {
                return 'Legal Name: use only letters and spaces (not empty or spaces only).';
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.trim())) {
                return 'Business Email: enter a valid email address.';
            }
            if (!/^[+0-9]+$/.test(ph) || ph.length < 10) {
                return 'Business Phone: use only digits and + (at least 10 digits).';
            }
            if (web.trim() !== '') {
                try {
                    var u = new URL(web.trim());
                    if (u.protocol !== 'http:' && u.protocol !== 'https:') {
                        return 'Website: URL must start with http:// or https://';
                    }
                } catch (e) {
                    return 'Website: enter a valid URL (e.g. https://example.com).';
                }
            }
            if (!/^[0-9]+$/.test(pin.trim())) {
                return 'Pin / ZIP Code: numbers only.';
            }
            return null;
        }

        /**
         * Client-side checks aligned with RegistrationController (compliance step).
         */
        function validateComplianceStep() {
            var p = (document.getElementById('merchant_pan_number') && document.getElementById('merchant_pan_number').value.trim()) || '';
            if (!p) {
                return 'PAN Number: this field is required.';
            }
            if (p.length !== 10 || !/^[A-Za-z0-9]{10}$/.test(p)) {
                return 'PAN Number: enter exactly 10 letters or numbers.';
            }
            var n = (document.getElementById('name_on_pan_card') && document.getElementById('name_on_pan_card').value.trim()) || '';
            if (!n) {
                return 'Name on PAN: this field is required.';
            }
            if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(n)) {
                return 'Name on PAN: use only letters and spaces (include at least one letter).';
            }
            var g = (document.getElementById('gst_identification_no') && document.getElementById('gst_identification_no').value.trim()) || '';
            if (g !== '' && !/^[A-Za-z0-9]+$/.test(g)) {
                return 'GSTIN: use only letters and numbers.';
            }
            var s = (document.getElementById('gstin_state') && document.getElementById('gstin_state').value.trim()) || '';
            if (s !== '' && !/^[A-Za-z ]+$/.test(s)) {
                return 'GSTIN State: use only letters and spaces.';
            }
            var t = (document.getElementById('tan_no') && document.getElementById('tan_no').value.trim()) || '';
            if (t !== '' && !/^[A-Za-z0-9]+$/.test(t)) {
                return 'TAN: use only letters and numbers.';
            }
            return null;
        }

        /**
         * Client-side checks aligned with RegistrationController (bank step).
         */
        function validateBankStep() {
            var h = (document.getElementById('bank_account_holder_name') && document.getElementById('bank_account_holder_name').value.trim()) || '';
            if (!h) {
                return 'Account holder name: this field is required.';
            }
            if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(h)) {
                return 'Account holder name: use only letters and spaces (include at least one letter).';
            }
            var acc = (document.getElementById('bank_account_number') && document.getElementById('bank_account_number').value.trim()) || '';
            if (!acc) {
                return 'Bank account number: this field is required.';
            }
            if (acc.length < 8 || acc.length > 34) {
                return 'Bank account number: use 8–34 characters.';
            }
            if (!/^[A-Za-z0-9]+$/.test(acc)) {
                return 'Bank account number: use only letters and numbers.';
            }
            var bn = (document.getElementById('bank_name') && document.getElementById('bank_name').value.trim()) || '';
            if (!bn) {
                return 'Bank name: this field is required.';
            }
            if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(bn)) {
                return 'Bank name: use only letters and spaces (include at least one letter).';
            }
            var at = document.getElementById('account_type');
            if (!at || !at.value) {
                return 'Account type: select savings or current account.';
            }
            var br = (document.getElementById('bank_branch') && document.getElementById('bank_branch').value.trim()) || '';
            if (!br) {
                return 'Branch: this field is required.';
            }
            if (!/^(?=.*[A-Za-z0-9])[A-Za-z0-9 ]+$/.test(br)) {
                return 'Branch: use only letters, numbers, and spaces (include at least one letter or digit).';
            }
            var ifsc = (document.getElementById('bank_ifsc_code') && document.getElementById('bank_ifsc_code').value.trim()) || '';
            if (!ifsc) {
                return 'IFSC code: this field is required.';
            }
            if (!/^[A-Za-z0-9]+$/.test(ifsc)) {
                return 'IFSC code: use only letters and numbers.';
            }
            return null;
        }

        /**
         * Client-side checks aligned with RegistrationController (login step).
         */
        function validateLoginStep() {
            function isValidEmailStr(s) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(s).trim());
            }

            var n = (document.getElementById('contact_name') && document.getElementById('contact_name').value.trim()) || '';
            if (!n) {
                return 'Primary contact name: this field is required.';
            }
            if (!/^(?=.*[A-Za-z])[A-Za-z ]+$/.test(n)) {
                return 'Primary contact name: use only letters and spaces (include at least one letter).';
            }

            var ph = (document.getElementById('contact_mobile') && document.getElementById('contact_mobile').value) || '';
            if (!ph.trim()) {
                return 'Contact mobile: this field is required.';
            }
            if (!/^\+?[0-9]{10,19}$/.test(ph)) {
                return 'Contact mobile: optional + at the start, then 10–19 digits.';
            }

            var ce = (document.getElementById('contact_email') && document.getElementById('contact_email').value.trim()) || '';
            if (!ce) {
                return 'Contact email: this field is required.';
            }
            if (!isValidEmailStr(ce)) {
                return 'Contact email: enter a valid email address.';
            }

            var le = (document.getElementById('login_name') && document.getElementById('login_name').value.trim()) || '';
            if (!le) {
                return 'Login email: this field is required.';
            }
            if (!isValidEmailStr(le)) {
                return 'Login email: enter a valid email address.';
            }

            var pw = (document.getElementById('password') && document.getElementById('password').value) || '';
            if (!pw) {
                return 'Password: this field is required.';
            }
            if (pw.length < 12) {
                return 'Password: use at least 12 characters.';
            }
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/.test(pw)) {
                return 'Password: include upper & lower case, a number, and a symbol (@$!%*?&).';
            }

            var pc = (document.getElementById('password_confirmation') && document.getElementById('password_confirmation').value) || '';
            if (!pc) {
                return 'Please confirm your password.';
            }
            if (pw !== pc) {
                return 'Passwords do not match.';
            }
            return null;
        }

        var signupForm = document.getElementById('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', function (e) {
                clearLoginClientErrors();
                ['contact_name', 'contact_mobile', 'contact_email', 'login_name', 'password', 'password_confirmation'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.dispatchEvent(new Event('blur'));
                    }
                });
                var loginErr = validateLoginStep();
                if (loginErr) {
                    e.preventDefault();
                    showLoginClientError(loginErr);
                }
            });
        }

        nextBtn.addEventListener('click', function () {
            if (currentIndex === 0) {
                clearBusinessClientErrors();
                // Sync all inline messages under fields (same rules as blur handlers)
                ['business_name', 'legal_name', 'business_email', 'business_phone', 'website_link', 'business_postal_code'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.dispatchEvent(new Event('blur'));
                    }
                });
                var err = validateBusinessStep();
                if (err) {
                    showBusinessClientError(err);
                    return;
                }
            }
            if (currentIndex === 1) {
                clearComplianceClientErrors();
                ['merchant_pan_number', 'name_on_pan_card', 'gst_identification_no', 'gstin_state', 'tan_no'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.dispatchEvent(new Event('blur'));
                    }
                });
                var cerr = validateComplianceStep();
                if (cerr) {
                    showComplianceClientError(cerr);
                    return;
                }
            }
            if (currentIndex === 2) {
                clearBankClientErrors();
                ['bank_account_holder_name', 'bank_account_number', 'bank_name', 'account_type', 'bank_branch', 'bank_ifsc_code'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.dispatchEvent(new Event('blur'));
                    }
                });
                var berr = validateBankStep();
                if (berr) {
                    showBankClientError(berr);
                    return;
                }
            }
            updateStep(1);
        });
    })();
</script>
</body>
</html>


