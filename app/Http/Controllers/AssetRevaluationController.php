<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetRevaluation;
use App\Support\AdminAuthorization;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetRevaluationController extends Controller
{
    /**
     * Display a listing of asset revaluations and the revaluation form.
     */
    public function index(): View
    {
        $revaluations = AssetRevaluation::with(['asset.assetType', 'asset.subType', 'asset.brand'])
            ->orderByDesc('id')
            ->get();

        // Active assets available to revalue (excludes sold, disposed and merged assets)
        $assets = Asset::active()
            ->with(['assetType', 'subType', 'brand'])
            ->orderBy('asset_code')
            ->get();

        return view('asset-revaluations.index', compact('revaluations', 'assets'));
    }

    /**
     * Store a newly created asset revaluation in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('merged_into_asset_id')],
            'revaluation_date' => ['required', 'date'],
            'revalued_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'admin_password' => ['required', 'string'],
        ]);

        // Authorize via admin password
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The revaluation was not recorded.']);
        }

        $asset = Asset::active()->findOrFail($validated['asset_id']);

        $revalDate = Carbon::parse($validated['revaluation_date'])->startOfDay();
        $previousValue = $asset->netBookValue($revalDate);

        // Compute remaining useful life at revaluation_date
        $activeLayers = $asset->layers()->whereNull('end_date')->get();
        $initialStart = $activeLayers->min('start_date')
            ?? ($asset->purchase_date ? $asset->purchase_date->toDateString() : $asset->created_at?->toDateString());

        $lifeMonths = $activeLayers->whereNotNull('useful_life_months')->max('useful_life_months')
            ?: ($asset->useful_life_months ?: ($asset->subType?->useful_life_years ? $asset->subType->useful_life_years * 12 : null));

        $remainingLife = null;
        if ($lifeMonths && $initialStart) {
            $elapsedMonths = Depreciation::monthsBetween(Carbon::parse($initialStart)->startOfDay(), $revalDate);
            $remainingLife = max(0, $lifeMonths - $elapsedMonths);
        }

        DB::transaction(function () use ($asset, $validated, $previousValue, $admin, $remainingLife) {
            AssetRevaluation::create([
                'asset_id' => $asset->id,
                'revaluation_date' => $validated['revaluation_date'],
                'previous_value' => $previousValue,
                'revalued_amount' => $validated['revalued_amount'],
                'reason' => $validated['reason'] ?? null,
                'authorized_by' => $admin->name,
                'comments' => $validated['comments'] ?? null,
            ]);

            // Mark existing layers as finished at revaluation_date to prevent double counting
            $asset->layers()->whereNull('end_date')->update([
                'end_date' => $validated['revaluation_date'],
            ]);

            // New depreciation layer starting at revaluation_date with remaining useful life
            $asset->layers()->create([
                'source_asset_id' => $asset->id,
                'cost' => $validated['revalued_amount'],
                'start_date' => $validated['revaluation_date'],
                'end_date' => null,
                'useful_life_months' => $remainingLife,
            ]);

            // Update asset cost to the revalued amount
            $asset->cost = $validated['revalued_amount'];
            $asset->save();

            // Record activity log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Asset Revalued',
                'description' => "Asset {$asset->asset_code} revalued to " . number_format($validated['revalued_amount'], 2) . " (previous value: " . number_format($previousValue, 2) . ")",
                'status' => 1,
            ]);
        });

        return redirect()->route('asset-revaluations.index')->with('success', 'Asset revaluation recorded successfully.');
    }
}

