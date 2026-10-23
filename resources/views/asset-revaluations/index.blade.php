<x-app-layout>
    <x-slot name="header">Asset Revaluation</x-slot>

    <!-- Asset Revaluation Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Record Asset Revaluation</h3>
                <p>Record upward or downward revaluation of an asset's carrying value.</p>
            </div>
        </div>

        <div class="card-body">
            <form id="asset-revaluation-form" action="{{ route('asset-revaluations.store') }}" method="POST">
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

                    <!-- Revaluation Date -->
                    <div class="form-group">
                        <label for="revaluation_date" class="form-label">
                            Revaluation Date <span class="required">*</span>
                        </label>
                        <input type="date" 
                               id="revaluation_date" 
                               name="revaluation_date" 
                               value="{{ old('revaluation_date', date('Y-m-d')) }}" 
                               required 
                               class="form-control">
                        @error('revaluation_date')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Revalued Amount -->
                    <div class="form-group">
                        <label for="revalued_amount" class="form-label">
                            Revalued Amount (LKR) <span class="required">*</span>
                        </label>
                        <input type="number" 
                               id="revalued_amount" 
                               name="revalued_amount" 
                               value="{{ old('revalued_amount') }}" 
                               step="0.01" 
                               min="0" 
                               required 
                               placeholder="0.00" 
                               class="form-control">
                        @error('revalued_amount')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div class="form-group">
                        <label for="reason" class="form-label">
                            Reason
                        </label>
                        <input type="text" 
                               id="reason" 
                               name="reason" 
                               value="{{ old('reason') }}" 
                               placeholder="e.g. Market appraisal, impairment review" 
                               maxlength="255" 
                               class="form-control">
                        @error('reason')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Comments -->
                <div class="form-group">
                    <label for="comments" class="form-label">Comments</label>
                    <textarea id="comments" name="comments" rows="3" maxlength="1000" class="form-control" placeholder="Any notes about this revaluation (optional)">{{ old('comments') }}</textarea>
                    @error('comments')
                        <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        Clear
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Record Revaluation
                    </button>
                </div>
            </form>

            {{-- Asks for admin password when "Record Revaluation" is clicked --}}
            <x-admin-password-modal form="asset-revaluation-form" />
        </div>
    </div>

    <!-- Revaluations Records Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Revaluation Records</h3>
                <p>History of assets that have been revalued.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">
                Total: {{ $revaluations->count() }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table" data-datatable data-empty="No revaluations recorded yet.">
                <thead>
                    <tr>
                        <th>Revaluation ID</th>
                        <th>Asset Code</th>
                        <th style="text-align: right;">Previous Value</th>
                        <th style="text-align: right;">Revalued Amount</th>
                        <th>Reason</th>
                        <th>Authorized By</th>
                        <th>Revaluation Date</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($revaluations as $revaluation)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $revaluation->reference }}
                            </td>
                            <td>
                                <div>
                                    {{ $revaluation->asset->asset_code ?? 'Asset #' . $revaluation->asset_id }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $revaluation->asset?->brand?->name ?? ($revaluation->asset?->brand_name ?? '') }} {{ $revaluation->asset?->model ?? '' }}
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->previous_value }}">
                                {{ number_format($revaluation->previous_value, 2) }}
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->revalued_amount }}">
                                {{ number_format($revaluation->revalued_amount, 2) }}
                            </td>
                            <td>
                                {{ $revaluation->reason ?: '-' }}
                            </td>
                            <td>
                                {{ $revaluation->authorized_by ?: '-' }}
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);">
                                {{ $revaluation->revaluation_date ? $revaluation->revaluation_date->format('Y-m-d') : '-' }}
                            </td>
                            <td style="white-space: pre-line; max-width: 260px;">{{ $revaluation->comments ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

