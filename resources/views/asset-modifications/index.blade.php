<x-app-layout>
    <x-slot name="header">Asset Modification</x-slot>

    <!-- Modification Form -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Modification</h3>
                <p>Record physical or valuation changes to an existing asset.</p>
            </div>
        </div>
        <div class="card-body">
            {{-- CHANGED: added id="asset-modification-form" so the admin password popup can hook into submit.
            <form action="{{ route('asset-modifications.store') }}" method="POST">
            --}}
            <form id="asset-modification-form" action="{{ route('asset-modifications.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Modification ID <span class="required">*</span></label>
                        <input type="text" name="modification_id" value="{{ old('modification_id') }}" class="form-control" placeholder="e.g. MOD-001" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asset <span class="required">*</span></label>
                        {{-- CHANGED: added id="asset_id" (used by the script below). Only active assets are listed. --}}
                        <select name="asset_id" id="asset_id" class="form-select" required>
                            <option value="">-- Select Asset --</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_code }} - {{ $asset->brand_name }} {{ $asset->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Modification Date <span class="required">*</span></label>
                        <input type="date" name="modification_date" value="{{ old('modification_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    {{-- CHANGED: "Authorized By" input removed. Authorization is now an admin
                         password popup shown when "Record Modification" is clicked (see <x-admin-password-modal> below).
                    <div class="form-group">
                        <label class="form-label">Authorized By</label>
                        <input type="text" name="authorized_by" value="{{ old('authorized_by') }}" class="form-control" placeholder="e.g. Officer Name" maxlength="150">
                    </div>
                    --}}

                    {{-- CHANGED: "Removed Asset / Component" was a free-text box. It is now a dropdown that lists
                         the item(s) that were added to the selected asset. Choosing one REVERTS that modification.
                    <div class="form-group">
                        <label class="form-label">Removed Asset / Component</label>
                        <input type="text" name="removed_asset" value="{{ old('removed_asset') }}" class="form-control" placeholder="e.g. 8GB RAM" maxlength="150">
                    </div>
                    --}}
                    <div class="form-group">
                        <label class="form-label">Removed Asset / Component</label>
                        <select name="removed_asset_id" id="removed_asset_id" class="form-control form-select"></select>
                        <small id="removed_asset_hint" style="display: block; margin-top: 0.25rem; font-size: 0.75rem; color: var(--text-muted);"></small>
                    </div>

                    <div class="form-group">
                        {{-- CHANGED: no longer always required - it is disabled when reverting (see script below) --}}
                        <label class="form-label">Modification Item <span class="required" id="component_required_mark">*</span></label>
                        <select name="component_asset_id" id="component_asset_id" class="form-control form-select" required>
                            <option value="">-- Select Component Asset --</option>
                            @foreach ($components as $component)
                                <option value="{{ $component->id }}" {{ old('component_asset_id') == $component->id ? 'selected' : '' }}>
                                    {{ $component->asset_code }} - {{ $component->brand_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('component_asset_id')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Value (LKR)</label>
                        <input type="number" name="new_value" value="{{ old('new_value') }}" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Location</label>
                        <input type="text" name="new_location" value="{{ old('new_location') }}" class="form-control" placeholder="e.g. Branch B" maxlength="100">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Clear</button>
                    {{-- CHANGED: added id so the script can relabel it to "Revert Modification" --}}
                    <button type="submit" id="modification_submit" class="btn btn-primary">Record Modification</button>
                </div>
            </form>

            {{-- NEW: asks for an admin password when "Record Modification" is clicked --}}
            <x-admin-password-modal form="asset-modification-form" />

            {{-- NEW: revert behaviour. Picking an asset fills "Removed Asset / Component" with the item(s)
                 that were added to it. Choosing one switches the form to REVERT mode: the Modification
                 Item is disabled and the button reads "Revert Modification". --}}
            <script>
                (function () {
                    var revertOptions = @json($revertOptions); // { combinedAssetId: [ {id, label}, ... ] }
                    var previousRemoved = @json(old('removed_asset_id'));

                    var assetSelect = document.getElementById('asset_id');
                    var removedSelect = document.getElementById('removed_asset_id');
                    var removedHint = document.getElementById('removed_asset_hint');
                    var componentSelect = document.getElementById('component_asset_id');
                    var requiredMark = document.getElementById('component_required_mark');
                    var submitBtn = document.getElementById('modification_submit');

                    function fillRemovedOptions() {
                        var options = revertOptions[assetSelect.value] || [];
                        removedSelect.innerHTML = '';

                        var none = document.createElement('option');
                        none.value = '';
                        none.textContent = options.length ? '-- None (new modification) --' : '-- Nothing to revert --';
                        removedSelect.appendChild(none);

                        options.forEach(function (opt) {
                            var o = document.createElement('option');
                            o.value = opt.id;
                            o.textContent = opt.label;
                            removedSelect.appendChild(o);
                        });

                        removedSelect.disabled = options.length === 0;
                        removedHint.textContent = options.length
                            ? 'Select an item to revert: the combined asset is removed and the original assets are restored.'
                            : 'Only combined assets that were created by a modification can be reverted.';
                    }

                    function updateMode() {
                        var reverting = removedSelect.value !== '';

                        componentSelect.disabled = reverting;
                        componentSelect.required = !reverting;
                        if (reverting) { componentSelect.value = ''; }
                        requiredMark.hidden = reverting;
                        submitBtn.textContent = reverting ? 'Revert Modification' : 'Record Modification';

                        // An asset cannot be combined with itself
                        Array.prototype.forEach.call(componentSelect.options, function (o) {
                            o.disabled = o.value !== '' && o.value === assetSelect.value;
                        });
                    }

                    assetSelect.addEventListener('change', function () { fillRemovedOptions(); updateMode(); });
                    removedSelect.addEventListener('change', updateMode);

                    // Form "Clear" button: restore the default mode after the browser resets the fields
                    document.getElementById('asset-modification-form').addEventListener('reset', function () {
                        setTimeout(function () { fillRemovedOptions(); updateMode(); }, 0);
                    });

                    fillRemovedOptions();
                    if (previousRemoved) { removedSelect.value = previousRemoved; } // restore after a validation error
                    updateMode();
                })();
            </script>
        </div>
    </div>

    <!-- Modifications List -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Modification History</h3>
                <p>Record of all asset modifications and upgrades.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $modifications->total() }}</span>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mod ID</th>
                        <th>Asset</th>
                        <th>Date</th>
                        <th>Removed</th>
                        <th>Added</th>
                        <th class="text-right">New Value</th>
                        <th>New Location</th>
                        <th>Authorized By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($modifications as $mod)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $mod->modification_id }}
                                {{-- NEW: mark modifications that have since been reverted --}}
                                @if ($mod->reverted_at)
                                    <div><span class="badge badge-secondary" title="Reverted on {{ $mod->reverted_at->format('Y-m-d H:i') }}">Reverted</span></div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $mod->asset->asset_code ?? 'Asset #' . $mod->asset_id }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mod->asset->brand_name ?? '' }} {{ $mod->asset->model ?? '' }}</div>
                            </td>
                            <td>{{ $mod->modification_date }}</td>
                            <td>{{ $mod->removed_asset ?? '-' }}</td>
                            <td>
                                {{ $mod->added_asset ?? '-' }}
                                {{-- NEW: show the new combined asset this modification created (if it still exists) --}}
                                @if ($mod->resultAsset)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Combined into {{ $mod->resultAsset->asset_code }}</div>
                                @endif
                            </td>
                            <td class="text-right" style="font-weight: 500;">{{ $mod->new_value !== null ? number_format($mod->new_value, 2) : '-' }}</td>
                            <td>{{ $mod->new_location ?? '-' }}</td>
                            <td>{{ $mod->authorized_by ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No modifications recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($modifications->hasPages())
            <div class="table-pagination">
                {{ $modifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
