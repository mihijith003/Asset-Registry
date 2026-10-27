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
     *
     * For each layer:
     *   accumulated = complete months elapsed (bounded by useful_life_months and by end_date where set)
     *                 * monthly_depreciation
     *
     * Layers with a null useful_life_months or null monthly_depreciation are treated as not depreciated.
     * Accumulated depreciation for a layer never exceeds cost - residual_value for that layer.
     */
    public static function accumulated(iterable $layers, Carbon $asOf, ?float $residualValue = null): float
    {
        $layerList = is_array($layers) ? array_values($layers) : iterator_to_array($layers, false);

        if (empty($layerList)) {
            return 0.0;
        }

        $asOfDate = $asOf->copy()->startOfDay();

        // Filter layers that have started as of $asOfDate:
        // Exclude layers that have not started yet (start_date > asOfDate).
        $applicableLayers = [];
        foreach ($layerList as $layer) {
            $start = self::value($layer, 'start_date');

            if ($start && Carbon::parse($start)->startOfDay()->gt($asOfDate)) {
                continue; // Not yet started as of this date
            }

            $applicableLayers[] = $layer;
        }

        if (empty($applicableLayers)) {
            return 0.0;
        }

        $totalAccumulated = 0.0;

        foreach ($applicableLayers as $layer) {
            $cost = (float) self::value($layer, 'cost');
            $life = self::value($layer, 'useful_life_months');
            $monthly = self::value($layer, 'monthly_depreciation');
            $start = self::value($layer, 'start_date');
            $end = self::value($layer, 'end_date');

            if ($life === null || (int) $life <= 0 || $monthly === null || (float) $monthly <= 0 || $cost <= 0 || ! $start) {
                continue; // null useful_life_months or null monthly_depreciation = not depreciated
            }

            $life = (int) $life;
            $monthly = (float) $monthly;

            // A layer depreciates from start_date and stops at end_date (if set)
            $effectiveEnd = ($end && Carbon::parse($end)->startOfDay()->lt($asOfDate))
                ? Carbon::parse($end)->startOfDay()
                : $asOfDate;

            $months = min($life, self::monthsBetween(Carbon::parse($start)->startOfDay(), $effectiveEnd));
            $layerResidual = max(0.0, $cost - ($monthly * $life));
            $maxLayerDepreciation = max(0.0, $cost - $layerResidual);

            $totalAccumulated += min($months * $monthly, $maxLayerDepreciation);
        }

        return round($totalAccumulated, 2);
    }

    /**
     * Split $amount of cost out of $layers, taking from the layers whose source asset is in $preferSourceIds
     * first (null = no preference) and any overflow from the other layers, each in proportion to its cost.
     * Every slice keeps its layer's start date and life, and slices monthly_depreciation in the same proportion
     * so that the slices sum back to the original monthly_depreciation.
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
            $layerCost = (float) $l['cost'];
            $share = $shares[$i];
            $left = round($layerCost - $share, 2);

            $hasMonthly = array_key_exists('monthly_depreciation', $l) && $l['monthly_depreciation'] !== null;
            $monthly = $hasMonthly ? (float) $l['monthly_depreciation'] : null;

            if ($monthly !== null) {
                if ($share <= 0) {
                    $monthlyShare = 0.0;
                    $monthlyLeft = round($monthly, 2);
                } elseif ($left <= 0) {
                    $monthlyShare = round($monthly, 2);
                    $monthlyLeft = 0.0;
                } else {
                    $monthlyShare = $layerCost > 0 ? round($monthly * $share / $layerCost, 2) : 0.0;
                    $monthlyLeft = round($monthly - $monthlyShare, 2);
                }
            } else {
                $monthlyShare = null;
                $monthlyLeft = null;
            }

            if ($share > 0) {
                $sliceRemoved = ['cost' => $share] + $l;
                if ($hasMonthly) {
                    $sliceRemoved['monthly_depreciation'] = $monthlyShare;
                }
                $removed[] = $sliceRemoved;
            }
            if ($left > 0) {
                $sliceRemaining = ['cost' => $left] + $l;
                if ($hasMonthly) {
                    $sliceRemaining['monthly_depreciation'] = $monthlyLeft;
                }
                $remaining[] = $sliceRemaining;
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
