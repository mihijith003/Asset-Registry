<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // NEW: for the merged-asset check
use Illuminate\View\View;
use App\Models\ActivityLog; 

class AssetDisposalController extends Controller
{
    /**
     * Display a listing of asset disposals and the disposal form.
     */
    public function index(): View
    {
        $disposals = AssetDisposal::with('asset')
            ->orderByDesc('id')
            ->get(); 

        // Assets that were merged into a combined asset are no longer disposable.
        $assets = Asset::whereNull('merged_into_asset_id')->orderBy('asset_code')->get();

        return view('asset-disposals.index', compact('disposals', 'assets'));
    }

    /**
     * Store a newly created asset disposal in storage.
     * Note: Inputs are limited to asset_id, method, and comments.
     * No fields for disposal_method or reason.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')],
            'method' => ['required', 'string', 'in:Donated,Scrapped,Lost'],
            'comments' => ['nullable', 'string', 'max:1000'], 

            // An Admin password must be supplied to record the disposal
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

        //Logic for Lost items
        $validated['approval_status'] = ($validated['method'] === 'Lost')? 'pending' : 'approved';

        unset($validated['admin_password']);

        // Keep the created record so we can link to its printable document below.
      
        $disposal = AssetDisposal::create([
            'asset_id' => $validated['asset_id'],
            'method' => $validated['method'],
            'disposal_date' => now()->toDateString(),
            // Record the name of the admin whose password authorized the disposal.
            'authorized_by' => $admin->name,
            'comments' => $validated['comments'] ?? null,
            'approval_status' => $validated['approval_status'],
        ]);

          ActivityLog::create([
       'user_id' => auth()->id(),
       'action' => 'Asset Disposed - ' . $validated['method'],
       'description' => "Recorded disposal for Asset ID: " . $validated['asset_id'],
       'status' => 1,
       ]);

        // Also pass the document link so the page can offer "Print document" straight away.
        // return redirect()->route('asset-disposals.index')->with('success', 'Asset disposal recorded successfully.');
        return redirect()->route('asset-disposals.index')
            ->with('success','Disposal recorded. Lost items require Admin approval.')
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

    //Approve pending lost item claim
    public function approve($id){
        $disposal = \App\Models\AssetDisposal::findOrFail($id);
        $disposal->update(['approval_status' => 'approved']);

        //Soft delete the actual asset from the database
        $asset = \App\Models\Asset::find($disposal->asset_id);
        if($asset){
            $asset->delete();
        }

        return back()->with('success', 'Lost item approved and removed from database.');
    }

    //Reject pending lost item claim
    public function reject($id){
        $disposal = \App\Models\AssetDisposal::findOrFail($id);

        //Deleting thr disposal record restores the item to the active assets Registry
        $disposal->delete();

        return back()->with('success', 'Lost item rejected and restored to the active assets registry.');
}
}
