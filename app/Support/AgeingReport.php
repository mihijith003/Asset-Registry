<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ageing of the assets held at an "as at" date: a type / sub type x age band matrix of counts and net book values.
 *
 * Which assets: PeriodMovement::heldAt() - decided by date from the event history (withTrashed), the same rules as
 * the Period Report's balances, so the two tabs agree.
 * Age: from purchase_date, else created_at - the date the Period Report counts an addition on and an asset's first
 * depreciation layer starts on. Whole months; an asset is in the band whose [from, to) years contain its age.
 */
class AgeingReport
{
    /**
     * The age bands, in years: [from, to) with to = null for "and over". The ONE place to change them - labels,
     * boundaries and columns are all derived from this.
     */
    public const BANDS = [[0, 1], [1, 2], [2, 3], [3, 5], [5, null]];

    public function __construct(public Carbon $asAt, public array $filters = [])
    {
    }

    /**
     * Band key => label, e.g. '0-1' => '0-1 years', '5+' => '5+ years'.
     */
    public static function bands(): array
    {
        return collect(self::BANDS)->mapWithKeys(function (array $band) {
            [$from, $to] = $band;
            $key = $to === null ? "{$from}+" : "{$from}-{$to}";

            return [$key => "{$key} years"];
        })->all();
    }

    /**
     * The date an asset's age counts from: purchase_date, else created_at (the ONE rule, shared with the dashboard).
     */
    public static function ageFrom(Asset $asset): ?Carbon
    {
        $since = $asset->purchase_date ?? $asset->created_at;

        return $since ? Carbon::parse($since)->startOfDay() : null;
    }

    /**
     * Age in whole months at $asAt.
     */
    public static function ageMonths(Asset $asset, Carbon $asAt): int
    {
        $since = self::ageFrom($asset);

        return $since ? Depreciation::monthsBetween($since, $asAt->copy()->startOfDay()) : 0;
    }

    /**
     * Share of $count in $total as a percentage, 1 dp (the Ageing Report's percentage row and the dashboard chart).
     */
    public static function share(int $count, int $total): float
    {
        return $total ? round($count / $total * 100, 1) : 0.0;
    }

    public static function bandFor(int $months): string
    {
        foreach (self::BANDS as [$from, $to]) {
            if ($months >= $from * 12 && ($to === null || $months < $to * 12)) {
                return $to === null ? "{$from}+" : "{$from}-{$to}";
            }
        }

        return array_key_first(self::bands());   // a date after the as-at date cannot happen (not held yet)
    }

    /**
     * @return object{bands: array, assets: Collection, types: Collection, total: object}
     */
    public function run(): object
    {
        $asAt = $this->asAt->copy()->startOfDay();
        $held = PeriodMovement::heldAt($asAt, $this->filters);
        (new \Illuminate\Database\Eloquent\Collection($held->pluck('asset')->all()))->loadMissing(['assetType', 'subType', 'brand']);

        $assets = $held->map(function ($row) use ($asAt) {
            $since = self::ageFrom($row->asset);
            $months = self::ageMonths($row->asset, $asAt);

            return (object) [
                'asset' => $row->asset,
                'since' => $since?->toDateString(),
                'sinceIsCreated' => $row->asset->purchase_date === null,
                'months' => $months,
                'band' => self::bandFor($months),
                'cost' => $row->cost,
                'accumulated' => round($row->cost - $row->nbv, 2),
                'nbv' => $row->nbv,
                'writtenDown' => $row->writtenDown,
            ];
        })->sortBy(fn ($a) => [$a->months * -1, $a->asset->asset_code])->values();

        $cells = fn (Collection $group) => $this->cells($group);

        $types = $assets->groupBy(fn ($a) => $a->asset->asset_type_id)
            ->map(fn (Collection $ofType) => (object) [
                'id' => $ofType->first()->asset->asset_type_id,
                'name' => $ofType->first()->asset->assetType->type_name ?? 'Unknown type',
                'cells' => $cells($ofType),
                'subTypes' => $ofType->groupBy(fn ($a) => $a->asset->asset_sub_type_id)
                    ->map(fn (Collection $ofSub) => (object) [
                        'id' => $ofSub->first()->asset->asset_sub_type_id,
                        'name' => trim($ofSub->first()->asset->subType->sub_type_name ?? 'Unknown sub type'),
                        'cells' => $cells($ofSub),
                    ])->sortBy('name')->values(),
            ])->sortBy('name')->values();

        $total = (object) ['cells' => $cells($assets)];
        $count = $assets->count();
        $total->share = collect(self::bands())->map(fn ($label, $band) => self::share($total->cells[$band]->count, $count))->all();

        return (object) ['bands' => self::bands(), 'assets' => $assets, 'types' => $types, 'total' => $total];
    }

    /**
     * Band key (and 'total') => {count, nbv, writtenDown} for a group of assets.
     */
    private function cells(Collection $group): array
    {
        $cell = fn (Collection $in) => (object) [
            'count' => $in->count(),
            'nbv' => round($in->sum('nbv'), 2),
            'writtenDown' => $in->where('writtenDown', true)->count(),
        ];

        $cells = [];
        foreach (array_keys(self::bands()) as $band) {
            $cells[$band] = $cell($group->where('band', $band));
        }
        $cells['total'] = $cell($group);

        return $cells;
    }

    /**
     * "2y 3m" for a number of whole months.
     */
    public static function ageLabel(int $months): string
    {
        return intdiv($months, 12) . 'y ' . ($months % 12) . 'm';
    }
}
