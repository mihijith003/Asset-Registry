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
            <form action="{{ route('asset-modifications.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Modification ID <span class="required">*</span></label>
                        <input type="text" name="modification_id" value="{{ old('modification_id') }}" class="form-control" placeholder="e.g. MOD-001" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asset <span class="required">*</span></label>
                        <select name="asset_id" class="form-select" required>
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

                    <div class="form-group">
                        <label class="form-label">Authorized By</label>
                        <input type="text" name="authorized_by" value="{{ old('authorized_by') }}" class="form-control" placeholder="e.g. Officer Name" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Removed Asset / Component</label>
                        <input type="text" name="removed_asset" value="{{ old('removed_asset') }}" class="form-control" placeholder="e.g. 8GB RAM" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Added Asset / Component</label>
                        <input type="text" name="added_asset" value="{{ old('added_asset') }}" class="form-control" placeholder="e.g. 16GB RAM" maxlength="150">
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
                    <button type="submit" class="btn btn-primary">Record Modification</button>
                </div>
            </form>
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
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $mod->modification_id }}</td>
                            <td>
                                <div>{{ $mod->asset->asset_code ?? 'Asset #' . $mod->asset_id }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mod->asset->brand_name ?? '' }} {{ $mod->asset->model ?? '' }}</div>
                            </td>
                            <td>{{ $mod->modification_date }}</td>
                            <td>{{ $mod->removed_asset ?? '-' }}</td>
                            <td>{{ $mod->added_asset ?? '-' }}</td>
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
