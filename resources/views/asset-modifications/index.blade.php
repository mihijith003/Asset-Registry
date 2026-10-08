<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Asset Modification') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Asset Modification Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Record Asset Modification</h3>
                    <p class="text-xs text-slate-500">Record physical or valuation changes to an existing asset.</p>
                </div>
            </div>

            <form action="{{ route('asset-modifications.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Modification ID -->
                    <div>
                        <label for="modification_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Modification ID <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="modification_id" 
                               name="modification_id" 
                               value="{{ old('modification_id') }}"
                               placeholder="e.g. MOD-001"
                               maxlength="50"
                               required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('modification_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Asset ID -->
                    <div>
                        <label for="asset_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Asset <span class="text-rose-500">*</span>
                        </label>
                        <select id="asset_id" 
                                name="asset_id" 
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">-- Select Asset --</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_code }} - {{ $asset->brand_name }} {{ $asset->model }}
                                </option>
                            @endforeach
                        </select>
                        @error('asset_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Modification Date -->
                    <div>
                        <label for="modification_date" class="block text-sm font-medium text-slate-700 mb-1">
                            Modification Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               id="modification_date" 
                               name="modification_date" 
                               value="{{ old('modification_date', date('Y-m-d')) }}"
                               required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('modification_date')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Authorized By -->
                    <div>
                        <label for="authorized_by" class="block text-sm font-medium text-slate-700 mb-1">
                            Authorized By
                        </label>
                        <input type="text" 
                               id="authorized_by" 
                               name="authorized_by" 
                               value="{{ old('authorized_by') }}"
                               placeholder="e.g. Officer Name"
                               maxlength="150"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('authorized_by')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Removed Asset -->
                    <div>
                        <label for="removed_asset" class="block text-sm font-medium text-slate-700 mb-1">
                            Removed Asset / Component
                        </label>
                        <input type="text" 
                               id="removed_asset" 
                               name="removed_asset" 
                               value="{{ old('removed_asset') }}"
                               placeholder="e.g. 8GB RAM, Old Battery"
                               maxlength="150"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('removed_asset')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Added Asset -->
                    <div>
                        <label for="added_asset" class="block text-sm font-medium text-slate-700 mb-1">
                            Added Asset / Component
                        </label>
                        <input type="text" 
                               id="added_asset" 
                               name="added_asset" 
                               value="{{ old('added_asset') }}"
                               placeholder="e.g. 16GB RAM, New Battery"
                               maxlength="150"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('added_asset')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Value -->
                    <div>
                        <label for="new_value" class="block text-sm font-medium text-slate-700 mb-1">
                            New Value (LKR)
                        </label>
                        <input type="number" 
                               id="new_value" 
                               name="new_value" 
                               value="{{ old('new_value') }}"
                               step="0.01" 
                               min="0"
                               placeholder="0.00"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('new_value')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Location -->
                    <div>
                        <label for="new_location" class="block text-sm font-medium text-slate-700 mb-1">
                            New Location
                        </label>
                        <input type="text" 
                               id="new_location" 
                               name="new_location" 
                               value="{{ old('new_location') }}"
                               placeholder="e.g. Branch B - Lab 1"
                               maxlength="100"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('new_location')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="reset" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Clear
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Record Modification
                    </button>
                </div>
            </form>
        </div>

        <!-- Modifications Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Modification History</h3>
                    <p class="text-xs text-slate-500">Record of all asset modifications and upgrades.</p>
                </div>
                <span class="px-3 py-1 bg-slate-200 text-slate-700 text-xs font-semibold rounded-full">
                    Total: {{ $modifications->total() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Mod ID</th>
                            <th class="px-6 py-3">Asset</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Removed Component</th>
                            <th class="px-6 py-3">Added Component</th>
                            <th class="px-6 py-3 text-right">New Value</th>
                            <th class="px-6 py-3">New Location</th>
                            <th class="px-6 py-3">Authorized By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($modifications as $mod)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-semibold text-indigo-600">
                                    {{ $mod->modification_id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">{{ $mod->asset->asset_code ?? 'Asset #' . $mod->asset_id }}</div>
                                    <div class="text-xs text-slate-500">{{ $mod->asset->brand_name ?? '' }} {{ $mod->asset->model ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-xs">
                                    {{ $mod->modification_date }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $mod->removed_asset ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $mod->added_asset ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-900">
                                    {{ $mod->new_value !== null ? number_format($mod->new_value, 2) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $mod->new_location ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $mod->authorized_by ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    No modifications recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($modifications->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $modifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

