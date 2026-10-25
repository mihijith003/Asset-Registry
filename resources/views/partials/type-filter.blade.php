{{--
    Asset Type / Sub Type filter bar (GET form, so the filter lives in the query string and "Print Summary" can
    carry it to the print view). Server side: App\Http\Controllers\Concerns\FiltersByAssetType.

    @include('partials.type-filter', [
        'assetTypes'    => $assetTypes,      // optional: defaults to every type
        'assetSubTypes' => $assetSubTypes,   // optional: defaults to every sub type
        'action'        => route('...'),     // optional: defaults to the current URL
        'fields'        => [                 // optional extra filters, rendered after the type selects
            ['name' => 'date_from', 'label' => 'From', 'type' => 'date'],
            ['name' => 'date_to',   'label' => 'To',   'type' => 'date'],
            ['name' => 'method', 'label' => 'Method', 'type' => 'select', 'options' => ['' => 'All', 'Lost' => 'Lost']],
        ],
        'keep'          => ['fy'],           // optional: other query keys to carry over as hidden fields
    ])

    Current values come from the query string, so they are kept after Apply.
--}}
@php
    $assetTypes = $assetTypes ?? \App\Models\AssetType::orderBy('type_name')->get();
    $assetSubTypes = $assetSubTypes ?? \App\Models\AssetSubType::orderBy('sub_type_name')->get();
    $action = $action ?? url()->current();
    $fields = $fields ?? [];
    $keep = $keep ?? [];

    $selectedType = (string) request()->query('asset_type_id', '');
    $selectedSubType = (string) request()->query('asset_sub_type_id', '');
    $selectedType = $selectedType === 'all' ? '' : $selectedType;
    $selectedSubType = $selectedSubType === 'all' ? '' : $selectedSubType;
@endphp

@once
    <style>
        .type-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
        .type-filter .form-group { margin-bottom: 0; min-width: 180px; flex: 0 1 220px; }
        .type-filter .filter-actions { display: flex; align-items: center; gap: 0.75rem; }
    </style>
@endonce

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" class="type-filter" data-type-filter>
            @foreach ($keep as $key)
                @if (request()->filled($key))
                    <input type="hidden" name="{{ $key }}" value="{{ request()->query($key) }}">
                @endif
            @endforeach

            <div class="form-group">
                <label class="form-label" for="filter_asset_type_id">Asset Type</label>
                <select name="asset_type_id" id="filter_asset_type_id" class="form-select" data-filter-type>
                    <option value="">All types</option>
                    @foreach ($assetTypes as $type)
                        <option value="{{ $type->id }}" {{ $selectedType === (string) $type->id ? 'selected' : '' }}>{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="filter_asset_sub_type_id">Asset Sub Type</label>
                <select name="asset_sub_type_id" id="filter_asset_sub_type_id" class="form-select" data-filter-sub-type>
                    <option value="">All sub types</option>
                    @foreach ($assetSubTypes as $subType)
                        <option value="{{ $subType->id }}" data-asset-type-id="{{ $subType->asset_type_id }}" {{ $selectedSubType === (string) $subType->id ? 'selected' : '' }}>{{ trim($subType->sub_type_name) }}</option>
                    @endforeach
                </select>
            </div>

            @foreach ($fields as $field)
                @php
                    $name = $field['name'];
                    $value = (string) request()->query($name, $field['default'] ?? '');
                    $id = 'filter_' . $name;
                @endphp
                <div class="form-group">
                    <label class="form-label" for="{{ $id }}">{{ $field['label'] ?? ucfirst(str_replace('_', ' ', $name)) }}</label>
                    @if (($field['type'] ?? 'text') === 'select')
                        <select name="{{ $name }}" id="{{ $id }}" class="form-select">
                            @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" {{ $value === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $field['type'] ?? 'text' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" class="form-control">
                    @endif
                </div>
            @endforeach

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ $action }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

@once
    <script>
        // Show only the sub types of the selected type (same approach as the registry form). With "All types"
        // every sub type is listed.
        document.querySelectorAll('form[data-type-filter]').forEach(function (form) {
            var typeSelect = form.querySelector('[data-filter-type]');
            var subTypeSelect = form.querySelector('[data-filter-sub-type]');
            if (!typeSelect || !subTypeSelect) {
                return;
            }

            function filterSubTypes() {
                var typeId = typeSelect.value;
                for (var i = 0; i < subTypeSelect.options.length; i++) {
                    var opt = subTypeSelect.options[i];
                    if (!opt.value) {
                        continue;
                    }
                    var matches = !typeId || opt.getAttribute('data-asset-type-id') === typeId;
                    opt.hidden = !matches;
                    opt.disabled = !matches;
                    opt.style.display = matches ? '' : 'none';
                    if (!matches && opt.selected) {
                        subTypeSelect.value = '';
                    }
                }
            }

            typeSelect.addEventListener('change', filterSubTypes);
            filterSubTypes();
        });
    </script>
@endonce
