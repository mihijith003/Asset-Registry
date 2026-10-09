<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--primary);">Welcome back, {{ Auth::user()->name }}!</h1>
            <p style="color: var(--text-muted);">Manage your organization's physical and digital assets, track modifications, manage sales, and log disposals.</p>
        </div>
    </div>

    <div class="card-grid">
        <a href="{{ route('assets.index') }}" class="stat-card">
            <h3>Asset Registry</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Register new assets</p>
        </a>

        <a href="{{ route('asset-modifications.index') }}" class="stat-card">
            <h3>Asset Modification</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Record upgrades & moves</p>
        </a>

        <a href="{{ route('asset-sales.index') }}" class="stat-card">
            <h3>Asset Sale</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Log asset sales</p>
        </a>

        <a href="{{ route('asset-disposals.index') }}" class="stat-card">
            <h3>Asset Disposal</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Track scrapped assets</p>
        </a>
    </div>
</x-app-layout>
