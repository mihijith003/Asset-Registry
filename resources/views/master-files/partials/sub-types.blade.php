<!-- Add Asset Sub Type Form Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3>Add Asset Sub Type</h3>
            <p>Define sub types belonging to a parent asset type and their code segments.</p>
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('asset-subtypes.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <!-- Parent Asset Type -->
                <div class="form-group">
                    <label for="sub_type_parent_type" class="form-label">
                        Parent Asset Type <span class="required">*</span>
                    </label>
                    <select id="sub_type_parent_type"
                            name="asset_type_id"
                            required
                            class="form-select">
                        <option value="">-- Select Asset Type --</option>
                        @foreach ($assetTypes as $type)
                            <option value="{{ $type->id }}" {{ (old('_method') !== 'PATCH' && old('asset_type_id') == $type->id) ? 'selected' : '' }}>
                                {{ $type->type_name }} ({{ $type->type_code }})
                            </option>
                        @endforeach
                    </select>
                    @if (old('_method') !== 'PATCH')
                        @error('asset_type_id', 'assetSubType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <!-- Sub Type Name -->
                <div class="form-group">
                    <label for="sub_type_name" class="form-label">
                        Sub Type Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="sub_type_name"
                           name="sub_type_name"
                           value="{{ old('_method') !== 'PATCH' ? old('sub_type_name') : '' }}"
                           required
                           placeholder="e.g. Laptop, Desktop, Office Chair"
                           maxlength="100"
                           class="form-control">
                    @if (old('_method') !== 'PATCH')
                        @error('sub_type_name', 'assetSubType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <!-- Sub Type Code -->
                <div class="form-group">
                    <label for="sub_type_code" class="form-label">
                        Sub Type Code <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="sub_type_code"
                           name="sub_type_code"
                           value="{{ old('_method') !== 'PATCH' ? old('sub_type_code') : '' }}"
                           required
                           placeholder="e.g. LAP, DSK, CHR"
                           maxlength="10"
                           class="form-control"
                           style="text-transform: uppercase;">
                    <small class="form-hint">Second segment of asset codes (e.g. "LAP" in EL-LAP-001). Unique within parent type.</small>
                    @if (old('_method') !== 'PATCH')
                        @error('sub_type_code', 'assetSubType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <!-- Useful Life (Years) -->
                <div class="form-group">
                    <label for="useful_life_years" class="form-label">
                        Default Useful Life (Years)
                    </label>
                    <input type="number"
                           id="useful_life_years"
                           name="useful_life_years"
                           value="{{ old('_method') !== 'PATCH' ? old('useful_life_years') : '' }}"
                           min="1"
                           max="100"
                           placeholder="e.g. 5 (leave blank if not depreciated)"
                           class="form-control">
                    <small class="form-hint">Default life for new assets. Changing this here applies only to assets registered from now on.</small>
                    @if (old('_method') !== 'PATCH')
                        @error('useful_life_years', 'assetSubType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Clear</button>
                <button type="submit" class="btn btn-primary">Add Sub Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Asset Sub Types List Card -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <div>
            <h3>Asset Sub Types</h3>
            <p>All registered asset sub types and their asset counts.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $assetSubTypes->count() }}</span>
            @include('partials.print-summary-button', ['route' => 'master-files.print', 'params' => ['tab' => 'sub-types']])
        </div>
    </div>

    {{-- Asset Type filter (GET, kept in the query string so Print Summary prints the same list) --}}
    @php $filterTypeId = (string) request()->query('asset_type_id', ''); @endphp
    <div class="card-body" style="border-bottom: 1px solid var(--border-color);">
        <form method="GET" action="{{ route('master-files.index') }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;">
            <input type="hidden" name="tab" value="sub-types">
            <div class="form-group" style="margin-bottom: 0; min-width: 220px;">
                <label class="form-label" for="mf_filter_asset_type_id">Asset Type</label>
                <select name="asset_type_id" id="mf_filter_asset_type_id" class="form-select">
                    <option value="">All types</option>
                    @foreach ($assetTypes as $type)
                        <option value="{{ $type->id }}" {{ $filterTypeId === (string) $type->id ? 'selected' : '' }}>{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('master-files.index', ['tab' => 'sub-types']) }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table" data-datatable data-empty="No asset sub types found.">
            <thead>
                <tr>
                    <th style="width: 22%;">Parent Asset Type</th>
                    <th style="width: 15%;">Sub Type Code</th>
                    <th>Sub Type Name</th>
                    <th style="text-align: center; width: 18%;">Default Life</th>
                    <th style="text-align: center; width: 12%;">Assets</th>
                    <th class="no-sort" style="text-align: right; width: 16%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assetSubTypes as $subType)
                    <tr>
                        <td style="font-weight: 500;">
                            <div>{{ $subType->assetType?->type_name ?? '-' }}</div>
                            @if ($subType->assetType?->type_code)
                                <div style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);">
                                    Prefix: {{ $subType->assetType->type_code }}
                                </div>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                            {{ $subType->sub_type_code }}
                        </td>
                        <td style="font-weight: 500;">
                            {{ $subType->sub_type_name }}
                        </td>
                        <td style="text-align: center;">
                            @if ($subType->useful_life_years)
                                <span class="badge badge-secondary">{{ $subType->useful_life_years }} {{ $subType->useful_life_years == 1 ? 'year' : 'years' }}</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.8125rem;">Not depreciated</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary">{{ $subType->assets_count }}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button"
                                    class="btn btn-secondary btn-sm"
                                    style="padding: 0.25rem 0.6rem; font-size: 0.75rem; margin-right: 0.25rem;"
                                    data-edit-subtype
                                    data-action="{{ route('asset-subtypes.update', $subType->id) }}"
                                    data-type-id="{{ $subType->asset_type_id }}"
                                    data-name="{{ $subType->sub_type_name }}"
                                    data-code="{{ $subType->sub_type_code }}"
                                    data-years="{{ $subType->useful_life_years ?? '' }}">
                                Edit
                            </button>

                            <form method="POST" action="{{ route('asset-subtypes.destroy', $subType->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete the &quot;{{ $subType->sub_type_name }}&quot; sub type?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; background-color: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Asset Sub Type Modal Dialog --}}
<div id="edit-subtype-modal" class="modal-overlay" {{ (old('_method') === 'PATCH' && isset($errors) && $errors->assetSubType->any()) ? '' : 'hidden' }}>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="edit-subtype-title">
        <h3 id="edit-subtype-title">Edit Asset Sub Type</h3>
        <p class="modal-sub">Update this sub type's name, code, parent type or default useful life.</p>

        {{-- Retroactive asset code warning --}}
        <div style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 0.75rem 1rem; border-radius: var(--radius); font-size: 0.8125rem; margin-bottom: 0.75rem;">
            <strong>Warning:</strong> Changing a Sub Type Code does <em>not</em> retroactively change the asset codes of assets already registered under it.
        </div>

        {{-- Useful life notice --}}
        <div style="background-color: #f8fafc; color: var(--text-muted); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius); font-size: 0.8125rem; margin-bottom: 1.25rem;">
            <strong>Note:</strong> Changing the default useful life here affects only assets registered from now on. Existing assets and depreciation layers retain their current values.
        </div>

        <form id="edit-subtype-form" method="POST" action="{{ old('edit_action', '') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="edit_action" id="edit_subtype_action" value="{{ old('edit_action', '') }}">

            <!-- Parent Asset Type -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_sub_type_asset_type_id" class="form-label">Parent Asset Type <span class="required">*</span></label>
                <select id="edit_sub_type_asset_type_id"
                        name="asset_type_id"
                        required
                        class="form-select">
                    <option value="">-- Select Asset Type --</option>
                    @foreach ($assetTypes as $type)
                        <option value="{{ $type->id }}" {{ (old('_method') === 'PATCH' && old('asset_type_id') == $type->id) ? 'selected' : '' }}>
                            {{ $type->type_name }} ({{ $type->type_code }})
                        </option>
                    @endforeach
                </select>
                @if (old('_method') === 'PATCH')
                    @error('asset_type_id', 'assetSubType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Sub Type Name -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_sub_type_name" class="form-label">Sub Type Name <span class="required">*</span></label>
                <input type="text"
                       id="edit_sub_type_name"
                       name="sub_type_name"
                       value="{{ old('_method') === 'PATCH' ? old('sub_type_name') : '' }}"
                       required
                       maxlength="100"
                       class="form-control">
                @if (old('_method') === 'PATCH')
                    @error('sub_type_name', 'assetSubType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Sub Type Code -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_sub_type_code" class="form-label">Sub Type Code <span class="required">*</span></label>
                <input type="text"
                       id="edit_sub_type_code"
                       name="sub_type_code"
                       value="{{ old('_method') === 'PATCH' ? old('sub_type_code') : '' }}"
                       required
                       maxlength="10"
                       class="form-control"
                       style="text-transform: uppercase;">
                <small class="form-hint">Second segment of asset codes. Unique within parent type. Max 10 chars.</small>
                @if (old('_method') === 'PATCH')
                    @error('sub_type_code', 'assetSubType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Useful Life (Years) -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_useful_life_years" class="form-label">Default Useful Life (Years)</label>
                <input type="number"
                       id="edit_useful_life_years"
                       name="useful_life_years"
                       value="{{ old('_method') === 'PATCH' ? old('useful_life_years') : '' }}"
                       min="1"
                       max="100"
                       placeholder="e.g. 5 (leave blank if not depreciated)"
                       class="form-control">
                @if (old('_method') === 'PATCH')
                    @error('useful_life_years', 'assetSubType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-edit-subtype-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('edit-subtype-modal');
        if (!modal) { return; }

        var form = document.getElementById('edit-subtype-form');
        var actionInput = document.getElementById('edit_subtype_action');
        var typeSelect = document.getElementById('edit_sub_type_asset_type_id');
        var nameInput = document.getElementById('edit_sub_type_name');
        var codeInput = document.getElementById('edit_sub_type_code');
        var yearsInput = document.getElementById('edit_useful_life_years');
        var cancelBtn = modal.querySelector('[data-edit-subtype-cancel]');

        function closeModal() {
            modal.hidden = true;
        }

        // Delegated click on Edit button (works across DataTables pagination/search)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-edit-subtype]');
            if (!btn) { return; }

            form.action = btn.dataset.action;
            actionInput.value = btn.dataset.action;
            if (typeSelect) {
                typeSelect.value = btn.dataset.typeId || '';
            }
            nameInput.value = btn.dataset.name || '';
            codeInput.value = btn.dataset.code || '';
            yearsInput.value = btn.dataset.years || '';

            modal.hidden = false;
            nameInput.focus();
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) { closeModal(); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) { closeModal(); }
        });
    })();
</script>
