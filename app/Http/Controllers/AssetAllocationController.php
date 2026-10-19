<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Allocate a registered asset to a branch or to an employee.
class AssetAllocationController extends Controller
{
    public function store(Request $request, int $asset): RedirectResponse
    {
        // Only active assets can be allocated (not sold, disposed or merged into a combined asset).
        $asset = Asset::active()->findOrFail($asset);

        $validated = $request->validate([
            'allocation_type' => ['required', Rule::in(array_keys(AssetAllocation::TYPES))],
            'allocated_to' => ['required', 'string', 'max:150'],
        ]);

        $allocation = $asset->allocations()->create([
            'allocation_type' => $validated['allocation_type'],
            'allocated_to' => trim($validated['allocated_to']),
            'allocated_by' => $request->user()->name,
        ]);

        return redirect()->route('assets.index')
            ->with('success', $asset->asset_code . ' allocated to ' . $allocation->label . '.');
    }
}
