<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * NEW: straight-line, monthly depreciation (no residual value).
 *
 * Every asset's cost is held in one or more "layers" (asset_depreciation_layers). A layer has its own cost,
 * start date and useful life, so an asset made by combining assets that were registered on different dates
 * keeps each part's own age: the combined asset's depreciation is simply the sum of its layers.
 *
 *   accumulated depreciation of a layer = cost x (complete months since the start date, capped at the
 *                                         life) / (useful life in months)
 */
class Depreciation
{
    /**
     * Complete months from $from to $to (0 if $to is not after $from).
     */
    public static function monthsBetween(Carbon $from, Carbon $to): int
    {
        if ($to->lte($from)) {
            return 0;
        }

        $months = ($to->year - $from->year) * 12 + ($to->month - $from->month);

        if ($to->day < $from->day) {
            $months--;
        }

        return max(0, $months);
    }

    /**
     * Accumulated depreciation of the given layers (models or arrays) as of a date.
     * When residual_value is provided (or resolved from the asset), it is apportioned
     * across the active layers in proportion to each layer's cost, and depreciation stops
     * once net book value reaches residual_value.
     * Layers with end_date finished before or on $asOf are superseded and excluded.
     */
    public static function accumulated(iterable $layers, Carbon $asOf, ?float $residualValue = null): float
    {
        $layerList = is_array($layers) ? array_values($layers) : iterator_to_array($layers, false);

        if (empty($layerList)) {
            return 0.0;
        }

        $asOfDate = $asOf->copy()->startOfDay();

        // Filter active layers as of $asOfDate:
        // Exclude layers that have not started yet (start_date > asOfDate)
        // or that have already finished (end_date <= asOfDate).
        $activeLayers = [];
        foreach ($layerList as $layer) {
            $start = self::value($layer, 'start_date');
            $end = self::value($layer, 'end_date');

            if ($start && Carbon::parse($start)->startOfDay()->gt($asOfDate)) {
                continue; // Not yet started as of this date
            }

            if ($end && Carbon::parse($end)->startOfDay()->lte($asOfDate)) {
                continue; // Superseded / finished at or before this date
            }

            $activeLayers[] = $layer;
        }

        if (empty($activeLayers)) {
            return 0.0;
        }

        if ($residualValue === null) {
            $residualValue = self::resolveResidualValue($activeLayers);
        }

        $totalCost = 0.0;
        foreach ($activeLayers as $layer) {
            $cost = (float) self::value($layer, 'cost');
            if ($cost > 0) {
                $totalCost += $cost;
            }
        }

        if ($totalCost <= 0.0) {
            return 0.0;
        }

        $residualValue = max(0.0, min((float) $residualValue, $totalCost));
        $depreciableRatio = ($totalCost > 0) ? ($totalCost - $residualValue) / $totalCost : 1.0;

        $totalAccumulated = 0.0;

        foreach ($activeLayers as $layer) {
            $cost = (float) self::value($layer, 'cost');
            $life = (int) self::value($layer, 'useful_life_months');
            $start = self::value($layer, 'start_date');
            $end = self::value($layer, 'end_date');

            if ($life <= 0 || $cost <= 0 || ! $start) {
                continue; // no useful life set = not depreciated
            }

            // If a layer has an end_date that occurs before $asOfDate, cap the period at end_date
            $effectiveEnd = ($end && Carbon::parse($end)->startOfDay()->lt($asOfDate))
                ? Carbon::parse($end)->startOfDay()
                : $asOfDate;

            $months = min($life, self::monthsBetween(Carbon::parse($start)->startOfDay(), $effectiveEnd));
            $depreciableCost = $cost * $depreciableRatio;
            $totalAccumulated += $depreciableCost * $months / $life;
        }

        $maxDepreciation = max(0.0, $totalCost - $residualValue);

        return round(min($totalAccumulated, $maxDepreciation), 2);
    }

