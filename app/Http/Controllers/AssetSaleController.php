<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetSale;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // NEW: for the merged-asset check
use Illuminate\View\View;

class AssetSaleController extends Controller
{
    /**
     * Display a listing of asset sales and the sale recording form.
     */
    public function index(): View
    {
        $sales = AssetSale::with(['asset.assetType', 'asset.subType', 'asset.brand'])
            ->orderByDesc('id')
            ->get(); 
       
        $assets = Asset::active()->with(['assetType', 'subType', 'brand'])->orderBy('asset_code')->get();

        return view('asset-sales.index', compact('sales', 'assets'));
    }

    /**
     * Store a newly created asset sale in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')->whereNull('superseded_by_asset_id')],
            'buyer_name' => ['required', 'string', 'max:150'],
            'buyer_contact' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'comments' => ['nullable', 'string', 'max:1000'], 
            'admin_password' => ['required', 'string'],
        ]);

        // Verify the password against the Admin users
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The sale was not recorded.']);
        }

        $sale = AssetSale::create([
            'asset_id' => $validated['asset_id'],
            'sale_date' => now()->toDateString(),
            'buyer_name' => $validated['buyer_name'],
            'buyer_contact' => $validated['buyer_contact'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'price' => $validated['price'],
            'authorized_by' => $admin->name,
            'comments' => $validated['comments'] ?? null,
        ]);

        
        return redirect()->route('asset-sales.index')
            ->with('success', 'Asset sale recorded successfully.')
            ->with('print_url', route('asset-sales.print', $sale));
    }

    /**
     * NEW: printable sale voucher for one sale.
     */
    public function print(AssetSale $sale): View
    {
        $sale->load(['asset.assetType', 'asset.subType']);

        return view('asset-sales.print', compact('sale'));
    }
}

