{{-- Master Files > Asset Types print summary (route: master-files.print?tab=asset-types) --}}
@extends('layouts.print')

@section('title', 'Asset Types')
@section('back', route('master-files.index', ['tab' => 'asset-types']))
@section('doc-title', 'Asset Types')
@section('doc-ref', 'Master Files')

@section('content')
    @include('partials.print-header', ['title' => 'Asset Types', 'typeFilters' => []])

    <table class="data">
        <thead>
            <tr>
                <th>Type Name</th>
                <th>Type Code</th>
                <th class="num">Sub Types</th>
                <th class="num">Assets</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assetTypes as $type)
                <tr>
                    <td>{{ $type->type_name }}</td>
                    <td class="mono">{{ $type->type_code }}</td>
                    <td class="num">{{ $type->sub_types_count }}</td>
                    <td class="num">{{ $type->assets_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No asset types found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total ({{ $assetTypes->count() }} {{ \Illuminate\Support\Str::plural('type', $assetTypes->count()) }})</td>
                <td class="num">{{ $assetTypes->sum('sub_types_count') }}</td>
                <td class="num">{{ $assetTypes->sum('assets_count') }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="note">Assets: registered assets that have not been deleted, the same count as the Master Files screen.</p>
@endsection
