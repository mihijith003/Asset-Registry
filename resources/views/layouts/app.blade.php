<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Asset Management') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                Asset Manager
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('assets.index') }}" class="nav-item {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                    Asset Registry
                </a>
                <a href="{{ route('asset-modifications.index') }}" class="nav-item {{ request()->routeIs('asset-modifications.*') ? 'active' : '' }}">
                    Asset Modification
                </a>
                <a href="{{ route('asset-sales.index') }}" class="nav-item {{ request()->routeIs('asset-sales.*') ? 'active' : '' }}">
                    Asset Sale
                </a>
                <a href="{{ route('asset-disposals.index') }}" class="nav-item {{ request()->routeIs('asset-disposals.*') ? 'active' : '' }}">
                    Asset Disposal
                </a>
            </nav>

            <div class="sidebar-footer">
                @auth
                    <div>{{ Auth::user()->name }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                @endauth
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="top-header">
                <div class="page-title">
                    @if (isset($header))
                        {{ $header }}
                    @else
                        @yield('header', 'Overview')
                    @endif
                </div>
            </header>

            <main class="content-container">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (isset($slot) && !empty((string)$slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
</body>
</html>
