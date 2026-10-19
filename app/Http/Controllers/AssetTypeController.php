<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use App\Models\AssetSubType;
use Illuminate\Http\Request;

class AssetTypeController extends Controller
{
    public function index()
    {
        $assetTypes = AssetType::orderBy('name')->get();
        return view('asset-types.index', compact('assetTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:asset_types,name'],
        ]);

        AssetType::create($validated);
        return back()->with('success', 'Asset type added successfully.');
    }

    public function update(Request $request, AssetType $assetType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:asset_types,name,' . $assetType->id],
        ]);

        $assetType->update($validated);
        return back()->with('success', 'Asset type updated successfully.');
    }

    public function destroy(AssetType $assetType)
    {
        // Safety Check: Do not delete if this type has subtypes linked to it.
        if (AssetSubType::where('asset_type_id', $assetType->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete asset type: It contains active subtypes.']);
        }

        $assetType->delete();
        return back()->with('success', 'Asset type deleted successfully.');
    }
}