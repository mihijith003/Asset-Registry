<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\AssetDisposal;
use App\Models\AssetModification;
use App\Models\AssetRevaluation;
use App\Models\AssetSale;
use App\Support\AgeingReport;
use App\Support\Depreciation;
use App\Support\PeriodMovement;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Reporting page, one tab per report (?tab=period | ageing, default period). The tab decides what is COMPUTED: only
 * the active tab's report is built, and each tab reads only its own filters from the query string.
 *  - Period Report: movement summary (Opening -> Closing NBV per asset type) and detail sections for a date range.
 *  - Ageing Report: the assets held at an "as at" date, by type / sub type and age band.
 */
class ReportController extends Controller
{
    use FiltersByAssetType;

    public const TABS = ['period' => 'Period Report', 'ageing' => 'Ageing Report'];

    /** Detail sections below the movement summary, in display order. */
    public const SECTIONS = [
        'registrations' => 'Registrations',
        'sales' => 'Sales',
        'disposals' => 'Disposals',
        'modifications' => 'Modifications',
        'revaluations' => 'Revaluations',
        'depreciation' => 'Depreciation',
    ];

    public function index(Request $request): View
    {
        $tab = $this->tab($request);
        $data = $tab === 'ageing' ? $this->ageing($request) : $this->report($request);

        return view('reports.reporting', ['tab' => $tab, 'tabs' => self::TABS] + $data);
    }

    
     //Print Summary of ONE tab, with that tab's filters (same query string as the screen).
    
    public function print(Request $request): View
    {
        return $this->tab($request) === 'ageing'
            ? view('reports.ageing-print', $this->ageing($request))
            : view('reports.period-print', $this->report($request, $this->printSection($request)));
    }

    /**
     * Period Report print of ONE part: ?section=summary (movement summary + adjustments) or a SECTIONS key (that
     * detail section alone). Absent or not a known part: the full report (summary + the ticked sections).
     */
    private function printSection(Request $request): ?string
    {
        $section = $request->query('section');

        return is_string($section) && ($section === 'summary' || isset(self::SECTIONS[$section])) ? $section : null;
    }

    
      //The active tab: ?tab= when it names a tab, else the Period Report.
     
    private function tab(Request $request): string
    {
        $tab = $request->query('tab');

        return is_string($tab) && isset(self::TABS[$tab]) ? $tab : 'period';
    }

    
    //Ageing Report: ?as_at= (default today, not in the future) and the type / sub type filter.
     
    private function ageing(Request $request): array
    {
        $filters = $this->typeFilters($request);
        $input = ['as_at' => $request->query->has('as_at') ? $request->query('as_at') : Carbon::today()->toDateString()];

        $validator = Validator::make($input, ['as_at' => 'required|date|before_or_equal:today'],
            ['as_at.before_or_equal' => 'The as-at date cannot be in the future.']);

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo($request->fullUrlWithoutQuery(['as_at']));
        }

        $asAt = Carbon::parse($input['as_at'])->startOfDay();
        $ageing = (new AgeingReport($asAt, $filters))->run();

        $filterFields = [['name' => 'as_at', 'label' => 'As at', 'type' => 'date', 'default' => $input['as_at']]];

