{{--
    Detail behind the Adjustments column: the revaluation records whose recorded previous value differs from what the
    current depreciation figures produce. Collapsed on screen (opened from an Adjustments figure), always shown in print.
--}}
@php($money = fn ($v) => number_format($v, 2))
<div id="period-adjustments" @unless ($print) hidden @endunless style="margin-top: 1rem;">
    <h4 style="margin: 0 0 0.25rem; font-size: 0.95rem;">Adjustments</h4>
    <p style="margin: 0 0 0.5rem; font-size: 0.85rem; color: #475569;">
        These revaluations recorded a value that does not match what the current depreciation figures produce for that date.
        The difference is shown as an adjustment so the summary still balances.
    </p>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Asset</th>
                <th>Date</th>
                <th style="text-align: right;">Recorded value</th>
                <th style="text-align: right;">Recalculated value</th>
                <th style="text-align: right;">Difference</th>
            </tr>
        </thead>
        @foreach ($result->rows->filter(fn ($row) => abs($row->adjustment) > 0.004) as $row)
            <tbody data-adjustment-type="{{ $row->code }}">
                <tr>
                    <td colspan="5" style="font-weight: 600; background: #f8fafc;">{{ $row->name }}</td>
                    <td style="text-align: right; font-weight: 600; background: #f8fafc; font-variant-numeric: tabular-nums;">{{ $money($row->adjustment) }}</td>
                </tr>
                @foreach ($row->adjustmentDetail as $line)
                    <tr>
                        <td>{{ $line['ref'] }}</td>
                        <td>{{ $line['asset'] }}</td>
                        <td>{{ $line['date'] }}</td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums;">{{ $money($line['recorded']) }}</td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums;">{{ $money($line['recalculated']) }}</td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums;">{{ $money($line['amount']) }}</td>
                    </tr>
                @endforeach
                @if (abs($row->rounding) > 0.004)
                    <tr>
                        <td colspan="5" style="color: #64748b;">Rounding of the summary figures</td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums;">{{ $money($row->rounding) }}</td>
                    </tr>
                @endif
            </tbody>
        @endforeach
    </table>
</div>

@unless ($print)
    <script>
        document.querySelectorAll('[data-show-adjustments]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                var panel = document.getElementById('period-adjustments');
                panel.hidden = false;
                var section = panel.querySelector('[data-adjustment-type="' + link.dataset.showAdjustments + '"]') || panel;
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
@endunless