    private static function resolveResidualValue(array $layerList): float
    {
        static $assetCache = [];

        foreach ($layerList as $layer) {
            if ($layer instanceof \App\Models\AssetDepreciationLayer) {
                if ($layer->relationLoaded('asset') && $layer->asset) {
                    return (float) ($layer->asset->residual_value ?? 0);
                }
                if (! empty($layer->asset_id)) {
                    $asset = $assetCache[$layer->asset_id] ??= \App\Models\Asset::find($layer->asset_id);
                    return (float) ($asset?->residual_value ?? 0);
                }
            } elseif (is_array($layer) && isset($layer['asset_id'])) {
                $asset = $assetCache[$layer['asset_id']] ??= \App\Models\Asset::find($layer['asset_id']);
                return (float) ($asset?->residual_value ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * Split $amount of cost out of $layers, taking from the layers whose source asset is in $preferSourceIds
     * first (null = no preference) and any overflow from the other layers, each in proportion to its cost.
     * Every slice keeps its layer's start date and life.
     *
     * @return array{0: array, 1: array}  [removed layers, remaining layers]
     */
    public static function slice(array $layers, float $amount, ?array $preferSourceIds = null): array
    {
        $layers = array_values($layers);
        $total = array_sum(array_map(fn ($l) => (float) $l['cost'], $layers));
        $amount = max(0.0, min($amount, $total));

        $isPreferred = fn ($l) => $preferSourceIds === null || in_array((int) ($l['source_asset_id'] ?? 0), $preferSourceIds, true);

        $preferredTotal = 0.0;
        foreach ($layers as $l) {
            if ($isPreferred($l)) {
                $preferredTotal += (float) $l['cost'];
            }
        }
        $otherTotal = $total - $preferredTotal;

        $fromPreferred = min($amount, $preferredTotal);
        $fromOthers = $amount - $fromPreferred;

        $shares = [];
        foreach ($layers as $i => $l) {
            $cost = (float) $l['cost'];
            if ($isPreferred($l)) {
                $shares[$i] = $preferredTotal > 0 ? round($fromPreferred * $cost / $preferredTotal, 2) : 0.0;
            } else {
                $shares[$i] = $otherTotal > 0 ? round($fromOthers * $cost / $otherTotal, 2) : 0.0;
            }
        }

        // Put any rounding difference on the layer with the largest share so the slices add up exactly.
        $drift = round($amount - array_sum($shares), 2);
        if ($drift != 0.0 && $shares) {
            $largest = array_keys($shares, max($shares))[0];
            $shares[$largest] = round($shares[$largest] + $drift, 2);
        }

        $removed = [];
        $remaining = [];
        foreach ($layers as $i => $l) {
            $share = $shares[$i];
            $left = round((float) $l['cost'] - $share, 2);

            if ($share > 0) {
                $removed[] = ['cost' => $share] + $l;
            }
            if ($left > 0) {
                $remaining[] = ['cost' => $left] + $l;
            }
        }

        return [$removed, $remaining];
    }

    // ---------------------------------------------------------------- financial years

    public static function startMonth(): int
    {
        return min(12, max(1, (int) config('depreciation.financial_year_start_month', 4)));
    }

    /**
     * The calendar year in which the financial year that contains $date starts.
     */
    public static function financialYearOf(?Carbon $date = null): int
    {
        $date ??= Carbon::today();

        return $date->month >= self::startMonth() ? $date->year : $date->year - 1;
    }

    /**
     * "2025/2026" (or "2025" when the financial year is the calendar year).
     */
    public static function label(int $startYear): string
    {
        return self::startMonth() === 1 ? (string) $startYear : $startYear . '/' . ($startYear + 1);
    }

    /**
     * Labels for the "Financial Year" dropdowns, newest first.
     *
     * @return string[]
     */
    public static function financialYearOptions(): array
    {
        $current = self::financialYearOf();
        $options = [];

        for ($y = $current + (int) config('depreciation.years_forward', 1); $y >= $current - (int) config('depreciation.years_back', 10); $y--) {
            $options[] = self::label($y);
        }

        return $options;
    }

    /**
     * First day of the financial year with this label, and the first day AFTER it (end boundary).
     *
     * @return array{0: Carbon, 1: Carbon}|null  null for a label that is not a valid financial year
     */
    public static function range(string $label): ?array
    {
        if (! preg_match('/^(\d{4})(?:\/(\d{4}))?$/', $label, $m)) {
            return null;
        }

        $startYear = (int) $m[1];

        if ($label !== self::label($startYear)) {
            return null;
        }

        $start = Carbon::create($startYear, self::startMonth(), 1)->startOfDay();

        return [$start, $start->copy()->addYear()];
    }

    private static function value($layer, string $key)
    {
        return is_array($layer) ? ($layer[$key] ?? null) : $layer->{$key};
    }
}
