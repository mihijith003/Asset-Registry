<x-app-layout>
    <x-slot name="header">Depreciation</x-slot>

    @php
        $totalCost = collect($rows)->sum('cost');
        $totalCharge = collect($rows)->sum('charge');
        $totalBookValue = collect($rows)->sum('book_value');
    @endphp

    <!-- Depreciation report -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Depreciation Report</h3>
                <p>Straight-line, calculated monthly (complete months only), with no residual value.</p>
            </div>
            <form method="GET" action="{{ route('depreciation.index') }}" style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="fy" class="form-label" style="margin: 0;">Financial Year</label>
                <select name="fy" id="fy" class="form-select" style="width: auto;" onchange="this.form.submit()">
                    @foreach ($financialYears as $year)
                        <option value="{{ $year }}" {{ $selected === $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="card-body">
            <div class="summary-row">
                <div><span class="muted">Figures up to</span> <strong>{{ $closingDate->format('Y-m-d') }}</strong></div>
                <div><span class="muted">Total cost</span> <strong>{{ number_format($totalCost, 2) }}</strong></div>
                <div><span class="muted">Depreciation for {{ $selected }}</span> <strong>{{ number_format($totalCharge, 2) }}</strong></div>
                <div><span class="muted">Net book value</span> <strong>{{ number_format($totalBookValue, 2) }}</strong></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" data-datatable data-empty="No assets were depreciating in this financial year.">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Asset</th>
                        <th>Status</th>
                        <th class="text-right">Life (Years)</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Opening Accum. Dep.</th>
                        <th class="text-right">Charge for the Year</th>
                        <th class="text-right">Closing Accum. Dep.</th>
                        <th class="text-right">Net Book Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $row->asset->asset_code }}</td>
                            <td>
                                {{ trim(($row->asset->brand_name ?? '') . ' ' . ($row->asset->model ?? '')) ?: '-' }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $row->asset->subType->sub_type_name ?? '' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $row->status === 'Active' ? 'badge-success' : 'badge-secondary' }}">{{ $row->status }}</span>
                            </td>
                            <td class="text-right" data-order="{{ $row->life_years ?? -1 }}">{{ $row->life_years ?? 'Not set' }}</td>
                            <td class="text-right" data-order="{{ $row->cost }}">{{ number_format($row->cost, 2) }}</td>
                            <td class="text-right" data-order="{{ $row->opening }}">{{ number_format($row->opening, 2) }}</td>
                            <td class="text-right" data-order="{{ $row->charge }}">{{ number_format($row->charge, 2) }}</td>
                            <td class="text-right" data-order="{{ $row->closing }}">{{ number_format($row->closing, 2) }}</td>
                            <td class="text-right" style="font-weight: 500;" data-order="{{ $row->book_value }}">{{ number_format($row->book_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Useful life settings -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Useful Life Settings</h3>
                <p>Years over which each kind of asset is written off. Leave blank to not depreciate that kind of asset. Saving also updates assets already registered.</p>
            </div>
        </div>
        <div class="card-body">
            <form id="depreciation-settings-form" method="POST" action="{{ route('depreciation.settings') }}">
                @csrf
                <div class="form-row">
                    @foreach ($subTypes as $subType)
                        <div class="form-group">
                            <label class="form-label" for="life_{{ $subType->id }}">{{ $subType->assetType->type_name ?? '' }} - {{ trim($subType->sub_type_name) }}</label>
                            <input type="number" id="life_{{ $subType->id }}" name="useful_life_years[{{ $subType->id }}]"
                                   value="{{ old('useful_life_years.' . $subType->id, $subType->useful_life_years) }}"
                                   class="form-control" min="1" max="100" step="1" placeholder="Years">
                        </div>
                    @endforeach
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>

            <x-admin-password-modal form="depreciation-settings-form" />
        </div>
    </div>
</x-app-layout>
