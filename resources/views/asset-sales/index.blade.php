<x-app-layout>
    <x-slot name="header">Asset Sale</x-slot>

    {{-- NEW: shown right after a sale is recorded, linking to its printable voucher --}}
    @if (session('print_url'))
        <div class="alert alert-success" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <span>The sale voucher is ready to print.</span>
            <a href="{{ session('print_url') }}" target="_blank" rel="noopener" class="btn btn-primary">Print document</a>
        </div>
    @endif

    <!-- Asset Sale Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Sale</h3>
                <p>Record monetization or sale of an asset.</p>
            </div>
        </div>

        <div class="card-body">
            {{-- CHANGED: added id="asset-sale-form" so the admin password popup can hook into submit.
            <form action="{{ route('asset-sales.store') }}" method="POST">
            --}}
            <form id="asset-sale-form" action="{{ route('asset-sales.store') }}" method="POST">
                @csrf
                <div class="form-row">
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
                                    {{ $asset->asset_code }} - {{ $asset->brand?->name ?? $asset->brand_name }} {{ $asset->model }}
                                </option>
                            @endforeach
                        </select>
                        @error('asset_id')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Buyer Name -->
                    <div class="form-group">
                        <label for="buyer_name" class="form-label">
                            Buyer Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="buyer_name" 
                               name="buyer_name" 
                               value="{{ old('buyer_name') }}" 
                               required 
                               placeholder="e.g. John Doe or Acme Corp" 
                               maxlength="150" 
                               class="form-control">
                        @error('buyer_name')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Buyer Contact -->
                    <div class="form-group">
                        <label for="buyer_contact" class="form-label">
                            Buyer Contact
                        </label>
                        <input type="text" 
                               id="buyer_contact" 
                               name="buyer_contact" 
                               value="{{ old('buyer_contact') }}" 
                               placeholder="e.g. Phone number or email" 
                               maxlength="100" 
                               class="form-control">
                        @error('buyer_contact')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <label for="payment_method" class="form-label">
                            Payment Method
                        </label>
                        <select id="payment_method" name="payment_method" class="form-select">
                            <option value="">-- Select Payment Method --</option>
                            @foreach (['Cash', 'Cheque', 'Bank Transfer', 'Other'] as $method)
                                <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                                    {{ $method }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
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
                </div>

                {{-- NEW: optional notes from the person recording the sale --}}
                <div class="form-group">
                    <label for="comments" class="form-label">Comments</label>
                    <textarea id="comments" name="comments" rows="3" maxlength="1000" class="form-control" placeholder="Any notes about this sale (optional)">{{ old('comments') }}</textarea>
                    @error('comments')
                        <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
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

            {{-- NEW: asks for an admin password when "Record Sale" is clicked --}}
            <x-admin-password-modal form="asset-sale-form" />
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
                Total: {{ $sales->count() }}
            </span>
        </div>

        <div class="table-responsive">
            {{-- CHANGED: DataTables (search / sort / paging) is applied to this table by resources/js/app.js --}}
            <table class="table" data-datatable data-empty="No sales recorded yet.">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Asset Code</th>
                        <th>Buyer</th>
                        <th style="text-align: right;">Price</th>
                        <th>Payment Method</th>
                        <th>Authorized By</th>
                        <th>Sale Date</th>
                        <th>Comments</th>
                        {{-- NEW: print action column --}}
                        <th class="no-sort" style="text-align: right;">Document</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $sale->reference }}
                            </td>
                            <td>
                                <div>
                                    {{ $sale->asset->asset_code ?? 'Asset #' . $sale->asset_id }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $sale->asset?->brand?->name ?? ($sale->asset?->brand_name ?? '') }} {{ $sale->asset?->model ?? '' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $sale->buyer_name ?: '-' }}</div>
                                @if ($sale->buyer_contact)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $sale->buyer_contact }}</div>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $sale->price }}">
                                {{ number_format($sale->price, 2) }}
                            </td>
                            <td>
                                {{ $sale->payment_method ?: '-' }}
                            </td>
                            <td>
                                {{ $sale->authorized_by ?? '-' }}
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);">
                                {{ $sale->sale_date ?? '-' }}
                            </td>
                            <td style="white-space: pre-line; max-width: 260px;">{{ $sale->comments ?: '-' }}</td>
                            {{-- NEW: opens the printable voucher in a new tab --}}
                            <td style="text-align: right;">
                                <a href="{{ route('asset-sales.print', $sale) }}" target="_blank" rel="noopener" class="btn btn-secondary">Print</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
