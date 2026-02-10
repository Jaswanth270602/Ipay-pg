<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --bc-primary: #6366f1;
            --bc-primary-dark: #4f46e5;
            --bc-bg: #020617;
        }

        body {
            background: radial-gradient(circle at top left, #1d213b 0, #020617 40%, #020617 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 12px;
        }

        .auth-container {
            width: 100%;
            max-width: 80%;
            margin: 0 auto;
        }

        .auth-card {
            background: rgba(15, 23, 42, 0.98);
            border-radius: 24px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.9);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            min-height: 600px;
        }

        .auth-left {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-right {
            padding: 48px 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .logo-section img {
            height: 48px;
            width: auto;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            color: #f9fafb;
        }

        .auth-title {
            font-size: clamp(2rem, 3vw, 2.5rem);
            font-weight: 800;
            line-height: 1.1;
            color: #f9fafb;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .auth-subtitle {
            font-size: 16px;
            color: #9ca3af;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 10px;
        }

        .form-control {
            background: #020617;
            border: 1.5px solid rgba(55, 65, 81, 0.9);
            border-radius: 12px;
            padding: 16px 18px;
            font-size: 16px;
            color: #e5e7eb;
            transition: all 0.2s ease;
            height: auto;
        }

        .form-control:focus {
            background: #020617;
            border-color: var(--bc-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            color: #e5e7eb;
        }

        .form-control::placeholder {
            color: #6b7280;
        }

        .form-check-input {
            background-color: #020617;
            border: 1.5px solid rgba(55, 65, 81, 0.9);
            width: 20px;
            height: 20px;
            margin-top: 4px;
        }

        .form-check-input:checked {
            background-color: var(--bc-primary);
            border-color: var(--bc-primary);
        }

        .form-check-label {
            color: #d1d5db;
            font-size: 14px;
            margin-left: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--bc-primary) 0%, var(--bc-primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            color: #fecaca;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .test-credentials-accordion {
            margin-top: 32px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(55, 65, 81, 0.9);
            overflow: hidden;
        }

        .accordion-header {
            padding: 16px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s ease;
        }

        .accordion-header:hover {
            background: rgba(15, 23, 42, 0.95);
        }

        .accordion-title {
            font-size: 14px;
            font-weight: 600;
            color: #d1d5db;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .accordion-icon {
            transition: transform 0.3s ease;
            color: var(--bc-primary);
        }

        .accordion-header.active .accordion-icon {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-content.active {
            max-height: 500px;
        }

        .accordion-body {
            padding: 0 20px 20px 20px;
            font-size: 13px;
            color: #9ca3af;
        }

        .credential-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 4px;
        }

        .credential-value {
            color: #9ca3af;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .signup-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #9ca3af;
        }

        .signup-link a {
            color: var(--bc-primary);
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .svg-illustration {
            width: 100%;
            max-width: 400px;
            height: auto;
            margin: 0 auto;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 992px) {
            .auth-container {
                max-width: 95%;
            }
            .auth-card {
                grid-template-columns: 1fr;
            }
            .auth-left {
                padding: 32px 24px;
                min-height: 300px;
            }
            .auth-right {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Left Panel: Branding & Illustration -->
            <div class="auth-left">
                <div class="logo-section">
                    <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}">
                    <div class="brand-name">{{ config('app.name') }}</div>
                </div>
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-subtitle">Sign in to your {{ config('app.name') }} dashboard and manage your payments</p>
                
                <!-- SVG Illustration -->
                <svg class="svg-illustration" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="loginGradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1;stop-opacity:0.3" />
                            <stop offset="100%" style="stop-color:#a855f7;stop-opacity:0.2" />
                        </linearGradient>
                        <linearGradient id="loginGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#22c55e;stop-opacity:0.2" />
                            <stop offset="100%" style="stop-color:#16a34a;stop-opacity:0.15" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Background circles -->
                    <circle cx="200" cy="150" r="120" fill="url(#loginGradient1)"/>
                    <circle cx="200" cy="150" r="80" fill="url(#loginGradient2)"/>
                    
                    <!-- Shield/Lock Icon -->
                    <g transform="translate(150, 100)">
                        <rect x="0" y="0" width="100" height="120" rx="12" fill="rgba(99, 102, 241, 0.2)" stroke="rgba(99, 102, 241, 0.6)" stroke-width="2"/>
                        <path d="M 50 30 L 50 60 L 30 60 L 30 100 L 70 100 L 70 60 L 50 60" stroke="rgba(99, 102, 241, 0.8)" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <circle cx="50" cy="45" r="8" fill="rgba(99, 102, 241, 0.6)"/>
                    </g>
                    
                    <!-- User/Profile Icon -->
                    <g transform="translate(120, 180)">
                        <circle cx="30" cy="20" r="18" fill="rgba(168, 85, 247, 0.3)" stroke="rgba(168, 85, 247, 0.6)" stroke-width="2"/>
                        <path d="M 10 50 Q 10 40, 20 40 L 40 40 Q 50 40, 50 50 L 50 60 L 10 60 Z" fill="rgba(168, 85, 247, 0.3)" stroke="rgba(168, 85, 247, 0.6)" stroke-width="2"/>
                    </g>
                    
                    <!-- Checkmark -->
                    <g transform="translate(250, 180)">
                        <circle cx="30" cy="30" r="25" fill="rgba(34, 197, 94, 0.2)" stroke="rgba(34, 197, 94, 0.6)" stroke-width="2"/>
                        <path d="M 20 30 L 28 38 L 40 22" stroke="#22c55e" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                </svg>
            </div>

            <!-- Right Panel: Login Form -->
            <div class="auth-right">
                <h2 class="mb-4" style="font-size: 28px; font-weight: 700; color: #f9fafb;">Sign in</h2>

                @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               placeholder="Enter your email"
                               required 
                               autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-primary mb-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Sign In
                    </button>
                </form>

                <div class="signup-link">
                    New to {{ config('app.name') }}?
                    <a href="{{ route('signup') }}">Create a merchant account</a>
                </div>

                <!-- Test Credentials Accordion -->
                <div class="test-credentials-accordion">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <div class="accordion-title">
                            <i class="bi bi-info-circle"></i>
                            <span>Test Credentials</span>
                        </div>
                        <i class="bi bi-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="credential-item">
                                <div class="credential-label">Admin Account</div>
                                <div class="credential-value">admin@badlicash.test / Password123!</div>
                            </div>
                            <div class="credential-item">
                                <div class="credential-label">Merchant Account</div>
                                <div class="credential-value">merchant1@badlicash.test / Password123!</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');
            
            // Close all accordions
            document.querySelectorAll('.accordion-header').forEach(h => {
                h.classList.remove('active');
                h.nextElementSibling.classList.remove('active');
            });
            
            // Toggle current accordion
            if (!isActive) {
                header.classList.add('active');
                content.classList.add('active');
            }
        }
    </script>
</body>
</html>
