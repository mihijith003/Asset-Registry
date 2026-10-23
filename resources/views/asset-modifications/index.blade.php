<x-app-layout>
    <x-slot name="header">Asset Modification</x-slot>

    <!-- Modification Form -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Modification</h3>
                <p>Upgrade or downgrade an asset by adding a component, or by swapping a component for another one.</p>
            </div>
        </div>
        <div class="card-body">
            <form id="asset-modification-form" action="{{ route('asset-modifications.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Modification Type <span class="required">*</span></label>
                        <div style="display: flex; gap: 1.5rem; align-items: center; min-height: 38px;">
                            <div class="form-check form-check-inline" style="display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input class="form-check-input" type="radio" name="modification_type" id="type_upgrade" value="upgrade" {{ old('modification_type') === 'upgrade' ? 'checked' : '' }} required style="cursor: pointer; margin: 0;">
                                <label class="form-check-label" for="type_upgrade" style="cursor: pointer; margin-bottom: 0;">Upgrade</label>
                            </div>
                            <div class="form-check form-check-inline" style="display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input class="form-check-input" type="radio" name="modification_type" id="type_downgrade" value="downgrade" {{ old('modification_type') === 'downgrade' ? 'checked' : '' }} required style="cursor: pointer; margin: 0;">
                                <label class="form-check-label" for="type_downgrade" style="cursor: pointer; margin-bottom: 0;">Downgrade</label>
                            </div>
                        </div>
                        @error('modification_type')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asset <span class="required">*</span></label>
                        <select name="asset_id" id="asset_id" class="form-select" required>
                            <option value="">-- Select Asset --</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" data-cost="{{ $asset->cost }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_code }} - {{ $asset->brand?->name ?? $asset->brand_name }} {{ $asset->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Modification Date <span class="required">*</span></label>
                        <input type="date" name="modification_date" value="{{ old('modification_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>

                {{-- Note: location is managed via asset allocations on the registry page --}}
                <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: -0.5rem; margin-bottom: 1.25rem;">
                    <span style="font-weight: 500;">Note:</span> The combined asset automatically inherits the selected asset's current allocation. To allocate or reassign it to a different branch or employee, use the <strong>Allocate</strong> button on the <a href="{{ route('assets.index') }}" style="color: var(--primary); text-decoration: underline;">Asset Registry</a>.
                </div>

                {{-- Component that is taken OUT (optional). It is registered as an asset of its own, with this price. --}}
                <h4 class="form-section-title">Component removed <span class="form-section-hint">(optional)</span></h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Removed Component</label>
                        <select name="removed_part" id="removed_part" class="form-select"></select>
                        <small class="form-hint" id="removed_part_hint"></small>
                        @error('removed_part')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" data-removed-new hidden>
                        <label class="form-label">Brand <span class="required">*</span></label>
                        <input type="text" name="removed_brand" value="{{ old('removed_brand') }}" class="form-control" placeholder="e.g. Kingston" maxlength="100">
                        @error('removed_brand')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" data-removed-new hidden>
                        <label class="form-label">Model</label>
                        <input type="text" name="removed_model" value="{{ old('removed_model') }}" class="form-control" placeholder="e.g. 8GB DDR4" maxlength="100">
                    </div>

                    <div class="form-group" data-removed-new hidden>
                        <label class="form-label">Sub Type</label>
                        <select name="removed_asset_sub_type_id" class="form-select">
                            <option value="">-- Same as the asset --</option>
                            @foreach ($assetSubTypes as $subType)
                                <option value="{{ $subType->id }}" {{ old('removed_asset_sub_type_id') == $subType->id ? 'selected' : '' }}>{{ $subType->sub_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-removed-new hidden>
                        <label class="form-label">Originally Came With</label>
                        <select name="removed_origin_asset_id" id="removed_origin" class="form-select"></select>
                        <small class="form-hint">The removed component is traced back to this asset in the registry.</small>
                    </div>

                    <div class="form-group" data-removed-price hidden>
                        <label class="form-label">Removed Component Price (LKR) <span class="required">*</span></label>
                        <input type="number" name="removed_price" id="removed_price" value="{{ old('removed_price') }}" class="form-control" step="0.01" min="0" placeholder="0.00">
                        @error('removed_price')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Component that goes IN (always required; it must already be in the registry). --}}
                <h4 class="form-section-title">Component added</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Modification Item <span class="required">*</span></label>
                        <select name="component_asset_id" id="component_asset_id" class="form-control form-select" required>
                            <option value="">-- Select Component Asset --</option>
                            @foreach ($components as $component)
                                <option value="{{ $component->id }}" data-cost="{{ $component->cost }}" {{ old('component_asset_id') == $component->id ? 'selected' : '' }}>
                                    {{ $component->asset_code }} - {{ $component->brand_name }} {{ $component->model }} ({{ number_format($component->cost, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-hint">Components removed in earlier modifications are listed here too.</small>
                        @error('component_asset_id')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Result</label>
                        <div id="modification_preview" class="modification-preview">Select an asset and a component.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comments" class="form-label">Comments</label>
                    <textarea id="comments" name="comments" rows="3" maxlength="1000" class="form-control" placeholder="Any notes about this modification (optional)">{{ old('comments') }}</textarea>
                    @error('comments')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Clear</button>
                    <button type="submit" id="modification_submit" class="btn btn-primary">Record Modification</button>
                </div>
            </form>

            {{-- Asks for an admin password when "Record Modification" is clicked --}}
            <x-admin-password-modal form="asset-modification-form" />

            <script>
                (function () {
                    var partOptions = @json($partOptions); // { assetId: [ {id, label, cost}, ... ] }
                    var previousPart = @json(old('removed_part'));
                    var previousOrigin = @json(old('removed_origin_asset_id'));
                    var originSelect = document.getElementById('removed_origin');

                    var assetSelect = document.getElementById('asset_id');
                    var partSelect = document.getElementById('removed_part');
                    var partHint = document.getElementById('removed_part_hint');
                    var priceInput = document.getElementById('removed_price');
                    var componentSelect = document.getElementById('component_asset_id');
                    var preview = document.getElementById('modification_preview');
                    var form = document.getElementById('asset-modification-form');
                    var newFields = form.querySelectorAll('[data-removed-new]');
                    var priceGroup = form.querySelector('[data-removed-price]');

                    function money(n) {
                        return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    function optionCost(select) {
                        var opt = select.options[select.selectedIndex];
                        return opt && opt.dataset.cost !== undefined ? parseFloat(opt.dataset.cost) : null;
                    }

                    // "Removed Component": nothing, one of the asset's original parts, or a typed-in component.
                    function fillParts() {
                        var parts = partOptions[assetSelect.value] || [];
                        partSelect.innerHTML = '';

                        var none = document.createElement('option');
                        none.value = '';
                        none.textContent = '-- None (nothing removed) --';
                        partSelect.appendChild(none);

                        parts.forEach(function (p) {
                            var o = document.createElement('option');
                            o.value = p.id;
                            o.textContent = p.label + ' (original part)';
                            o.dataset.cost = p.cost;
                            partSelect.appendChild(o);
                        });

                        var other = document.createElement('option');
                        other.value = 'new';
                        other.textContent = 'Other component (enter details)';
                        partSelect.appendChild(other);

                        // "Originally came with": the asset itself or one of the parts it was made from
                        originSelect.innerHTML = '';
                        if (assetSelect.value !== '') {
                            var self = document.createElement('option');
                            self.value = assetSelect.value;
                            self.textContent = assetSelect.options[assetSelect.selectedIndex].textContent.trim() + ' (this asset)';
                            originSelect.appendChild(self);
                            parts.forEach(function (p) {
                                var o = document.createElement('option');
                                o.value = p.id;
                                o.textContent = p.label;
                                originSelect.appendChild(o);
                            });
                        }

                        partHint.textContent = parts.length
                            ? 'This asset was made from earlier assets: removing one of them returns it to the registry.'
                            : 'The removed component is added to the registry as a new asset with the price below.';
                    }

                    function updateMode() {
                        var removing = partSelect.value !== '';
                        var typed = partSelect.value === 'new';

                        newFields.forEach(function (el) { el.hidden = !typed; });
                        priceGroup.hidden = !removing;
                        priceInput.required = removing;
                        if (!removing) { priceInput.value = ''; }

                        // An asset cannot be combined with itself
                        Array.prototype.forEach.call(componentSelect.options, function (o) {
                            o.disabled = o.value !== '' && o.value === assetSelect.value;
                        });

                        updatePreview();
                    }

                    // Live preview: new cost and modification type label from radio selection.
                    function updatePreview() {
                        var assetCost = optionCost(assetSelect);
                        var addedCost = optionCost(componentSelect);
                        if (assetCost === null || addedCost === null) {
                            preview.textContent = 'Select an asset and a component.';
                            return;
                        }

                        var removedPrice = partSelect.value !== '' ? (parseFloat(priceInput.value) || 0) : 0;
                        var newCost = assetCost - removedPrice + addedCost;
                        var diff = addedCost - removedPrice;
                        var selectedRadio = form.querySelector('input[name="modification_type"]:checked');
                        var label = selectedRadio ? (selectedRadio.value === 'upgrade' ? 'Upgrade' : 'Downgrade') : '';

                        preview.innerHTML = (label ? '<strong>' + label + '</strong> &middot; ' : '')
                            + 'new asset cost LKR ' + money(newCost)
                            + ' <span class="muted">(' + (diff >= 0 ? '+' : '-') + money(Math.abs(diff)) + ')</span>';
                    }

                    form.querySelectorAll('input[name="modification_type"]').forEach(function (radio) {
                        radio.addEventListener('change', updatePreview);
                    });

                    assetSelect.addEventListener('change', function () { fillParts(); updateMode(); });
                    partSelect.addEventListener('change', function () {
                        // picking an original part suggests its current price (still editable)
                        var cost = optionCost(partSelect);
                        priceInput.value = cost !== null ? cost : '';
                        updateMode();
                    });
                    componentSelect.addEventListener('change', updatePreview);
                    priceInput.addEventListener('input', updatePreview);

                    // Form "Clear" button: restore the default state after the browser resets the fields
                    form.addEventListener('reset', function () {
                        setTimeout(function () { fillParts(); updateMode(); }, 0);
                    });

                    fillParts();
                    if (previousPart) { partSelect.value = previousPart; } // restore after a validation error
                    if (previousOrigin) { originSelect.value = previousOrigin; }
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
                <p>Record of all asset upgrades and downgrades.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $modifications->count() }}</span>
        </div>

        <div class="table-responsive">
            {{-- DataTables (search / sort / paging) is applied to this table by resources/js/app.js --}}
            <table class="table" data-datatable data-empty="No modifications recorded yet.">
                <thead>
                    <tr>
                        <th>Mod ID</th>
                        <th>Type</th>
                        <th>Asset</th>
                        <th>Date</th>
                        <th>Removed</th>
                        <th>Added</th>
                        <th class="text-right">New Value</th>
                        <th>New Location</th>
                        <th>Authorized By</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modifications as $mod)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $mod->reference }}
                                {{-- modifications made before upgrade/downgrade existed could be reverted --}}
                                @if ($mod->reverted_at)
                                    <div><span class="badge badge-secondary" title="Reverted on {{ $mod->reverted_at->format('Y-m-d H:i') }}">Reverted</span></div>
                                @endif
                            </td>
                            <td>
                                @if ($mod->modification_type)
                                    <span class="badge {{ $mod->modification_type === 'upgrade' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($mod->modification_type) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div>
                                    @if ($mod->asset)
                                        <a href="{{ route('assets.timeline', $mod->asset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $mod->asset->asset_code }}</a>
                                    @else
                                        Asset #{{ $mod->asset_id }}
                                    @endif
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mod->asset->brand_name ?? '' }} {{ $mod->asset->model ?? '' }}</div>
                            </td>
                            <td>{{ $mod->modification_date }}</td>
                            <td>
                                @if ($mod->removedAsset)
                                    <a href="{{ route('assets.timeline', $mod->removedAsset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $mod->removedAsset->asset_code }}</a>
                                    @if ($mod->removed_asset && $mod->removed_asset !== $mod->removedAsset->asset_code)
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mod->removed_asset }}</div>
                                    @endif
                                @else
                                    {{ $mod->removed_asset ?? '-' }}
                                @endif
                                @if ($mod->removed_price !== null)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Price {{ number_format($mod->removed_price, 2) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($mod->componentAsset)
                                    <a href="{{ route('assets.timeline', $mod->componentAsset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $mod->componentAsset->asset_code }}</a>
                                    @if ($mod->added_asset && $mod->added_asset !== $mod->componentAsset->asset_code)
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mod->added_asset }}</div>
                                    @endif
                                @else
                                    {{ $mod->added_asset ?? '-' }}
                                @endif
                                {{-- the new combined asset this modification created (if it still exists) --}}
                                @if ($mod->resultAsset)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Combined into <a href="{{ route('assets.timeline', $mod->resultAsset->id) }}" style="font-family: monospace; font-weight: 600;">{{ $mod->resultAsset->asset_code }}</a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right" style="font-weight: 500;" data-order="{{ $mod->new_value ?? -1 }}">{{ $mod->new_value !== null ? number_format($mod->new_value, 2) : '-' }}</td>
                            <td>{{ $mod->new_location ?? '-' }}</td>
                            <td>{{ $mod->authorized_by ?? '-' }}</td>
                            <td style="white-space: pre-line; max-width: 260px;">{{ $mod->comments ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
