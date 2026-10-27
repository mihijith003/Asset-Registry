<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Brand;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    use FiltersByAssetType;

    /**
     * Labels per sticker sheet: must equal --label-cols x --label-rows in assets/labels.blade.php (the sheet warns
     * on screen if they differ). Used for the registry's "start at label position" limit.
     */
    public const LABELS_PER_PAGE = 24;

    /**
     * Display a listing of assets and registration form.
     */
    public function index(Request $request): View
    {
        $filters = $this->typeFilters($request);

        // NEW: allocations (newest first = current + history); originAsset / removalModification trace removed components
        $assets = $this->applyTypeFilter(
            Asset::with(['assetType', 'brand', 'components.brand', 'allocations', 'originAsset', 'removalModification.asset', 'layers', 'modifications', 'supersedes', 'revaluationIn.asset'])->active(),
            $filters
        )
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
     * Printable "Asset Register": the same current assets and filters as the registry list.
     */
    public function print(Request $request): View
    {
        $filters = $this->typeFilters($request);

        $assets = $this->applyTypeFilter(
            Asset::with(['assetType', 'subType', 'brand', 'allocations', 'layers'])->active(),
            $filters
        )
            ->orderBy('asset_code')
            ->get();

        return view('assets.print', compact('assets'));
    }

    /**
     * Printable QR label sheet for the same current assets and filters as the registry list. ?start=N leaves the
     * first N-1 label slots blank so a part-used sticker sheet can be reused; ?asset=<id> (a registry row's Label
     * button) restricts the sheet to that one asset, still within active() and the filter.
     */
    public function labels(Request $request): View
    {
        $filters = $this->typeFilters($request);
        $backUrl = route('assets.index', $request->except(['start', 'asset']));

        $validator = Validator::make($request->only('asset'), ['asset' => 'nullable|exists:assets,id']);
        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo($backUrl);
        }
        $assetId = $validator->validated()['asset'] ?? null;

        $assets = $this->applyTypeFilter(
            Asset::with(['assetType', 'subType', 'brand'])->active(),
            $filters
        )
            ->when($assetId, fn ($query, $id) => $query->whereKey($id))
            ->orderBy('asset_code')
            ->get();

        $start = min(self::LABELS_PER_PAGE, max(1, (int) $request->query('start', 1)));

        return view('assets.labels', [
            'assets' => $assets,
            'blanks' => $start - 1,
            'perPage' => self::LABELS_PER_PAGE,
            'backUrl' => $backUrl,
            'single' => $assetId !== null,
        ]);
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'asset_sub_type_id' => [
                'required',
                Rule::exists('asset_sub_types', 'id')->where(function ($query) use ($request) {
                    return $query->where('asset_type_id', $request->input('asset_type_id'));
                }),
            ],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'financial_year' => ['nullable', 'string', 'in:' . implode(',', Depreciation::financialYearOptions())],
            'cost' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'po_reference' => ['nullable', 'string', 'max:100'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
            'useful_life_months' => ['required', 'integer', 'min:1', 'max:600'],
            'monthly_depreciation' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $cost = (float) $request->input('cost', 0);
                    $months = (int) $request->input('useful_life_months', 0);
                    $monthly = (float) $value;

                    if ($months > 0 && round($monthly * $months, 2) > round($cost, 2)) {
                        $fail('Total depreciation of ' . money($monthly * $months) . ' cannot exceed the asset cost of ' . money($cost) . ' — the asset cannot depreciate below zero.');
                    }
                },
            ],
            'comments' => ['nullable', 'string', 'max:1000'],
        ], [
            'asset_sub_type_id.exists' => 'The selected asset sub type does not belong to the selected asset type.',
        ]);

        $validated['useful_life_months'] = (int) $validated['useful_life_months'];
        $validated['monthly_depreciation'] = round((float) $validated['monthly_depreciation'], 2);
        $validated['residual_value'] = max(0.0, round((float) $validated['cost'] - ($validated['monthly_depreciation'] * $validated['useful_life_months']), 2));

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
            'description' => "Asset {$asset->asset_code} (" . trim("{$brandText} {$asset->model}") . ") registered. " . money($asset->cost),
            'status' => 1,
        ]);

        return redirect()->route('assets.index')->with('success', 'Asset registered successfully.');
    }
}
