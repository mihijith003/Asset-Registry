{{-- Ageing Report print view (route: reports.print?tab=ageing) - same query parameters as the screen tab --}}
@extends('layouts.print')

@section('title', 'Ageing Report')
@section('back', route('reports.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Ageing Report')
@section('doc-ref', 'As at ' . $asAt)

@section('content')
    @include('partials.print-header', [
        'title' => 'Ageing Report - Assets Held by Age',
        'filters' => ['As at' => $asAt],
    ])

    @include('reports.partials.ageing-matrix', ['ageing' => $ageing, 'print' => true])
@endsection
