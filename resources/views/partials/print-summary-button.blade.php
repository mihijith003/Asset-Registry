{{--
    "Print Summary": opens this page's print view in a new tab with the CURRENT query string, so the printout shows
    exactly the filtered data on screen.

    @include('partials.print-summary-button', ['route' => 'asset-sales.print-summary'])
    @include('partials.print-summary-button', ['url' => route('...'), 'label' => 'Print'])
--}}
@php
    $printUrl = $url ?? route($route, $params ?? []);
    $queryString = request()->getQueryString();

    if ($queryString) {
        $printUrl .= (str_contains($printUrl, '?') ? '&' : '?') . $queryString;
    }
@endphp
<a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ $label ?? 'Print Summary' }}</a>
