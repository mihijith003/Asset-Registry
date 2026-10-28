{{--
    Reporting page (route: reports.index, URL /reports/period). One tab per report, the Master Files pattern
    (?tab=, nav-tabs, a partial per tab), except that the tabs are LINKS: the controller builds only the active
    tab's report, so switching tabs loads the page for that tab. A tab link carries the filters both tabs share
    (type / sub type), never one tab's dates.

    Loop variables are $tabKey / $tabLabel, not $key / $label: an @include sees this view's variables, and the
    print-summary-button partial reads $label as its caption.
--}}
@php
    $sharedFilters = array_filter(request()->only(['asset_type_id', 'asset_sub_type_id']), fn ($v) => is_scalar($v) && $v !== '');
@endphp
<x-app-layout>
    <x-slot name="header">Reporting</x-slot>

    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        @foreach ($tabs as $tabKey => $tabLabel)
            <li class="nav-item" role="presentation">
                @if ($tab === $tabKey)
                    <span class="nav-link active" id="{{ $tabKey }}-tab" role="tab" aria-selected="true" aria-current="page"
                          style="cursor: default;">{{ $tabLabel }}</span>
                @else
                    <a href="{{ route('reports.index', ['tab' => $tabKey] + $sharedFilters) }}" class="nav-link"
                       id="{{ $tabKey }}-tab" role="tab" aria-selected="false">{{ $tabLabel }}</a>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" role="tabpanel" aria-labelledby="{{ $tab }}-tab">
            @include('reports.partials.' . $tab . '-tab')
        </div>
    </div>
</x-app-layout>
