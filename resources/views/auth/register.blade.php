<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>{{ config('app.name', 'Laravel') }}</h1>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Create a new account</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control">
                    @error('name')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control">
                    @error('email')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control">
                    @error('password')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control">
                    @error('password_confirmation')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions" style="margin-top: 1.5rem; padding-top: 0; border: none; justify-content: space-between; align-items: center;">
                    <a href="{{ route('login') }}" style="font-size: 0.875rem;">Already registered?</a>

                    <button type="submit" class="btn btn-primary">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
