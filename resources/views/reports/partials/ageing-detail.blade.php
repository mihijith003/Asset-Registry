{{-- Asset list behind an ageing figure (rows tagged with type / sub type / band for the screen's filter). --}}
<table class="{{ $tableClass }}">
    <thead>
        <tr>
            <th>Asset Code</th>
            <th>Type / Sub Type</th>
            <th>Brand &amp; Model</th>
            <th>Purchase Date</th>
            <th style="{{ $num }}">Age</th>
            <th style="{{ $num }}">Cost</th>
            <th style="{{ $num }}">Accumulated Depreciation</th>
            <th style="{{ $num }}">Net Book Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $a)
            <tr data-type="{{ $a->asset->asset_type_id }}" data-sub="{{ $a->asset->asset_sub_type_id }}" data-band="{{ $a->band }}">
                <td class="mono">{{ $a->asset->asset_code }}</td>
                <td>{{ $kind($a) }}</td>
                <td>{{ trim(($a->asset->brand->name ?? $a->asset->brand_name ?? '') . ' ' . ($a->asset->model ?? '')) ?: '-' }}</td>
                <td>{{ $a->since ?? '-' }}@if ($a->sinceIsCreated)<span style="color: #64748b;"> (registered)</span>@endif</td>
                <td style="{{ $num }}">{{ \App\Support\AgeingReport::ageLabel($a->months) }}</td>
                <td style="{{ $num }}">{{ $money($a->cost) }}</td>
                <td style="{{ $num }}">{{ $money($a->accumulated) }}</td>
                <td style="{{ $num }}">{{ $money($a->nbv) }}@if ($a->writtenDown)<span class="ageing-tag">fully depreciated</span>@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
