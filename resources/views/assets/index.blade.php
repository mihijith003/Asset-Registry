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
                        <label class="form-label" for="asset_type_id">Asset Type <span class="required">*</span></label>
                        <select name="asset_type_id" id="asset_type_id" class="form-select" required>
                            <option value="">-- Select Asset Type --</option>
                            @foreach ($assetTypes as $type)
                                <option value="{{ $type->id }}" {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }} ({{ $type->type_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="asset_sub_type_id">Asset Sub Type <span class="required">*</span></label>
                        <select name="asset_sub_type_id" id="asset_sub_type_id" class="form-select" required>
                            <option value="">-- Select Asset Sub Type --</option>
                            @foreach ($assetSubTypes as $subType)
                                <option value="{{ $subType->id }}" data-asset-type-id="{{ $subType->asset_type_id }}" {{ old('asset_sub_type_id') == $subType->id ? 'selected' : '' }}>
                                    {{ $subType->sub_type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">— Select brand —</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="form-control" placeholder="e.g. Latitude 5420" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Financial Year</label>
                        <select name="financial_year" class="form-select">
                            <option value="">-- Select Financial Year --</option>
                            @foreach ($financialYears as $year)
                                <option value="{{ $year }}" {{ old('financial_year', $currentFinancialYear) === $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cost">Cost (LKR) <span class="required">*</span></label>
                        <input type="number" id="cost" name="cost" value="{{ old('cost') }}" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="useful_life_months">Useful Life (months) <span class="required">*</span></label>
                        <input type="number" id="useful_life_months" name="useful_life_months" value="{{ old('useful_life_months') }}" class="form-control" min="1" max="600" step="1" required placeholder="e.g. 48">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="monthly_depreciation">Monthly Depreciation (LKR) <span class="required">*</span></label>
                        <input type="number" id="monthly_depreciation" name="monthly_depreciation" value="{{ old('monthly_depreciation') }}" class="form-control" step="0.01" min="0.01" required placeholder="e.g. 6000.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="derived_residual_value">Residual Value (LKR)</label>
                        <input type="text" id="derived_residual_value" class="form-control" readonly tabindex="-1" value="—" style="background-color: var(--bg-main); font-weight: 500;">
                        <small id="derived_residual_hint" class="form-hint">Calculated: Cost &minus; (Monthly Depreciation &times; Useful Life)</small>
                    </div>
                </div>

                {{-- Visually grouped procurement fields --}}
                <h4 class="form-section-title">Procurement details <span class="form-section-hint">(optional)</span></h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <input type="text" name="supplier" value="{{ old('supplier') }}" class="form-control" placeholder="e.g. Supplier Name" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">PO Reference</label>
                        <input type="text" name="po_reference" value="{{ old('po_reference') }}" class="form-control" placeholder="e.g. PO-1002" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Invoice Reference</label>
                        <input type="text" name="invoice_reference" value="{{ old('invoice_reference') }}" class="form-control" placeholder="e.g. INV-5001" maxlength="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="comments">Comments</label>
                    <textarea id="comments" name="comments" class="form-control" rows="3" maxlength="1000" placeholder="Optional comments or notes about this asset">{{ old('comments') }}</textarea>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primary">Register Asset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assets List -->
    {{-- Type / sub type filter (GET); Print Summary carries the same filter to the print view --}}
    @include('partials.type-filter', ['assetTypes' => $assetTypes, 'assetSubTypes' => $assetSubTypes, 'action' => route('assets.index')])

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Registered Assets</h3>
                <p>Overview of all active assets in the database.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $assets->count() }}</span>
                @include('partials.print-summary-button', ['route' => 'assets.print'])
                {{-- QR label sheet for the same filter (raw query string, as the print button does); "Start at" skips
                     the slots already used on a part-used sticker sheet. --}}
                @php
                    parse_str(request()->getQueryString() ?? '', $labelQuery);
                @endphp
                @php
                    $labelQuery = array_filter(\Illuminate\Support\Arr::except($labelQuery, ['start', 'asset']), 'is_scalar');
                @endphp
                <form method="GET" action="{{ route('assets.labels') }}" target="_blank" data-label-form style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    @foreach ($labelQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    {{-- Set and enabled only by a row's Label button (script at the bottom of this page). --}}
                    <input type="hidden" name="asset" value="" disabled data-label-asset-input>
                    <label for="label_start" style="font-size: 0.875rem; color: var(--text-muted); white-space: nowrap;">Start at label</label>
                    <input type="number" id="label_start" name="start" value="1" min="1" max="{{ \App\Http\Controllers\AssetController::LABELS_PER_PAGE }}"
                           class="form-control" style="width: 4.5rem;">
                    <button type="submit" class="btn btn-secondary">Print Labels</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            {{-- CHANGED: DataTables (search / sort / paging) is applied to this table by resources/js/app.js --}}
            <table class="table" data-datatable data-empty="No assets registered yet.">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Type</th>
                        <th>Brand & Model</th>
                        <th>Financial Year</th>
                        {{-- CHANGED: was "Location"; now shows the current allocation (branch / employee) --}}
                        <th>Allocated To</th>
                        <th class="text-right">Cost</th>
                        {{-- NEW: straight-line monthly depreciation as of today --}}
                        <th class="text-right">Accum. Depreciation</th>
                        <th class="text-right">Net Book Value</th>
                        <th>Registered At</th>
                        {{-- NEW: per-row actions --}}
                        <th class="no-sort" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $asset)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600;">
                                <a href="{{ route('assets.timeline', $asset->id) }}" style="color: var(--primary);">{{ $asset->asset_code }}</a>
                            </td>
                            <td>{{ $asset->assetType->type_name ?? 'N/A' }}</td>
                            <td>
                                {{ $asset->brand?->name ?? ($asset->brand_name ?? '-') }} {{ $asset->model ? '(' . $asset->model . ')' : '' }}
                                {{-- NEW: flag combined assets and say which assets they were made from --}}
                                {{-- NEW: a component removed in a modification - trace it back to where it came from --}}
                                @if ($asset->removalModification)
                                    <div style="margin-top: 0.25rem;">
                                        <span class="badge badge-danger">Removed component</span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        @if ($asset->originAsset)
                                            Originally came with <a href="{{ route('assets.timeline', $asset->originAsset->id) }}">{{ $asset->originAsset->asset_code }}</a> ({{ trim(($asset->originAsset->brand?->name ?? $asset->originAsset->brand_name) . ' ' . $asset->originAsset->model) }}).
                                        @endif
                                        Removed from
                                        @if ($asset->removalModification->asset)
                                            <a href="{{ route('assets.timeline', $asset->removalModification->asset->id) }}">{{ $asset->removalModification->asset->asset_code }}</a>
                                        @else
                                            Asset #{{ $asset->removalModification->asset_id }}
                                        @endif
                                        in {{ $asset->removalModification->reference }} ({{ $asset->removalModification->modification_date }}).
                                    </div>
                                @endif
                                @php
                                    $revalPredecessor = $asset->supersedes ?? $asset->revaluationIn?->asset;
                                @endphp
                                @if ($revalPredecessor)
                                    <div style="margin-top: 0.25rem;">
                                        <a href="{{ route('assets.timeline', $revalPredecessor->id) }}" class="badge badge-secondary" style="text-decoration: none;">
                                            Revalued from {{ $revalPredecessor->asset_code }}
                                        </a>
                                    </div>
                                @endif
                                @if ($asset->components->isNotEmpty())
                                    @if ($asset->isCombinationUndone())
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                            Previously combined &mdash; see <a href="{{ route('asset-modifications.index') }}">modification history</a>
                                        </div>
                                    @else
                                        <div style="margin-top: 0.25rem;">
                                            <span class="badge badge-secondary">Combined asset</span>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                            Combination of {{ $asset->components->count() }} previous assets:
                                            @foreach ($asset->components as $cIdx => $c)
                                                @if ($cIdx > 0) + @endif
                                                <a href="{{ route('assets.timeline', $c->id) }}">{{ $c->asset_code }}</a> ({{ trim(($c->brand?->name ?? $c->brand_name) . ' ' . $c->model) }})
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $asset->financial_year ?? '-' }}</td>
                            @php $current = $asset->allocations->first(); @endphp
                            <td>
                                @if ($current)
                                    <span class="badge {{ $current->allocation_type === 'employee' ? 'badge-secondary' : 'badge-success' }}">{{ \App\Models\AssetAllocation::TYPES[$current->allocation_type] ?? $current->allocation_type }}</span>
                                    <div style="margin-top: 0.25rem;">{{ $current->allocated_to }}</div>
                                @elseif ($asset->location)
                                    {{-- assets registered before allocations existed keep their old free-text location --}}
                                    {{ $asset->location }}
                                @else
                                    <span style="color: var(--text-muted);">Unallocated</span>
                                @endif
                            </td>
                            <td class="text-right" style="font-weight: 500;" data-order="{{ $asset->cost }}">{{ number_format($asset->cost, 2) }}</td>
                            @php
                                $accumulated = $asset->accumulatedDepreciation();
                                $bookValue = $asset->netBookValue();
                            @endphp
                            <td class="text-right" data-order="{{ $accumulated }}">{{ number_format($accumulated, 2) }}</td>
                            <td class="text-right" style="font-weight: 500;" data-order="{{ $bookValue }}">{{ number_format($bookValue, 2) }}</td>
                            <td>{{ $asset->created_at ? $asset->created_at->format('Y-m-d') : '-' }}</td>
                            {{-- NEW: opens the Allocate dialog (script at the bottom of this page) --}}
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary" data-allocate
                                        data-action="{{ route('assets.allocate', $asset->id) }}"
                                        data-asset="{{ $asset->asset_code }} - {{ trim(($asset->brand?->name ?? $asset->brand_name) . ' ' . $asset->model) }}"
                                        data-current="{{ $current?->label ?? ($asset->location ?: 'Unallocated') }}"
                                        data-history="{{ $asset->allocations->map(fn ($a) => [
                                            'label' => $a->label,
                                            'by' => $a->allocated_by,
                                            'at' => $a->created_at?->format('Y-m-d H:i'),
                                        ])->values()->toJson() }}">Allocate</button>
                                {{-- One label for this asset; the script adds the toolbar's "Start at label" value. --}}
                                <a href="{{ route('assets.labels', $labelQuery + ['asset' => $asset->id]) }}" target="_blank" rel="noopener"
                                   class="btn btn-secondary" data-label-asset="{{ $asset->id }}" title="Print a label for {{ $asset->asset_code }}">Label</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- NEW: Allocate dialog. One shared dialog; the row's "Allocate" button fills in the asset, form URL and history. --}}
    <div id="allocate-modal" class="modal-overlay" hidden>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="allocate-title">
            <h3 id="allocate-title">Allocate Asset</h3>
            <p id="allocate-asset" class="modal-sub"></p>

            <form id="allocate-form" method="POST" action="">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="allocation_type">Allocate to <span class="required">*</span></label>
                    <select name="allocation_type" id="allocation_type" class="form-select" required>
                        @foreach (\App\Models\AssetAllocation::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="allocated_to" id="allocated_to_label">Branch <span class="required">*</span></label>
                    <input type="text" name="allocated_to" id="allocated_to" class="form-control" maxlength="150" required autocomplete="off">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-allocate-cancel>Cancel</button>
                    <button type="submit" class="btn btn-primary">Allocate</button>
                </div>
            </form>

            <div class="modal-history">
                <div class="form-label">Allocation history</div>
                <ul id="allocate-history"></ul>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var costInput = document.getElementById('cost');
            var lifeInput = document.getElementById('useful_life_months');
            var monthlyInput = document.getElementById('monthly_depreciation');
            var residualOutput = document.getElementById('derived_residual_value');
            var residualHint = document.getElementById('derived_residual_hint');

            if (costInput && lifeInput && monthlyInput && residualOutput) {
                function updateResidualPreview() {
                    var cost = parseFloat(costInput.value);
                    var months = parseInt(lifeInput.value, 10);
                    var monthly = parseFloat(monthlyInput.value);

                    if (isNaN(cost) || isNaN(months) || isNaN(monthly) || months <= 0) {
                        residualOutput.value = '—';
                        residualOutput.style.color = '';
                        if (residualHint) {
                            residualHint.textContent = 'Calculated: Cost − (Monthly Depreciation × Useful Life)';
                            residualHint.style.color = '';
                        }
                        return;
                    }

                    var residual = Math.round((cost - (monthly * months)) * 100) / 100;
                    residualOutput.value = residual.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    if (residual < 0) {
                        residualOutput.style.color = 'var(--danger)';
                        if (residualHint) {
                            residualHint.textContent = 'Invalid: total depreciation exceeds cost (cannot depreciate below zero).';
                            residualHint.style.color = 'var(--danger)';
                        }
                    } else {
                        residualOutput.style.color = '';
                        if (residualHint) {
                            residualHint.textContent = 'Calculated: Cost − (Monthly Depreciation × Useful Life)';
                            residualHint.style.color = '';
                        }
                    }
                }

                costInput.addEventListener('input', updateResidualPreview);
                lifeInput.addEventListener('input', updateResidualPreview);
                monthlyInput.addEventListener('input', updateResidualPreview);

                var regForm = costInput.closest('form');
                if (regForm) {
                    regForm.addEventListener('reset', function () {
                        setTimeout(updateResidualPreview, 0);
                    });
                }

                updateResidualPreview();
            }
        })();

        (function () {
            var typeSelect = document.getElementById('asset_type_id');
            var subTypeSelect = document.getElementById('asset_sub_type_id');
            if (!typeSelect || !subTypeSelect) {
                return;
            }

            function filterSubTypes(resetSelection) {
                var selectedTypeId = typeSelect.value;
                var currentSubVal = subTypeSelect.value;
                var hasValidSelection = false;

                for (var i = 0; i < subTypeSelect.options.length; i++) {
                    var opt = subTypeSelect.options[i];
                    if (!opt.value) {
                        continue;
                    }
                    var matches = !!(selectedTypeId && opt.getAttribute('data-asset-type-id') === selectedTypeId);
                    opt.hidden = !matches;
                    opt.disabled = !matches;
                    opt.style.display = matches ? '' : 'none';
                    if (matches && opt.value === currentSubVal) {
                        hasValidSelection = true;
                    }
                }

                if (resetSelection || (!hasValidSelection && currentSubVal)) {
                    subTypeSelect.value = '';
                }
            }

            typeSelect.addEventListener('change', function () {
                filterSubTypes(true);
            });

            var form = typeSelect.closest('form');
            if (form) {
                form.addEventListener('reset', function () {
                    setTimeout(function () {
                        filterSubTypes(true);
                    }, 0);
                });
            }

            // Initial filter on page load (preserves old() selection if matching)
            filterSubTypes(false);
        })();

        (function () {
            var modal = document.getElementById('allocate-modal');
            var form = document.getElementById('allocate-form');
            var typeSelect = document.getElementById('allocation_type');
            var valueInput = document.getElementById('allocated_to');
            var valueLabel = document.getElementById('allocated_to_label');
            var historyList = document.getElementById('allocate-history');

            var hints = {
                branch: { label: 'Branch name', placeholder: 'e.g. Chilaw' },
                employee: { label: 'Employee number', placeholder: 'e.g. EMP-001' }
            };

            function updateHint() {
                var hint = hints[typeSelect.value] || hints.branch;
                valueLabel.innerHTML = hint.label + ' <span class="required">*</span>';
                valueInput.placeholder = hint.placeholder;
            }

            function closeModal() { modal.hidden = true; }

            // Delegated, because DataTables only keeps the rows of the current page in the document.
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-allocate]');
                if (!btn) { return; }

                form.action = btn.dataset.action;
                document.getElementById('allocate-asset').textContent = btn.dataset.asset + '  |  Currently: ' + btn.dataset.current;
                typeSelect.value = 'branch';
                valueInput.value = '';
                updateHint();

                historyList.innerHTML = '';
                var history = JSON.parse(btn.dataset.history || '[]');
                if (!history.length) {
                    historyList.innerHTML = '<li class="muted">No allocations yet.</li>';
                }
                history.forEach(function (h) {
                    var li = document.createElement('li');
                    li.textContent = h.label + ' - ' + (h.at || '') + (h.by ? ' by ' + h.by : '');
                    historyList.appendChild(li);
                });

                modal.hidden = false;
                valueInput.focus();
            });

            typeSelect.addEventListener('change', updateHint);
            modal.querySelector('[data-allocate-cancel]').addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });
        })();

        // A row's "Label" button prints that one asset through the toolbar's Print Labels form, so it carries the
        // same filter and whatever "Start at label" value is in the box. (Without JS the link still works, from slot 1.)
        (function () {
            var form = document.querySelector('[data-label-form]');
            if (!form) { return; }
            var assetInput = form.querySelector('[data-label-asset-input]');

            document.addEventListener('click', function (e) {
                var link = e.target.closest('[data-label-asset]');
                if (!link) { return; }
                e.preventDefault();

                assetInput.value = link.dataset.labelAsset;
                assetInput.disabled = false;
                form.requestSubmit();   // runs the start box's min / max check like the bulk button
                setTimeout(function () { assetInput.disabled = true; assetInput.value = ''; }, 0);
            });
        })();
    </script>
</x-app-layout>
