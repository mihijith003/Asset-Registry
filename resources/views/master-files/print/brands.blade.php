{{-- Master Files > Brands print summary (route: master-files.print?tab=brands) --}}
@extends('layouts.print')

@section('title', 'Brands')
@section('back', route('master-files.index', ['tab' => 'brands']))
@section('doc-title', 'Brands')
@section('doc-ref', 'Master Files')

@section('content')
    @include('partials.print-header', ['title' => 'Brands', 'typeFilters' => []])

    <table class="data">
        <thead>
            <tr>
                <th>Brand Name</th>
                <th class="num">Assets</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($brands as $brand)
                <tr>
                    <td>{{ $brand->name }}</td>
                    <td class="num">{{ $brand->assets_count }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No brands found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total ({{ $brands->count() }} {{ \Illuminate\Support\Str::plural('brand', $brands->count()) }})</td>
                <td class="num">{{ $brands->sum('assets_count') }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="note">Assets: registered assets linked to the brand (brand_id) that have not been deleted, the same count as the Master Files screen.</p>
@endsection
