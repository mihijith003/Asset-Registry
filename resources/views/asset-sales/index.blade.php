<x-app-layout>
    <x-slot name="header">Asset Sale</x-slot>

    <!-- Asset Sale Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Sale</h3>
                <p>Record monetization or sale of an asset.</p>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('asset-sales.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <!-- Sale ID -->
                    <div class="form-group">
                        <label for="sale_id" class="form-label">
                            Sale ID <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="sale_id" 
                               name="sale_id" 
                               value="{{ old('sale_id') }}"
                               placeholder="e.g. SALE-001"
                               maxlength="50"
                               required
                               class="form-control">
                        @error('sale_id')
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

                    <!-- Price -->
                    <div class="form-group">
                        <label for="price" class="form-label">
                            Price (LKR) <span class="required">*</span>
                        </label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               value="{{ old('price') }}"
                               step="0.01" 
                               min="0"
                               required
                               placeholder="0.00"
                               class="form-control">
                        @error('price')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Authorized By -->
                    <div class="form-group">
                        <label for="authorized_by" class="form-label">
                            Authorized By
                        </label>
                        <input type="text" 
                               id="authorized_by" 
                               name="authorized_by" 
                               value="{{ old('authorized_by') }}"
                               placeholder="e.g. Finance Manager"
                               maxlength="100"
                               class="form-control">
                        @error('authorized_by')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        Clear
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Records Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Sales Records</h3>
                <p>History of assets that have been sold.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">
                Total: {{ $sales->total() }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Asset Code</th>
                        <th style="text-align: right;">Price</th>
                        <th>Authorized By</th>
                        <th>Sale Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $sale->sales_ID ?? $sale->sale_id }}
                            </td>
                            <td>
                                <div>
                                    {{ $sale->asset->asset_code ?? 'Asset #' . $sale->asset_id }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $sale->asset->brand_name ?? '' }} {{ $sale->asset->model ?? '' }}
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: 500;">
                                {{ number_format($sale->price, 2) }}
                            </td>
                            <td>
                                {{ $sale->authorized_BY ?? ($sale->authorized_by ?? '-') }}
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);">
                                {{ $sale->sale_date ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No sales recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="table-pagination">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
