{{--
    Ageing matrix + detail lists, shared by the Ageing tab and its print view.
    $ageing from App\Support\AgeingReport::run(); $print = true on the print view.

    Columns come from $ageing->bands (AgeingReport::BANDS) - no band labels here. Screen: every non-zero figure
    lists its assets below the matrix (one list at a time). Print: every list is shown, by type / sub type / band.
--}}
@php
    $bands = $ageing->bands;
    $columns = $bands + ['total' => 'Total'];
    $money = fn ($v) => money($v);
    $tableClass = $print ? 'data' : 'table';
    $num = 'text-align: right; font-variant-numeric: tabular-nums;';
    $kind = fn ($a) => ($a->asset->assetType->type_name ?? '-') . ' / ' . trim($a->asset->subType->sub_type_name ?? '-');
@endphp

@once
    <style>
        .ageing-cell { display: inline-block; text-align: right; line-height: 1.25; }
        .ageing-cell small { display: block; color: #64748b; font-size: 0.75rem; }
        button.ageing-cell { background: none; border: 0; padding: 0; font: inherit; color: #1d4ed8; cursor: pointer; }
        button.ageing-cell:hover strong, button.ageing-cell.selected strong { text-decoration: underline; }
        button.ageing-cell.selected { outline: 2px solid #93c5fd; outline-offset: 2px; border-radius: 3px; }
        .ageing-sub td:first-child { padding-left: 1.75rem; }
        .ageing-type td { font-weight: 600; background: #f8fafc; }
        .ageing-tag { font-size: 0.7rem; color: #92400e; background: #fef3c7; border-radius: 3px; padding: 0 0.3rem; margin-left: 0.3rem; white-space: nowrap; }
    </style>
@endonce

{{-- One cell: count, NBV on a second line; clickable on screen when non-zero. $filter = which assets it covers. --}}
@php
    $cell = function ($c, array $filter, string $title) use ($print, $money) {
        if ($c->count === 0) {
            return '<span class="ageing-cell" style="color: #cbd5e1;">&ndash;</span>';
        }
        $inner = '<strong>' . $c->count . '</strong><small>' . e($money($c->nbv)) . '</small>';
        if ($print) {
            return '<span class="ageing-cell">' . $inner . '</span>';
        }
        $data = collect($filter)->map(fn ($v, $k) => 'data-' . $k . '="' . e($v) . '"')->implode(' ');

        return '<button type="button" class="ageing-cell" data-ageing-cell ' . $data . ' data-title="' . e($title) . '">' . $inner . '</button>';
    };
@endphp

<table class="{{ $tableClass }}">
    <thead>
        <tr>
            <th>Asset Type / Sub Type</th>
            @foreach ($columns as $label)
                <th style="{{ $num }}">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($ageing->types as $type)
            <tr class="ageing-type">
                <td>{{ $type->name }}</td>
                @foreach ($columns as $band => $label)
                    <td style="{{ $num }}">{!! $cell($type->cells[$band], ['type' => $type->id, 'band' => $band], $type->name . ' - ' . $label) !!}</td>
                @endforeach
            </tr>
            @foreach ($type->subTypes as $sub)
                <tr class="ageing-sub">
                    <td>{{ $sub->name }}</td>
                    @foreach ($columns as $band => $label)
                        <td style="{{ $num }}">{!! $cell($sub->cells[$band], ['type' => $type->id, 'sub' => $sub->id, 'band' => $band], $type->name . ' / ' . $sub->name . ' - ' . $label) !!}</td>
                    @endforeach
                </tr>
            @endforeach
        @empty
            <tr><td colspan="{{ count($columns) + 1 }}">No assets held at this date for the selected filters.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="font-weight: 700;">
            <td>Total</td>
            @foreach ($columns as $band => $label)
                <td style="{{ $num }}">{!! $cell($ageing->total->cells[$band], ['band' => $band], 'All assets - ' . $label) !!}</td>
            @endforeach
        </tr>
        <tr>
            <td>Share of assets (by count)</td>
            @foreach ($bands as $band => $label)
                <td style="{{ $num }}">{{ number_format($ageing->total->share[$band], 1) }}%</td>
            @endforeach
            <td style="{{ $num }}">{{ $ageing->assets->isEmpty() ? '0.0' : '100.0' }}%</td>
        </tr>
        <tr>
            <td>Fully depreciated (still held)</td>
            @foreach ($columns as $band => $label)
                <td style="{{ $num }}">{{ $ageing->total->cells[$band]->writtenDown }}</td>
            @endforeach
        </tr>
    </tfoot>
</table>
<p style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem;">
    Each figure is the number of assets, with their total net book value at the as-at date below it. Age is measured from the
    purchase date (the registration date where none was recorded), in whole months. "Fully depreciated" = every depreciation
    layer has charged its whole useful life, so the net book value is down to the residual value. Assets are those held at the
    as-at date, decided from the event history: sold, disposed, superseded or merged on or before it are excluded; later ones are not.
</p>

{{-- Detail lists --}}
@php
    $detailTable = function ($rows) use ($tableClass, $money, $num, $kind, $print) {
        return view('reports.partials.ageing-detail', compact('rows', 'tableClass', 'money', 'num', 'kind', 'print'))->render();
    };
@endphp

@if ($print)
    @foreach ($ageing->types as $type)
        @foreach ($type->subTypes as $sub)
            @foreach ($bands as $band => $label)
                @php
                    $rows = $ageing->assets->filter(fn ($a) => $a->asset->asset_type_id === $type->id && $a->asset->asset_sub_type_id === $sub->id && $a->band === $band);
                @endphp
                @if ($rows->isNotEmpty())
                    <h2>{{ $type->name }} / {{ $sub->name }} &mdash; {{ $label }} ({{ $rows->count() }})</h2>
                    {!! $detailTable($rows) !!}
                @endif
            @endforeach
        @endforeach
    @endforeach
@elseif ($ageing->assets->isNotEmpty())
    <div id="ageing-detail" hidden style="margin-top: 1.25rem;">
        <h4 style="margin: 0 0 0.5rem; font-size: 0.95rem;" data-ageing-title></h4>
        {!! $detailTable($ageing->assets) !!}
    </div>
    <script>
        (function () {
            var panel = document.getElementById('ageing-detail');
            var title = panel.querySelector('[data-ageing-title]');
            var rows = panel.querySelectorAll('tbody tr[data-band]');

            document.addEventListener('click', function (e) {
                var cell = e.target.closest('[data-ageing-cell]');
                if (!cell) { return; }
                document.querySelectorAll('[data-ageing-cell].selected').forEach(function (c) { c.classList.remove('selected'); });
                cell.classList.add('selected');

                // A cell covers its type (if any), sub type (if any) and band ("total" = every band).
                var shown = 0;
                rows.forEach(function (row) {
                    var match = (!cell.dataset.type || row.dataset.type === cell.dataset.type)
                        && (!cell.dataset.sub || row.dataset.sub === cell.dataset.sub)
                        && (cell.dataset.band === 'total' || row.dataset.band === cell.dataset.band);
                    row.hidden = !match;
                    shown += match ? 1 : 0;
                });
                title.textContent = cell.dataset.title + ' (' + shown + ')';
                panel.hidden = false;
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        })();
    </script>
@endif
