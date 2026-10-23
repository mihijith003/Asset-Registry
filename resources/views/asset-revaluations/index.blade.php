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
                                <option value="{{ $asset->id }}"
                                        data-remaining-life="{{ $asset->remaining_life_months ?? '' }}"
                                        data-start-date="{{ $asset->initial_start_date ?? '' }}"
                                        data-life-months="{{ $asset->total_life_months ?? '' }}"
                                        {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
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

                    <!-- New Useful Life (months) -->
                    <div class="form-group">
                        <label for="new_useful_life_months" class="form-label">
                            New Useful Life (months) <span class="required">*</span>
                        </label>
                        <input type="number"
                               id="new_useful_life_months"
                               name="new_useful_life_months"
                               value="{{ old('new_useful_life_months') }}"
                               min="1"
                               max="600"
                               step="1"
                               required
                               placeholder="e.g. 48"
                               class="form-control">
                        <small class="form-hint">Pre-filled with the selected asset's current remaining life.</small>
                        @error('new_useful_life_months')
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

            <script>
                (function () {
                    var assetSelect = document.getElementById('asset_id');
                    var dateInput = document.getElementById('revaluation_date');
                    var lifeInput = document.getElementById('new_useful_life_months');
                    if (!assetSelect || !lifeInput) {
                        return;
                    }

                    function completeMonthsBetween(startStr, endStr) {
                        var s = startStr.split('-').map(Number);
                        var e = endStr.split('-').map(Number);
                        if (s.length !== 3 || e.length !== 3) {
                            return 0;
                        }
                        var months = (e[0] - s[0]) * 12 + (e[1] - s[1]);
                        if (e[2] < s[2]) {
                            months -= 1;
                        }
                        return Math.max(0, months);
                    }

                    function updateRemainingLife() {
                        var opt = assetSelect.options[assetSelect.selectedIndex];
                        if (!opt || !opt.value) {
                            lifeInput.value = '';
                            return;
                        }

                        var startStr = opt.getAttribute('data-start-date');
                        var totalLife = parseInt(opt.getAttribute('data-life-months'), 10);
                        var fallbackRemaining = opt.getAttribute('data-remaining-life');

                        if (startStr && !isNaN(totalLife) && totalLife > 0 && dateInput && dateInput.value) {
                            var elapsed = completeMonthsBetween(startStr, dateInput.value);
                            lifeInput.value = Math.max(1, totalLife - elapsed);
                        } else if (fallbackRemaining) {
                            lifeInput.value = fallbackRemaining;
                        } else {
                            lifeInput.value = '';
                        }
                    }

                    assetSelect.addEventListener('change', updateRemainingLife);
                    if (dateInput) {
                        dateInput.addEventListener('change', function () {
                            if (assetSelect.value) {
                                updateRemainingLife();
                            }
                        });
                    }
                })();
            </script>
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
                        <th>New Asset Code</th>
                        <th style="text-align: right;">Previous Value</th>
                        <th style="text-align: right;">Revalued Amount</th>
                        <th style="text-align: center;">New Life</th>
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
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $revaluation->resultAsset?->asset_code ?? '-' }}
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->previous_value }}">
                                {{ number_format($revaluation->previous_value, 2) }}
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->revalued_amount }}">
                                {{ number_format($revaluation->revalued_amount, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if ($revaluation->new_useful_life_months)
                                    <span class="badge badge-secondary">{{ $revaluation->new_useful_life_months }} mos</span>
                                @else
                                    -
                                @endif
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
