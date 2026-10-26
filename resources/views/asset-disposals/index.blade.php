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
                        <select name="method" id="method" class="form-control" required>
                            <option value="">-- Select Disposal Method --</option>
                            <option value="Donated" {{ old('method') == 'Donated' ? 'selected' : '' }}>Donated</option>
                            <option value="Scrapped" {{ old('method') == 'Scrapped' ? 'selected' : '' }}>Scrapped</option>
                            <option value="Lost" {{ old('method') == 'Lost' ? 'selected' : '' }}>Lost / Missing</option>
                        </select>
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

    <!-- Pending Approvals Table -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h3>Pending Approvals</h3>
            <p>Lost items requiring admin authorization before removal from the registry.</p>
        </div>
        <span style="font-size: 0.875rem; color: var(--text-muted);">
            Action Required
        </span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Disposal ID</th>
                    <th>Asset Code</th>
                    <th>Reported Date</th>
                    <th class="no-sort" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingDisposals as $pending)
                    <tr>
                        <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                            {{ $pending->reference }}
                        </td>
                        <td>
                            <div>
                                @if ($pending->asset)
                                    <a href="{{ route('assets.timeline', $pending->asset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $pending->asset->asset_code }}</a>
                                @else
                                    Asset #{{ $pending->asset_id }}
                                @endif
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $pending->asset->brand_name ?? '' }} {{ $pending->asset->model ?? '' }}
                            </div>
                        </td>
                        <td style="font-size: 0.875rem; color: var(--text-muted);">
                            {{ $pending->disposal_date ?? '-' }}
                        </td>
                        <td style="text-align: right;">
                            {{-- Both actions ask for an admin password (<x-admin-password-modal>) before submitting --}}
                            <form id="approve-disposal-{{ $pending->id }}" action="{{ route('asset-disposals.approve', $pending->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm" style="background-color: var(--primary); color: white; border: none; padding: 0.25rem 0.75rem; border-radius: 4px;">Approve</button>
                            </form>
                            <x-admin-password-modal form="approve-disposal-{{ $pending->id }}" />

                            <form id="reject-disposal-{{ $pending->id }}" action="{{ route('asset-disposals.reject', $pending->id) }}" method="POST" style="display:inline-flex; gap: 0.5rem; align-items: center; margin-left: 0.5rem;">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="rejection_reason" class="form-control" required maxlength="500"
                                       placeholder="Reason for rejecting" aria-label="Reason for rejecting {{ $pending->reference }}" style="width: 220px;">
                                <button type="submit" class="btn btn-sm" style="background-color: #dc3545; color: white; border: none; padding: 0.25rem 0.75rem; border-radius: 4px;">Reject</button>
                            </form>
                            <x-admin-password-modal form="reject-disposal-{{ $pending->id }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                            No pending approvals at this time.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>



    <!-- Disposals Table -->
    @include('partials.type-filter', ['fields' => $filterFields])

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Disposal Records</h3>
                <p>History of assets that have been disposed or retired.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.875rem; color: var(--text-muted);">Total: {{ $disposals->count() }}</span>
                @include('partials.print-summary-button', ['route' => 'asset-disposals.print-summary'])
            </div>
        </div>

        <div class="table-responsive">
            {{-- CHANGED: DataTables (search / sort / paging) is applied to this table by resources/js/app.js --}}
            <table class="table" data-datatable data-empty="No disposals recorded yet.">
                <thead>
                    <tr>
                        <th>Disposal ID</th>
                        <th>Asset Code</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Disposal Date</th>
                        {{-- NEW: shows the admin who authorized the disposal --}}
                        <th>Authorized By</th>
                        <th>Comments</th>
                        {{-- NEW: print action column --}}
                        <th class="no-sort" style="text-align: right;">Document</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($disposals as $disposal)
                        <tr @if ($disposal->approval_status === 'rejected') style="color: var(--text-muted);" @endif>
                            <td style="font-family: monospace; font-weight: 600; color: var(--primary);">
                                {{ $disposal->reference }}
                            </td>
                            <td>
                                <div>
                                    @if ($disposal->asset)
                                        <a href="{{ route('assets.timeline', $disposal->asset->id) }}" style="font-family: monospace; font-weight: 600; color: var(--primary);">{{ $disposal->asset->asset_code }}</a>
                                    @else
                                        Asset #{{ $disposal->asset_id }}
                                    @endif
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $disposal->asset->brand_name ?? '' }} {{ $disposal->asset->model ?? '' }}
                                </div>
                            </td>
                            <td style="font-weight: 500;">
                                {{ $disposal->method }}
                            </td>
                            <td>
                                @if ($disposal->approval_status === 'rejected')
                                    {{-- kept as history: the claim was turned down and the asset stays in the registry --}}
                                    <span class="badge badge-secondary" style="text-decoration: line-through;">Rejected</span>
                                    @if ($disposal->rejection_reason)
                                        <div style="font-size: 0.75rem; margin-top: 0.25rem; max-width: 220px;">Reason: {{ $disposal->rejection_reason }}</div>
                                    @endif
                                @elseif ($disposal->approval_status === 'pending')
                                    <span class="badge badge-danger">Pending</span>
                                @else
                                    <span class="badge badge-success">Approved</span>
                                @endif
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);">
                                {{ $disposal->disposal_date ?? '-' }}
                            </td>
                            {{-- NEW: authorized-by cell ('-' for older rows recorded before this column existed) --}}
                            <td>
                                {{ $disposal->authorized_by ?? '-' }}
                            </td>
                            <td style="white-space: pre-line; max-width: 260px;">{{ $disposal->notes ?: '-' }}</td>
                            {{-- NEW: opens the printable disposal record in a new tab --}}
                            <td style="text-align: right;">
                                <a href="{{ route('asset-disposals.print', $disposal) }}" target="_blank" rel="noopener" class="btn btn-secondary">Print</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
