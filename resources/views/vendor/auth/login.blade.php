<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vendor Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-1">Vendor Login</h4>
                    <p class="text-muted mb-4">Login to track your links, orders and balances.</p>
                    <form method="POST" action="{{ route('vendor.login.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Vendor Login ID</label>
                            <input type="text" name="vendor_login_id" class="form-control @error('vendor_login_id') is-invalid @enderror" value="{{ old('vendor_login_id') }}" required>
                            @error('vendor_login_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberVendor">
                            <label class="form-check-label" for="rememberVendor">Remember me</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Sign in</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

