<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Depreciation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared Asset Type / Sub Type filter for the list pages and their "Print Summary" views
 * (filter bar: resources/views/partials/type-filter.blade.php).
 *
 *   $filters = $this->typeFilters($request);
 *   $this->applyTypeFilter(Asset::query(), $filters);              // assets table
 *   $this->applyTypeFilter(AssetSale::query(), $filters, 'asset');  // event table: filters the related asset
 */
trait FiltersByAssetType
{
    /**
     * Validation rules for the shared filters (merge with a page's own rules, e.g. a date range).
     */
    public static function typeFilterRules(): array
    {
        return [
            'asset_type_id' => 'nullable|exists:asset_types,id',
            'asset_sub_type_id' => 'nullable|exists:asset_sub_types,id',
        ];
    }

    /**
     * The validated type / sub type filters from the query string ("all" counts as no filter).
     *
     * An invalid value redirects to the same page WITHOUT the type filters (not back(), which can loop on a GET page
     * whose previous URL is itself).
     *
     * @return array{asset_type_id: ?string, asset_sub_type_id: ?string}
     */
    protected function typeFilters(Request $request): array
    {
        $input = [];
        foreach (['asset_type_id', 'asset_sub_type_id'] as $key) {
            $value = $request->query($key);
            $input[$key] = ($value === '' || $value === 'all') ? null : $value;
        }

        $validator = Validator::make($input, self::typeFilterRules());

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($request->fullUrlWithoutQuery(['asset_type_id', 'asset_sub_type_id']));
        }

        return $input;
    }

    /**
     * Apply the filters to a query. With $relation (e.g. 'asset' on sales, disposals, modifications,
     * revaluations) the filter applies to the related asset, including soft-deleted ones.
     */
    protected function applyTypeFilter(Builder $query, array $filters, ?string $relation = null): Builder
    {
        if (empty($filters['asset_type_id']) && empty($filters['asset_sub_type_id'])) {
            return $query;
        }

        if ($relation === null) {
            return $query->ofType($filters);
        }

        return $query->whereHas($relation, fn (Builder $asset) => $asset->withTrashed()->ofType($filters));
    }

    /**
     * Validation rules for the event pages' date range.
     */
    public static function dateFilterRules(): array
    {
        return [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ];
    }

    /**
     * Filters for the event pages (sales, disposals, modifications, revaluations): type, sub type, date range and
     * any page-specific $extraRules (e.g. ['approval_status' => 'nullable|in:approved,pending']).
     *
     * Dates default to the current financial year when they are NOT in the query string (first visit, "Clear").
     * A date submitted blank means "no bound", so both blank = all dates. "all" / blank on any other filter = none.
     */
    protected function eventFilters(Request $request, array $extraRules = []): array
    {
        [$yearStart, $yearEnd] = Depreciation::range(Depreciation::label(Depreciation::financialYearOf()));
        $defaults = [
            'date_from' => $yearStart->toDateString(),
            'date_to' => $yearEnd->copy()->subDay()->toDateString(), // range() returns the first day AFTER the year
        ];

        $rules = self::typeFilterRules() + self::dateFilterRules() + $extraRules;

        $input = [];
        foreach (array_keys($rules) as $key) {
            $value = $request->query->has($key) ? $request->query($key) : ($defaults[$key] ?? null);
            $input[$key] = ($value === '' || $value === 'all') ? null : $value;
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            // Drop only the offending filters (a bad date falls back to the financial-year default).
            throw (new ValidationException($validator))
                ->redirectTo($request->fullUrlWithoutQuery(array_keys($validator->errors()->messages())));
        }

        return $input;
    }

    /**
     * Restrict $column to the filters' date range (either bound may be absent).
     */
    protected function applyDateFilter(Builder $query, array $filters, string $column): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn (Builder $q, $from) => $q->whereDate($column, '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $to) => $q->whereDate($column, '<=', $to));
    }

    /**
     * The date range in words for the print header, e.g. "2026-04-01 to 2027-03-31".
     */
    protected function periodLabel(array $filters): string
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        return match (true) {
            $from && $to => "{$from} to {$to}",
            (bool) $from => "From {$from}",
            (bool) $to => "Up to {$to}",
            default => 'All dates',
        };
    }

    /**
     * The date fields for the shared filter bar (partials.type-filter), pre-filled with the effective values.
     */
    protected function dateFilterFields(array $filters): array
    {
        return [
            ['name' => 'date_from', 'label' => 'Date from', 'type' => 'date', 'default' => $filters['date_from']],
            ['name' => 'date_to', 'label' => 'Date to', 'type' => 'date', 'default' => $filters['date_to']],
        ];
    }
}
