{{-- Ageing Report tab (reports.index?tab=ageing): its own filter bar (as-at date + type / sub type) and the matrix. --}}

@include('partials.type-filter', [
    'fields' => $filterFields,
    'action' => route('reports.index'),
    'keep' => ['tab'],
    'clear' => route('reports.index', ['tab' => 'ageing']),
])

<div class="card">
    <div class="card-header">
        <div>
            <h3>Asset Ageing</h3>
            <p>Assets held at {{ $asAt }}, by age from purchase date. Click a figure to list its assets.</p>
        </div>
        @include('partials.print-summary-button', ['route' => 'reports.print', 'params' => ['tab' => 'ageing'], 'label' => 'Print Summary'])
    </div>
    <div class="table-responsive" style="padding: 0 1.5rem 1.5rem;">
        @include('reports.partials.ageing-matrix', ['ageing' => $ageing, 'print' => false])
    </div>
</div>
