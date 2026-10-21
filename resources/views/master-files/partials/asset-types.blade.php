<!-- Add Asset Type Form Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3>Add Asset Type</h3>
            <p>Define top-level asset categories and their prefix codes.</p>
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('asset-types.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <!-- Type Name -->
                <div class="form-group">
                    <label for="type_name" class="form-label">
                        Type Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="type_name"
                           name="type_name"
                           value="{{ old('_method') !== 'PATCH' ? old('type_name') : '' }}"
                           required
                           placeholder="e.g. Electronics, Furniture"
                           maxlength="100"
                           class="form-control">
                    @if (old('_method') !== 'PATCH')
                        @error('type_name', 'assetType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <!-- Type Code -->
                <div class="form-group">
                    <label for="type_code" class="form-label">
                        Type Code <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="type_code"
                           name="type_code"
                           value="{{ old('_method') !== 'PATCH' ? old('type_code') : '' }}"
                           required
                           placeholder="e.g. EL, FUR"
                           maxlength="10"
                           class="form-control"
                           style="text-transform: uppercase;">
                    <small class="form-hint">Used as the prefix of every asset code (e.g. "EL" in EL-LAP-001). Max 10 chars.</small>
                    @if (old('_method') !== 'PATCH')
                        @error('type_code', 'assetType')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Clear</button>
                <button type="submit" class="btn btn-primary">Add Asset Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Asset Types List Card -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <div>
            <h3>Asset Types</h3>
            <p>All registered asset types and their associated sub types and assets.</p>
        </div>
        <span style="font-size: 0.875rem; color: var(--text-muted);">
            Total: {{ $assetTypes->count() }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table" data-datatable data-empty="No asset types found.">
            <thead>
                <tr>
                    <th style="width: 15%;">Type Code</th>
                    <th>Type Name</th>
                    <th style="text-align: center; width: 15%;">Sub Types</th>
                    <th style="text-align: center; width: 15%;">Assets</th>
                    <th class="no-sort" style="text-align: right; width: 18%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assetTypes as $type)
                    <tr>
                        <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                            {{ $type->type_code }}
                        </td>
                        <td style="font-weight: 500;">
                            {{ $type->type_name }}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary">{{ $type->sub_types_count }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary">{{ $type->assets_count }}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button"
                                    class="btn btn-secondary btn-sm"
                                    style="padding: 0.25rem 0.6rem; font-size: 0.75rem; margin-right: 0.25rem;"
                                    data-edit-type
                                    data-action="{{ route('asset-types.update', $type->id) }}"
                                    data-name="{{ $type->type_name }}"
                                    data-code="{{ $type->type_code }}">
                                Edit
                            </button>

                            <form method="POST" action="{{ route('asset-types.destroy', $type->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete the &quot;{{ $type->type_name }}&quot; asset type?');">
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

{{-- Edit Asset Type Modal Dialog --}}
<div id="edit-type-modal" class="modal-overlay" {{ (old('_method') === 'PATCH' && isset($errors) && $errors->assetType->any()) ? '' : 'hidden' }}>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="edit-type-title">
        <h3 id="edit-type-title">Edit Asset Type</h3>
        <p class="modal-sub">Update the name or code for this asset type.</p>

        {{-- Retroactive asset code warning --}}
        <div style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 0.75rem 1rem; border-radius: var(--radius); font-size: 0.8125rem; margin-bottom: 1.25rem;">
            <strong>Warning:</strong> Changing a Type Code does <em>not</em> retroactively change the asset codes of assets already registered under it.
        </div>

        <form id="edit-type-form" method="POST" action="{{ old('edit_action', '') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="edit_action" id="edit_type_action" value="{{ old('edit_action', '') }}">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_type_name" class="form-label">Type Name <span class="required">*</span></label>
                <input type="text"
                       id="edit_type_name"
                       name="type_name"
                       value="{{ old('_method') === 'PATCH' ? old('type_name') : '' }}"
                       required
                       maxlength="100"
                       class="form-control">
                @if (old('_method') === 'PATCH')
                    @error('type_name', 'assetType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_type_code" class="form-label">Type Code <span class="required">*</span></label>
                <input type="text"
                       id="edit_type_code"
                       name="type_code"
                       value="{{ old('_method') === 'PATCH' ? old('type_code') : '' }}"
                       required
                       maxlength="10"
                       class="form-control"
                       style="text-transform: uppercase;">
                <small class="form-hint">Used as the prefix of every asset code. Max 10 chars.</small>
                @if (old('_method') === 'PATCH')
                    @error('type_code', 'assetType')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-edit-type-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('edit-type-modal');
        if (!modal) { return; }

        var form = document.getElementById('edit-type-form');
        var actionInput = document.getElementById('edit_type_action');
        var nameInput = document.getElementById('edit_type_name');
        var codeInput = document.getElementById('edit_type_code');
        var cancelBtn = modal.querySelector('[data-edit-type-cancel]');

        function closeModal() {
            modal.hidden = true;
        }

        // Delegated click on Edit button (works across DataTables pagination/search)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-edit-type]');
            if (!btn) { return; }

            form.action = btn.dataset.action;
            actionInput.value = btn.dataset.action;
            nameInput.value = btn.dataset.name || '';
            codeInput.value = btn.dataset.code || '';

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
