<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- For page scripts that format money (config/app.php currency_symbol; see app/helpers.php money()) --}}
    <meta name="currency-symbol" content="{{ currency_symbol() }}">

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
                <a href="{{ route('master-files.index') }}" class="nav-item {{ request()->routeIs('master-files.*') ? 'active' : '' }}">
                    Master Files
                </a>
                <a href="{{ route('depreciation.index') }}" class="nav-item {{ request()->routeIs('depreciation.*') ? 'active' : '' }}">
                    Depreciation
                </a>
                <a href="{{ route('asset-modifications.index') }}" class="nav-item {{ request()->routeIs('asset-modifications.*') ? 'active' : '' }}">
                    Asset Modification
                </a>
                <a href="{{ route('asset-revaluations.index') }}" class="nav-item {{ request()->routeIs('asset-revaluations.*') ? 'active' : '' }}">
                    Revaluation
                </a>
                <a href="{{ route('asset-sales.index') }}" class="nav-item {{ request()->routeIs('asset-sales.*') ? 'active' : '' }}">
                    Asset Sale
                </a>
                <a href="{{ route('asset-disposals.index') }}" class="nav-item {{ request()->routeIs('asset-disposals.*') ? 'active' : '' }}">
                    Asset Disposal
                </a>
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    Reporting
                </a>
            </nav>
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

                @auth
                    <div class="header-user">
                        <span>{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </div>
                @endauth
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

                @if (isset($errors) && $errors->any())
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
