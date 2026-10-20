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
     * NEW: "active" assets - not sold, not disposed and not merged into a combined asset.
     * Only these can be listed in the registry or used in a new sale/modification.
     */
    public function scopeActive($query)
    {
        return $query->doesntHave('sale')->doesntHave('disposal')->whereNull('merged_into_asset_id');
    }

    /**
     * NEW: the original assets that were combined to create this asset (empty for normal assets).
     */
    public function components()
    {
        return $this->hasMany(Asset::class, 'merged_into_asset_id');
    }

    /**
     * NEW: the combined asset this asset was merged into (null if it has not been merged).
     */
    public function mergedInto()
    {
        return $this->belongsTo(Asset::class, 'merged_into_asset_id');
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
     */
    public function accumulatedDepreciation(?\Illuminate\Support\Carbon $asOf = null): float
    {
        return \App\Support\Depreciation::accumulated(
            $this->layers,
            $asOf ?? \Illuminate\Support\Carbon::today(),
            (float) ($this->residual_value ?? 0)
        );
    }

    /**
     * NEW: net book value (cost less accumulated depreciation) as of a date (today by default).
     */
    public function netBookValue(?\Illuminate\Support\Carbon $asOf = null): float
    {
        return round((float) $this->cost - $this->accumulatedDepreciation($asOf), 2);
    }

    /**
     * NEW: for a component removed from another asset, the asset it originally came with (null otherwise).
     */
    public function originAsset()
    {
        return $this->belongsTo(Asset::class, 'origin_asset_id');
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

    public function sale()
    {
        return $this->hasOne(AssetSale::class, 'asset_id');
    }

    public function disposal()
    {
        return $this->hasOne(AssetDisposal::class, 'asset_id');
    }
}
