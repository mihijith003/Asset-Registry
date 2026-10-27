<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetModification;
use App\Models\AssetRevaluation;
use App\Models\AssetSale;
use App\Models\AssetType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Movement summary for a period: Opening NBV + Additions + Revaluation Adj. - Depreciation - Disposals & Sales
 * + Adjustments = Closing NBV, per asset type, with a reconciliation check that is never hidden.
 *
 * Everything is decided by DATE from the event history (purchases, modifications, revaluations, sales, approved
 * disposals), never from current status flags, and starts from Asset::withTrashed().
 *
 *  - An asset enters by PURCHASE (an Addition) or as a PRODUCT of a modification / revaluation (a transfer in).
 *    It is a product only if that event is the first one that mentions it AND it was created no earlier than the
 *    event's input assets - so a bought asset that later becomes a removed component is still an addition.
 *  - It leaves by SALE / approved DISPOSAL (Disposals & Sales) or by MERGE / SUPERSEDE (a transfer out: its value
 *    continues in the combined asset or the revaluation successor). Pending disposals = still held.
 *  - Same-date events for one asset run in the only order its held / not-held state allows; see orderDay().
 *
 * Book value at a date is computed from layers (cost - Depreciation::accumulated), choosing the layers in force for
 * that moment - see value(). Revaluation Adj. uses the STORED revalued_amount - previous_value; where that differs
 * from the movement the layers show, the gap is an Adjustment attributed to that revaluation record. A gap no
 * revaluation record explains is an error (see row()), never absorbed.
 */
class PeriodMovement
{
    public const TOLERANCE = 0.01;

    private const IN_KINDS = ['purchase' => 0, 'mod_product' => 1, 'reval_product' => 1, 'mod_revival' => 2, 'reval_in' => 2];
    private const HELD_KINDS = ['reval_inplace' => 0, 'mod_out' => 1, 'reval_out' => 2, 'sale' => 3, 'disposal' => 3];

    /** Day before date_from: the opening position is "as at end of" this day. */
    public string $openingDate;

    /** min(date_to, today): closing position; events after it are ignored. */
    public string $closingDate;

    public bool $closingCapped;

