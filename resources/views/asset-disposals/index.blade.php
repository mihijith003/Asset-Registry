<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Asset Disposal') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Asset Disposal Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Record Asset Disposal</h3>
                    <p class="text-xs text-slate-500">Record retirement or scrapping of obsolete assets.</p>
                </div>
            </div>

            <form action="{{ route('asset-disposals.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Disposal ID -->
                    <div>
                        <label for="disposal_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Disposal ID <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="disposal_id" 
                               name="disposal_id" 
                               value="{{ old('disposal_id') }}"
                               placeholder="e.g. DISP-001"
                               maxlength="50"
                               required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('disposal_id')
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

                    <!-- Method -->
                    <div>
                        <label for="method" class="block text-sm font-medium text-slate-700 mb-1">
                            Method <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="method" 
                               name="method" 
                               value="{{ old('method') }}"
                               placeholder="e.g. Recycled, Scrapped, Donated"
                               maxlength="100"
                               required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('method')
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
                        Record Disposal
                    </button>
                </div>
            </form>
        </div>

        <!-- Disposals Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Disposal Records</h3>
                    <p class="text-xs text-slate-500">History of assets that have been disposed or retired.</p>
                </div>
                <span class="px-3 py-1 bg-slate-200 text-slate-700 text-xs font-semibold rounded-full">
                    Total: {{ $disposals->total() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Disposal ID</th>
                            <th class="px-6 py-3">Asset Code</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Disposal Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($disposals as $disposal)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-semibold text-indigo-600">
                                    {{ $disposal->disposal_id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">
                                        {{ $disposal->asset->asset_code ?? 'Asset #' . $disposal->asset_id }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $disposal->asset->brand_name ?? '' }} {{ $disposal->asset->model ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-700 font-medium">
                                    {{ $disposal->method }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    {{ $disposal->disposal_date ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    No disposals recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($disposals->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $disposals->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

