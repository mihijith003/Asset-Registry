{{--
    Period Report print view (route: reports.print, tab=period) - same query parameters as the screen.
    $only (ReportController::printSection): null = full report (movement summary + the ticked sections);
    'summary' = the movement summary alone; a ReportController::SECTIONS key = that detail section alone.
    One print header per page, naming what is printed.
--}}
@php
    $report = \App\Http\Controllers\ReportController::TABS['period'];
    $part = match (true) {
        $only === null => null,
        $only === 'summary' => 'Movement Summary',
        default => \App\Http\Controllers\ReportController::SECTIONS[$only],
    };
@endphp
@extends('layouts.print')

@section('title', $part ? "{$report} - {$part}" : $report)
@section('back', route('reports.index', \Illuminate\Support\Arr::except(request()->query(), ['section'])))
@section('orientation', 'landscape')
@section('doc-title', $part ? "{$report} - {$part}" : $report)
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', [
        'title' => $part ? "{$report} - {$part}" : "{$report} - Full Report",
        'filters' => ['Period' => $period],
        'note' => $closingNote,
    ])

    @if ($only === null || $only === 'summary')
        @include('reports.partials.period-movement', ['result' => $result, 'tableClass' => 'data'])
    @endif

    @if ($only !== 'summary')
        @include('reports.partials.period-sections', ['sections' => $sections, 'print' => true])
    @endif
@endsection
