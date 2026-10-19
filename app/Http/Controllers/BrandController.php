<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Asset;
use Illuminate\Http\Request;


class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('name')->get();
        return view('brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
        ]);

        Brand::create($validated);
        return back()->with('success', 'Brand added successfully.');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name,' . $brand->id],
        ]);

        $brand->update($validated);
        return back()->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        // Safety Check: Do not delete if assets are actively using this brand.
        // Update 'brand_id' if your assets table uses a different column name for the foreign key.
        if (Asset::where('brand_id', $brand->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete brand: It is currently assigned to one or more assets.']);
        }

        $brand->delete();
        return back()->with('success', 'Brand deleted successfully.');
    }
}
