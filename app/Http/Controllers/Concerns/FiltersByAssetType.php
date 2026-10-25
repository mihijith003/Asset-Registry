<?php

namespace App\Http\Controllers\Concerns;

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
}
