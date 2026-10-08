<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Asset Registry') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Asset Registration Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Register New Asset</h3>
                    <p class="text-xs text-slate-500">Add a new item to the organizational asset registry.</p>
                </div>
            </div>

            <form action="{{ route('assets.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Asset Type Dropdown -->
                    <div>
                        <label for="asset_type_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Asset Type <span class="text-rose-500">*</span>
                        </label>
                        <select id="asset_type_id" 
                                name="asset_type_id" 
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">-- Select Asset Type --</option>
                            @foreach ($assetTypes as $type)
                                <option value="{{ $type->id }}" {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }} ({{ $type->type_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('asset_type_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Brand Name -->
                    <div>
                        <label for="brand_name" class="block text-sm font-medium text-slate-700 mb-1">
                            Brand Name
                        </label>
                        <input type="text" 
                               id="brand_name" 
                               name="brand_name" 
                               value="{{ old('brand_name') }}"
                               placeholder="e.g. Dell, Apple, Toyota"
                               maxlength="100"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('brand_name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Model -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-slate-700 mb-1">
                            Model
                        </label>
                        <input type="text" 
                               id="model" 
                               name="model" 
                               value="{{ old('model') }}"
                               placeholder="e.g. Latitude 5420, Camry"
                               maxlength="100"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('model')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Financial Year -->
                    <div>
                        <label for="financial_year" class="block text-sm font-medium text-slate-700 mb-1">
                            Financial Year
                        </label>
                        <input type="text" 
                               id="financial_year" 
                               name="financial_year" 
                               value="{{ old('financial_year') }}"
                               placeholder="e.g. 2024/2025"
                               maxlength="9"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('financial_year')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-slate-700 mb-1">
                            Location
                        </label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               value="{{ old('location') }}"
                               placeholder="e.g. Head Office - Room 302"
                               maxlength="150"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('location')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cost -->
                    <div>
                        <label for="cost" class="block text-sm font-medium text-slate-700 mb-1">
                            Cost (LKR) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               id="cost" 
                               name="cost" 
                               value="{{ old('cost') }}"
                               step="0.01" 
                               min="0"
                               required
                               placeholder="0.00"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('cost')
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
                        Register Asset
                    </button>
                </div>
            </form>
        </div>

        <!-- Registered Assets Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Registered Assets</h3>
                    <p class="text-xs text-slate-500">Overview of all active assets in the database.</p>
                </div>
                <span class="px-3 py-1 bg-slate-200 text-slate-700 text-xs font-semibold rounded-full">
                    Total: {{ $assets->total() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Asset Code</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Brand & Model</th>
                            <th class="px-6 py-3">Financial Year</th>
                            <th class="px-6 py-3">Location</th>
                            <th class="px-6 py-3 text-right">Cost</th>
                            <th class="px-6 py-3">Registered At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-semibold text-indigo-600">
                                    {{ $asset->asset_code }}
                                </td>
                                <td class="px-6 py-4 text-slate-800 font-medium">
                                    {{ $asset->assetType->type_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $asset->brand_name ?? '-' }} {{ $asset->model ? '(' . $asset->model . ')' : '' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $asset->financial_year ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $asset->location ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-900">
                                    {{ number_format($asset->cost, 2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    {{ $asset->created_at ? $asset->created_at->format('Y-m-d') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    No assets registered yet. Fill the form above to add an asset.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assets->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $assets->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

