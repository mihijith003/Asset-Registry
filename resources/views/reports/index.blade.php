<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Reports</h3>
                <p>Every report can be viewed on screen and opened as a printable document.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="card-grid">
                @foreach ($reports as $report)
                    @if (Route::has($report['route']))
                        <a href="{{ route($report['route']) }}" class="stat-card">
                            <h3>{{ $report['title'] }}</h3>
                            <p style="color: var(--text-muted); font-size: 0.875rem;">{{ $report['description'] }}</p>
                            <p style="margin-top: 0.75rem; font-size: 0.875rem; font-weight: 600; color: var(--primary);">Open report &rarr;</p>
                        </a>
                    @else
                        {{-- report not built yet: shown without a link --}}
                        <div class="stat-card" style="opacity: 0.6; cursor: default;">
                            <h3>{{ $report['title'] }}</h3>
                            <p style="color: var(--text-muted); font-size: 0.875rem;">{{ $report['description'] }}</p>
                            <p style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--text-muted);">Coming soon</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
