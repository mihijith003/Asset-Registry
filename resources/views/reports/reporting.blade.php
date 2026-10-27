{{--
    Reporting page (route: reports.index, URL /reports/period). One tab per report, the Master Files pattern
    (?tab=, nav-tabs, a partial per tab), except that the tabs are LINKS: the controller builds only the active
    tab's report, so switching tabs loads the page for that tab - with none of the other tab's filters.
--}}
<x-app-layout>
    <x-slot name="header">Reporting</x-slot>

    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <li class="nav-item" role="presentation">
                <a href="{{ route('reports.index', ['tab' => $key]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}"
                   id="{{ $key }}-tab" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                   style="display: inline-block; text-decoration: none;">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" role="tabpanel" aria-labelledby="{{ $tab }}-tab">
            @include('reports.partials.' . $tab . '-tab')
        </div>
    </div>
</x-app-layout>
