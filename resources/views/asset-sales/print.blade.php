{{-- NEW: printable sale voucher for a single asset sale (route: asset-sales.print) --}}
@extends('layouts.print')

@section('title', 'Asset Sale Voucher - ' . $sale->reference)
@section('back', route('asset-sales.index'))
@section('doc-title', 'Asset Sale Voucher')
@section('doc-ref', 'Sale ID: ' . $sale->reference)

@section('content')
    @php
        $asset = $sale->asset;
        $recordedCost = $asset ? (float) $asset->cost : null;
        // CHANGED: the gain / loss is measured against the net book value on the sale date (cost less depreciation)
        // $difference = $recordedCost !== null ? (float) $sale->price - $recordedCost : null;
        $saleDate = $sale->sale_date ? \Illuminate\Support\Carbon::parse($sale->sale_date) : \Illuminate\Support\Carbon::today();
        $accumulated = $asset ? $asset->accumulatedDepreciation($saleDate) : null;
        $bookValue = $asset ? round($recordedCost - $accumulated, 2) : null;
        $difference = $bookValue !== null ? round((float) $sale->price - $bookValue, 2) : null;
    @endphp

    <h2>Sale details</h2>
    <table class="details">
        <tr><th>Sale ID</th><td class="mono">{{ $sale->reference }}</td></tr>
        <tr><th>Sale date</th><td>{{ $sale->sale_date ?? '-' }}</td></tr>
        <tr><th>Authorized by</th><td>{{ $sale->authorized_by ?? '-' }}</td></tr>
        {{-- Buyer details are not captured by the sale form yet; they show only if present in the record --}}
        @if ($sale->buyer_name)
            <tr><th>Buyer</th><td>{{ $sale->buyer_name }}{{ $sale->buyer_contact ? ' (' . $sale->buyer_contact . ')' : '' }}</td></tr>
        @endif
        @if ($sale->comments)
            <tr><th>Comments</th><td style="white-space: pre-line;">{{ $sale->comments }}</td></tr>
        @endif
    </table>

    <h2>Asset sold</h2>
    <table class="details">
        @if ($asset)
            <tr><th>Asset code</th><td class="mono">{{ $asset->asset_code }}</td></tr>
            <tr><th>Type</th><td>{{ $asset->assetType->type_name ?? '-' }}{{ $asset->subType ? ' / ' . $asset->subType->sub_type_name : '' }}</td></tr>
            <tr><th>Brand &amp; model</th><td>{{ trim(($asset->brand_name ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}</td></tr>
            <tr><th>Financial year</th><td>{{ $asset->financial_year ?? '-' }}</td></tr>
            <tr><th>Location</th><td>{{ $asset->location_label ?? '-' }}</td></tr>
        @else
            <tr><th>Asset</th><td>Asset #{{ $sale->asset_id }} (no longer in the registry)</td></tr>
        @endif
    </table>

    <h2>Amounts (LKR)</h2>
    <table class="details">
        @if ($recordedCost !== null)
            <tr><th>Recorded cost</th><td class="num">{{ number_format($recordedCost, 2) }}</td></tr>
            <tr><th>Accumulated depreciation (to sale date)</th><td class="num">{{ number_format($accumulated, 2) }}</td></tr>
            <tr><th>Net book value</th><td class="num">{{ number_format($bookValue, 2) }}</td></tr>
        @endif
        <tr class="total-row"><th>Sale price</th><td class="num">{{ number_format($sale->price, 2) }}</td></tr>
        @if ($difference !== null)
            <tr>
                <th>{{ $difference >= 0 ? 'Gain on sale' : 'Loss on sale' }}</th>
                <td class="num">{{ number_format(abs($difference), 2) }}</td>
            </tr>
        @endif
    </table>
    <p class="note">The asset has been removed from the active asset registry as a result of this sale.</p>

    <div class="signatures">
        <div class="signature">
            <div class="line"></div>
            <div class="name">{{ $sale->authorized_by }}</div>
            <div class="sub">Authorized by</div>
        </div>
        <div class="signature">
            <div class="line"></div>
            <div class="name"></div>
            <div class="sub">Buyer (name &amp; signature)</div>
        </div>
    </div>
@endsection
