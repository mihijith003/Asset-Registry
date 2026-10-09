<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>{{ config('app.name', 'Laravel') }}</h1>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Sign in to your account</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control">
                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control">
                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                    <input id="remember_me" type="checkbox" name="remember" style="cursor: pointer;">
                    <label for="remember_me" style="cursor: pointer; font-size: 0.875rem;">Remember me</label>
                </div>

                <div class="form-actions" style="margin-top: 1.5rem; padding-top: 0; border: none; justify-content: space-between; align-items: center;">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 0.875rem;">Forgot your password?</a>
                    @else
                        <span></span>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        Log in
                    </button>
                </div>
            </form>
            
            @if (Route::has('register'))
            <div class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}">Register here</a>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
