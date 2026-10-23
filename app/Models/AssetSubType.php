<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetSubType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'asset_type_id',
        'sub_type_name',
        'sub_type_code',
        'useful_life_years', 
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_sub_type_id');
    }
}
