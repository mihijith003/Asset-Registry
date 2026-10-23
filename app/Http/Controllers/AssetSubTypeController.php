<?php

namespace App\Http\Controllers;

use App\Models\AssetSubType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetSubTypeController extends Controller
{
    /**
     * Store a newly created asset sub type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('assetSubType', [
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'sub_type_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_sub_types', 'sub_type_name')
                    ->where(fn ($q) => $q->where('asset_type_id', $request->asset_type_id)),
            ],
            'sub_type_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('asset_sub_types', 'sub_type_code')
                    ->where(fn ($q) => $q->where('asset_type_id', $request->asset_type_id)),
            ],
            'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $validated['sub_type_code'] = strtoupper(trim($validated['sub_type_code']));
        $validated['sub_type_name'] = trim($validated['sub_type_name']);
        $validated['useful_life_years'] = ! empty($validated['useful_life_years']) ? (int) $validated['useful_life_years'] : null;

        AssetSubType::create($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'sub-types')
            ->with('success', 'Asset sub type added successfully.');
    }

    /**
     * Update the specified asset sub type.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $subType = AssetSubType::findOrFail($id);

        $validated = $request->validateWithBag('assetSubType', [
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'sub_type_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_sub_types', 'sub_type_name')
                    ->where(fn ($q) => $q->where('asset_type_id', $request->asset_type_id))
                    ->ignore($subType->id),
            ],
            'sub_type_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('asset_sub_types', 'sub_type_code')
                    ->where(fn ($q) => $q->where('asset_type_id', $request->asset_type_id))
                    ->ignore($subType->id),
            ],
            'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $validated['sub_type_code'] = strtoupper(trim($validated['sub_type_code']));
        $validated['sub_type_name'] = trim($validated['sub_type_name']);
        $validated['useful_life_years'] = ! empty($validated['useful_life_years']) ? (int) $validated['useful_life_years'] : null;

        $subType->update($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'sub-types')
            ->with('success', 'Asset sub type updated successfully.');
    }

    /**
     * Remove the specified asset sub type if no assets reference it.
     */
    public function destroy($id): RedirectResponse
    {
        $subType = AssetSubType::findOrFail($id);

        $assetCount = $subType->assets()->count();

        if ($assetCount > 0) {
            $countLabel = $assetCount . ' ' . ($assetCount === 1 ? 'asset' : 'assets');

            return redirect()->route('master-files.index')
                ->with('active_tab', 'sub-types')
                ->with('error', "Cannot delete asset sub type: it is still in use by {$countLabel}.");
        }

        $subType->delete();

        return redirect()->route('master-files.index')
            ->with('active_tab', 'sub-types')
            ->with('success', 'Asset sub type deleted successfully.');
    }
}
