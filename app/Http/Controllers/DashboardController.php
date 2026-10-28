<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetSale;
use App\Support\AgeingReport;
use App\Support\Depreciation;
use App\Support\PeriodMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard: the whole estate at a glance, no filters. Every figure is computed here; the view only renders it.
 *
 * Tiles and the first three charts count the assets currently held - Asset::active(), the registry's own scope, so the
 * totals always match the registry. Age uses the Ageing Report's bands and age rule (AgeingReport::BANDS / ageMonths),
 * and "fully depreciated" its definition (PeriodMovement::writtenDownAt), so the two never disagree. The Disposals &
 * Sales chart is the one view of assets that have LEFT, for the current financial year, valued like the Period Report.
 */
class DashboardController extends Controller
{
    /** Sub types shown by name in the "Assets by Sub Type" chart; the rest are grouped as "Other". */
    private const TOP_SUB_TYPES = 10;

    public function index(Request $request): View
    {
        $today = Carbon::today();
        $assets = Asset::active()->with(['assetType', 'subType', 'layers'])->get();

        $held = $assets->map(fn (Asset $asset) => (object) [
            'asset' => $asset,
            'cost' => (float) $asset->cost,
            'nbv' => $asset->netBookValue($today),
            'writtenDown' => PeriodMovement::writtenDownAt($asset, $today->toDateString()),
            'band' => AgeingReport::bandFor(AgeingReport::ageMonths($asset, $today)),
        ]);

        $count = $held->count();
        $writtenDown = $held->where('writtenDown', true)->count();
        $tiles = (object) [
            'count' => $count,
            'cost' => round($held->sum('cost'), 2),
            'nbv' => round($held->sum('nbv'), 2),
            'writtenDown' => $writtenDown,
            'writtenDownShare' => AgeingReport::share($writtenDown, $count),
            // Only for users who may open the disposals page (the link would 403 otherwise); the view hides a zero.
            'pendingDisposals' => $request->user()->can('dispose assets')
                ? AssetDisposal::where('approval_status', 'pending')->count()
                : null,
        ];

        $charts = [
            'subTypes' => $this->bySubType($held),
            'ages' => $this->byAge($held),
            'values' => $this->valueByType($held),
            'outcomes' => $this->outcomes($today),
            'currency' => currency_symbol(),   // for the chart axis / tooltips
        ];

        $activity = ActivityLog::with('user')->latest('created_at')->latest('id')->limit(10)->get();

        // The dashboard needs only a login; the registry and reports need 'register assets', so their links are
        // shown only to users who may open them.
        $links = (object) [
            'registry' => $request->user()->can('register assets') ? route('assets.index') : null,
            'ageing' => $request->user()->can('register assets') ? route('reports.index', ['tab' => 'ageing']) : null,
            'disposals' => route('asset-disposals.index'),
        ];

        return view('dashboard', compact('tiles', 'charts', 'activity', 'links'));
    }

    
     //Counts by sub type, largest first; beyond the top TOP_SUB_TYPES grouped as "Other".
     
    private function bySubType(Collection $held): array
    {
        $counts = $held->countBy(fn ($h) => trim($h->asset->subType->sub_type_name ?? 'No sub type'))
            ->sortKeys()->sortDesc();
        $top = $counts->take(self::TOP_SUB_TYPES);
        if ($counts->count() > self::TOP_SUB_TYPES) {
            $top->put('Other', $counts->slice(self::TOP_SUB_TYPES)->sum());
        }

        return ['labels' => $top->keys()->all(), 'counts' => $top->values()->all()];
    }

    /**
     * Counts and share by age band - the Ageing Report's bands and percentage rule.
     */
    private function byAge(Collection $held): array
    {
        $bands = AgeingReport::bands();
        $counts = collect($bands)->map(fn ($label, $band) => $held->where('band', $band)->count());

        return [
            'labels' => array_values($bands),
            'counts' => $counts->values()->all(),
            'shares' => $counts->map(fn ($n) => AgeingReport::share($n, $held->count()))->values()->all(),
        ];
    }

    /**
     * Total cost and total net book value per asset type (the gap is the depreciation to date).
     */
    private function valueByType(Collection $held): array
    {
        $types = $held->groupBy(fn ($h) => $h->asset->assetType->type_name ?? 'Unknown type')->sortKeys();

        return [
            'labels' => $types->keys()->all(),
            'cost' => $types->map(fn ($g) => round($g->sum('cost'), 2))->values()->all(),
            'nbv' => $types->map(fn ($g) => round($g->sum('nbv'), 2))->values()->all(),
        ];
    }

    /**
     * Assets that left the register this financial year: sales and approved disposals by outcome, with their value.
     * Sold = proceeds (and the NBV given up); disposals = the NBV written off. NBV is the Period Report's figure for the
     * same event (PeriodMovement replay, withTrashed), so this chart agrees with that report's Sales / Disposals.
     */
    private function outcomes(Carbon $today): array
    {
        [$start, $endExclusive] = Depreciation::range(Depreciation::label(Depreciation::financialYearOf($today)));
        $end = $endExclusive->copy()->subDay();
        $movement = new PeriodMovement($start, $end);
        $traces = $movement->run()->traces;
        $until = $movement->closingDate;

        $sales = AssetSale::whereDate('sale_date', '>=', $start)->whereDate('sale_date', '<=', $until)->get();
        $disposals = AssetDisposal::where('approval_status', 'approved')
            ->whereDate('disposal_date', '>=', $start)->whereDate('disposal_date', '<=', $until)->get();
        $nbv = fn ($event, string $kind) => $traces->get($event->asset_id)?->valueAt[$kind . ':' . $event->id]['nbv'] ?? 0.0;

        $rows = ['Sold' => (object) [
            'count' => $sales->count(),
            'value' => round($sales->sum(fn ($s) => (float) $s->price), 2),
            'nbv' => round($sales->sum(fn ($s) => $nbv($s, 'sale')), 2),
        ]];
        foreach (['Donated', 'Scrapped', 'Lost'] as $method) {
            $ofMethod = $disposals->where('method', $method);
            $value = round($ofMethod->sum(fn ($d) => $nbv($d, 'disposal')), 2);
            $rows[$method] = (object) ['count' => $ofMethod->count(), 'value' => $value, 'nbv' => $value];
        }

        return [
            'labels' => array_keys($rows),
            'counts' => array_values(array_map(fn ($r) => $r->count, $rows)),
            'values' => array_values(array_map(fn ($r) => $r->value, $rows)),
            'nbv' => array_values(array_map(fn ($r) => $r->nbv, $rows)),
            'period' => 'Financial year ' . Depreciation::label(Depreciation::financialYearOf($today))
                . ' (' . $start->toDateString() . ' to ' . $until . ')',
            'total' => array_sum(array_map(fn ($r) => $r->count, $rows)),
        ];
    }
}
