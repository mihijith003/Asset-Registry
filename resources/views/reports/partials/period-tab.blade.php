{{-- Period Report tab (reports.index?tab=period, the default): its own filter bar, presets, summary and sections. --}}

{{-- Period + type / sub type filter (GET). The presets fill in the two dates and apply. --}}
@include('partials.type-filter', [
    'fields' => $filterFields,
    'action' => route('reports.index'),
    'keep' => ['tab'],
    'clear' => route('reports.index', ['tab' => 'period']),
])

<div class="card">
    <div class="card-body" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
        <span style="font-size: 0.875rem; color: var(--text-muted); margin-right: 0.25rem;">Quick periods:</span>
        @foreach ($presets as $key => $preset)
            <button type="button" class="btn btn-secondary" data-period-preset
                    data-from="{{ $preset['from'] }}" data-to="{{ $preset['to'] }}">{{ $preset['label'] }}</button>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3>Movement Summary</h3>
            <p>Period {{ $period }}@if ($closingNote) &mdash; <strong style="color: #b45309;">{{ $closingNote }}</strong>@endif</p>
        </div>
        @include('partials.print-summary-button', ['route' => 'reports.print', 'params' => ['tab' => 'period'], 'label' => 'Print Summary'])
    </div>
    <div class="table-responsive" style="padding: 0 1.5rem 1.5rem;">
        @include('reports.partials.period-movement', ['result' => $result, 'tableClass' => 'table'])
    </div>
</div>

@include('reports.partials.period-sections', ['sections' => $sections, 'print' => false])

<script>
    document.querySelectorAll('[data-period-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.querySelector('form[data-type-filter]');
            if (!form) { return; }
            form.querySelector('[name="date_from"]').value = button.dataset.from;
            form.querySelector('[name="date_to"]').value = button.dataset.to;
            form.requestSubmit();
        });
    });
</script>
