<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Brand;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetController extends Controller
{
    /**
     * Display a listing of assets and registration form.
     */
    public function index(): View
    {
        // NEW: allocations (newest first = current + history); originAsset / removalModification trace removed components
        $assets = Asset::with(['assetType', 'brand', 'components', 'allocations', 'originAsset', 'removalModification.asset', 'layers'])
            ->active()
            ->orderByDesc('id')
            ->get(); 

        $assetTypes = AssetType::orderBy('type_name')->get();
        $assetSubTypes = \App\Models\AssetSubType::all();
        $brands = Brand::orderBy('name')->get();

        // Dropdown of financial years, and the figures for the depreciation columns
        $financialYears = Depreciation::financialYearOptions();
        $currentFinancialYear = Depreciation::label(Depreciation::financialYearOf());

        return view('assets.index', compact('assets', 'assetTypes', 'assetSubTypes', 'brands', 'financialYears', 'currentFinancialYear'));
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'asset_sub_type_id' => ['required', 'exists:asset_sub_types,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'financial_year' => ['nullable', 'string', 'in:' . implode(',', Depreciation::financialYearOptions())],
            'cost' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'po_reference' => ['nullable', 'string', 'max:100'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
            'residual_value' => ['nullable', 'numeric', 'min:0', 'lte:cost'],
            'useful_life_months' => ['nullable', 'integer', 'min:1'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ], [
            'residual_value.lte' => 'The residual value must not exceed the asset cost.',
        ]);

        $asset = DB::transaction(function () use ($validated) {
            $validated['asset_code'] = Asset::generateCode(
                (int) $validated['asset_type_id'],
                (int) $validated['asset_sub_type_id']
            );

            $asset = Asset::create($validated);
            $asset->addInitialLayer();

            return $asset;
        });

        // Record activity log
        $brandText = $asset->brand?->name ?? $asset->brand_name;
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Asset Registered',
            'description' => "Asset {$asset->asset_code} (" . trim("{$brandText} {$asset->model}") . ") registered. " . number_format($asset->cost, 2),
            'status' => 1,
        ]);

        return redirect()->route('assets.index')->with('success', 'Asset registered successfully.');
    }
}

