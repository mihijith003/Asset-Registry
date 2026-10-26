<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'asset_code',
        'asset_type_id',
        'asset_sub_type_id',
        'brand_id',
        'brand_name',
        'model',
        'purchase_date',
        'supplier',
        'po_reference',
        'invoice_reference',
        'financial_year',
        'location',
        'cost',
        'residual_value',
        'useful_life_months',
        'comments',
        'merged_into_asset_id', // NEW: set on the original assets once they are combined into a new asset
        'superseded_by_asset_id', // NEW: set on the old asset when superseded by a revalued successor asset
        'origin_asset_id', // NEW: for a removed component, the asset it originally came with
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    /**
     * Generate a sequential asset code for a type and subtype combination: <type_code>-<sub_type_code>-<nnn>.
     */
    public static function generateCode(int $assetTypeId, int $assetSubTypeId): string
    {
        $type = AssetType::findOrFail($assetTypeId);
        $subType = AssetSubType::findOrFail($assetSubTypeId);

        $count = static::withTrashed()
            ->where('asset_type_id', $assetTypeId)
            ->where('asset_sub_type_id', $assetSubTypeId)
            ->count();

        $number = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "{$type->type_code}-{$subType->sub_type_code}-{$number}";
    }

    /**
     * NEW: "active" assets - not sold, not disposed, not merged into a combined asset, and not superseded by revaluation.
     * Only these can be listed in the registry or used in a new sale/modification/revaluation.
     */
    public function scopeActive($query)
    {
        return $query->doesntHave('sale')
            // A REJECTED disposal (a Lost claim an admin turned down) does not count: the asset is still held.
            ->whereDoesntHave('disposals', fn ($q) => $q->where('approval_status', '!=', 'rejected'))
            ->whereNull('merged_into_asset_id')
            ->whereNull('superseded_by_asset_id');
    }

    public const UNAVAILABLE_MESSAGE = 'This asset is no longer available (it may have been sold, disposed of, revalued or combined).';

    /**
     * The asset if it is still active, for an action about to write against it. MUST be called inside
     * DB::transaction(): the row is locked first, so a second user acting on the same asset waits until the first
     * commits, and the active() check that follows then sees their sale / disposal / revaluation.
     * (Locking first matters: a plain read inside the transaction would use a snapshot that predates the other write.)
     */
    public static function findActiveForUpdate($id): ?self
    {
        static::withTrashed()->whereKey($id)->lockForUpdate()->first();

        return static::active()->find($id);
    }

    /**
     * Type / sub type filter used by the list pages and their print views. Takes the request or an array with
     * asset_type_id / asset_sub_type_id; a value that is missing, empty or "all" is not filtered on.
     * (For sales, disposals etc. use FiltersByAssetType::applyTypeFilter(), which filters the related asset.)
     */
    public function scopeOfType($query, $filters)
    {
        $filters = $filters instanceof \Illuminate\Http\Request ? $filters->query() : (array) $filters;

        foreach (['asset_type_id', 'asset_sub_type_id'] as $column) {
            $value = $filters[$column] ?? null;

            if ($value !== null && $value !== '' && $value !== 'all') {
                $query->where($this->qualifyColumn($column), $value);
            }
        }

        return $query;
    }

    /**
     * NEW: the original assets that were combined to create this asset (empty for normal assets).
     */
    public function components()
    {
        return $this->hasMany(Asset::class, 'merged_into_asset_id');
    }

    /**
     * Whether this asset's combination was undone by a later modification (via the revive path).
     */
    public function isCombinationUndone(): bool
    {
        if ($this->components->isEmpty()) {
            return false;
        }

        $producedAssetIds = $this->modifications->pluck('result_asset_id')->filter()->all();
        if (empty($producedAssetIds)) {
            return false;
        }

        return $this->components->contains(fn ($component) => in_array($component->id, $producedAssetIds, true));
    }

    /**
     * Whether this asset is currently an active combined asset.
     */
    public function isCurrentlyCombined(): bool
    {
        return $this->components->isNotEmpty() && ! $this->isCombinationUndone();
    }

    /**
     * NEW: the combined asset this asset was merged into (null if it has not been merged).
     */
    public function mergedInto()
    {
        return $this->belongsTo(Asset::class, 'merged_into_asset_id')->withTrashed();
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    /**
     * NEW: the asset's sub type (used on the printed sale / disposal documents).
     */
    public function subType()
    {
        return $this->belongsTo(AssetSubType::class, 'asset_sub_type_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * NEW: the layers of cost this asset's depreciation is calculated from.
     */
    public function layers()
    {
        return $this->hasMany(AssetDepreciationLayer::class)->orderBy('start_date');
    }

    /**
     * NEW: one layer for a newly registered asset, starting today (or on purchase date), with the asset's custom useful life or its sub type's default.
     */
    public function addInitialLayer(): void
    {
        $years = $this->subType?->useful_life_years;

        $this->layers()->create([
            'source_asset_id' => $this->id,
            'cost' => $this->cost,
            'start_date' => $this->purchase_date ? $this->purchase_date->toDateString() : now()->toDateString(),
            'useful_life_months' => $this->useful_life_months ?? ($years ? $years * 12 : null),
        ]);
    }

    /**
     * NEW: accumulated depreciation as of a date (today by default).
     * Excludes superseded depreciation layers (with end_date set) per IAS 16 elimination treatment.
     */
    public function accumulatedDepreciation(?\Illuminate\Support\Carbon $asOf = null): float
    {
        $activeLayers = $this->layers->whereNull('end_date');

        return \App\Support\Depreciation::accumulated(
            $activeLayers,
            $asOf ?? \Illuminate\Support\Carbon::today(),
            (float) ($this->residual_value ?? 0)
        );
    }

    /**
     * NEW: net book value (cost - accumulated depreciation) as of a date (today by default).
     * Excludes superseded depreciation layers (with end_date set).
     */
    public function netBookValue(?\Illuminate\Support\Carbon $asOf = null): float
    {
        $activeLayers = $this->layers->whereNull('end_date');
        $costBasis = $activeLayers->isNotEmpty() ? (float) $activeLayers->sum('cost') : (float) $this->cost;

        return round($costBasis - $this->accumulatedDepreciation($asOf), 2);
    }

    /**
     * NEW: for a component removed from another asset, the asset it originally came with (null otherwise).
     */
    public function originAsset()
    {
        return $this->belongsTo(Asset::class, 'origin_asset_id')->withTrashed();
    }

    /**
     * NEW: the modification in which this asset was removed from another asset (null if it never was).
     */
    public function removalModification()
    {
        return $this->hasOne(AssetModification::class, 'removed_asset_id')->latestOfMany('id');
    }

    /**
     * NEW: ids of every asset this asset was combined from, however many modifications deep.
     */
    public function descendantIds(): array
    {
        $ids = [];

        foreach ($this->components as $part) {
            $ids[] = $part->id;
            $ids = array_merge($ids, $part->descendantIds());
        }

        return $ids;
    }

    /**
     * Resolve the entire identity chain for this asset across revaluations, combinations, and removals.
     * Walks superseded_by_asset_id and merged_into_asset_id forwards to the current asset,
     * and backwards to every predecessor (including origin_asset_id for removed components).
     * Guards against infinite loops on cyclic data and returns the chain ordered oldest first.
     *
     * @param  array<int, string>  $with  Optional relationships to eager-load on the returned collection.
     * @return \Illuminate\Database\Eloquent\Collection<int, Asset>
     */
    public function resolveChain(array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $visited = [$this->id => true];

        // Move forwards along superseded_by_asset_id and merged_into_asset_id to the current asset
        $cursor = $this;
        $maxSteps = 500;
        while ($cursor && $maxSteps-- > 0) {
            $nextId = $cursor->superseded_by_asset_id ?? $cursor->merged_into_asset_id;
            if (! $nextId || isset($visited[$nextId])) {
                break;
            }

            $next = static::withTrashed()->find($nextId);
            if (! $next) {
                break;
            }

            $visited[$next->id] = true;
            $cursor = $next;
        }

        // Move backwards from all discovered nodes to every predecessor (and any removed components branching off)
        $frontier = array_keys($visited);
        $maxIterations = 500;
        while (! empty($frontier) && $maxIterations-- > 0) {
            $frontierNodes = static::withTrashed()
                ->whereIn('id', $frontier)
                ->get(['id', 'origin_asset_id', 'superseded_by_asset_id', 'merged_into_asset_id']);

            $relatedIds = $frontierNodes->pluck('origin_asset_id')
                ->merge($frontierNodes->pluck('superseded_by_asset_id'))
                ->merge($frontierNodes->pluck('merged_into_asset_id'))
                ->filter()
                ->all();

            $connected = static::withTrashed()
                ->where(function ($query) use ($frontier, $relatedIds) {
                    $query->whereIn('superseded_by_asset_id', $frontier)
                        ->orWhereIn('merged_into_asset_id', $frontier)
                        ->orWhereIn('origin_asset_id', $frontier);

                    if (! empty($relatedIds)) {
                        $query->orWhereIn('id', $relatedIds);
                    }
                })
                ->pluck('id')
                ->all();

            $newFrontier = [];
            foreach ($connected as $connId) {
                if (! isset($visited[$connId])) {
                    $visited[$connId] = true;
                    $newFrontier[] = $connId;
                }
            }

            $frontier = $newFrontier;
        }

        // 3. Load all assets in the chain with requested eager loads, ordered oldest first
        $query = static::withTrashed()->whereIn('id', array_keys($visited));
        if (! empty($with)) {
            $query->with($with);
        }

        return $query->orderBy('created_at')->orderBy('id')->get();
    }

    /**
     * NEW: every allocation of this asset to a branch / employee (newest first).
     */
    public function allocations()
    {
        return $this->hasMany(AssetAllocation::class)->latest('id');
    }

    /**
     * NEW: the asset's current allocation (its newest allocation row, null if never allocated).
     */
    public function currentAllocation()
    {
        return $this->hasOne(AssetAllocation::class)->latestOfMany();
    }

    /**
     * NEW: where the asset is now, as text. Uses the current allocation; assets that were never
     * allocated fall back to the older free-text "location" column (null when neither exists).
     */
    public function getLocationLabelAttribute(): ?string
    {
        return $this->currentAllocation?->label ?: ($this->location ?: null);
    }

    public function modifications()
    {
        return $this->hasMany(AssetModification::class);
    }

    public function sales()
    {
        return $this->hasMany(AssetSale::class);
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function revaluations()
    {
        return $this->hasMany(AssetRevaluation::class);
    }

    /**
     * The successor asset created when this asset was revalued (null if not superseded).
     */
    public function supersededBy()
    {
        return $this->belongsTo(Asset::class, 'superseded_by_asset_id')->withTrashed();
    }

    /**
     * The predecessor asset that this asset superseded upon revaluation (null if not a revaluation successor).
     */
    public function supersedes()
    {
        return $this->hasOne(Asset::class, 'superseded_by_asset_id')->withTrashed();
    }

    /**
     * The revaluation event that created this successor asset (null if not created by a revaluation).
     */
    public function revaluationIn()
    {
        return $this->hasOne(AssetRevaluation::class, 'result_asset_id');
    }

    public function sale()
    {
        return $this->hasOne(AssetSale::class, 'asset_id');
    }

    /**
     * The asset's effective disposal: pending or approved. A rejected claim is history only (see disposals()),
     * so it does not make the asset "Disposed" anywhere (depreciation, timeline status, registry).
     */
    public function disposal()
    {
        return $this->hasOne(AssetDisposal::class, 'asset_id')->where('approval_status', '!=', 'rejected');
    }
}
