<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Asset Sale') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Asset Sale Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Record Asset Sale</h3>
                    <p class="text-xs text-slate-500">Record monetization or sale of an asset.</p>
                </div>
            </div>

            <form action="{{ route('asset-sales.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Sale ID -->
                    <div>
                        <label for="sale_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Sale ID <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="sale_id" 
                               name="sale_id" 
                               value="{{ old('sale_id') }}"
                               placeholder="e.g. SALE-001"
                               maxlength="50"
                               required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('sale_id')
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

                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-slate-700 mb-1">
                            Price (LKR) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               value="{{ old('price') }}"
                               step="0.01" 
                               min="0"
                               required
                               placeholder="0.00"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('price')
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
                               placeholder="e.g. Finance Manager"
                               maxlength="100"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('authorized_by')
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
                        Record Sale
                    </button>
                </div>
            </form>
        </div>

        <!-- Sales Records Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Sales Records</h3>
                    <p class="text-xs text-slate-500">History of assets that have been sold.</p>
                </div>
                <span class="px-3 py-1 bg-slate-200 text-slate-700 text-xs font-semibold rounded-full">
                    Total: {{ $sales->total() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Sale ID</th>
                            <th class="px-6 py-3">Asset Code</th>
                            <th class="px-6 py-3 text-right">Price</th>
                            <th class="px-6 py-3">Authorized By</th>
                            <th class="px-6 py-3">Sale Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-semibold text-indigo-600">
                                    {{ $sale->sales_ID ?? $sale->sale_id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">
                                        {{ $sale->asset->asset_code ?? 'Asset #' . $sale->asset_id }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $sale->asset->brand_name ?? '' }} {{ $sale->asset->model ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-900">
                                    {{ number_format($sale->price, 2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $sale->authorized_BY ?? ($sale->authorized_by ?? '-') }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    {{ $sale->sale_date ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No sales recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

