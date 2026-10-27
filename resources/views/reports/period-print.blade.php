{{-- Period Report print view (route: reports.period.print) - same query parameters as the screen --}}
@extends('layouts.print')

@section('title', 'Period Report')
@section('back', route('reports.period', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Period Report')
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', [
        'title' => 'Period Report - Movement Summary',
        'filters' => ['Period' => $period],
        'note' => $closingNote,
    ])

    @include('reports.partials.period-movement', ['result' => $result, 'tableClass' => 'data'])

    @include('reports.partials.period-sections', ['sections' => $sections, 'print' => true])
@endsection
