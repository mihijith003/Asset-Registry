<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetRevaluation;
use App\Support\AdminAuthorization;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetRevaluationController extends Controller
{
    use FiltersByAssetType;

    /**
     * Display a listing of asset revaluations and the revaluation form.
     */
    public function index(Request $request): View
    {
        $filters = $this->eventFilters($request);
        $filterFields = $this->dateFilterFields($filters);
        $revaluations = $this->filteredRevaluations($filters)->orderByDesc('id')->get();

        // Active assets available to revalue (excludes sold, disposed, merged and superseded assets)
        $assets = Asset::active()
            ->with(['assetType', 'subType', 'brand', 'layers'])
            ->orderBy('asset_code')
            ->get();

        $today = Carbon::today();
        foreach ($assets as $asset) {
            $activeLayers = $asset->layers->whereNull('end_date');
            $minStart = $activeLayers->min('start_date');
            $initialStart = $minStart
                ? ($minStart instanceof \DateTimeInterface ? $minStart->format('Y-m-d') : (string) $minStart)
                : ($asset->purchase_date ? $asset->purchase_date->toDateString() : $asset->created_at?->toDateString());

            $lifeMonths = $activeLayers->whereNotNull('useful_life_months')->max('useful_life_months')
                ?: ($asset->useful_life_months ?: ($asset->subType?->useful_life_years ? $asset->subType->useful_life_years * 12 : null));

            $remainingLife = null;
            if ($lifeMonths && $initialStart) {
                $elapsedMonths = Depreciation::monthsBetween(Carbon::parse($initialStart)->startOfDay(), $today);
                $remainingLife = max(1, (int) $lifeMonths - $elapsedMonths);
            }

            $asset->setAttribute('remaining_life_months', $remainingLife);
            $asset->setAttribute('initial_start_date', $initialStart);
            $asset->setAttribute('total_life_months', $lifeMonths);
        }

        return view('asset-revaluations.index', compact('revaluations', 'assets', 'filterFields'));
    }

    /**
     * "Revaluation History": the revaluations on the screen list (same filters).
     */
    public function printSummary(Request $request): View
    {
        $filters = $this->eventFilters($request);
        $revaluations = $this->filteredRevaluations($filters)->orderBy('revaluation_date')->orderBy('id')->get();

        $rows = $revaluations->map(fn (AssetRevaluation $revaluation) => (object) [
            'revaluation' => $revaluation,
            'asset' => $revaluation->asset,
            'change' => $revaluation->previous_value !== null
                ? round($revaluation->revalued_amount - $revaluation->previous_value, 2)
                : null,
        ]);

        $totals = (object) [
            'count' => $rows->count(),
            'change' => round($rows->sum('change'), 2),
        ];
        $period = $this->periodLabel($filters);

        return view('asset-revaluations.print-summary', compact('rows', 'totals', 'period'));
    }

    private function filteredRevaluations(array $filters)
    {
        // asset() / resultAsset() are withTrashed(): a revalued asset is superseded and may be soft-deleted later
        $query = AssetRevaluation::with(['asset.assetType', 'asset.subType', 'asset.brand', 'resultAsset']);

        $this->applyTypeFilter($query, $filters, 'asset');

        return $this->applyDateFilter($query, $filters, 'revaluation_date');
    }

    /**
     * Store a newly created asset revaluation in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')->whereNull('superseded_by_asset_id')],
            'revaluation_date' => ['required', 'date'],
            'revalued_amount' => ['required', 'numeric', 'min:0'],
            'new_useful_life_months' => ['required', 'integer', 'min:1', 'max:600'],
            'reason' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'admin_password' => ['required', 'string'],
        ]);

        // a. Authorize via admin password
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The revaluation was not recorded.']);
        }

        $asset = Asset::active()->findOrFail($validated['asset_id']);

        // b. Record previous_value = the old asset's netBookValue() at revaluation_date
        $revalDate = Carbon::parse($validated['revaluation_date'])->startOfDay();
        $previousValue = $asset->netBookValue($revalDate);

        DB::transaction(function () use ($asset, $validated, $previousValue, $admin) {
            // c - f. Create the successor asset and supersede the old asset
            $successor = $this->applyRevaluation($asset, $validated, $admin->name);

            // g. Save the revaluation row with asset_id = old asset and result_asset_id = successor
            AssetRevaluation::create([
                'asset_id' => $asset->id,
                'result_asset_id' => $successor?->id,
                'revaluation_date' => $validated['revaluation_date'],
                'previous_value' => $previousValue,
                'revalued_amount' => $validated['revalued_amount'],
                'new_useful_life_months' => (int) $validated['new_useful_life_months'],
                'reason' => $validated['reason'] ?? null,
                'authorized_by' => $admin->name,
                'comments' => $validated['comments'] ?? null,
            ]);

            // h. Record activity log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Asset Revalued',
                'description' => "Asset {$asset->asset_code} revalued to " . number_format($validated['revalued_amount'], 2)
                    . " (previous value: " . number_format($previousValue, 2) . ")"
                    . ($successor ? " -> new asset {$successor->asset_code}" : ''),
                'status' => 1,
            ]);
        });

        return redirect()->route('asset-revaluations.index')->with('success', 'Asset revaluation recorded successfully.');
    }

    /**
     * Create a new successor asset with the revalued amount and new useful life,
     * give it a single depreciation layer starting on the revaluation date,
     * carry over the old asset's current allocation, and close/supersede the old asset.
     *
     * REVERSIBILITY NOTE:
     * To switch back to the alternative "in-place revaluation" model (keeping the same
     * asset and resetting its depreciation basis in place), replace ONLY this method:
     *   1. Close open layers on $asset: $asset->layers()->whereNull('end_date')->update(['end_date' => $validated['revaluation_date']]);
     *   2. Create the new layer on $asset: $asset->layers()->create(['source_asset_id' => $asset->id, 'cost' => $validated['revalued_amount'], 'start_date' => $validated['revaluation_date'], 'end_date' => null, 'useful_life_months' => (int) $validated['new_useful_life_months']]);
     *   3. Update $asset in place: $asset->update(['cost' => $validated['revalued_amount'], 'useful_life_months' => (int) $validated['new_useful_life_months']]);
     *   4. Return null (so result_asset_id is null and superseded_by_asset_id remains null).
     */
    private function applyRevaluation(Asset $asset, array $validated, string $authorizedBy): ?Asset
    {
        $allocation = $asset->currentAllocation;

        // c. Create a SUCCESSOR asset copying metadata from the old one
        $successor = Asset::create([
            'asset_code' => Asset::generateCode((int) $asset->asset_type_id, (int) $asset->asset_sub_type_id),
            'asset_type_id' => $asset->asset_type_id,
            'asset_sub_type_id' => $asset->asset_sub_type_id,
            'brand_id' => $asset->brand_id,
            'brand_name' => $asset->brand_name,
            'model' => $asset->model,
            'purchase_date' => $asset->purchase_date ? $asset->purchase_date->toDateString() : null,
            'supplier' => $asset->supplier,
            'po_reference' => $asset->po_reference,
            'invoice_reference' => $asset->invoice_reference,
            'financial_year' => $asset->financial_year,
            'location' => $asset->location,
            'residual_value' => $asset->residual_value ?? 0,
            'comments' => $asset->comments,
            'cost' => $validated['revalued_amount'],
            'useful_life_months' => (int) $validated['new_useful_life_months'],
        ]);

        // d. Give the successor one depreciation layer
        $successor->layers()->create([
            'source_asset_id' => $successor->id,
            'cost' => $validated['revalued_amount'],
            'start_date' => $validated['revaluation_date'],
            'end_date' => null,
            'useful_life_months' => (int) $validated['new_useful_life_months'],
        ]);

        // e. Close the OLD asset: set end_date on all open layers and set superseded_by_asset_id
        $asset->layers()->whereNull('end_date')->update([
            'end_date' => $validated['revaluation_date'],
        ]);
        $asset->update([
            'superseded_by_asset_id' => $successor->id,
        ]);

        // f. Carry the old asset's CURRENT allocation over to the successor
        if ($allocation) {
            $successor->allocations()->create([
                'allocation_type' => $allocation->allocation_type,
                'allocated_to' => $allocation->allocated_to,
                'allocated_by' => $authorizedBy,
            ]);
        }

        return $successor;
    }
}
