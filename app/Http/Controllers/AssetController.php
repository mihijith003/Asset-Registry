<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetController extends Controller
{
    /**
     * Display a listing of assets and registration form.
     */
    public function index(): View
    {
        // CHANGED: uses the active() scope (also hides assets merged into a combined asset) and
        // loads "components" so combined assets can show what they were made from.
        // $assets = Asset::with('assetType')
        //     ->doesntHave('sale')
        //     ->doesntHave('disposal')
        //     ->orderByDesc('id')
        //     ->paginate(10);
        $assets = Asset::with(['assetType', 'components', 'allocations']) // NEW: allocations (newest first = current + history)
            ->active()
            ->orderByDesc('id')
            ->get(); // CHANGED: DataTables does the paging/search/sort in the browser

        $assetTypes = AssetType::orderBy('type_name')->get();
        $assetSubTypes = \App\Models\AssetSubType::all();

        return view('assets.index', compact('assets', 'assetTypes', 'assetSubTypes'));
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'asset_sub_type_id' => ['required', 'exists:asset_sub_types,id'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'financial_year' => ['nullable', 'string', 'max:9'],
            // CHANGED: location is no longer entered at registration - assets are allocated afterwards
            // (branch or employee) from the registry table.
            // 'location' => ['nullable', 'string', 'max:150'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        if (empty($validated['asset_code'])) {
            $validated['asset_code'] = 'AST-' . date('Y') . '-' . strtoupper(Str::random(5));
        }

        Asset::create($validated);

        return redirect()->route('assets.index')->with('success', 'Asset registered successfully.');
    }
}

