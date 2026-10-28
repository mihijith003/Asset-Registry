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
                            Revalued Amount <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon">{{ currency_symbol() }}</span>
                            <input type="number" 
                                   id="revalued_amount" 
                                   name="revalued_amount" 
                                   value="{{ old('revalued_amount') }}" 
                                   step="0.01" 
                                   min="0" 
                                   required 
                                   placeholder="0.00" 
                                   class="form-control">
                        </div>
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

                    <!-- New Monthly Depreciation -->
                    <div class="form-group">
                        <label for="monthly_depreciation" class="form-label">
                            New Monthly Depreciation <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon">{{ currency_symbol() }}</span>
                            <input type="number"
                                   id="monthly_depreciation"
                                   name="monthly_depreciation"
                                   value="{{ old('monthly_depreciation') }}"
                                   step="0.01"
                                   min="0.01"
                                   required
                                   placeholder="e.g. 6000.00"
                                   class="form-control">
                        </div>
                        @error('monthly_depreciation')
                            <p style="color: red; font-size: 0.8em; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Derived Residual Value -->
                    <div class="form-group">
                        <label for="reval_derived_residual" class="form-label">
                            Residual Value
                        </label>
                        <div class="input-group">
                            <span class="input-group-addon">{{ currency_symbol() }}</span>
                            <input type="text"
                                   id="reval_derived_residual"
                                   class="form-control"
                                   readonly
                                   tabindex="-1"
                                   value="—"
                                   style="background-color: var(--bg-main); font-weight: 500;">
                        </div>
                        <small id="reval_derived_residual_hint" class="form-hint">Calculated: Revalued Amount &minus; (New Monthly Depreciation &times; New Useful Life)</small>
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

                    assetSelect.addEventListener('change', function () {
                        updateRemainingLife();
                        updateRevalResidualPreview();
                    });
                    if (dateInput) {
                        dateInput.addEventListener('change', function () {
                            if (assetSelect.value) {
                                updateRemainingLife();
                                updateRevalResidualPreview();
                            }
                        });
                    }

                    var amountInput = document.getElementById('revalued_amount');
                    var monthlyInput = document.getElementById('monthly_depreciation');
                    var residualOutput = document.getElementById('reval_derived_residual');
                    var residualHint = document.getElementById('reval_derived_residual_hint');

                    function updateRevalResidualPreview() {
                        if (!amountInput || !monthlyInput || !residualOutput) {
                            return;
                        }
                        var amount = parseFloat(amountInput.value);
                        var months = parseInt(lifeInput.value, 10);
                        var monthly = parseFloat(monthlyInput.value);

                        if (isNaN(amount) || isNaN(months) || isNaN(monthly) || months <= 0) {
                            residualOutput.value = '—';
                            residualOutput.style.color = '';
                            if (residualHint) {
                                residualHint.textContent = 'Calculated: Revalued Amount − (New Monthly Depreciation × New Useful Life)';
                                residualHint.style.color = '';
                            }
                            return;
                        }

                        var residual = Math.round((amount - (monthly * months)) * 100) / 100;
                        residualOutput.value = residual.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        if (residual < 0) {
                            residualOutput.style.color = 'var(--danger)';
                            if (residualHint) {
                                residualHint.textContent = 'Invalid: total depreciation exceeds revalued amount (cannot depreciate below zero).';
                                residualHint.style.color = 'var(--danger)';
                            }
                        } else {
                            residualOutput.style.color = '';
                            if (residualHint) {
                                residualHint.textContent = 'Calculated: Revalued Amount − (New Monthly Depreciation × New Useful Life)';
                                residualHint.style.color = '';
                            }
                        }
                    }

                    if (amountInput) { amountInput.addEventListener('input', updateRevalResidualPreview); }
                    if (lifeInput) { lifeInput.addEventListener('input', updateRevalResidualPreview); }
                    if (monthlyInput) { monthlyInput.addEventListener('input', updateRevalResidualPreview); }
                    updateRevalResidualPreview();
                })();
            </script>
        </div>
    </div>

    <!-- Revaluations Records Table -->
    @include('partials.type-filter', ['fields' => $filterFields])

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Revaluation Records</h3>
                <p>History of assets that have been revalued.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $revaluations->count() }}</span>
                @include('partials.print-summary-button', ['route' => 'asset-revaluations.print-summary'])
            </div>
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
                                    @if ($revaluation->asset)
                                        <a href="{{ route('assets.timeline', $revaluation->asset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $revaluation->asset->asset_code }}</a>
                                    @else
                                        Asset #{{ $revaluation->asset_id }}
                                    @endif
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $revaluation->asset?->brand?->name ?? ($revaluation->asset?->brand_name ?? '') }} {{ $revaluation->asset?->model ?? '' }}
                                </div>
                            </td>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                @if ($revaluation->resultAsset)
                                    <a href="{{ route('assets.timeline', $revaluation->resultAsset->id) }}" style="color: var(--primary);">{{ $revaluation->resultAsset->asset_code }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->previous_value }}">
                                {{ money($revaluation->previous_value) }}
                            </td>
                            <td style="text-align: right; font-weight: 500;" data-order="{{ $revaluation->revalued_amount }}">
                                {{ money($revaluation->revalued_amount) }}
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
