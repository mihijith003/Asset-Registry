{{--
    Movement summary table, shared by the Period Report screen and its print view.
    $result from App\Support\PeriodMovement::run(); $tableClass = 'table' (screen) or 'data' (print).

    Every row (and the grand total) balances: Opening + Additions + Revaluation Adj. - Depreciation
    - Disposals & Sales + Adjustments = Closing. Adjustments are gaps attributed to specific revaluation records
    (stale history); they get a neutral notice and a detail table. A gap no revaluation record explains is an
    error and stays a red warning above the table.
--}}
@php
    $print = $tableClass === 'data';
    $columns = [
        'opening' => 'Opening NBV',
        'additions' => 'Additions',
        'revaluation' => 'Revaluation Adj.',
        'depreciation' => 'Depreciation',
        'disposals' => 'Disposals & Sales',
        'adjustment' => 'Adjustments',
        'closing' => 'Closing NBV',
    ];
    $money = fn ($v) => money($v);
    // Deductions are shown negative, so money() puts them in brackets: Rs. (4,550,000.00).
    $cell = fn ($row, $key) => money(in_array($key, ['depreciation', 'disposals'], true) ? -$row->$key : $row->$key);
    $hasAdjustments = $result->rows->contains(fn ($row) => abs($row->adjustment) > 0.004);
    $failing = $result->rows->reject(fn ($row) => $row->reconciles);
    if (! $result->total->reconciles && $failing->isEmpty()) {
        $failing = collect([$result->total]);
    }
@endphp

@foreach ($failing as $row)
    @include('reports.partials.period-discrepancy', ['row' => $row])
@endforeach

@if ($hasAdjustments)
    <div style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.6rem 0.9rem; margin-bottom: 0.75rem; font-size: 0.875rem;">
        This summary includes {{ $money(abs($result->total->adjustment)) }} of adjustments from historical records.
        See the Adjustments column.
    </div>
@endif

<table class="{{ $tableClass }}">
    <thead>
        <tr>
            <th>Asset Type</th>
            @foreach ($columns as $label)
                <th class="{{ $print ? 'num' : 'text-right' }}" style="text-align: right;">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($result->rows as $row)
            <tr>
                <td>{{ $row->name }} @if ($row->code)<span style="color: #64748b;">({{ $row->code }})</span>@endif</td>
                @foreach ($columns as $key => $label)
                    <td style="text-align: right; font-variant-numeric: tabular-nums;">
                        @if ($key === 'adjustment' && ! $print && abs($row->adjustment) > 0.004)
                            <a href="#period-adjustments" data-show-adjustments="{{ $row->code }}"
                               style="color: #1d4ed8; text-decoration: underline dotted; cursor: pointer;">{{ $money($row->adjustment) }}</a>
                        @else
                            {{ $cell($row, $key) }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) + 1 }}">No asset activity or balances for the selected period and filters.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="font-weight: 700;">
            <td>Grand total</td>
            @foreach ($columns as $key => $label)
                <td style="text-align: right; font-variant-numeric: tabular-nums;">{{ $cell($result->total, $key) }}</td>
            @endforeach
        </tr>
    </tfoot>
</table>
<p style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem;">
    Opening + Additions + Revaluation Adj. &minus; Depreciation &minus; Disposals &amp; Sales + Adjustments = Closing for every row.
    Depreciation and Disposals &amp; Sales are deductions, shown in brackets. Additions are assets bought during the period, at cost
    on entry; assets created by a modification or revaluation are not additions, and assets merged or superseded have not left the register.
    Revaluation Adj. is the recorded revalued amount minus the recorded previous value. Pending disposals are still held.
</p>

@if ($hasAdjustments)
    @include('reports.partials.period-adjustments', ['result' => $result, 'print' => $print, 'tableClass' => $tableClass])
@endif

@if ($result->notes)
    <ul style="font-size: 0.8rem; color: #b45309; margin-top: 0.25rem; padding-left: 1.25rem;">
        @foreach ($result->notes as $note)
            <li>{{ $note }}</li>
        @endforeach
    </ul>
@endif