        return ['ageing' => $ageing, 'asAt' => $asAt->toDateString(), 'filterFields' => $filterFields];
    }

    /**
     * $only (print views): 'summary' = no detail sections; a SECTIONS key = only that section, ticked or not;
     * null = the ticked sections. The movement summary is always computed - the sections' checks compare against it.
     */
    private function report(Request $request, ?string $only = null): array
    {
        $presets = $this->presets();
        $filters = $this->typeFilters($request);

        // First visit (no dates in the query string): this month. Once submitted, both dates are required.
        $input = $request->query->has('date_from') || $request->query->has('date_to')
            ? $request->only('date_from', 'date_to')
            : ['date_from' => $presets['this-month']['from'], 'date_to' => $presets['this-month']['to']];

        $validator = Validator::make($input, [
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from',
        ], ['date_from.before_or_equal' => 'The period cannot start in the future.']);

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($request->fullUrlWithoutQuery(['date_from', 'date_to']));
        }

        $from = Carbon::parse($input['date_from'])->startOfDay();
        $to = Carbon::parse($input['date_to'])->startOfDay();
        $movement = new PeriodMovement($from, $to, $filters);
        $result = $movement->run();

        $period = $from->toDateString() . ' to ' . $to->toDateString();
        $closingNote = $movement->closingCapped
            ? "Closing values as at {$movement->closingDate} (period end {$to->toDateString()} is in the future)"
            : null;

        $chosen = $this->chosenSections($request);
        $built = match (true) {
            $only === 'summary' => [],
            $only !== null => [$only],
            default => $chosen,
        };
        $sections = $this->sections($movement, $result, $filters, $built);

        $filterFields = [
            ['name' => 'date_from', 'label' => 'Date from', 'type' => 'date', 'default' => $input['date_from']],
            ['name' => 'date_to', 'label' => 'Date to', 'type' => 'date', 'default' => $input['date_to']],
            ['name' => 'sections', 'label' => 'Include sections', 'type' => 'checkboxes', 'options' => self::SECTIONS, 'checked' => $chosen],
        ];

        return compact('result', 'movement', 'period', 'closingNote', 'presets', 'filterFields', 'sections', 'only');
    }

    /**
     * Ticked sections from ?sections[]=... (all of them when the key is absent: first visit, presets, Clear). The
     * form always sends a "none" marker, so unticking every box really means no detail sections.
     */
    private function chosenSections(Request $request): array
    {
        if (! $request->query->has('sections')) {
            return array_keys(self::SECTIONS);
        }

        return array_values(array_intersect(array_keys(self::SECTIONS), (array) $request->query('sections')));
    }

    /**
     * The detail sections below the movement summary. Each one uses the summary's own figures (PeriodMovement traces)
     * for values, the same period (from .. the closing date) and the same type / sub type filter, and loads its
     * relations here: the views run no queries.
     *
     * A section whose total must equal a summary column carries 'check' => [label, section total, summary figure].
     */
    private function sections(PeriodMovement $movement, object $result, array $filters, array $chosen): array
    {
        $from = $movement->from->toDateString();
        $until = $movement->closingDate;
        $traces = $result->traces;
        $between = fn ($query, string $column) => $query->whereDate($column, '>=', $from)->whereDate($column, '<=', $until);
        $assetRelations = ['asset.assetType', 'asset.subType'];

        // Assets in the summary (all withTrashed): give them what the registration / depreciation rows show.
        (new EloquentCollection($traces->pluck('asset')->all()))->loadMissing(['assetType', 'subType', 'brand']);

        $sections = [];

        if (in_array('registrations', $chosen, true)) {
            $rows = $traces->filter(fn ($t) => isset($t->valueAt['purchase:0']))
                ->map(fn ($t) => (object) [
                    'date' => collect($t->events)->firstWhere(1, 'purchase')[0],
                    'asset' => $t->asset,
                    'cost' => $t->valueAt['purchase:0']['nbv'],
                ])
                ->sortBy(fn ($r) => $r->date . $r->asset->asset_code)->values();
            $sections['registrations'] = [
                'rows' => $rows,
                'totals' => ['cost' => round($rows->sum('cost'), 2)],
                'check' => ['Additions', round($rows->sum('cost'), 2), $result->total->additions],
            ];
        }

        if (in_array('sales', $chosen, true)) {
            $sales = $this->applyTypeFilter($between(AssetSale::with($assetRelations), 'sale_date'), $filters, 'asset')
                ->orderBy('sale_date')->orderBy('id')->get();
            $rows = $sales->map(function (AssetSale $sale) use ($traces) {
                $at = $traces->get($sale->asset_id)?->valueAt['sale:' . $sale->id] ?? null;

                return (object) [
                    'sale' => $sale,
                    'cost' => $at['cost'] ?? null,
                    'nbv' => $at['nbv'] ?? null,
                    'gain' => $at ? round((float) $sale->price - $at['nbv'], 2) : null,
                ];
            });
            $sections['sales'] = ['rows' => $rows, 'totals' => [
                'cost' => round($rows->sum('cost'), 2),
                'nbv' => round($rows->sum('nbv'), 2),
                'proceeds' => round($rows->sum(fn ($r) => (float) $r->sale->price), 2),
                'gain' => round($rows->sum('gain'), 2),
            ]];
        }

        if (in_array('disposals', $chosen, true)) {
            $disposals = $this->applyTypeFilter($between(AssetDisposal::with($assetRelations), 'disposal_date'), $filters, 'asset')
                ->orderBy('disposal_date')->orderBy('id')->get();
            $rows = $disposals->map(function (AssetDisposal $disposal) use ($traces, $movement) {
                // Approved: the value the summary wrote off. Pending / rejected: the asset was still held that day.
                $trace = $traces->get($disposal->asset_id);
                $at = $trace?->valueAt['disposal:' . $disposal->id] ?? null;
                if (! $at && $trace && $disposal->approval_status !== 'approved') {
                    $at = $movement->bookValue($trace->asset, substr((string) $disposal->disposal_date, 0, 10));
                }

                return (object) ['disposal' => $disposal, 'cost' => $at['cost'] ?? null, 'nbv' => $at['nbv'] ?? null];
            });
            $counted = $rows->reject(fn ($r) => $r->disposal->approval_status === 'rejected');
            $sections['disposals'] = ['rows' => $rows, 'totals' => [
                'byMethod' => $counted->groupBy(fn ($r) => $r->disposal->method ?: 'Not stated')->sortKeys()
                    ->map(fn ($group) => (object) ['count' => $group->count(), 'cost' => round($group->sum('cost'), 2), 'nbv' => round($group->sum('nbv'), 2)]),
                'rejected' => $rows->count() - $counted->count(),
                'approvedNbv' => round($rows->where('disposal.approval_status', 'approved')->sum('nbv'), 2),
            ]];
        }

        if (isset($sections['sales']) && isset($sections['disposals'])) {
            $sections['disposals']['check'] = ['Disposals & Sales',
                round($sections['sales']['totals']['nbv'] + $sections['disposals']['totals']['approvedNbv'], 2), $result->total->disposals];
        }

        if (in_array('modifications', $chosen, true)) {
            $mods = $this->applyTypeFilter($between(AssetModification::with(['asset', 'componentAsset', 'removedAsset', 'resultAsset']), 'modification_date'), $filters, 'asset')
                ->orderBy('modification_date')->orderBy('id')->get();
            $reverted = $mods->whereNotNull('reverted_at')->count();   // ignored by the summary too
            $rows = $mods->whereNull('reverted_at')->values()->map(function (AssetModification $mod) {
                $before = $mod->parent_original_cost !== null ? (float) $mod->parent_original_cost : null;
                $after = $mod->new_value !== null ? (float) $mod->new_value : null;

                return (object) ['mod' => $mod, 'before' => $before, 'after' => $after,
                    'change' => $before !== null && $after !== null ? round($after - $before, 2) : null];
            });
            $sections['modifications'] = ['rows' => $rows, 'totals' => [
                'upgrades' => $rows->where('mod.modification_type', 'upgrade')->count(),
                'downgrades' => $rows->where('mod.modification_type', 'downgrade')->count(),
                'change' => round($rows->sum('change'), 2),
                'unknown' => $rows->whereNull('change')->count(),
                'reverted' => $reverted,
            ]];
        }

        if (in_array('revaluations', $chosen, true)) {
            $revals = $this->applyTypeFilter($between(AssetRevaluation::with([...$assetRelations, 'asset.layers', 'resultAsset.layers']), 'revaluation_date'), $filters, 'asset')
                ->orderBy('revaluation_date')->orderBy('id')->get();
            $rows = $revals->map(function (AssetRevaluation $reval) {
                // The layer(s) the revaluation opened: on the successor, or on the asset itself (legacy, in place).
                $date = $reval->revaluation_date->toDateString();
                $opened = ($reval->resultAsset ?? $reval->asset)?->layers
                    ->filter(fn ($layer) => $layer->start_date?->toDateString() === $date) ?? collect();
                $monthly = $opened->whereNotNull('monthly_depreciation');

                return (object) [
                    'reval' => $reval,
                    'change' => round($reval->revalued_amount - $reval->previous_value, 2),
                    'monthly' => $monthly->isEmpty() ? null : round($monthly->sum(fn ($l) => (float) $l->monthly_depreciation), 2),
                ];
            });
            $change = round($rows->sum('change'), 2);
            $sections['revaluations'] = ['rows' => $rows, 'totals' => [
                'previous' => round($rows->sum(fn ($r) => $r->reval->previous_value), 2),
                'revalued' => round($rows->sum(fn ($r) => $r->reval->revalued_amount), 2),
                'change' => $change,
            ], 'check' => ['Revaluation Adj.', $change, $result->total->revaluation]];
        }

        if (in_array('depreciation', $chosen, true)) {
            $rows = $movement->depreciationDetail($result);
            $charge = round($rows->sum('charge'), 2);
            $sections['depreciation'] = ['rows' => $rows, 'totals' => [
                'charge' => $charge,
                'accumulated' => round($rows->sum('accumulated'), 2),
                'nbv' => round($rows->sum('nbv'), 2),
            ], 'check' => ['Depreciation', $charge, $result->total->depreciation]];
        }

        // The same check as the summary's reconciliation: a gap above the tolerance is shown, never hidden.
        foreach ($sections as &$section) {
            if (isset($section['check'])) {
                [$column, $detail, $summary] = $section['check'];
                $section['check'] = (object) ['column' => $column, 'detail' => $detail, 'summary' => $summary,
                    'difference' => round($detail - $summary, 2), 'ok' => abs($detail - $summary) <= PeriodMovement::TOLERANCE];
            }
        }
        unset($section);

        return $sections;
    }

    
    //Quick presets for the period selector. "Month End" is Last Month.
    
    private function presets(): array
    {
        $today = Carbon::today();
        [$fyStart, $fyEnd] = Depreciation::range(Depreciation::label(Depreciation::financialYearOf($today)));
        $lastFyStart = $fyStart->copy()->subYear();

        return [
            'this-month' => ['label' => 'This Month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->copy()->endOfMonth()->toDateString()],
            'last-month' => ['label' => 'Last Month (Month End)', 'from' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this-fy' => ['label' => 'This Financial Year', 'from' => $fyStart->toDateString(), 'to' => $fyEnd->copy()->subDay()->toDateString()],
            'last-fy' => ['label' => 'Last Financial Year', 'from' => $lastFyStart->toDateString(), 'to' => $fyStart->copy()->subDay()->toDateString()],
        ];
    }
}