    private array $accCache = [];

    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public array $filters = [],
        ?Carbon $today = null,
    ) {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $this->openingDate = $from->copy()->subDay()->toDateString();
        $this->closingCapped = $to->gt($today);
        $this->closingDate = ($this->closingCapped ? $today : $to)->toDateString();
    }

    /**
     * @return object{rows: Collection, total: object, assets: Collection, notes: array}
     */
    public function run(): object
    {
        $assets = Asset::withTrashed()->with('layers')->ofType($this->filters)->get()->keyBy('id');
        $createdAt = Asset::withTrashed()->pluck('created_at', 'id');   // for the "created by this event" check

        $mods = AssetModification::orderBy('id')->get();
        $revals = AssetRevaluation::orderBy('id')->get();
        $notes = [];

        if ($n = $mods->whereNotNull('reverted_at')->count()) {
            $notes[] = "{$n} reverted modification(s) (legacy) are ignored.";
            $mods = $mods->whereNull('reverted_at');
        }

        $products = $this->classifyProducts($mods, $revals, $createdAt);
        $events = $this->buildEvents($assets, $mods, $revals, $products, $notes);

        $traces = collect();
        $flows = [];       // [type_id][column] => amount
        $eventIo = [];     // [event key][type_id] => ['in' => .., 'out' => .., 'stored' => ..]

        foreach ($assets as $asset) {
            $trace = $this->replay($asset, $events[$asset->id] ?? [], $notes, $eventIo);
            $traces->put($asset->id, $trace);

            $f = &$flows[$asset->asset_type_id];
            foreach (['opening', 'additions', 'reval_stored', 'depreciation', 'disposals', 'closing'] as $col) {
                $f[$col] = round(($f[$col] ?? 0) + $trace->$col, 2);
            }
            unset($f);
        }

        // Attribute gaps. (a) A revaluation whose stored figures differ from what the layers produce is an
        // ADJUSTMENT: known stale history, shown in the Adjustments column so the row still balances. (b) Anything
        // else - a modification that did not conserve value, or a remainder no record explains - is an ERROR.
        // Amounts carry the column's sign: Opening + ... + Adjustments = Closing.
        $adjustments = [];
        $errors = [];
        foreach ($eventIo as $key => $byType) {
            foreach ($byType as $typeId => $io) {
                $engine = round(($io['in'] ?? 0) - ($io['out'] ?? 0), 2);
                if (str_starts_with($key, 'reval:') && isset($io['stored'])) {
                    $amount = round($engine - $io['stored'], 2);
                    if (abs($amount) > 0.004) {
                        // Recalculated = the previous value the layers imply for the recorded revalued amount, so
                        // Recorded - Recalculated = Difference exactly.
                        $adjustments[] = ['type' => $typeId, 'ref' => $io['label'], 'asset' => $io['asset'], 'date' => $io['date'],
                            'recorded' => $io['previous'], 'recalculated' => round($io['previous'] - $amount, 2), 'amount' => $amount];
                    }
                } elseif (abs($engine) > self::TOLERANCE) {
                    $errors[] = ['type' => $typeId, 'amount' => $engine, 'label' => $io['label']
                        . ': value in ' . number_format($io['in'] ?? 0, 2) . ' vs value out ' . number_format($io['out'] ?? 0, 2)];
                }
            }
        }

        $types = AssetType::whereIn('id', array_keys($flows))->orderBy('type_name')->get();
        $rows = $types->map(fn ($type) => $this->row($type->type_name, $type->type_code, $flows[$type->id],
            collect($adjustments)->where('type', $type->id)->values(), collect($errors)->where('type', $type->id)->values()))
            ->filter(fn ($row) => $row->hasActivity)
            ->values();

        $sum = fn ($col) => round($rows->sum($col), 2);
        $total = $this->row('Grand total', null, [
            'opening' => $sum('opening'), 'additions' => $sum('additions'), 'reval_stored' => $sum('revaluation'),
            'depreciation' => $sum('depreciation'), 'disposals' => $sum('disposals'), 'closing' => $sum('closing'),
        ], collect($adjustments), collect($errors));

        return (object) ['rows' => $rows, 'total' => $total, 'assets' => $traces->filter(fn ($t) => $t->involved)->values(), 'notes' => $notes];
    }

    /**
     * One summary row. adjustment = the attributed revaluation gaps plus any sub-cent rounding remainder, so that
     * expectedClosing + adjustment = closing. A remainder above TOLERANCE is never absorbed: it is the row's error.
     */
    private function row(string $name, ?string $code, array $f, Collection $adjustments, Collection $errors): object
    {
        $row = (object) [
            'name' => $name,
            'code' => $code,
            'opening' => $f['opening'] ?? 0.0,
            'additions' => $f['additions'] ?? 0.0,
            'revaluation' => $f['reval_stored'] ?? 0.0,
            'depreciation' => $f['depreciation'] ?? 0.0,
            'disposals' => $f['disposals'] ?? 0.0,
            'closing' => $f['closing'] ?? 0.0,
        ];

        $row->expectedClosing = round($row->opening + $row->additions + $row->revaluation - $row->depreciation - $row->disposals, 2);
        $gap = round($row->closing - $row->expectedClosing, 2);
        $explained = round($adjustments->sum('amount'), 2);
        $remainder = round($gap - $explained, 2);

        $row->adjustmentDetail = $adjustments->all();
        $row->rounding = abs($remainder) <= self::TOLERANCE ? $remainder : 0.0;
        $row->adjustment = round($explained + $row->rounding, 2);
        $row->error = round($gap - $row->adjustment, 2);
        $row->reconciles = abs($row->error) <= self::TOLERANCE;
        $row->errors = $errors->pluck('amount', 'label')->all();
        $row->unexplained = round($row->error - $errors->sum('amount'), 2);
        $row->hasActivity = collect([$row->opening, $row->additions, $row->revaluation, $row->depreciation, $row->disposals, $row->closing])
            ->contains(fn ($v) => abs($v) > 0.004);

        return $row;
    }

    // ------------------------------------------------------------------ classification

    /**
     * asset_id => ['kind' => 'mod'|'reval', 'id' => event id] for every asset that was CREATED by a modification
     * (result / removed component) or a revaluation (successor).
     */
    private function classifyProducts(Collection $mods, Collection $revals, Collection $createdAt): array
    {
        $mentions = [];   // asset_id => [sortKey, kind, eventId, isProductRole]
        $note = function ($assetId, string $date, int $roleRank, string $kind, int $id) use (&$mentions) {
            if (! $assetId) {
                return;
            }
            $key = [$date, $roleRank, $kind === 'mod' ? 0 : 1, $id];
            if (! isset($mentions[$assetId]) || $key < $mentions[$assetId][0]) {
                $mentions[$assetId] = [$key, $kind, $id, $roleRank === 0];
            }
        };

        foreach ($mods as $m) {
            $d = $this->d($m->modification_date);
            $note($m->result_asset_id, $d, 0, 'mod', $m->id);
            $note($m->removed_asset_id, $d, 0, 'mod', $m->id);
            $note($m->asset_id, $d, 1, 'mod', $m->id);
            $note($m->component_asset_id, $d, 1, 'mod', $m->id);
        }
        foreach ($revals as $r) {
            $d = $this->d($r->revaluation_date);
            $note($r->result_asset_id, $d, 0, 'reval', $r->id);
            $note($r->asset_id, $d, 1, 'reval', $r->id);
        }

        $modsById = $mods->keyBy('id');
        $revalsById = $revals->keyBy('id');
        $products = [];

        foreach ($mentions as $assetId => [, $kind, $eventId, $isProductRole]) {
            if (! $isProductRole) {
                continue;
            }
            $inputs = $kind === 'mod'
                ? [$modsById[$eventId]->asset_id, $modsById[$eventId]->component_asset_id]
                : [$revalsById[$eventId]->asset_id];
            $latestInput = collect($inputs)->filter()->map(fn ($id) => $createdAt[$id] ?? null)->filter()->max();

            // Created no earlier than what the event consumed (created_at, not the typed-in event date).
            if (! $latestInput || ($createdAt[$assetId] ?? null) >= $latestInput) {
                $products[$assetId] = ['kind' => $kind, 'id' => $eventId];
            }
        }

        return $products;
    }

    /**
     * asset_id => [date => [events]] for the assets in the report.
     */
    private function buildEvents(Collection $assets, Collection $mods, Collection $revals, array $products, array &$notes): array
    {
        $events = [];
        $add = function ($assetId, ?string $date, string $kind, array $extra = []) use (&$events, $assets) {
            if (! $assets->has($assetId) || ! $date) {
                return;
            }
            $events[$assetId][$date][] = ['kind' => $kind, 'date' => $date] + $extra;
        };

        foreach ($assets as $asset) {
            if (! isset($products[$asset->id])) {
                $add($asset->id, $this->d($asset->purchase_date) ?? $this->d($asset->created_at), 'purchase', ['ref' => 0]);
            }
        }

        foreach ($mods as $m) {
            $d = $this->d($m->modification_date);
            $meta = ['ref' => $m->id, 'key' => 'mod:' . $m->id, 'label' => $m->reference];
            $add($m->asset_id, $d, 'mod_out', $meta);
            $add($m->component_asset_id, $d, 'mod_out', $meta);
            foreach ([$m->result_asset_id, $m->removed_asset_id] as $id) {
                $isProduct = ($products[$id] ?? null) === ['kind' => 'mod', 'id' => $m->id];
                $add($id, $d, $isProduct ? 'mod_product' : 'mod_revival', $meta);
            }
        }

        foreach ($revals as $r) {
            $d = $this->d($r->revaluation_date);
            $meta = ['ref' => $r->id, 'key' => 'reval:' . $r->id, 'label' => $r->reference,
                'stored' => round((float) $r->revalued_amount - (float) $r->previous_value, 2), 'previous' => (float) $r->previous_value];
            $add($r->asset_id, $d, $r->result_asset_id ? 'reval_out' : 'reval_inplace', $meta);
            if ($r->result_asset_id) {
                $isProduct = ($products[$r->result_asset_id] ?? null) === ['kind' => 'reval', 'id' => $r->id];
                $add($r->result_asset_id, $d, $isProduct ? 'reval_product' : 'reval_in', $meta);
            }
        }

        foreach (AssetSale::whereIn('asset_id', $assets->keys())->get() as $s) {
            if (! $s->sale_date) {
                $notes[] = "{$s->reference} has no sale date and is not included.";
            }
            $add($s->asset_id, $this->d($s->sale_date), 'sale', ['ref' => $s->id, 'label' => $s->reference]);
        }

        // Approved only: a pending Lost claim is still held, a rejected one never happened.
        foreach (AssetDisposal::whereIn('asset_id', $assets->keys())->where('approval_status', 'approved')->get() as $x) {
            if (! $x->disposal_date) {
                $notes[] = "{$x->reference} has no disposal date and is not included.";
            }
            $add($x->asset_id, $this->d($x->disposal_date), 'disposal', ['ref' => $x->id, 'label' => $x->reference]);
        }

        foreach ($assets as $asset) {
            if ($asset->superseded_by_asset_id && ! $revals->firstWhere('asset_id', $asset->id)) {
                $notes[] = "{$asset->asset_code} is marked superseded but has no revaluation record; it is treated as still held.";
            }
        }

        return $events;
    }

    // ------------------------------------------------------------------ replay one asset

    private function replay(Asset $asset, array $byDate, array &$notes, array &$eventIo): object
    {
        ksort($byDate);
        $t = (object) [
            'asset' => $asset, 'opening' => 0.0, 'additions' => 0.0, 'reval_stored' => 0.0, 'depreciation' => 0.0,
            'disposals' => 0.0, 'closing' => 0.0, 'events' => [], 'involved' => false,
        ];

        $held = false;
        $holdStart = null;
        $intervals = [];
        $openingDone = false;

        foreach ($byDate as $date => $dayEvents) {
            if ($date > $this->closingDate) {
                break;   // after the closing date (or today): has not happened for this report
            }
            if (! $openingDone && $date > $this->openingDate) {
                $t->opening = $held ? $this->value($asset, $this->openingDate, 'after') : 0.0;
                $openingDone = true;
            }

            $inPeriod = $date > $this->openingDate;
            $inplaceDone = false;
            $hasInplace = collect($dayEvents)->contains('kind', 'reval_inplace');

            foreach ($this->orderDay($dayEvents, $held, $asset, $notes) as $e) {
                $mode = $inplaceDone ? 'after' : ($hasInplace ? 'before' : 'during');
                $type = $asset->asset_type_id;

                switch ($e['kind']) {
                    case 'purchase':
                    case 'mod_product':
                    case 'reval_product':
                    case 'mod_revival':
                    case 'reval_in':
                        $v = $this->value($asset, $date, $mode);
                        $held = true;
                        $holdStart = $date;
                        if ($inPeriod) {
                            if ($e['kind'] === 'purchase') {
                                $t->additions = round($t->additions + $v, 2);
                            } else {
                                $eventIo[$e['key']][$type]['in'] = round(($eventIo[$e['key']][$type]['in'] ?? 0) + $v, 2);
                                $eventIo[$e['key']][$type]['label'] ??= $e['label'];
                            }
                            $t->events[] = [$date, $e['kind'], $e['label'] ?? 'Purchase', $v];
                        }
                        break;

                    case 'reval_inplace':
                        $before = $this->value($asset, $date, 'before');
                        $after = $this->value($asset, $date, 'after');
                        $inplaceDone = true;
                        if ($inPeriod) {
                            $t->reval_stored = round($t->reval_stored + $e['stored'], 2);
                            $eventIo[$e['key']][$type] = ['in' => $after, 'out' => $before, 'stored' => $e['stored'],
                                'previous' => $e['previous'], 'label' => $e['label'], 'asset' => $asset->asset_code . ' (in place)', 'date' => $date];
                            $t->events[] = [$date, 'reval_inplace', $e['label'], $e['stored']];
                        }
                        break;

                    default:   // mod_out, reval_out, sale, disposal
                        $v = $this->value($asset, $date, $mode);
                        $held = false;
                        $intervals[] = [$holdStart, $date];
                        if ($inPeriod) {
                            if (in_array($e['kind'], ['sale', 'disposal'], true)) {
                                $t->disposals = round($t->disposals + $v, 2);
                            } else {
                                $io = &$eventIo[$e['key']][$type];
                                $io['out'] = round(($io['out'] ?? 0) + $v, 2);
                                $io['label'] ??= $e['label'];
                                if ($e['kind'] === 'reval_out') {
                                    $t->reval_stored = round($t->reval_stored + $e['stored'], 2);
                                    $io += ['stored' => $e['stored'], 'previous' => $e['previous'], 'date' => $date];
                                    $io['label'] = $e['label'];
                                    $io['asset'] = $asset->asset_code . ' (superseded)';
                                }
                                unset($io);
                            }
                            $t->events[] = [$date, $e['kind'], $e['label'], $v];
                        }
                }
            }
        }

        if (! $openingDone) {
            $t->opening = $held && $holdStart <= $this->openingDate ? $this->value($asset, $this->openingDate, 'after') : 0.0;
        }
        if ($held) {
            $intervals[] = [$holdStart, null];
            $t->closing = $this->value($asset, $this->closingDate, 'after');
        }

        // Depreciation: per layer, the months completed while the asset was held, inside the period.
        foreach ($intervals as [$start, $end]) {
            $a = max($start, $this->openingDate);
            $b = min($end ?? $this->closingDate, $this->closingDate);
            if ($a >= $b) {
                continue;
            }
            foreach ($asset->layers as $layer) {
                $t->depreciation += $this->acc($layer, $b) - $this->acc($layer, $a);
            }
        }
        $t->depreciation = round($t->depreciation, 2);

        $t->involved = $t->opening || $t->closing || $t->additions || $t->disposals || $t->reval_stored || $t->events;

        return $t;
    }

    /**
     * Same-date events for one asset, in the only order its state allows: while held, the next event must take it
     * out (in-place revaluation first, then merge, supersede, sale / disposal); while not held, the next must bring it
     * in (purchase, then creation, then revival). Ties: record id.
     */
    private function orderDay(array $pending, bool $held, Asset $asset, array &$notes): array
    {
        $ordered = [];

        while ($pending) {
            $allowed = $held ? self::HELD_KINDS : self::IN_KINDS;
            $candidates = array_filter($pending, fn ($e) => isset($allowed[$e['kind']]));

            if (! $candidates) {
                $notes[] = "{$asset->asset_code}: event " . ($pending[array_key_first($pending)]['label'] ?? '') . ' on '
                    . $pending[array_key_first($pending)]['date'] . ' does not fit the asset\'s history and is ignored.';
                array_shift($pending);
                continue;
            }

            uasort($candidates, fn ($x, $y) => [$allowed[$x['kind']], $x['ref']] <=> [$allowed[$y['kind']], $y['ref']]);
            $i = array_key_first($candidates);
            $e = $pending[$i];
            unset($pending[$i]);
            $ordered[] = $e;

            if ($e['kind'] !== 'reval_inplace') {
                $held = isset(self::IN_KINDS[$e['kind']]);
            }
        }

        return $ordered;
    }

    // ------------------------------------------------------------------ valuation

    /**
     * Book value from the layers in force:
     *  - 'after'  end of the day: layers started on/before $date and not closed by then (end_date > $date)
     *  - 'during' a moment on the day: also layers that close that day (end_date = $date), e.g. just before a supersede
     *  - 'before' just before an in-place revaluation on the day: the closing layers, not the new ones it opened
     */
    private function value(Asset $asset, string $date, string $mode): float
    {
        $sum = 0.0;

        foreach ($asset->layers as $layer) {
            $s = $this->d($layer->start_date);
            $e = $this->d($layer->end_date);

            $inForce = match ($mode) {
                'after' => $s <= $date && ($e === null || $e > $date),
                'during' => $s <= $date && ($e === null || $e >= $date),
                'before' => $s <= $date && (($e !== null && $e >= $date) || ($e === null && $s < $date)),
            };

            if ($inForce) {
                $sum += (float) $layer->cost - $this->acc($layer, $date);
            }
        }

        return round($sum, 2);
    }

    private function acc($layer, string $date): float
    {
        return $this->accCache[$layer->id . '@' . $date] ??= Depreciation::accumulated([$layer], Carbon::parse($date));
    }

    private function d($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    }
}
