<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login – {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --li-primary: #E10600;
            --li-primary-soft: #fef2f2;
            --li-accent-dark: #1A1D24;
            --li-border-dark: #262B36;
            --li-text-main: #111827;
            --li-text-muted: #6b7280;
        }

        body {
            background: linear-gradient(160deg, #1A1D24 0%, #20242D 50%, #262B36 100%);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--li-text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .li-shell {
            width: 100%;
            max-width: 440px;
        }

        .li-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: -10px 12px 28px rgba(225, 6, 0, 0.12), 0 0 0 1px rgba(26, 29, 36, 0.06);
        }

        .li-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .li-header img {
            height: 30px;
            width: auto;
        }

        .li-app-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--li-text-main);
        }

        .li-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .li-subtitle {
            font-size: 13px;
            color: var(--li-text-muted);
            margin-bottom: 18px;
        }

        .li-label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--li-text-main);
        }

        .li-input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--li-border-dark);
            padding: 10px 14px;
            font-size: 14px;
            color: var(--li-text-main);
            background: #ffffff;
        }

        .li-input:focus {
            outline: none;
            border-color: var(--li-primary);
            box-shadow: 0 0 0 2px rgba(225, 6, 0, 0.2);
        }

        .li-error-box {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: 12px;
            color: #b91c1c;
            padding: 10px 12px;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .li-form-row {
            margin-bottom: 14px;
        }

        .li-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 6px 0 16px;
        }

        .li-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--li-text-muted);
        }

        .li-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid var(--li-border-dark);
        }

        .li-link-muted {
            font-size: 12px;
            color: var(--li-primary);
            text-decoration: none;
        }

        .li-link-muted:hover {
            text-decoration: underline;
        }

        .li-primary-btn {
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 6px;
            transition: all 0.18s ease;
        }

        .li-primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }

        .li-primary-btn:disabled,
        .li-primary-btn[disabled] {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
            opacity: 0.8;
        }

        .li-footer-text {
            font-size: 12px;
            color: var(--li-text-muted);
            margin-top: 14px;
            text-align: center;
        }

        .li-footer-text a {
            color: var(--li-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .li-footer-text a:hover {
            text-decoration: underline;
        }

        .li-accordion {
            margin-top: 20px;
            border-radius: 12px;
            background: #f9fafb;
            border: 1px solid var(--li-border-dark);
            overflow: hidden;
        }

        .li-accordion-header {
            padding: 12px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--li-text-main);
        }

        .li-accordion-header:hover {
            background: var(--li-primary-soft);
        }

        .li-accordion-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .li-accordion-icon {
            transition: transform 0.25s ease;
            color: var(--li-primary);
        }

        .li-accordion-header.active .li-accordion-icon {
            transform: rotate(180deg);
        }

        .li-accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.25s ease;
        }

        .li-accordion-content.active {
            max-height: 260px;
        }

        .li-accordion-body {
            padding: 0 14px 12px 14px;
            font-size: 12px;
            color: var(--li-text-muted);
        }

        .li-cred-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(31, 41, 55, 0.2);
        }

        .li-cred-item:last-child {
            border-bottom: none;
        }

        .li-cred-label {
            font-weight: 600;
            color: var(--li-text-main);
            margin-bottom: 2px;
        }

        .li-cred-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        @media (max-width: 480px) {
            .li-card {
                padding: 26px 20px;
                border-radius: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="li-shell">
        <div class="li-card">
            <div class="li-header">
                <img src="{{ asset(logo_path()) }}" alt="{{ config('app.name') }}">
                <div class="li-app-name">{{ config('app.name') }}</div>
            </div>
            <h1 class="li-title">Sign in</h1>
            <p class="li-subtitle">Enter your details to access your merchant dashboard.</p>

            @if($errors->any())
                <div class="li-error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="li-form-row">
                    <label for="login" class="li-label">Email address</label>
                    <input
                        type="text"
                        class="li-input"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="you@example.com"
                        maxlength="255"
                        required
                        autofocus
                    >
                </div>

                <div class="li-form-row">
                    <label for="password" class="li-label">Password</label>
                    <input
                        type="password"
                        class="li-input"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="li-meta-row">
                    <label class="li-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="li-link-muted">Forgot password?</a>
                </div>

                <button type="submit" class="li-primary-btn" id="login-submit" disabled>
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign in</span>
                </button>
            </form>

            <div class="li-footer-text">
                New to {{ config('app.name') }}?
                <a href="{{ route('signup') }}">Create a merchant account</a>
            </div>

            <div class="li-accordion">
                <div class="li-accordion-header" onclick="liToggleAccordion(this)">
                    <div class="li-accordion-title">
                        <i class="bi bi-info-circle"></i>
                        <span>View sandbox test credentials</span>
                    </div>
                    <i class="bi bi-chevron-down li-accordion-icon"></i>
                </div>
                <div class="li-accordion-content">
                    <div class="li-accordion-body">
                        <div class="li-cred-item">
                            <div class="li-cred-label">Admin account</div>
                            <div class="li-cred-value">admin@ipay.test / Password123!</div>
                        </div>
                        <div class="li-cred-item">
                            <div class="li-cred-label">Merchant account</div>
                            <div class="li-cred-value">merchant1@ipay.test / Password123!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const loginInput = document.getElementById('login');
            const passwordInput = document.getElementById('password');
            const submitButton = document.getElementById('login-submit');

            if (!loginInput || !passwordInput || !submitButton) {
                return;
            }

            // Client-side max lengths (match backend rules)
            const MAX_LOGIN = 255;
            const MAX_PASSWORD = 100;

            // Inline validation messages
            const loginError = document.createElement('div');
            loginError.style.color = '#dc2626';
            loginError.style.fontSize = '12px';
            loginError.style.marginTop = '4px';
            loginError.style.display = 'none';
            loginInput.parentNode.appendChild(loginError);

            const passwordError = document.createElement('div');
            passwordError.style.color = '#dc2626';
            passwordError.style.fontSize = '12px';
            passwordError.style.marginTop = '4px';
            passwordError.style.display = 'none';
            passwordInput.parentNode.appendChild(passwordError);

            function validateFields() {
                let loginVal = loginInput.value;
                let passwordVal = passwordInput.value;

                // Hard‑enforce max lengths on the client by trimming extra characters
                if (loginVal.length > MAX_LOGIN) {
                    loginVal = loginVal.substring(0, MAX_LOGIN);
                    loginInput.value = loginVal;
                }
                if (passwordVal.length > MAX_PASSWORD) {
                    passwordVal = passwordVal.substring(0, MAX_PASSWORD);
                    passwordInput.value = passwordVal;
                }

                loginVal = loginVal.trim();
                passwordVal = passwordVal.trim();

                let loginOk = loginVal.length > 0;
                let passwordOk = passwordVal.length > 0;

                if (loginVal.length === MAX_LOGIN) {
                    loginError.textContent = `Login ID/email must be at most ${MAX_LOGIN} characters.`;
                    loginError.style.display = 'block';
                } else {
                    loginError.textContent = '';
                    loginError.style.display = 'none';
                }

                if (passwordVal.length === MAX_PASSWORD) {
                    passwordError.textContent = `Password must be at most ${MAX_PASSWORD} characters.`;
                    passwordError.style.display = 'block';
                } else {
                    passwordError.textContent = '';
                    passwordError.style.display = 'none';
                }

                submitButton.disabled = !(loginOk && passwordOk);
            }

            loginInput.addEventListener('input', validateFields);
            passwordInput.addEventListener('input', validateFields);

            validateFields();
        })();

        // Simple countdown for lockout message (e.g. "Too many login attempts. Please try again in 39 seconds.")
        (function () {
            const errorBox = document.querySelector('.li-error-box');
            if (!errorBox) return;

            const originalText = errorBox.textContent || '';
            const match = originalText.match(/(\d+)\s*seconds?/i);
            if (!match) return;

            let remaining = parseInt(match[1], 10);
            if (isNaN(remaining) || remaining <= 0) return;

            function updateText() {
                errorBox.textContent = originalText.replace(/(\d+)\s*seconds?/i, remaining + ' seconds');
            }

            updateText();

            const timer = setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    errorBox.textContent = 'You can try signing in again now.';
                    return;
                }
                updateText();
            }, 1000);
        })();

        function liToggleAccordion(header) {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');

            document.querySelectorAll('.li-accordion-header').forEach(function (h) {
                h.classList.remove('active');
                if (h.nextElementSibling) {
                    h.nextElementSibling.classList.remove('active');
                }
            });

            if (!isActive) {
                header.classList.add('active');
                content.classList.add('active');
            }
        }
    </script>
</body>
</html>
