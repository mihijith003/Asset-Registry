{{-- NEW: printable disposal record for a single asset disposal (route: asset-disposals.print) --}}
@extends('layouts.print')

@section('title', 'Asset Disposal Record - ' . $disposal->reference)
@section('back', route('asset-disposals.index'))
@section('doc-title', 'Asset Disposal Record')
@section('doc-ref', 'Disposal ID: ' . $disposal->reference)

@section('content')
    @php
        $asset = $disposal->asset;
        // NEW: depreciation up to the disposal date
        $disposalDate = $disposal->disposal_date ? \Illuminate\Support\Carbon::parse($disposal->disposal_date) : \Illuminate\Support\Carbon::today();
        $accumulated = $asset ? $asset->accumulatedDepreciation($disposalDate) : null;
    @endphp

    <h2>Disposal details</h2>
    <table class="details">
        <tr><th>Disposal ID</th><td class="mono">{{ $disposal->reference }}</td></tr>
        <tr><th>Disposal date</th><td>{{ $disposal->disposal_date ?? '-' }}</td></tr>
        <tr><th>Method</th><td>{{ $disposal->method }}</td></tr>
        <tr><th>Authorized by</th><td>{{ $disposal->authorized_by ?? '-' }}</td></tr>
        @if ($disposal->comments)
            <tr><th>Comments</th><td style="white-space: pre-line;">{{ $disposal->comments }}</td></tr>
        @endif
    </table>

    <h2>Asset disposed</h2>
    <table class="details">
        @if ($asset)
            <tr><th>Asset code</th><td class="mono">{{ $asset->asset_code }}</td></tr>
            <tr><th>Type</th><td>{{ $asset->assetType->type_name ?? '-' }}{{ $asset->subType ? ' / ' . $asset->subType->sub_type_name : '' }}</td></tr>
            <tr><th>Brand &amp; model</th><td>{{ trim(($asset->brand_name ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}</td></tr>
            <tr><th>Financial year</th><td>{{ $asset->financial_year ?? '-' }}</td></tr>
            <tr><th>Last location</th><td>{{ $asset->location_label ?? '-' }}</td></tr>
            <tr><th>Recorded cost (LKR)</th><td class="num">{{ number_format($asset->cost, 2) }}</td></tr>
            <tr><th>Accumulated depreciation (to disposal date)</th><td class="num">{{ number_format($accumulated, 2) }}</td></tr>
            <tr class="total-row"><th>Net book value written off (LKR)</th><td class="num">{{ number_format($asset->cost - $accumulated, 2) }}</td></tr>
        @else
            <tr><th>Asset</th><td>Asset #{{ $disposal->asset_id }} (no longer in the registry)</td></tr>
        @endif
    </table>
    <p class="note">The asset has been removed from the active asset registry as a result of this disposal.</p>

    <div class="signatures">
        <div class="signature">
            <div class="line"></div>
            <div class="name">{{ $disposal->authorized_by }}</div>
            <div class="sub">Authorized by</div>
        </div>
        <div class="signature">
            <div class="line"></div>
            <div class="name"></div>
            <div class="sub">Received / disposed by (name &amp; signature)</div>
        </div>
    </div>
@endsection
