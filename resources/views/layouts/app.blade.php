<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel Asset Management') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-slate-800 bg-slate-100" x-data="{ sidebarOpen: false }">
        <div class="min-h-full flex">
            <!-- Desktop Sidebar -->
            <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30">
                @include('layouts.sidebar')
            </div>

            <!-- Mobile Off-Canvas Sidebar Backdrop & Drawer -->
            <div x-cloak x-show="sidebarOpen" class="relative z-50 md:hidden" role="dialog" aria-modal="true">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/80" 
                     @click="sidebarOpen = false"></div>

                <div class="fixed inset-0 flex">
                    <div x-show="sidebarOpen"
                         x-transition:enter="transition ease-in-out duration-300 transform"
                         x-transition:enter-start="-translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in-out duration-300 transform"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="-translate-x-full"
                         class="relative mr-16 flex w-full max-w-xs flex-1">
                        
                        <!-- Close button -->
                        <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5 text-white">
                                <span class="sr-only">Close sidebar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        @include('layouts.sidebar')
                    </div>
                </div>
            </div>

            <!-- Main Content Container (shifted for desktop sidebar) -->
            <div class="md:pl-64 flex flex-col flex-1 w-full min-h-screen">
                <!-- Top Navigation Bar -->
                <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <!-- Mobile Hamburger Button -->
                        <button type="button" 
                                @click="sidebarOpen = true" 
                                class="text-slate-500 hover:text-slate-700 md:hidden p-2 rounded-md hover:bg-slate-100 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="font-semibold text-lg text-slate-800">
                            @if (isset($header))
                                {{ $header }}
                            @else
                                @yield('header', 'Asset Management')
                            @endif
                        </div>
                    </div>

                    <!-- Right Header Items -->
                    <div class="flex items-center gap-4">
                        @auth
                            <div class="hidden sm:flex flex-col text-right">
                                <span class="text-xs text-slate-500">Logged in as</span>
                                <span class="text-sm font-semibold text-slate-700">{{ Auth::user()->name }}</span>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors" title="Profile">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </a>
                        @endauth
                    </div>
                </header>

                <!-- Page Body -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <!-- Flash Messages -->
                    @if (session('success'))
                        <div class="mb-6 flex items-center gap-3 p-4 text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm font-medium">{{ session('success') }}</div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 flex items-center gap-3 p-4 text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-sm">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm font-medium">{{ session('error') }}</div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 p-4 text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-sm">
                            <div class="flex items-center gap-2 font-semibold text-sm mb-2 text-rose-900">
                                <svg class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Please fix the following errors:
                            </div>
                            <ul class="list-disc list-inside text-xs space-y-1 text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Content Rendering: Supports both $slot and @yield('content') -->
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
