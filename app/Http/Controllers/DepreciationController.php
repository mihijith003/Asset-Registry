<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Support\Depreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// Straight-line monthly depreciation report (per financial year).
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
            ->whereNull('superseded_by_asset_id')
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

            $activeLayers = $asset->layers->whereNull('end_date');
            $layersForDep = $activeLayers->isNotEmpty() ? $activeLayers : $asset->layers;

            $closingAsOf = $endDate && $endDate->lt($closingDate) ? $endDate : $closingDate;
            $openingAsOf = $endDate && $endDate->lt($yearStart) ? $endDate : $yearStart;

            $opening = Depreciation::accumulated($layersForDep, $openingAsOf, (float) ($asset->residual_value ?? 0));
            $closing = Depreciation::accumulated($layersForDep, $closingAsOf, (float) ($asset->residual_value ?? 0));

            $rows[] = (object) [
                'asset' => $asset,
                'status' => $asset->sale ? 'Sold' : ($asset->disposal ? 'Disposed' : 'Active'),
                'cost' => (float) $asset->cost,
                'opening' => $opening,
                'charge' => round($closing - $opening, 2),
                'closing' => $closing,
                'book_value' => round((float) $asset->cost - $closing, 2),
                'life_years' => $layersForDep->whereNotNull('useful_life_months')->max('useful_life_months')
                    ? round($layersForDep->whereNotNull('useful_life_months')->max('useful_life_months') / 12, 1)
                    : null,
            ];
        }

        return view('depreciation.index', compact('rows', 'financialYears', 'selected', 'closingDate'));
    }
}
