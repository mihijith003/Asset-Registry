<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetDisposalController extends Controller
{
    /**
     * Display a listing of asset disposals and the disposal form.
     */
    public function index(): View
    {
        $disposals = AssetDisposal::with('asset')
            ->orderByDesc('id')
            ->paginate(10);

        $assets = Asset::orderBy('asset_code')->get();

        return view('asset-disposals.index', compact('disposals', 'assets'));
    }

    /**
     * Store a newly created asset disposal in storage.
     * Note: Inputs are limited to disposal_id, asset_id, and method.
     * No fields for disposal_method or reason.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'disposal_id' => ['required', 'string', 'max:50', 'unique:asset_disposals,disposal_id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'method' => ['required', 'string', 'max:100'],
        ]);

        AssetDisposal::create([
            'disposal_id' => $validated['disposal_id'],
            'asset_id' => $validated['asset_id'],
            'method' => $validated['method'],
            'disposal_date' => now()->toDateString(),
        ]);

        return redirect()->route('asset-disposals.index')->with('success', 'Asset disposal recorded successfully.');
    }
}

