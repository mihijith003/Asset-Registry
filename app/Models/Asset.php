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
        'brand_name',
        'model',
        'financial_year',
        'location',
        'cost',
    ];

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
}
