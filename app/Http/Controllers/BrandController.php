<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    /**
     * Store a newly created brand.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('brand', [
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
        ]);

        $validated['name'] = trim($validated['name']);

        Brand::create($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'brands')
            ->with('success', 'Brand added successfully.');
    }

    /**
     * Update the specified brand.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validateWithBag('brand', [
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($brand->id)],
        ]);

        $validated['name'] = trim($validated['name']);

        $brand->update($validated);

        return redirect()->route('master-files.index')
            ->with('active_tab', 'brands')
            ->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified brand if no assets reference it.
     */
    public function destroy($id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);

        // IMPORTANT: the brand_id foreign key on assets is ON DELETE SET NULL, not RESTRICT,
        // so unlike types and sub types the database will not stop the delete — it would
        // silently blank the brand on every asset using it. The controller check is the only guard.
        $assetCount = $brand->assets()->count();

        if ($assetCount > 0) {
            $countLabel = $assetCount . ' ' . ($assetCount === 1 ? 'asset' : 'assets');

            return redirect()->route('master-files.index')
                ->with('active_tab', 'brands')
                ->with('error', "Cannot delete brand: it is still in use by {$countLabel}.");
        }

        $brand->delete();

        return redirect()->route('master-files.index')
            ->with('active_tab', 'brands')
            ->with('success', 'Brand deleted successfully.');
    }
}
