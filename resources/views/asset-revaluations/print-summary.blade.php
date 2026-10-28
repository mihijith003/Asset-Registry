{{-- "Revaluation History" print summary (route: asset-revaluations.print-summary) - same filters as the Revaluation page --}}
@extends('layouts.print')

@section('title', 'Revaluation History')
@section('back', route('asset-revaluations.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Revaluation History')
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', ['title' => 'Revaluation History', 'filters' => ['Period' => $period]])

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Asset Code</th>
                <th>Type / Sub Type</th>
                <th class="num">Previous Value</th>
                <th class="num">Revalued Amount</th>
                <th class="num">Change</th>
                <th class="num">New Useful Life</th>
                <th>Successor Asset</th>
                <th>Reason</th>
                <th>Authorised By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php $revaluation = $row->revaluation; @endphp
                <tr>
                    <td>{{ $revaluation->revaluation_date?->format('Y-m-d') ?? '-' }}</td>
                    <td class="mono">{{ $row->asset->asset_code ?? 'Asset #' . $revaluation->asset_id }}</td>
                    <td>{{ $row->asset?->assetType->type_name ?? '-' }} / {{ trim($row->asset?->subType->sub_type_name ?? '-') }}</td>
                    <td class="num">{{ $revaluation->previous_value !== null ? money($revaluation->previous_value) : '-' }}</td>
                    <td class="num">{{ money($revaluation->revalued_amount) }}</td>
                    <td class="num">@include('partials.signed-amount', ['amount' => $row->change])</td>
                    <td class="num">{{ $revaluation->new_useful_life_months ? $revaluation->new_useful_life_months . ' months' : '-' }}</td>
                    <td class="mono">{{ $revaluation->resultAsset->asset_code ?? 'Revalued in place' }}</td>
                    <td>{{ $revaluation->reason ?: '-' }}</td>
                    <td>{{ $revaluation->authorized_by ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No revaluations match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $totals->count }} {{ \Illuminate\Support\Str::plural('revaluation', $totals->count) }}) - net change in value</td>
                <td class="num">@include('partials.signed-amount', ['amount' => $totals->change])</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
@endsection
