<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetController extends Controller
{
    /**
     * Display a listing of assets and registration form.
     */
    public function index(): View
    {
        $assets = Asset::with('assetType')
            ->orderByDesc('id')
            ->paginate(10);

        $assetTypes = AssetType::orderBy('type_name')->get();

        return view('assets.index', compact('assets', 'assetTypes'));
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'financial_year' => ['nullable', 'string', 'max:9'],
            'location' => ['nullable', 'string', 'max:150'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        if (empty($validated['asset_code'])) {
            $validated['asset_code'] = 'AST-' . date('Y') . '-' . strtoupper(Str::random(5));
        }

        Asset::create($validated);

        return redirect()->route('assets.index')->with('success', 'Asset registered successfully.');
    }
}

