<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetModification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetModificationController extends Controller
{
    /**
     * Display a listing of asset modifications and the modification form.
     */
    public function index(): View
    {
        $modifications = AssetModification::with('asset')
            ->orderByDesc('id')
            ->paginate(10);

        $assets = Asset::orderBy('asset_code')->get();
        $components = Asset::doesntHave('sale')->doesntHave('disposal')->get();

        return view('asset-modifications.index', compact('modifications', 'assets', 'components'));
    }

    /**
     * Show the form for creating a new asset modification.
     */
    public function create(): View
    {
        $components = Asset::doesntHave('sale')->doesntHave('disposal')->get();
        $assets = Asset::orderBy('asset_code')->get();

        return view('asset-modifications.index', compact('assets', 'components'));
    }

    /**
     * Store a newly created asset modification in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modification_id' => ['required', 'string', 'max:50', 'unique:asset_modifications,modification_id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'component_asset_id' => ['required', 'exists:assets,id'],
            'modification_date' => ['required', 'date'],
            'removed_asset' => ['nullable', 'string', 'max:150'],
            'new_value' => ['nullable', 'numeric', 'min:0'],
            'new_location' => ['nullable', 'string', 'max:100'],
            'authorized_by' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated) {
            $parentAsset = Asset::findOrFail($validated['asset_id']);
            $componentAsset = Asset::findOrFail($validated['component_asset_id']);

            //Calculate the new total cost
            $newTotalCost = $parentAsset->cost + $componentAsset->cost;

            // 3. Update the validated array with the new values BEFORE creating the modification log
            $validated['added_asset'] = trim($componentAsset->asset_code . ' - ' . ($componentAsset->brand_name ?? ''));
            $validated['new_value'] = $newTotalCost;

            AssetModification::create($validated);

            $parentAsset = Asset::findOrFail($validated['asset_id']);
            $componentAsset = Asset::findOrFail($validated['component_asset_id']);

            $parentAsset->cost += $componentAsset->cost;
            $parentAsset->save();

            AssetDisposal::create([
                'disposal_id' => 'DISP-MOD-' . $componentAsset->id . '-' . strtoupper(Str::random(6)),
                'asset_id' => $componentAsset->id,
                'method' => 'Merged into Asset ID: ' . $parentAsset->id,
                'disposal_date' => now()->toDateString(),
            ]);
        });

        return redirect()->route('asset-modifications.index')->with('success', 'Asset modification recorded successfully.');
    }
}

