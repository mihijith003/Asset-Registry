<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'type_code',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_type_id');
    }

    public function subTypes()
    {
        return $this->hasMany(AssetSubType::class, 'asset_type_id');
    }
}
