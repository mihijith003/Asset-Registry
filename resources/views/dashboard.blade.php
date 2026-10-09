<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-indigo-800 rounded-2xl p-6 sm:p-8 text-white shadow-md">
            <h1 class="text-2xl font-bold">Welcome back, {{ Auth::user()->name }}!</h1>
            <p class="mt-1 text-indigo-100 text-sm">
                Manage your organization's physical and digital assets, track modifications, manage sales, and log disposals.
            </p>
        </div>

        <!-- Quick Access Navigation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Asset Registry Card -->
            <a href="{{ route('assets.index') }}" class="group bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">Asset Registry</h3>
                        <p class="text-xs text-slate-500">Register new assets</p>
                    </div>
                </div>
            </a>

            <!-- Asset Modification Card -->
            <a href="{{ route('asset-modifications.index') }}" class="group bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">Asset Modification</h3>
                        <p class="text-xs text-slate-500">Record upgrades & moves</p>
                    </div>
                </div>
            </a>

            <!-- Asset Sale Card -->
            @can('sell assets')
            <a href="{{ route('asset-sales.index') }}" class="group bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">Asset Sale</h3>
                        <p class="text-xs text-slate-500">Log asset sales</p>
                    </div>
                </div>
            </a>
            <div class="card">
                <h4>Asset Sale</h4>
                <p>Log asset sales</p>
            </div>
            @endcan




            <!-- Asset Disposal Card -->
            @can('dispose assets')
            <a href="{{ route('asset-disposals.index') }}" class="group bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-rose-50 text-rose-600 rounded-xl group-hover:bg-rose-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">Asset Disposal</h3>
                        <p class="text-xs text-slate-500">Track scrapped assets</p>
                    </div>
                </div>
            </a>
            
            <div class="card">
                <h4>Asset Disposal</h4>
                <p>Track scrapped assets</p>
            </div>
            @endcan



        </div>
    </div>
</x-app-layout>
