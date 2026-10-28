{{-- Printable Asset Register: current assets (registry filters applied) - route: assets.print --}}
@extends('layouts.print')

@section('title', 'Asset Register')
@section('back', route('assets.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Asset Register')
@section('doc-ref', 'As at ' . now()->format('Y-m-d'))

@section('content')
    @include('partials.print-header', ['title' => 'Asset Register', 'filters' => ['As at' => now()->format('Y-m-d')]])

    @php
        // Same figures as the registry screen, worked out once per asset (relations are eager loaded).
        $rows = $assets->map(fn ($asset) => (object) [
            'asset' => $asset,
            'allocation' => $asset->allocations->first(),
            'accumulated' => $asset->accumulatedDepreciation(),
            'book_value' => $asset->netBookValue(),
        ]);
    @endphp

    <table class="data">
        <thead>
            <tr>
                <th>Asset Code</th>
                <th>Type / Sub Type</th>
                <th>Brand &amp; Model</th>
                <th>Allocated To</th>
                <th class="num">Cost</th>
                <th class="num">Accum. Depreciation</th>
                <th class="num">Net Book Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="mono">{{ $row->asset->asset_code }}</td>
                    <td>{{ $row->asset->assetType->type_name ?? '-' }} / {{ trim($row->asset->subType->sub_type_name ?? '-') }}</td>
                    <td>{{ trim(($row->asset->brand?->name ?? $row->asset->brand_name ?? '') . ' ' . ($row->asset->model ?? '')) ?: '-' }}</td>
                    <td>{{ $row->allocation?->label ?? ($row->asset->location ?: 'Unallocated') }}</td>
                    <td class="num">{{ money($row->asset->cost) }}</td>
                    <td class="num">{{ money($row->accumulated) }}</td>
                    <td class="num">{{ money($row->book_value) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No assets match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total ({{ $rows->count() }} {{ \Illuminate\Support\Str::plural('asset', $rows->count()) }})</td>
                <td class="num">{{ money($rows->sum(fn ($r) => (float) $r->asset->cost)) }}</td>
                <td class="num">{{ money($rows->sum('accumulated')) }}</td>
                <td class="num">{{ money($rows->sum('book_value')) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
