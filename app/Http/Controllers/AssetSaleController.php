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
        $sales = AssetSale::with('asset')
            ->orderByDesc('id')
            ->paginate(10);

        // CHANGED: assets that were merged into a combined asset are no longer sellable.
        // $assets = Asset::orderBy('asset_code')->get();
        $assets = Asset::whereNull('merged_into_asset_id')->orderBy('asset_code')->get();

        return view('asset-sales.index', compact('sales', 'assets'));
    }

    /**
     * Store a newly created asset sale in storage.
     * Note: Inputs are limited to sale_id, asset_id, price, and authorized_by.
     * No fields for status, asset_name, or sale_price.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'string', 'max:50', 'unique:asset_sales,sales_ID'],
            // CHANGED: also reject assets that were merged into a combined asset.
            // 'asset_id' => ['required', 'exists:assets,id'],
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')],
            'price' => ['required', 'numeric', 'min:0'],
            'comments' => ['nullable', 'string', 'max:1000'], // NEW: optional notes
            // CHANGED: the free-text "authorized_by" input was removed from the form.
            // 'authorized_by' => ['nullable', 'string', 'max:100'],
            // NEW: an Admin password must now be supplied (via the popup on "Record Sale").
            'admin_password' => ['required', 'string'],
        ]);

        // NEW: verify the password against the Admin users. If it doesn't match,
        // nothing is saved and the user is sent back with their input preserved.
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The sale was not recorded.']);
        }

        // CHANGED: keep the created record so we can link to its printable voucher below.
        // AssetSale::create([
        $sale = AssetSale::create([
            'sales_ID' => $validated['sale_id'],
            'asset_id' => $validated['asset_id'],
            'price' => $validated['price'],
            // CHANGED: previously taken from the typed-in field.
            // 'authorized_BY' => $validated['authorized_by'] ?? null,
            // NEW: record the name of the admin whose password authorized the sale.
            'authorized_BY' => $admin->name,
            'sale_date' => now()->toDateString(),
            'comments' => $validated['comments'] ?? null,
        ]);

        // CHANGED: also pass the voucher link so the page can offer "Print document" straight away.
        // return redirect()->route('asset-sales.index')->with('success', 'Asset sale recorded successfully.');
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

