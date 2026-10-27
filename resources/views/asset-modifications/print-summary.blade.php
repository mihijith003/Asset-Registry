{{-- "Modification History" print summary (route: asset-modifications.print-summary) - same filters as the Asset Modification page --}}
@extends('layouts.print')

@section('title', 'Modification History')
@section('back', route('asset-modifications.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Modification History')
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', ['title' => 'Modification History', 'filters' => ['Period' => $period, 'Modification Type' => $typeLabel]])

    @php
        // "EL-LAP-001" for a linked asset, else the free-text description stored on the modification.
        $assetLabel = fn ($asset, $text = null) => $asset->asset_code ?? ($text ?: '-');
    @endphp

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref</th>
                <th>Type</th>
                <th>Asset Before</th>
                <th>Component Added</th>
                <th>Component Removed</th>
                <th>Resulting Asset</th>
                <th class="num">Cost Before</th>
                <th class="num">Cost After</th>
                <th class="num">Change</th>
                <th>Authorised By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php $mod = $row->mod; @endphp
                <tr>
                    <td>{{ $mod->modification_date }}</td>
                    <td class="mono">{{ $mod->reference }}{{ $mod->reverted_at ? ' (reverted)' : '' }}</td>
                    <td>
                        @if ($mod->modification_type)
                            <span class="tag {{ $mod->modification_type === 'upgrade' ? 'gain' : 'loss' }}">{{ ucfirst($mod->modification_type) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="mono">{{ $assetLabel($mod->asset) }}</td>
                    <td class="mono">{{ $assetLabel($mod->componentAsset, $mod->added_asset) }}</td>
                    <td class="mono">{{ $mod->removedAsset || $mod->removed_asset ? $assetLabel($mod->removedAsset, $mod->removed_asset) : '-' }}</td>
                    <td class="mono">{{ $assetLabel($mod->resultAsset) }}</td>
                    <td class="num">{{ $row->before !== null ? money($row->before) : '-' }}</td>
                    <td class="num">{{ $row->after !== null ? money($row->after) : '-' }}</td>
                    <td class="num">@include('partials.signed-amount', ['amount' => $row->change])</td>
                    <td>{{ $mod->authorized_by ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="11">No modifications match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9">Net value change</td>
                <td class="num">@include('partials.signed-amount', ['amount' => $totals->change])</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-totals">
        <span>Modifications: <strong>{{ $rows->count() }}</strong></span>
        <span>Upgrades: <strong>{{ $totals->upgrades }}</strong></span>
        <span>Downgrades: <strong>{{ $totals->downgrades }}</strong></span>
        @if ($totals->unknown)
            <span>Not in net change (no recorded cost before/after): <strong>{{ $totals->unknown }}</strong></span>
        @endif
    </div>
@endsection
