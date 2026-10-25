{{--
    Opens a report's print view (a page that @extends('layouts.print')) in a new tab.

    Usage:  @include('reports.partials.print-button', ['url' => route('reports.fixed-asset-schedule.print', request()->query())])
--}}
<a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ $label ?? 'Print' }}</a>
