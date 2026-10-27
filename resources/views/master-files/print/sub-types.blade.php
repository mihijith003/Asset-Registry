{{-- Master Files > Asset Sub Types print summary, grouped by parent type (route: master-files.print?tab=sub-types) --}}
@extends('layouts.print')

@section('title', 'Asset Sub Types')
@section('back', route('master-files.index', array_merge(request()->only('asset_type_id'), ['tab' => 'sub-types'])))
@section('doc-title', 'Asset Sub Types')
@section('doc-ref', 'Master Files')

@section('content')
    @include('partials.print-header', ['title' => 'Asset Sub Types', 'typeFilters' => ['Type']])

    @php
        $subTypeCount = $groups->sum(fn ($type) => $type->subTypes->count());
        $assetCount = $groups->sum(fn ($type) => $type->subTypes->sum('assets_count'));
    @endphp

    <table class="data">
        <thead>
            <tr>
                <th>Sub Type Name</th>
                <th>Code</th>
                <th class="num">Assets</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $type)
                <tr>
                    <th colspan="3" style="background: var(--soft); text-align: left;">
                        {{ $type->type_name }} <span class="mono">({{ $type->type_code }})</span>
                    </th>
                </tr>
                @forelse ($type->subTypes as $subType)
                    <tr>
                        <td style="padding-left: 1.25rem;">{{ trim($subType->sub_type_name) }}</td>
                        <td class="mono">{{ $subType->sub_type_code }}</td>
                        <td class="num">{{ $subType->assets_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="padding-left: 1.25rem; color: var(--muted);">No sub types.</td></tr>
                @endforelse
                <tr>
                    <td colspan="2" style="font-weight: 600;">Subtotal: {{ $type->type_name }}</td>
                    <td class="num" style="font-weight: 600;">{{ $type->subTypes->sum('assets_count') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No asset sub types found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Grand total ({{ $subTypeCount }} {{ \Illuminate\Support\Str::plural('sub type', $subTypeCount) }})</td>
                <td class="num">{{ $assetCount }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="note">Assets: registered assets that have not been deleted, the same count as the Master Files screen.</p>
@endsection
