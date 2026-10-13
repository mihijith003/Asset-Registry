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
                        <label class="form-label">Asset Type <span class="required">*</span></label>
                        <select name="asset_type_id" class="form-select" required>
                            <option value="">-- Select Asset Type --</option>
                            @foreach ($assetTypes as $type)
                                <option value="{{ $type->id }}" {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }} ({{ $type->type_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asset Sub Type <span class="required">*</span></label>
                        <select name="asset_sub_type_id" class="form-select" required>
                            <option value="">-- Select Asset Sub Type --</option>
                            @foreach ($assetSubTypes as $subType)
                                <option value="{{ $subType->id }}" {{ old('asset_sub_type_id') == $subType->id ? 'selected' : '' }}>
                                    {{ $subType->sub_type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Brand Name</label>
                        <input type="text" name="brand_name" value="{{ old('brand_name') }}" class="form-control" placeholder="e.g. Dell" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="form-control" placeholder="e.g. Latitude 5420" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Financial Year</label>
                        <input type="text" name="financial_year" value="{{ old('financial_year') }}" class="form-control" placeholder="e.g. 2024/2025" maxlength="9">
                    </div>

                    {{-- CHANGED: "Location" input removed - assets are allocated to a branch or an employee
                         after registering, using the "Allocate" action in the table below.
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="e.g. Room 302" maxlength="150">
                    </div>
                    --}}

                    <div class="form-group">
                        <label class="form-label">Cost (LKR) <span class="required">*</span></label>
                        <input type="number" name="cost" value="{{ old('cost') }}" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>
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
                                {{ $asset->brand_name ?? '-' }} {{ $asset->model ? '(' . $asset->model . ')' : '' }}
                                {{-- NEW: flag combined assets and say which assets they were made from --}}
                                @if ($asset->components->isNotEmpty())
                                    <div style="margin-top: 0.25rem;">
                                        <span class="badge badge-secondary">Combined asset</span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        Combination of {{ $asset->components->count() }} previous assets:
                                        {{ $asset->components->map(fn ($c) => $c->asset_code . ' (' . trim($c->brand_name . ' ' . $c->model) . ')')->join(' + ') }}
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
                            <td>{{ $asset->created_at ? $asset->created_at->format('Y-m-d') : '-' }}</td>
                            {{-- NEW: opens the Allocate dialog (script at the bottom of this page) --}}
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary" data-allocate
                                        data-action="{{ route('assets.allocate', $asset->id) }}"
                                        data-asset="{{ $asset->asset_code }} - {{ trim($asset->brand_name . ' ' . $asset->model) }}"
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
