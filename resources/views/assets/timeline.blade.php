@extends('layouts.app')

@section('header', 'Asset Timeline — ' . $asset->asset_code)

@section('content')
<style>
    .timeline-header-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .timeline-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .timeline-summary-item {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 0.75rem 1rem;
    }

    .timeline-summary-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .timeline-summary-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .chain-flow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .chain-node {
        display: inline-flex;
        flex-direction: column;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        border: 1px solid var(--border-color);
        background: #fff;
        font-size: 0.8125rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .chain-node.is-viewed {
        border-color: var(--primary);
        background: #eef2ff;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
    }

    .chain-node-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .chain-node-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .chain-arrow {
        color: var(--text-muted);
        font-weight: 700;
        font-size: 1rem;
        padding: 0 0.15rem;
    }

    /* Vertical Timeline */
    .v-timeline {
        position: relative;
        padding-left: 2rem;
        margin: 0.5rem 0;
    }

    .v-timeline::before {
        content: '';
        position: absolute;
        top: 0.5rem;
        bottom: 0.5rem;
        left: 0.65rem;
        width: 2px;
        background: var(--border-color);
    }

    .v-timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .v-timeline-item:last-child {
        margin-bottom: 0;
    }

    .v-timeline-dot {
        position: absolute;
        left: -1.72rem;
        top: 1rem;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid var(--primary);
        z-index: 1;
    }

    .v-timeline-item.event-registered .v-timeline-dot {
        border-color: var(--success);
    }

    .v-timeline-item.event-sold .v-timeline-dot,
    .v-timeline-item.event-disposed .v-timeline-dot {
        border-color: var(--danger);
    }

    .v-timeline-item.event-revalued .v-timeline-dot,
    .v-timeline-item.event-modified .v-timeline-dot {
        border-color: var(--primary);
    }

    .v-timeline-item.event-allocated .v-timeline-dot {
        border-color: var(--secondary);
    }

    .v-timeline-card {
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        background: #fff;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .v-timeline-top {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .v-timeline-badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .v-timeline-date {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .v-timeline-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 0.15rem;
    }

    .v-timeline-subtitle {
        font-size: 0.8125rem;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
    }

    .v-timeline-transition {
        background: #f8fafc;
        border-left: 3px solid var(--primary);
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.75rem;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 0 var(--radius) var(--radius) 0;
    }

    .v-timeline-figures {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 0.5rem 1rem;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
    }

    .v-timeline-figure-label {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .v-timeline-figure-val {
        font-weight: 600;
        color: var(--text-main);
    }

    .v-timeline-comments {
        margin-top: 0.65rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }

    .asset-code-link {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        color: var(--primary);
        text-decoration: none;
    }

    .asset-code-link:hover {
        text-decoration: underline;
    }

    @media print {
        .sidebar,
        .top-header,
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
            color: #000 !important;
            font-size: 12px;
        }

        .layout-wrapper,
        .main-content,
        .content-container {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            margin-bottom: 1rem !important;
            break-inside: avoid;
        }

        .v-timeline-item {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .asset-code-link {
            color: #000 !important;
            text-decoration: none !important;
        }
    }
</style>

@php
    $brandAndModel = trim(($asset->brand?->name ?? $asset->brand_name ?? '') . ' ' . ($asset->model ?? '')) ?: '-';
    $accDep = $asset->accumulatedDepreciation();
    $nbv = $asset->netBookValue();

    if ($asset->sale) {
        $lifecycleStatus = 'Sold (' . $asset->sale->reference . ')';
    } elseif ($asset->disposal) {
        $lifecycleStatus = 'Disposed (' . ucfirst($asset->disposal->approval_status ?? 'approved') . ')';
    } elseif ($asset->supersededBy) {
        $lifecycleStatus = 'Superseded by ' . $asset->supersededBy->asset_code;
    } elseif ($asset->mergedInto) {
        $lifecycleStatus = 'Combined into ' . $asset->mergedInto->asset_code;
    } elseif ($asset->trashed()) {
        $lifecycleStatus = 'Archived';
    } else {
        $lifecycleStatus = 'Active in Registry';
    }
@endphp

<div class="timeline-header-bar no-print">
    <div>
        <span style="color: var(--text-muted); font-size: 0.875rem;">
            Full lifecycle audit trail across {{ $chain->count() }} {{ \Illuminate\Support\Str::plural('asset record', $chain->count()) }}
        </span>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print Timeline</button>
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">&larr; Back to Registry</a>
    </div>
</div>

<!-- Asset Summary & Identity Chain Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3>Asset Identity &amp; Chain Summary — <span style="font-family: monospace;">{{ $asset->asset_code }}</span></h3>
            <p>
                {{ $asset->assetType?->type_name ?? '-' }}{{ $asset->subType ? ' / ' . $asset->subType->sub_type_name : '' }}
                &middot; {{ $brandAndModel }}
            </p>
        </div>
        <div>
            @if ($currentAsset->id !== $asset->id)
                <span class="badge badge-secondary">
                    Current Successor:
                    <a href="{{ route('assets.timeline', $currentAsset->id) }}" class="asset-code-link" style="margin-left: 0.25rem;">
                        {{ $currentAsset->asset_code }}
                    </a>
                </span>
            @else
                <span class="badge badge-success">{{ $lifecycleStatus }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="timeline-summary-grid">
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Viewed Asset Code</div>
                <div class="timeline-summary-value">
                    <a href="{{ route('assets.timeline', $asset->id) }}" class="asset-code-link">{{ $asset->asset_code }}</a>
                </div>
            </div>
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Recorded Cost</div>
                <div class="timeline-summary-value">{{ money((float) $asset->cost) }}</div>
            </div>
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Accumulated Depreciation</div>
                <div class="timeline-summary-value">{{ money($accDep) }}</div>
            </div>
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Net Book Value</div>
                <div class="timeline-summary-value">{{ money($nbv) }}</div>
            </div>
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Location / Allocation</div>
                <div class="timeline-summary-value">{{ $asset->location_label ?? '-' }}</div>
            </div>
            <div class="timeline-summary-item">
                <div class="timeline-summary-label">Status</div>
                <div class="timeline-summary-value">{{ $lifecycleStatus }}</div>
            </div>
        </div>

        <div class="chain-flow">
            <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-right: 0.25rem;">
                Identity Chain (Oldest &rarr; Newest):
            </span>
            @foreach ($chain as $idx => $node)
                @php
                    if ($node->sale) {
                        $nodeState = 'Sold';
                    } elseif ($node->disposal) {
                        $nodeState = 'Disposed';
                    } elseif ($node->supersededBy) {
                        $nodeState = 'Revalued → ' . $node->supersededBy->asset_code;
                    } elseif ($node->mergedInto) {
                        $nodeState = 'Merged → ' . $node->mergedInto->asset_code;
                    } elseif ($node->originAsset) {
                        $nodeState = 'From ' . $node->originAsset->asset_code;
                    } else {
                        $nodeState = 'Active';
                    }
                @endphp
                @if ($idx > 0)
                    <span class="chain-arrow">&rarr;</span>
                @endif
                <div class="chain-node {{ $node->id === $asset->id ? 'is-viewed' : '' }}">
                    <div>
                        <a href="{{ route('assets.timeline', $node->id) }}" class="chain-node-code asset-code-link">
                            {{ $node->asset_code }}
                        </a>
                    </div>
                    <div class="chain-node-meta">
                        {{ money((float) $node->cost) }} &middot; {{ $nodeState }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Vertical Timeline Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3>Lifecycle Events (Newest First)</h3>
            <p>Complete history of registrations, allocations, modifications, revaluations, sales, and disposals across this asset chain.</p>
        </div>
        <span class="badge badge-secondary">{{ count($events) }} {{ \Illuminate\Support\Str::plural('Event', count($events)) }}</span>
    </div>
    <div class="card-body">
        @if (empty($events))
            <p style="color: var(--text-muted); text-align: center; padding: 1.5rem 0;">No lifecycle events recorded for this asset.</p>
        @else
            <div class="v-timeline">
                @foreach ($events as $event)
                    <div class="v-timeline-item event-{{ $event->type }}">
                        <div class="v-timeline-dot"></div>
                        <div class="v-timeline-card">
                            <div class="v-timeline-top">
                                <div class="v-timeline-badges">
                                    <span class="badge {{ $event->badge_class }}">{{ $event->badge_label }}</span>
                                    @if ($event->asset)
                                        <span class="badge badge-secondary" style="font-family: monospace;">
                                            Asset:
                                            <a href="{{ route('assets.timeline', $event->asset->id) }}" class="asset-code-link" style="margin-left: 0.25rem;">
                                                {{ $event->asset->asset_code }}
                                            </a>
                                        </span>
                                    @endif
                                </div>
                                <div class="v-timeline-date">{{ $event->date }}</div>
                            </div>

                            <div class="v-timeline-title">{{ $event->title }}</div>
                            @if (!empty($event->subtitle))
                                <div class="v-timeline-subtitle">{{ $event->subtitle }}</div>
                            @endif

                            {{-- Explicit Identity Transitions --}}
                            @if (!empty($event->transitions))
                                @foreach ($event->transitions as $tr)
                                    <div class="v-timeline-transition">
                                        @if ($tr['kind'] === 'revalued')
                                            Revalued:
                                            <a href="{{ route('assets.timeline', $tr['from']->id) }}" class="asset-code-link">{{ $tr['from']->asset_code }}</a>
                                            &rarr;
                                            <a href="{{ route('assets.timeline', $tr['to']->id) }}" class="asset-code-link">{{ $tr['to']->asset_code }}</a>
                                        @elseif ($tr['kind'] === 'combined')
                                            @if (!empty($tr['parent']))
                                                <a href="{{ route('assets.timeline', $tr['parent']->id) }}" class="asset-code-link">{{ $tr['parent']->asset_code }}</a>:
                                            @endif
                                            @if (!empty($tr['component']))
                                                Combined with
                                                <a href="{{ route('assets.timeline', $tr['component']->id) }}" class="asset-code-link">{{ $tr['component']->asset_code }}</a>
                                            @elseif (!empty($tr['component_text']))
                                                Combined with {{ $tr['component_text'] }}
                                            @else
                                                Modified
                                            @endif
                                            @if (!empty($tr['result']))
                                                &rarr;
                                                <a href="{{ route('assets.timeline', $tr['result']->id) }}" class="asset-code-link">{{ $tr['result']->asset_code }}</a>
                                            @endif
                                        @elseif ($tr['kind'] === 'removed')
                                            Removed from
                                            <a href="{{ route('assets.timeline', $tr['from']->id) }}" class="asset-code-link">{{ $tr['from']->asset_code }}</a>
                                            &rarr; registered as
                                            <a href="{{ route('assets.timeline', $tr['to']->id) }}" class="asset-code-link">{{ $tr['to']->asset_code }}</a>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            {{-- Key Figures --}}
                            @if (!empty($event->figures))
                                <div class="v-timeline-figures">
                                    @foreach ($event->figures as $label => $val)
                                        <div>
                                            <div class="v-timeline-figure-label">{{ $label }}</div>
                                            <div class="v-timeline-figure-val">
                                                @if (is_array($val) && isset($val['asset']))
                                                    <a href="{{ route('assets.timeline', $val['asset']->id) }}" class="asset-code-link">{{ $val['asset']->asset_code }}</a>{{ $val['suffix'] ?? '' }}
                                                @else
                                                    {{ $val }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($event->comments))
                                <div class="v-timeline-comments">
                                    <strong>Comments:</strong> {{ $event->comments }}
                                </div>
                            @endif

                            @if (!empty($event->document_url))
                                <div class="no-print" style="margin-top: 0.75rem;">
                                    <a href="{{ $event->document_url }}" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                        Print Official Record
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

