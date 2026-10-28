{{-- "Sales Report" print summary (route: asset-sales.print-summary) - same filters as the Asset Sale page --}}
@extends('layouts.print')

@section('title', 'Sales Report')
@section('back', route('asset-sales.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Sales Report')
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', ['title' => 'Sales Report', 'filters' => ['Period' => $period]])

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref</th>
                <th>Asset Code</th>
                <th>Type / Sub Type</th>
                <th class="num">Original Cost</th>
                <th class="num">Net Book Value at Sale Date</th>
                <th class="num">Proceeds</th>
                <th class="num">Gain / Loss</th>
                <th>Buyer</th>
                <th>Payment Method</th>
                <th>Authorised By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->sale->sale_date ?? '-' }}</td>
                    <td class="mono">{{ $row->sale->reference }}</td>
                    <td class="mono">{{ $row->asset->asset_code ?? 'Asset #' . $row->sale->asset_id }}</td>
                    <td>{{ $row->asset?->assetType->type_name ?? '-' }} / {{ trim($row->asset?->subType->sub_type_name ?? '-') }}</td>
                    <td class="num">{{ $row->asset ? money($row->asset->cost) : '-' }}</td>
                    <td class="num">{{ $row->book_value !== null ? money($row->book_value) : '-' }}</td>
                    <td class="num">{{ money($row->sale->price) }}</td>
                    <td class="num">@include('partials.signed-amount', ['amount' => $row->gain])</td>
                    <td>{{ $row->sale->buyer_name ?: '-' }}{{ $row->sale->buyer_contact ? ' (' . $row->sale->buyer_contact . ')' : '' }}</td>
                    <td>{{ $row->sale->payment_method ?: '-' }}</td>
                    <td>{{ $row->sale->authorized_by ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="11">No sales match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $rows->count() }} {{ \Illuminate\Support\Str::plural('sale', $rows->count()) }})</td>
                <td class="num">{{ money($totals->book_value) }}</td>
                <td class="num">{{ money($totals->proceeds) }}</td>
                <td class="num">@include('partials.signed-amount', ['amount' => $totals->gain])</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
@endsection
