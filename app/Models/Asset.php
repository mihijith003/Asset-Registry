<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'asset_code',
        'asset_type_id',
        'asset_sub_type_id',
        'brand_name',
        'model',
        'financial_year',
        'location',
        'cost',
        'merged_into_asset_id', // NEW: set on the original assets once they are combined into a new asset
        'origin_asset_id', // NEW: for a removed component, the asset it originally came with
    ];

    /**
     * NEW: generate a unique asset code (same AST-YYYY-XXXXX format the registry uses).
     */
    public static function generateCode(): string
    {
        do {
            $code = 'AST-' . date('Y') . '-' . strtoupper(Str::random(5));
        } while (static::where('asset_code', $code)->exists());

        return $code;
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

    protected static function booted()
    {
        static::creating(function ($asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = 'AST-' . strtoupper(Str::random(6));
            }
        });
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
     * NEW: one layer for a newly registered asset, starting today, with the life of its sub type.
     */
    public function addInitialLayer(): void
    {
        $years = $this->subType?->useful_life_years;

        $this->layers()->create([
            'source_asset_id' => $this->id,
            'cost' => $this->cost,
            'start_date' => now()->toDateString(),
            'useful_life_months' => $years ? $years * 12 : null,
        ]);
    }

    /**
     * NEW: accumulated depreciation as of a date (today by default).
     */
    public function accumulatedDepreciation(?\Illuminate\Support\Carbon $asOf = null): float
    {
        return \App\Support\Depreciation::accumulated($this->layers, $asOf ?? \Illuminate\Support\Carbon::today());
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

    public function sale()
    {
        return $this->hasOne(AssetSale::class, 'asset_id');
    }

    public function disposal()
    {
        return $this->hasOne(AssetDisposal::class, 'asset_id');
    }
}
