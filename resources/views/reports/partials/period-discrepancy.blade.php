{{--
    Red warning above the movement summary for a row that does not balance even after adjustments: a gap that no
    revaluation record explains. That points at a bug or a broken record, not stale history.
--}}
<div style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; padding: 0.6rem 0.9rem; margin-bottom: 0.75rem; font-size: 0.875rem;">
    <strong>&#9888; {{ $row->name }} does not reconcile.</strong>
    Opening + Additions + Revaluation Adj. &minus; Depreciation &minus; Disposals &amp; Sales + Adjustments =
    {{ number_format($row->expectedClosing + $row->adjustment, 2) }}, but Closing NBV is {{ number_format($row->closing, 2) }}
    (difference {{ number_format($row->error, 2) }}). This is not explained by any revaluation record.
    @if ($row->errors || abs($row->unexplained) > \App\Support\PeriodMovement::TOLERANCE)
        <ul style="margin: 0.25rem 0 0; padding-left: 1.25rem;">
            @foreach ($row->errors as $label => $amount)
                <li>{{ number_format($amount, 2) }} &mdash; {{ $label }}</li>
            @endforeach
            @if (abs($row->unexplained) > \App\Support\PeriodMovement::TOLERANCE)
                <li>{{ number_format($row->unexplained, 2) }} &mdash; not attributable to any record</li>
            @endif
        </ul>
    @endif
</div>
