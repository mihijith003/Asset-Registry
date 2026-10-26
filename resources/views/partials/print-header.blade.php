{{--
    Header for every print view: what the page shows, the filters applied (in words), and who generated it when.
    Without it a printed list cannot be checked against the data it came from.

    @include('partials.print-header', [
        'title'   => 'Asset Sales',
        'filters' => ['Period' => '2026-04-01 to 2027-03-31'],   // optional extra filters; null/'' shows "All"
        'typeFilters' => ['Type', 'Sub Type'],                    // optional: which of these to show (default both, [] = none)
    ])

    Type and Sub Type are read from the query string (asset_type_id / asset_sub_type_id). With no filters at all the
    line reads "Filters: None".
    Styles are inline because layouts.print does not load the Vite assets.
--}}
@php
    $typeId = request()->query('asset_type_id');
    $subTypeId = request()->query('asset_sub_type_id');

    $typeName = $typeId && $typeId !== 'all' ? optional(\App\Models\AssetType::find($typeId))->type_name : null;
    $subTypeName = $subTypeId && $subTypeId !== 'all' ? optional(\App\Models\AssetSubType::find($subTypeId))->sub_type_name : null;

    $typeLabels = ['Type' => $typeName, 'Sub Type' => $subTypeName ? trim($subTypeName) : null];
    $applied = array_intersect_key($typeLabels, array_flip($typeFilters ?? ['Type', 'Sub Type'])) + ($filters ?? []);
    $summary = collect($applied)
        ->map(fn ($value, $label) => $label . ': ' . (($value === null || $value === '') ? 'All' : $value))
        ->join(' | ') ?: 'Filters: None';
@endphp

@once
    <style>
        .print-header { margin: 1rem 0 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid #d1d5db; }
        .print-header h2.print-title { margin: 0; font-size: 1.2rem; font-weight: 700; color: #111827; text-transform: none; letter-spacing: normal; }
        .print-header .print-filters { margin-top: 0.3rem; font-size: 0.9rem; font-weight: 600; color: #374151; }
        .print-header .print-generated { margin-top: 0.25rem; font-size: 0.8rem; color: #6b7280; }
    </style>
@endonce

<div class="print-header">
    <h2 class="print-title">{{ $title }}</h2>
    <div class="print-filters">{{ $summary }}</div>
    <div class="print-generated">Generated on {{ now()->format('Y-m-d H:i T') }} by {{ auth()->user()->name ?? 'Unknown user' }}</div>
</div>
