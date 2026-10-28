<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nethu Builders - ERP Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-orange: #ff6b15;
            --brand-orange-hover: #e05a0f;
            --bg-dark-panel: #0d141e;
            --input-bg: #1a2332;
            --input-border: #2c3a50;
            --text-muted: #8b9bb4;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--bg-dark-panel);
        }

        /* Typography & Colors */
        .text-orange { color: var(--brand-orange) !important; }
        .text-muted-custom { color: var(--text-muted) !important; }
        
        .fw-black { font-weight: 900; }
        .letter-spacing-wide { letter-spacing: 2px; }

        /* Left Side - Image Panel */
        .left-panel {
            /* Background Image Added Here */
            background-image: linear-gradient(to right, rgba(13, 20, 30, 0.2), rgba(13, 20, 30, 0.9)), url('{{ asset('images/login-bg.png') }}');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .logo-box {
            background-color: var(--brand-orange);
            color: white;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 1.2rem;
        }

        .brand-title {
            font-size: 1.1rem;
            margin-bottom: -2px;
        }

        .brand-subtitle {
            font-size: 0.65rem;
            letter-spacing: 2px;
        }

        .hero-title {
            font-size: 3.5rem;
            line-height: 1;
            letter-spacing: -1px;
        }

        /* Right Side - Form Panel */
        .right-panel {
            background-color: var(--bg-dark-panel);
            color: white;
        }

        .form-wrapper {
            max-width: 420px;
            width: 100%;
        }

        .portal-indicator {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .portal-indicator::before {
            content: "";
            display: inline-block;
            width: 20px;
            height: 1px;
            background-color: var(--brand-orange);
            margin-right: 10px;
        }

        /* Custom Inputs */
        .form-label {
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #a0aec0;
        }

        .custom-input-group {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }

        .custom-input-group:focus-within {
            border-color: var(--brand-orange);
        }

        .custom-input-group .input-group-text {
            background-color: transparent;
            border: none;
            color: var(--text-muted);
        }

        .custom-input-group .form-control {
            background-color: transparent;
            border: none;
            color: white;
            box-shadow: none;
            padding-left: 0;
        }

        .custom-input-group .form-control::placeholder {
            color: #4a5568;
        }

        /* Checkbox */
        .form-check-input {
            background-color: transparent;
            border-color: var(--input-border);
        }
        
        .form-check-input:checked {
            background-color: var(--brand-orange);
            border-color: var(--brand-orange);
        }

        /* Buttons */
        .btn-orange {
            background-color: var(--brand-orange);
            color: white;
            border: none;
            padding: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 6px;
        }

        .btn-orange:hover {
            background-color: var(--brand-orange-hover);
            color: white;
        }

        .btn-outline-custom {
            background-color: transparent;
            border: 1px solid var(--input-border);
            color: var(--text-muted);
            padding: 10px;
            border-radius: 6px;
        }

        .btn-outline-custom:hover {
            background-color: var(--input-bg);
            color: white;
            border-color: #4a5568;
        }

        /* Divider */
        .divider-wrapper {
            position: relative;
            text-align: center;
        }

        .divider-wrapper::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--input-border);
            z-index: 1;
        }

        .divider-text {
            position: relative;
            z-index: 2;
            background-color: var(--bg-dark-panel);
            padding: 0 15px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .error-text {
            color: #dc3545;
            font-size: 0.75rem;
            margin-top: 5px;
            display: block;
        }

    </style>
</head>
<body>

<div class="container-fluid h-100 p-0">
    <div class="row g-0 h-100">
        
        <!-- Left Column: Branding & Image -->
        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5 left-panel text-white">
            
            <!-- Top Logo Area -->
            <div class="d-flex align-items-center">
                <div class="logo-box me-3 shadow-sm">
                    <i class="bi bi-cone-striped"></i>
                </div>
                <div>
                    <div class="brand-title fw-black letter-spacing-wide">NETHU_ BUILDERS</div>
                    <div class="brand-subtitle text-orange fw-bold">CONSTRUCTIONS PVT LTD</div>
                </div>
            </div>

            <!-- Bottom Text Area -->
            <div class="mb-4">
                <h1 class="hero-title fw-black text-white mb-0">ENTERPRISE</h1>
                <h1 class="hero-title fw-black text-orange mb-0">RESOURCE</h1>
                <h1 class="hero-title fw-black text-white mb-3">PLANNING</h1>
                <p class="text-white-50" style="max-width: 450px; font-size: 0.95rem; line-height: 1.6;">
                    Unified platform for project management, procurement, workforce scheduling, and financial oversight across all construction sites.
                </p>
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 right-panel">
            <div class="form-wrapper">
                
                <!-- Form Header -->
                
                <h2 class="fw-black text-white mb-0" style="font-size: 2.5rem; letter-spacing: -1px;">SIGN IN TO</h2>
                <h2 class="fw-black text-orange mb-3" style="font-size: 2.5rem; letter-spacing: -1px;">Nethu Builders</h2>
                

                <!-- Session Status -->
                @if (session('status'))
                    <div class="alert alert-success py-2 text-sm mb-4" style="font-size: 0.85rem; background-color: rgba(25, 135, 84, 0.1); border-color: #198754; color: #75b798;">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Laravel Form -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email / ID -->
                    <div class="mb-4">
                        <label class="form-label fw-bold"> EMAIL</label>
                        <div class="input-group custom-input-group px-2 py-1">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="name@nethu.lk" required autofocus>
                        </div>
                        @error('email')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <label class="form-label fw-bold mb-0">PASSWORD</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-orange text-decoration-none" style="font-size: 0.75rem; font-weight: 600;">Forgot password?</a>
                            @endif
                        </div>
                        <div class="input-group custom-input-group px-2 py-1">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
                            <span class="input-group-text" style="cursor: pointer;"><i class="bi bi-eye"></i></span>
                        </div>
                        @error('password')
                            <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-4 form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
                        <label class="form-check-label text-muted-custom" for="rememberMe" style="font-size: 0.85rem;">
                            Keep me signed in on this device
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-orange w-100 mb-4 d-flex justify-content-center align-items-center">
                        LOGIN <i class="bi bi-chevron-right ms-2" style="font-size: 0.8rem;"></i>
                    </button>

                    <!-- OR Divider -->
                    
                </form>

            </div>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>