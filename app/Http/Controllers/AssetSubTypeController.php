<?php

namespace App\Http\Controllers;

use App\Models\AssetSubType;
use App\Models\AssetType;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetSubTypeController extends Controller
{
    public function index()
    {
        // Load subtypes with their parent types for the table, and load types for the form dropdowns
        $subTypes = AssetSubType::with('assetType')->orderBy('sub_type_name')->get();
        $assetTypes = AssetType::orderBy('name')->get();
        
        return view('asset-subtypes.index', compact('subTypes', 'assetTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'sub_type_name' => ['required', 'string', 'max:100'],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
        ]);

        AssetSubType::create($validated);
        return back()->with('success', 'Asset subtype added successfully.');
    }

    public function update(Request $request, AssetSubType $assetSubType)
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'sub_type_name' => ['required', 'string', 'max:100'],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
        ]);

        $assetSubType->update($validated);
        return back()->with('success', 'Asset subtype updated successfully.');
    }

    public function destroy(AssetSubType $assetSubType)
    {
        // Safety Check: Do not delete if assets are actively using this subtype.
        if (Asset::where('asset_sub_type_id', $assetSubType->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete subtype: It is currently assigned to one or more assets.']);
        }

        $assetSubType->delete();
        return back()->with('success', 'Asset subtype deleted successfully.');
    }
}