<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetTypeController extends Controller
{
    /**
     * Store a newly created asset type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('assetType', [
            'type_name' => ['required', 'string', 'max:100', 'unique:asset_types,type_name'],
            'type_code' => ['required', 'string', 'max:10', 'unique:asset_types,type_code'],
        ]);

        $validated['type_code'] = strtoupper(trim($validated['type_code']));
        $validated['type_name'] = trim($validated['type_name']);

        AssetType::create($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'asset-types')
            ->with('success', 'Asset type added successfully.');
    }

    /**
     * Update the specified asset type.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $assetType = AssetType::findOrFail($id);

        $validated = $request->validateWithBag('assetType', [
            'type_name' => ['required', 'string', 'max:100', Rule::unique('asset_types', 'type_name')->ignore($assetType->id)],
            'type_code' => ['required', 'string', 'max:10', Rule::unique('asset_types', 'type_code')->ignore($assetType->id)],
        ]);

        $validated['type_code'] = strtoupper(trim($validated['type_code']));
        $validated['type_name'] = trim($validated['type_name']);

        $assetType->update($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'asset-types')
            ->with('success', 'Asset type updated successfully.');
    }

    /**
     * Remove the specified asset type if it has no dependent assets or sub types.
     */
    public function destroy($id): RedirectResponse
    {
        $assetType = AssetType::findOrFail($id);

        $assetCount = $assetType->assets()->count();
        $subTypeCount = $assetType->subTypes()->count();

        if ($assetCount > 0 || $subTypeCount > 0) {
            $messages = [];
            if ($subTypeCount > 0) {
                $messages[] = $subTypeCount . ' ' . ($subTypeCount === 1 ? 'sub type' : 'sub types');
            }
            if ($assetCount > 0) {
                $messages[] = $assetCount . ' ' . ($assetCount === 1 ? 'asset' : 'assets');
            }

            return redirect()->route('master-files.index')
                ->with('active_tab', 'asset-types')
                ->with('error', 'Cannot delete asset type: it is still in use by ' . implode(' and ', $messages) . '.');
        }

        $assetType->delete();

        return redirect()->route('master-files.index')
            ->with('active_tab', 'asset-types')
            ->with('success', 'Asset type deleted successfully.');
    }
}
