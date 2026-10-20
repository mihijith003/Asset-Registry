<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetSubType;
use App\Support\AdminAuthorization;
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Straight-line monthly depreciation report (per financial year) and the useful-life settings.
class DepreciationController extends Controller
{
    public function index(Request $request): View
    {
        $financialYears = Depreciation::financialYearOptions();
        $current = Depreciation::label(Depreciation::financialYearOf());
        $selected = in_array($request->query('fy'), $financialYears, true) ? $request->query('fy') : $current;

        [$yearStart, $yearEnd] = Depreciation::range($selected); // $yearEnd = first day AFTER the financial year
        $today = Carbon::today();
        $closingDate = $yearEnd->lt($today) ? $yearEnd : $today; // a financial year still running is shown up to today

        $rows = [];
        $assets = Asset::with(['layers', 'sale', 'disposal', 'assetType', 'subType'])
            ->whereNull('merged_into_asset_id')
            ->orderBy('asset_code')
            ->get();

        foreach ($assets as $asset) {
            // Depreciation stops on the day the asset is sold or disposed of.
            $endDate = $asset->sale?->sale_date ?? $asset->disposal?->disposal_date;
            $endDate = $endDate ? Carbon::parse($endDate)->startOfDay() : null;

            if ($endDate && $endDate->lt($yearStart)) {
                continue; // gone before this financial year began
            }

            // Left out only if it did not exist yet: registered after this financial year ended, or in the future.
            // (An asset registered today is listed, with 0.00 depreciation so far.)
            $firstStart = $asset->layers->min('start_date');
            if (! $firstStart || $firstStart->gte($yearEnd) || $firstStart->gt($today)) {
                continue;
            }

            $closingAsOf = $endDate && $endDate->lt($closingDate) ? $endDate : $closingDate;
            $openingAsOf = $endDate && $endDate->lt($yearStart) ? $endDate : $yearStart;

            $opening = Depreciation::accumulated($asset->layers, $openingAsOf, (float) ($asset->residual_value ?? 0));
            $closing = Depreciation::accumulated($asset->layers, $closingAsOf, (float) ($asset->residual_value ?? 0));

            $rows[] = (object) [
                'asset' => $asset,
                'status' => $asset->sale ? 'Sold' : ($asset->disposal ? 'Disposed' : 'Active'),
                'cost' => (float) $asset->cost,
                'opening' => $opening,
                'charge' => round($closing - $opening, 2),
                'closing' => $closing,
                'book_value' => round((float) $asset->cost - $closing, 2),
                'life_years' => $asset->layers->whereNotNull('useful_life_months')->max('useful_life_months')
                    ? round($asset->layers->whereNotNull('useful_life_months')->max('useful_life_months') / 12, 1)
                    : null,
            ];
        }

        $subTypes = AssetSubType::with('assetType')->orderBy('asset_type_id')->orderBy('sub_type_name')->get();

        return view('depreciation.index', compact('rows', 'financialYears', 'selected', 'closingDate', 'subTypes'));
    }

    /**
     * Save the useful life (years) of each sub type. It also updates the assets that are already registered.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'useful_life_years' => ['required', 'array'],
            'useful_life_years.*' => ['nullable', 'integer', 'min:1', 'max:100'],
            'admin_password' => ['required', 'string'],
        ]);

        if (! AdminAuthorization::findAdminByPassword($validated['admin_password'])) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. The settings were not saved.']);
        }

        DB::transaction(function () use ($validated) {
            foreach (AssetSubType::all() as $subType) {
                if (! array_key_exists($subType->id, $validated['useful_life_years'])) {
                    continue;
                }

                $years = $validated['useful_life_years'][$subType->id];
                $years = $years === null || $years === '' ? null : (int) $years;

                $subType->update(['useful_life_years' => $years]);

                // Layers hold their own copy of the life; update the ones whose cost came from this sub type
                // (except assets that have their own custom useful_life_months explicitly set).
                DB::table('asset_depreciation_layers')
                    ->whereIn('source_asset_id', Asset::where('asset_sub_type_id', $subType->id)->whereNull('useful_life_months')->pluck('id'))
                    ->update(['useful_life_months' => $years ? $years * 12 : null]);
            }
        });

        return redirect()->route('depreciation.index')->with('success', 'Useful life settings saved.');
    }
}
