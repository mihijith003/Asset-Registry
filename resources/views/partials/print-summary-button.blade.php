{{--
    "Print Summary": opens this page's print view in a new tab with the CURRENT query string, so the printout shows
    exactly the filtered data on screen.

    @include('partials.print-summary-button', ['route' => 'asset-sales.print-summary'])
    @include('partials.print-summary-button', ['url' => route('...'), 'label' => 'Print'])
    @include('partials.print-summary-button', ['route' => 'master-files.print', 'params' => ['tab' => 'brands']])

    With 'route', explicit 'params' override same-named keys in the current query string (so a tab's button always
    prints that tab, even on a URL like ?tab=sub-types&asset_type_id=1).
--}}
@php
    if (isset($url)) {
        $printUrl = $url;
        $queryString = request()->getQueryString();

        if ($queryString) {
            $printUrl .= (str_contains($printUrl, '?') ? '&' : '?') . $queryString;
        }
    } else {
        // The RAW query string, not request()->query(): middleware turns a blank "date_from=" into null and route()
        // drops nulls, which would turn "all dates" back into the default period on the printout.
        parse_str(request()->getQueryString() ?? '', $currentQuery);
        $printUrl = route($route, ($params ?? []) + $currentQuery);
    }
@endphp
<a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ $label ?? 'Print Summary' }}</a>
