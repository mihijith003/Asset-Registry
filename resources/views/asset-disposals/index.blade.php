<x-app-layout>
    <x-slot name="header">Asset Disposal</x-slot>

    <!-- Asset Disposal Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Disposal</h3>
                <p>Record retirement or scrapping of obsolete assets.</p>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('asset-disposals.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <!-- Disposal ID -->
                    <div class="form-group">
                        <label for="disposal_id" class="form-label">
                            Disposal ID <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="disposal_id" 
                               name="disposal_id" 
                               value="{{ old('disposal_id') }}"
                               placeholder="e.g. DISP-001"
                               maxlength="50"
                               required
                               class="form-control">
                        @error('disposal_id')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Asset ID -->
                    <div class="form-group">
                        <label for="asset_id" class="form-label">
                            Asset <span class="required">*</span>
                        </label>
                        <select id="asset_id" 
                                name="asset_id" 
                                required
                                class="form-select">
                            <option value="">-- Select Asset --</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_code }} - {{ $asset->brand_name }} {{ $asset->model }}
                                </option>
                            @endforeach
                        </select>
                        @error('asset_id')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Method -->
                    <div class="form-group">
                        <label for="method" class="form-label">
                            Method <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="method" 
                               name="method" 
                               value="{{ old('method') }}"
                               placeholder="e.g. Recycled, Scrapped, Donated"
                               maxlength="100"
                               required
                               class="form-control">
                        @error('method')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        Clear
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Record Disposal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Disposals Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Disposal Records</h3>
                <p>History of assets that have been disposed or retired.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">
                Total: {{ $disposals->total() }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Disposal ID</th>
                        <th>Asset Code</th>
                        <th>Method</th>
                        <th>Disposal Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($disposals as $disposal)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $disposal->disposal_id }}
                            </td>
                            <td>
                                <div>
                                    {{ $disposal->asset->asset_code ?? 'Asset #' . $disposal->asset_id }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $disposal->asset->brand_name ?? '' }} {{ $disposal->asset->model ?? '' }}
                                </div>
                            </td>
                            <td style="font-weight: 500;">
                                {{ $disposal->method }}
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);">
                                {{ $disposal->disposal_date ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No disposals recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($disposals->hasPages())
            <div class="table-pagination">
                {{ $disposals->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
