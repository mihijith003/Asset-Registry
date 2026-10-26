<!-- Add Brand Form Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3>Add Brand</h3>
            <p>Register manufacturer and vendor brands used across assets.</p>
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('brands.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <!-- Brand Name -->
                <div class="form-group" style="flex: 1;">
                    <label for="brand_name" class="form-label">
                        Brand Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="brand_name"
                           name="name"
                           value="{{ old('_method') !== 'PATCH' ? old('name') : '' }}"
                           required
                           placeholder="e.g. Dell, HP, Apple, Herman Miller"
                           maxlength="100"
                           class="form-control">
                    @if (old('_method') !== 'PATCH')
                        @error('name', 'brand')
                            <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Clear</button>
                <button type="submit" class="btn btn-primary">Add Brand</button>
            </div>
        </form>
    </div>
</div>

<!-- Brands List Card -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <div>
            <h3>Brands</h3>
            <p>All registered brands and their associated asset counts.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $brands->count() }}</span>
            @include('partials.print-summary-button', ['route' => 'master-files.print', 'params' => ['tab' => 'brands']])
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" data-datatable data-empty="No brands found.">
            <thead>
                <tr>
                    <th>Brand Name</th>
                    <th style="text-align: center; width: 25%;">Assets</th>
                    <th class="no-sort" style="text-align: right; width: 25%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($brands as $brand)
                    <tr>
                        <td style="font-weight: 500;">
                            {{ $brand->name }}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary">{{ $brand->assets_count }}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button"
                                    class="btn btn-secondary btn-sm"
                                    style="padding: 0.25rem 0.6rem; font-size: 0.75rem; margin-right: 0.25rem;"
                                    data-edit-brand
                                    data-action="{{ route('brands.update', $brand->id) }}"
                                    data-name="{{ $brand->name }}">
                                Edit
                            </button>

                            <form method="POST" action="{{ route('brands.destroy', $brand->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete the &quot;{{ $brand->name }}&quot; brand?');">
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

{{-- Edit Brand Modal Dialog --}}
<div id="edit-brand-modal" class="modal-overlay" {{ (old('_method') === 'PATCH' && isset($errors) && $errors->brand->any()) ? '' : 'hidden' }}>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="edit-brand-title">
        <h3 id="edit-brand-title">Edit Brand</h3>
        <p class="modal-sub">Update the brand name.</p>

        <form id="edit-brand-form" method="POST" action="{{ old('edit_action', '') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="edit_action" id="edit_brand_action" value="{{ old('edit_action', '') }}">

            <!-- Brand Name -->
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="edit_brand_name" class="form-label">Brand Name <span class="required">*</span></label>
                <input type="text"
                       id="edit_brand_name"
                       name="name"
                       value="{{ old('_method') === 'PATCH' ? old('name') : '' }}"
                       required
                       maxlength="100"
                       class="form-control">
                @if (old('_method') === 'PATCH')
                    @error('name', 'brand')
                        <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-edit-brand-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('edit-brand-modal');
        if (!modal) { return; }

        var form = document.getElementById('edit-brand-form');
        var actionInput = document.getElementById('edit_brand_action');
        var nameInput = document.getElementById('edit_brand_name');
        var cancelBtn = modal.querySelector('[data-edit-brand-cancel]');

        function closeModal() {
            modal.hidden = true;
        }

        // Delegated click on Edit button (works across DataTables pagination/search)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-edit-brand]');
            if (!btn) { return; }

            form.action = btn.dataset.action;
            actionInput.value = btn.dataset.action;
            nameInput.value = btn.dataset.name || '';

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
