{{--
    Dashboard (DashboardController@index): the whole estate at a glance. Every figure arrives as data from the
    controller - no queries here. Charts: resources/js/dashboard.js (Chart.js bundled by Vite, no CDN) reads the
    JSON in #dashboard-data.
--}}
@php
    $money = fn ($v) => money($v);
    // A tile is a link only when the user may open the target page.
    $tile = fn (?string $href) => $href ? ['tag' => 'a', 'attr' => 'href="' . e($href) . '"'] : ['tag' => 'div', 'attr' => ''];
@endphp
<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <style>
        .dash-tiles { margin-bottom: 1.5rem; }
        .dash-tiles .stat-card .value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; font-variant-numeric: tabular-nums; }
        .dash-tiles .stat-card .sub { color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem; }
        .dash-tiles div.stat-card:hover { transform: none; box-shadow: none; border-color: var(--border-color); }
        .dash-tiles .stat-card.warn { border-color: #fcd34d; background: #fffbeb; }
        .dash-charts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .dash-charts .card { margin-bottom: 0; }
        .dash-chart { position: relative; height: 300px; }
        .dash-empty { color: var(--text-muted); font-style: italic; padding: 2rem 0; text-align: center; }
        @media (max-width: 900px) { .dash-charts { grid-template-columns: 1fr; } }
    </style>

    {{-- Stat tiles --}}
    <div class="card-grid dash-tiles">
        @php
            $t = $tile($links->registry);
        @endphp
        <{{ $t['tag'] }} {!! $t['attr'] !!} class="stat-card">
            <h3>Total Assets</h3>
            <div class="value">{{ number_format($tiles->count) }}</div>
            <div class="sub">Currently held, as on the registry</div>
        </{{ $t['tag'] }}>

        <{{ $t['tag'] }} {!! $t['attr'] !!} class="stat-card">
            <h3>Total Cost</h3>
            <div class="value">{{ $money($tiles->cost) }}</div>
            <div class="sub">Purchase cost of the assets held</div>
        </{{ $t['tag'] }}>

        <{{ $t['tag'] }} {!! $t['attr'] !!} class="stat-card">
            <h3>Total Net Book Value</h3>
            <div class="value">{{ $money($tiles->nbv) }}</div>
            <div class="sub">Today, after depreciation</div>
        </{{ $t['tag'] }}>

        @php
            $t = $tile($links->ageing);
        @endphp
        <{{ $t['tag'] }} {!! $t['attr'] !!} class="stat-card">
            <h3>Fully Depreciated</h3>
            <div class="value">{{ number_format($tiles->writtenDown) }}</div>
            <div class="sub">{{ number_format($tiles->writtenDownShare, 1) }}% of assets held &middot; written down to residual value</div>
        </{{ $t['tag'] }}>

        @if ($tiles->pendingDisposals)
            <a href="{{ $links->disposals }}" class="stat-card warn">
                <h3>Pending Disposals</h3>
                <div class="value">{{ number_format($tiles->pendingDisposals) }}</div>
                <div class="sub">Awaiting approval</div>
            </a>
        @endif
    </div>

    @if ($tiles->count === 0)
        <div class="card"><div class="card-body"><p class="dash-empty">No assets registered yet.</p></div></div>
    @else
        <div class="dash-charts">
            <div class="card">
                <div class="card-header"><div><h3>Assets by Sub Type</h3><p>Number of assets held{{ in_array('Other', $charts['subTypes']['labels'], true) ? '; beyond the top 10 grouped as Other' : '' }}</p></div></div>
                <div class="card-body"><div class="dash-chart" style="height: {{ max(220, 34 * count($charts['subTypes']['labels']) + 60) }}px;"><canvas data-chart="subTypes" role="img" aria-label="Assets by sub type"></canvas></div></div>
            </div>

            <div class="card">
                <div class="card-header"><div><h3>Age Distribution</h3><p>Assets held today, by age from purchase date (same bands as the Ageing Report)</p></div></div>
                <div class="card-body"><div class="dash-chart"><canvas data-chart="ages" role="img" aria-label="Assets by age band"></canvas></div></div>
            </div>

            <div class="card">
                <div class="card-header"><div><h3>Value by Asset Type</h3><p>Total cost and net book value today; the gap is the depreciation to date</p></div></div>
                <div class="card-body"><div class="dash-chart"><canvas data-chart="values" role="img" aria-label="Cost and net book value by asset type"></canvas></div></div>
            </div>

            <div class="card">
                <div class="card-header"><div><h3>Disposals &amp; Sales</h3><p>{{ $charts['outcomes']['period'] }}; approved disposals only</p></div></div>
                <div class="card-body">
                    @if ($charts['outcomes']['total'] === 0)
                        <p class="dash-empty">No assets sold or disposed of this financial year.</p>
                    @else
                        <div class="dash-chart"><canvas data-chart="outcomes" role="img" aria-label="Disposals and sales by outcome"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <script type="application/json" id="dashboard-data">@json($charts)</script>
        @vite('resources/js/dashboard.js')
    @endif

    {{-- Recent activity (activity_logs; user is null when the account was deleted - ON DELETE SET NULL) --}}
    <div class="card">
        <div class="card-header"><div><h3>Recent Activity</h3><p>The 10 most recent entries in the activity log</p></div></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date &amp; Time</th><th>User</th><th>Action</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse ($activity as $log)
                        <tr>
                            <td style="white-space: nowrap;">{{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $log->user->name ?? 'system' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color: var(--text-muted);">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
