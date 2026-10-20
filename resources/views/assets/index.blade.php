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
                        {{-- CHANGED: dropdown instead of free text
                        <input type="text" name="financial_year" value="{{ old('financial_year') }}" class="form-control" placeholder="e.g. 2024/2025" maxlength="9">
                        --}}
                        <select name="financial_year" class="form-select">
                            <option value="">-- Select Financial Year --</option>
                            @foreach ($financialYears as $year)
                                <option value="{{ $year }}" {{ old('financial_year', $currentFinancialYear) === $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cost (LKR) <span class="required">*</span></label>
                        <input type="number" name="cost" value="{{ old('cost') }}" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Residual Value (LKR)</label>
                        <input type="number" name="residual_value" value="{{ old('residual_value', 0) }}" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Useful Life (months)</label>
                        <input type="number" name="useful_life_months" value="{{ old('useful_life_months') }}" class="form-control" min="1" placeholder="e.g. 36">
                        <small class="form-hint">Leave blank to use the sub type's default life</small>
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
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Registered Assets</h3>
                <p>Overview of all active assets in the database.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $assets->count() }}</span>
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
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $asset->asset_code }}</td>
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
                                            Originally came with {{ $asset->originAsset->asset_code }} ({{ trim(($asset->originAsset->brand?->name ?? $asset->originAsset->brand_name) . ' ' . $asset->originAsset->model) }}).
                                        @endif
                                        Removed from {{ $asset->removalModification->asset->asset_code ?? 'Asset #' . $asset->removalModification->asset_id }}
                                        in {{ $asset->removalModification->reference }} ({{ $asset->removalModification->modification_date }}).
                                    </div>
                                @endif
                                @if ($asset->components->isNotEmpty())
                                    <div style="margin-top: 0.25rem;">
                                        <span class="badge badge-secondary">Combined asset</span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        Combination of {{ $asset->components->count() }} previous assets:
                                        {{ $asset->components->map(fn ($c) => $c->asset_code . ' (' . trim(($c->brand?->name ?? $c->brand_name) . ' ' . $c->model) . ')')->join(' + ') }}
                                    </div>
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
                                $bookValue = round((float) $asset->cost - $accumulated, 2);
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
    </script>
</x-app-layout>
