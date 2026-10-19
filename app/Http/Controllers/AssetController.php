<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetType;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    /**
     * Display a listing of assets and registration form.
     */
    public function index(): View
    {
        
        // NEW: allocations (newest first = current + history); originAsset / removalModification trace removed components
        $assets = Asset::with(['assetType', 'components', 'allocations', 'originAsset', 'removalModification.asset', 'layers'])
            ->active()
            ->orderByDesc('id')
            ->get(); 

        $assetTypes = AssetType::orderBy('type_name')->get();
        $assetSubTypes = \App\Models\AssetSubType::all();

        // Dropdown of financial years, and the figures for the depreciation columns
        $financialYears = Depreciation::financialYearOptions();
        $currentFinancialYear = Depreciation::label(Depreciation::financialYearOf());

        return view('assets.index', compact('assets', 'assetTypes', 'assetSubTypes', 'financialYears', 'currentFinancialYear'));
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
            'financial_year' => ['nullable', 'string', 'in:' . implode(',', Depreciation::financialYearOptions())],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        if (empty($validated['asset_code'])) {
            $validated['asset_code'] = 'AST-' . date('Y') . '-' . strtoupper(Str::random(5));
        }

        $asset = Asset::create($validated);
        $asset->addInitialLayer(); 

        //Record activity log
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Asset Registered',
            'description' =>  "Asset {$asset->asset_code} (" . trim("{$asset->brand_name} {$asset->model}") . ") registered." . number_format($asset->cost,2),
            'status' => 1,
        ]);

        return redirect()->route('assets.index')->with('success', 'Asset registered successfully.');
    }
}

