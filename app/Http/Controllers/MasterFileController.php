<?php

namespace App\Http\Controllers;

use App\Models\AssetSubType;
use App\Models\AssetType;
use App\Models\Brand;
use Illuminate\View\View;

class MasterFileController extends Controller
{
    /**
     * Display the Master Files management page.
     */
    public function index(): View
    {
        $assetTypes = AssetType::withCount(['subTypes', 'assets'])
            ->orderBy('type_name')
            ->get();

        $assetSubTypes = AssetSubType::with('assetType')
            ->withCount('assets')
            ->orderBy('asset_type_id')
            ->orderBy('sub_type_name')
            ->get();

        $brands = Brand::withCount('assets')
            ->orderBy('name')
            ->get();

        return view('master-files.index', compact('assetTypes', 'assetSubTypes', 'brands'));
    }
}

