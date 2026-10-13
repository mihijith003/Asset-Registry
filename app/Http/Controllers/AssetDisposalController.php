<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // NEW: for the merged-asset check
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

        // Assets that were merged into a combined asset are no longer disposable.
        // $assets = Asset::orderBy('asset_code')->get();
        $assets = Asset::whereNull('merged_into_asset_id')->orderBy('asset_code')->get();

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
            
            // CHANGED: also reject assets that were merged into a combined asset.
            
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')],
            'method' => ['required', 'string', 'max:100'],
            'comments' => ['nullable', 'string', 'max:1000'], // NEW: optional notes

            // An Admin password must be supplied (via the popup on "Record Disposal").
            'admin_password' => ['required', 'string'],
        ]);

        // Verify the password against the Admin users. If it doesn't match,
        // nothing is saved and the user is sent back with their input preserved.
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The disposal was not recorded.']);
        }

        // Keep the created record so we can link to its printable document below.
      
        $disposal = AssetDisposal::create([
            'disposal_id' => $validated['disposal_id'],
            'asset_id' => $validated['asset_id'],
            'method' => $validated['method'],
            'disposal_date' => now()->toDateString(),
            // Record the name of the admin whose password authorized the disposal.
            'authorized_by' => $admin->name,
            'comments' => $validated['comments'] ?? null,
        ]);

        // Also pass the document link so the page can offer "Print document" straight away.
        // return redirect()->route('asset-disposals.index')->with('success', 'Asset disposal recorded successfully.');
        return redirect()->route('asset-disposals.index')
            ->with('success', 'Asset disposal recorded successfully.')
            ->with('print_url', route('asset-disposals.print', $disposal));
    }

    /**
     * NEW: printable disposal record for one disposal.
     */
    public function print(AssetDisposal $disposal): View
    {
        $disposal->load(['asset.assetType', 'asset.subType']);

        return view('asset-disposals.print', compact('disposal'));
    }
}

