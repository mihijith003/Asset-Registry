<x-app-layout>
    <x-slot name="header">Asset Registry</x-slot>

    <!-- Registration Form -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Register New Asset</h3>
                <p>Add a new item to the organizational asset registry.</p>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('assets.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Asset Type <span class="required">*</span></label>
                        <select name="asset_type_id" class="form-select" required>
                            <option value="">-- Select Asset Type --</option>
                            @foreach ($assetTypes as $type)
                                <option value="{{ $type->id }}" {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }} ({{ $type->type_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Brand Name</label>
                        <input type="text" name="brand_name" value="{{ old('brand_name') }}" class="form-control" placeholder="e.g. Dell" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="form-control" placeholder="e.g. Latitude 5420" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Financial Year</label>
                        <input type="text" name="financial_year" value="{{ old('financial_year') }}" class="form-control" placeholder="e.g. 2024/2025" maxlength="9">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="e.g. Room 302" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cost (LKR) <span class="required">*</span></label>
                        <input type="number" name="cost" value="{{ old('cost') }}" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primary">Register Asset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assets List -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Registered Assets</h3>
                <p>Overview of all active assets in the database.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $assets->total() }}</span>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Type</th>
                        <th>Brand & Model</th>
                        <th>Financial Year</th>
                        <th>Location</th>
                        <th class="text-right">Cost</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $asset->asset_code }}</td>
                            <td>{{ $asset->assetType->type_name ?? 'N/A' }}</td>
                            <td>{{ $asset->brand_name ?? '-' }} {{ $asset->model ? '(' . $asset->model . ')' : '' }}</td>
                            <td>{{ $asset->financial_year ?? '-' }}</td>
                            <td>{{ $asset->location ?? '-' }}</td>
                            <td class="text-right" style="font-weight: 500;">{{ number_format($asset->cost, 2) }}</td>
                            <td>{{ $asset->created_at ? $asset->created_at->format('Y-m-d') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No assets registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assets->hasPages())
            <div class="table-pagination">
                {{ $assets->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
