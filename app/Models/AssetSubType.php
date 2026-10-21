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
        'useful_life_years', 
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }
}
