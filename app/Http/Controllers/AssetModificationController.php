<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetModification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('asset-modifications.index', compact('modifications', 'assets'));
    }

    /**
     * Store a newly created asset modification in storage.
     *
     * CRITICAL: Do NOT write logic to update the assets table here.
     * The database trigger `trg_asset_modifications_after_insert` handles
     * updating `cost` and `location` in `assets` automatically upon insert.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modification_id' => ['required', 'string', 'max:50', 'unique:asset_modifications,modification_id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'modification_date' => ['required', 'date'],
            'removed_asset' => ['nullable', 'string', 'max:150'],
            'added_asset' => ['nullable', 'string', 'max:150'],
            'new_value' => ['nullable', 'numeric', 'min:0'],
            'new_location' => ['nullable', 'string', 'max:100'],
            'authorized_by' => ['nullable', 'string', 'max:150'],
        ]);

        // Handled purely by inserting into asset_modifications.
        // Database trigger trg_asset_modifications_after_insert updates cost & location on assets.
        AssetModification::create($validated);

        return redirect()->route('asset-modifications.index')->with('success', 'Asset modification recorded successfully.');
    }
}

