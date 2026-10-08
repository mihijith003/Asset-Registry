<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetSale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $assets = Asset::orderBy('asset_code')->get();

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
            'asset_id' => ['required', 'exists:assets,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'authorized_by' => ['nullable', 'string', 'max:100'],
        ]);

        AssetSale::create([
            'sales_ID' => $validated['sale_id'],
            'asset_id' => $validated['asset_id'],
            'price' => $validated['price'],
            'authorized_BY' => $validated['authorized_by'] ?? null,
            'sale_date' => now()->toDateString(),
        ]);

        return redirect()->route('asset-sales.index')->with('success', 'Asset sale recorded successfully.');
    }
}

