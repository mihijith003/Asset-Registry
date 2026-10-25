<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\Asset;
use App\Models\AssetSale;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule; // NEW: for the merged-asset check
use Illuminate\View\View;

class AssetSaleController extends Controller
{
    use FiltersByAssetType;

    /**
     * Display a listing of asset sales and the sale recording form.
     */
    public function index(Request $request): View
    {
        $filters = $this->eventFilters($request);
        $sales = $this->filteredSales($filters, ['asset.assetType', 'asset.subType', 'asset.brand'])->orderByDesc('id')->get();

        $assets = Asset::active()->with(['assetType', 'subType', 'brand'])->orderBy('asset_code')->get();
        $filterFields = $this->dateFilterFields($filters);

        return view('asset-sales.index', compact('sales', 'assets', 'filterFields'));
    }

    /**
     * "Sales Report": the sales on the screen list (same filters), with book value and gain / loss at the sale date.
     */
    public function printSummary(Request $request): View
    {
        $filters = $this->eventFilters($request);
        $sales = $this->filteredSales($filters, ['asset.assetType', 'asset.subType', 'asset.layers'])
            ->orderBy('sale_date')->orderBy('id')->get();

        $rows = $sales->map(function (AssetSale $sale) {
            $asset = $sale->asset;
            $saleDate = $sale->sale_date ? Carbon::parse($sale->sale_date)->startOfDay() : Carbon::today();
            $bookValue = $asset ? $asset->netBookValue($saleDate) : null;

            return (object) [
                'sale' => $sale,
                'asset' => $asset,
                'book_value' => $bookValue,
                'gain' => $bookValue !== null ? round((float) $sale->price - $bookValue, 2) : null,
            ];
        });

        $totals = (object) [
            'proceeds' => round($rows->sum(fn ($r) => (float) $r->sale->price), 2),
            'book_value' => round($rows->sum('book_value'), 2),
            'gain' => round($rows->sum('gain'), 2),
        ];
        $period = $this->periodLabel($filters);

        return view('asset-sales.print-summary', compact('rows', 'totals', 'period'));
    }

    private function filteredSales(array $filters, array $with)
    {
        $query = AssetSale::with($with); // AssetSale::asset() is withTrashed(): sold assets may be soft-deleted

        $this->applyTypeFilter($query, $filters, 'asset');

        return $this->applyDateFilter($query, $filters, 'sale_date');
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

