<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'asset_type_id',
        'asset_sub_type_id',
        'brand_name',
        'model',
        'financial_year',
        'location',
        'cost',
        'merged_into_asset_id', // NEW: set on the original assets once they are combined into a new asset
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
