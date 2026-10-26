<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\AssetSubType;
use App\Models\AssetType;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterFileController extends Controller
{
    use FiltersByAssetType;

    public const TABS = ['asset-types', 'sub-types', 'brands'];

    /**
     * Display the Master Files management page.
     * The Sub Types tab can be filtered by asset type (?tab=sub-types&asset_type_id=..).
     */
    public function index(Request $request): View
    {
        $filters = $this->typeFilters($request);

        $assetTypes = AssetType::withCount(['subTypes', 'assets'])
            ->orderBy('type_name')
            ->get();

        $assetSubTypes = AssetSubType::with('assetType')
            ->withCount('assets')
            ->when($filters['asset_type_id'], fn ($q, $typeId) => $q->where('asset_type_id', $typeId))
            ->orderBy('asset_type_id')
            ->orderBy('sub_type_name')
            ->get();

        $brands = Brand::withCount('assets')
            ->orderBy('name')
            ->get();

        // Tab to show when the page is opened from a link 
        $requestedTab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : null;

        return view('master-files.index', compact('assetTypes', 'assetSubTypes', 'brands', 'requestedTab'));
    }

    /**
     * Print Summary of ONE tab (?tab=asset-types | sub-types | brands, default asset-types).
     * Counts use withCount(), like the screen: assets that are not soft-deleted.
     */
    public function print(Request $request): View
    {
        $validator = Validator::make(
            ['tab' => $request->query('tab', 'asset-types')],
            ['tab' => 'required|in:' . implode(',', self::TABS)]
        );

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(route('master-files.index'));
        }

        $tab = $validator->validated()['tab'];

        if ($tab === 'asset-types') {
            $assetTypes = AssetType::withCount(['subTypes', 'assets'])->orderBy('type_name')->get();

            return view('master-files.print.asset-types', compact('assetTypes'));
        }

        if ($tab === 'sub-types') {
            $filters = $this->typeFilters($request);

            // Grouped by parent type: each type with its sub types (and their asset counts).
            $groups = AssetType::with(['subTypes' => fn ($q) => $q->withCount('assets')->orderBy('sub_type_name')])
                ->when($filters['asset_type_id'], fn ($q, $typeId) => $q->whereKey($typeId))
                ->orderBy('type_name')
                ->get();

            return view('master-files.print.sub-types', compact('groups'));
        }

        $brands = Brand::withCount('assets')->orderBy('name')->get();

        return view('master-files.print.brands', compact('brands'));
    }
}
