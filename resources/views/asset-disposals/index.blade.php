<x-app-layout>
    <x-slot name="header">Asset Disposal</x-slot>

    {{-- NEW: shown right after a disposal is recorded, linking to its printable record --}}
    @if (session('print_url'))
        <div class="alert alert-success" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <span>The disposal record is ready to print.</span>
            <a href="{{ session('print_url') }}" target="_blank" rel="noopener" class="btn btn-primary">Print document</a>
        </div>
    @endif

    <!-- Asset Disposal Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Disposal</h3>
                <p>Record retirement or scrapping of obsolete assets.</p>
            </div>
        </div>

        <div class="card-body">
            {{-- CHANGED: added id="asset-disposal-form" so the admin password popup can hook into submit.
            <form action="{{ route('asset-disposals.store') }}" method="POST">
            --}}
            <form id="asset-disposal-form" action="{{ route('asset-disposals.store') }}" method="POST">
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

                {{-- NEW: optional notes from the person recording the disposal --}}
                <div class="form-group">
                    <label for="comments" class="form-label">Comments</label>
                    <textarea id="comments" name="comments" rows="3" maxlength="1000" class="form-control" placeholder="Any notes about this disposal (optional)">{{ old('comments') }}</textarea>
                    @error('comments')
                        <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
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

            {{-- NEW: asks for an admin password when "Record Disposal" is clicked --}}
            <x-admin-password-modal form="asset-disposal-form" />
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
                        {{-- NEW: shows the admin who authorized the disposal --}}
                        <th>Authorized By</th>
                        <th>Comments</th>
                        {{-- NEW: print action column --}}
                        <th style="text-align: right;">Document</th>
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
                            {{-- NEW: authorized-by cell ('-' for older rows recorded before this column existed) --}}
                            <td>
                                {{ $disposal->authorized_by ?? '-' }}
                            </td>
                            <td style="white-space: pre-line; max-width: 260px;">{{ $disposal->comments ?: '-' }}</td>
                            {{-- NEW: opens the printable disposal record in a new tab --}}
                            <td style="text-align: right;">
                                <a href="{{ route('asset-disposals.print', $disposal) }}" target="_blank" rel="noopener" class="btn btn-secondary">Print</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- CHANGED: colspan 4 -> 5 for the new "Authorized By" column, then 5 -> 6 for "Document"
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            --}}
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
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
